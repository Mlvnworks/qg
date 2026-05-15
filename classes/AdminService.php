<?php

class AdminService
{
    private ?PDO $pdo;
    private App $app;
    private Auth $auth;
    private Security $security;

    public function __construct(?PDO $pdo, App $app, Auth $auth, Security $security)
    {
        $this->pdo = $pdo;
        $this->app = $app;
        $this->auth = $auth;
        $this->security = $security;
    }

    public function dashboardSummary(): array
    {
        $pdo = $this->app->ensureDatabase();

        $row = $pdo->query('
            SELECT
                (SELECT COUNT(*) FROM users) AS total_users,
                (SELECT COUNT(*) FROM users WHERE status = "active") AS active_users,
                (SELECT COUNT(*) FROM users WHERE status = "inactive") AS inactive_users,
                (SELECT COUNT(*) FROM quizzes WHERE date_deleted IS NULL) AS total_quizzes,
                (SELECT COUNT(*) FROM quizzes WHERE status = "active" AND date_deleted IS NULL) AS active_quizzes,
                (SELECT COUNT(*) FROM quizzes WHERE status <> "active" AND date_deleted IS NULL) AS inactive_quizzes,
                (SELECT COUNT(*) FROM quiz_attempts) AS total_attempts,
                (SELECT COUNT(*) FROM quiz_attempts WHERE status = "completed") AS completed_attempts,
                (SELECT COUNT(*) FROM quiz_attempts WHERE status = "ongoing") AS ongoing_attempts
        ')->fetch() ?: [];

        return array_merge([
            'total_users' => 0,
            'active_users' => 0,
            'inactive_users' => 0,
            'total_quizzes' => 0,
            'active_quizzes' => 0,
            'inactive_quizzes' => 0,
            'total_attempts' => 0,
            'completed_attempts' => 0,
            'ongoing_attempts' => 0,
        ], $row);
    }

    public function recentUsers(int $limit = 6): array
    {
        $stmt = $this->app->ensureDatabase()->query(
            '
            SELECT id, full_name, email, role, status, created_at
            FROM users
            ORDER BY created_at DESC
            LIMIT ' . max(1, (int) $limit)
        );

        return $stmt->fetchAll();
    }

    public function recentQuizzes(int $limit = 6): array
    {
        $stmt = $this->app->ensureDatabase()->query(
            '
            SELECT q.id, q.title, q.topic, q.difficulty, q.status, q.created_at, u.full_name AS creator_name
            FROM quizzes q
            INNER JOIN users u ON u.id = q.user_id
            WHERE q.date_deleted IS NULL
            ORDER BY q.created_at DESC
            LIMIT ' . max(1, (int) $limit)
        );

        return $stmt->fetchAll();
    }

    public function recentAttempts(int $limit = 6): array
    {
        $stmt = $this->app->ensureDatabase()->query(
            '
            SELECT qa.id, q.id AS quiz_id, q.title AS quiz_title, creator.full_name AS creator_name,
                   taker.full_name AS taker_name, qa.score, qa.total_questions, qa.percentage,
                   qa.result, qa.status, qa.started_at, qa.completed_at
            FROM quiz_attempts qa
            INNER JOIN quizzes q ON q.id = qa.quiz_id
            INNER JOIN users creator ON creator.id = q.user_id
            INNER JOIN users taker ON taker.id = qa.user_id
            ORDER BY qa.started_at DESC, qa.id DESC
            LIMIT ' . max(1, (int) $limit)
        );

        return $stmt->fetchAll();
    }

    public function recentActivity(int $limit = 8): array
    {
        $stmt = $this->app->ensureDatabase()->query(
            '
            SELECT al.id, al.action, al.description, al.created_at, u.full_name
            FROM activity_logs al
            LEFT JOIN users u ON u.id = al.user_id
            ORDER BY al.created_at DESC, al.id DESC
            LIMIT ' . max(1, (int) $limit)
        );

        return $stmt->fetchAll();
    }

    public function listActivityLogs(array $filters): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $userId = (int) ($filters['user_id'] ?? 0);
        $action = trim((string) ($filters['action'] ?? ''));
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $sortBy = trim((string) ($filters['sort'] ?? 'created_at'));
        $sortDir = trim((string) ($filters['dir'] ?? 'desc'));

        $allowedSorts = [
            'action' => 'al.action',
            'user' => 'u.full_name',
            'created_at' => 'al.created_at',
        ];
        $sortSql = $allowedSorts[$sortBy] ?? $allowedSorts['created_at'];
        $dirSql = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';

        $sql = '
            SELECT al.id, al.user_id, al.action, al.description, al.created_at,
                   u.full_name, u.email
            FROM activity_logs al
            LEFT JOIN users u ON u.id = al.user_id
            WHERE 1 = 1
        ';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (al.action LIKE :search OR al.description LIKE :search OR u.full_name LIKE :search OR u.email LIKE :search) ';
            $params['search'] = '%' . $search . '%';
        }

        if ($userId > 0) {
            $sql .= ' AND al.user_id = :user_id ';
            $params['user_id'] = $userId;
        }

        if ($action !== '') {
            $sql .= ' AND al.action = :action ';
            $params['action'] = $action;
        }

        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) === 1) {
            $sql .= ' AND DATE(al.created_at) >= :date_from ';
            $params['date_from'] = $dateFrom;
        }

        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) === 1) {
            $sql .= ' AND DATE(al.created_at) <= :date_to ';
            $params['date_to'] = $dateTo;
        }

        $sql .= ' ORDER BY ' . $sortSql . ' ' . $dirSql . ', al.id DESC LIMIT 150';

        $stmt = $this->app->ensureDatabase()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function activityActionOptions(): array
    {
        $stmt = $this->app->ensureDatabase()->query('
            SELECT DISTINCT action
            FROM activity_logs
            ORDER BY action ASC
        ');

        return array_map(static function (array $row): string {
            return (string) $row['action'];
        }, $stmt->fetchAll());
    }

    public function listUsers(array $filters): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $role = trim((string) ($filters['role'] ?? ''));
        $sortBy = trim((string) ($filters['sort'] ?? 'created_at'));
        $sortDir = trim((string) ($filters['dir'] ?? 'desc'));

        $allowedSorts = [
            'name' => 'u.full_name',
            'email' => 'u.email',
            'role' => 'u.role',
            'status' => 'u.status',
            'created_at' => 'u.created_at',
            'updated_at' => 'u.updated_at',
        ];
        $allowedStatuses = ['active', 'inactive'];
        $allowedRoles = ['admin', 'user'];
        $sortSql = $allowedSorts[$sortBy] ?? $allowedSorts['created_at'];
        $dirSql = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';

        $sql = '
            SELECT u.id, u.full_name, u.email, u.role, u.status, u.created_at, u.updated_at,
                   COUNT(DISTINCT q.id) AS quizzes_created,
                   COUNT(DISTINCT qa.id) AS attempts_taken
            FROM users u
            LEFT JOIN quizzes q ON q.user_id = u.id AND q.date_deleted IS NULL
            LEFT JOIN quiz_attempts qa ON qa.user_id = u.id
            WHERE 1 = 1
        ';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (u.full_name LIKE :search OR u.email LIKE :search) ';
            $params['search'] = '%' . $search . '%';
        }

        if (in_array($status, $allowedStatuses, true)) {
            $sql .= ' AND u.status = :status ';
            $params['status'] = $status;
        }

        if (in_array($role, $allowedRoles, true)) {
            $sql .= ' AND u.role = :role ';
            $params['role'] = $role;
        }

        $sql .= ' GROUP BY u.id ORDER BY ' . $sortSql . ' ' . $dirSql . ', u.id DESC';

        $stmt = $this->app->ensureDatabase()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getUserById(int $userId): ?array
    {
        $stmt = $this->app->ensureDatabase()->prepare('
            SELECT u.id, u.full_name, u.email, u.role, u.status, u.created_at, u.updated_at,
                   COUNT(DISTINCT q.id) AS quizzes_created,
                   COUNT(DISTINCT qa.id) AS attempts_taken,
                   COALESCE(AVG(CASE WHEN qa.status = "completed" THEN qa.percentage END), 0) AS average_score
            FROM users u
            LEFT JOIN quizzes q ON q.user_id = u.id AND q.date_deleted IS NULL
            LEFT JOIN quiz_attempts qa ON qa.user_id = u.id
            WHERE u.id = :user_id
            GROUP BY u.id
            LIMIT 1
        ');
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetch() ?: null;
    }

    public function getUserCreatedQuizzes(int $userId, int $limit = 8): array
    {
        $stmt = $this->app->ensureDatabase()->prepare(
            '
            SELECT q.id, q.title, q.topic, q.difficulty, q.status, q.share_code, q.created_at
            FROM quizzes q
            WHERE q.user_id = :user_id AND q.date_deleted IS NULL
            ORDER BY q.created_at DESC
            LIMIT ' . max(1, (int) $limit)
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function getUserAttempts(int $userId, int $limit = 8): array
    {
        $stmt = $this->app->ensureDatabase()->prepare(
            '
            SELECT qa.id, qa.quiz_id, q.title, qa.score, qa.total_questions, qa.percentage, qa.result, qa.status, qa.started_at, qa.completed_at
            FROM quiz_attempts qa
            INNER JOIN quizzes q ON q.id = qa.quiz_id
            WHERE qa.user_id = :user_id
            ORDER BY qa.started_at DESC, qa.id DESC
            LIMIT ' . max(1, (int) $limit)
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function createUser(array $payload, int $adminId): int
    {
        $fullName = trim((string) ($payload['full_name'] ?? ''));
        $email = $this->security->normalizeEmail((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $role = trim((string) ($payload['role'] ?? 'user'));
        $status = trim((string) ($payload['status'] ?? 'active'));

        $this->validateUserPayload($fullName, $email, $password, $role, $status, true);

        $pdo = $this->app->ensureDatabase();
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => $email]);

        if ($check->fetch()) {
            throw new RuntimeException('Email already exists.');
        }

        $stmt = $pdo->prepare('
            INSERT INTO users (full_name, email, password_hash, role, status, created_at, updated_at)
            VALUES (:full_name, :email, :password_hash, :role, :status, NOW(), NOW())
        ');
        $stmt->execute([
            'full_name' => $fullName,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $role,
            'status' => $status,
        ]);

        $userId = (int) $pdo->lastInsertId();
        $this->auth->logActivity($adminId, 'admin_user_created', 'Created user "' . $email . '".');

        return $userId;
    }

    public function updateUser(int $userId, array $payload, int $adminId): void
    {
        $user = $this->getUserById($userId);

        if ($user === null) {
            throw new RuntimeException('User not found.');
        }

        $fullName = trim((string) ($payload['full_name'] ?? ''));
        $email = $this->security->normalizeEmail((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $role = trim((string) ($payload['role'] ?? 'user'));
        $status = trim((string) ($payload['status'] ?? 'active'));

        $this->validateUserPayload($fullName, $email, $password, $role, $status, false);

        $pdo = $this->app->ensureDatabase();
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :user_id LIMIT 1');
        $check->execute([
            'email' => $email,
            'user_id' => $userId,
        ]);

        if ($check->fetch()) {
            throw new RuntimeException('Email already exists.');
        }

        $sql = '
            UPDATE users
            SET full_name = :full_name,
                email = :email,
                role = :role,
                status = :status,
                updated_at = NOW()
        ';
        $params = [
            'full_name' => $fullName,
            'email' => $email,
            'role' => $role,
            'status' => $status,
            'user_id' => $userId,
        ];

        if ($password !== '') {
            $sql .= ', password_hash = :password_hash ';
            $params['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $sql .= ' WHERE id = :user_id ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $currentUser = $this->auth->user();
        if ($currentUser !== null && (int) $currentUser['id'] === $userId) {
            $_SESSION['user_snapshot'] = [
                'id' => $userId,
                'full_name' => $fullName,
                'email' => $email,
                'role' => $role,
                'status' => $status,
            ];
        }

        $this->auth->logActivity($adminId, 'admin_user_updated', 'Updated user "' . $email . '".');
    }

    public function updateUserStatus(int $userId, string $status, int $adminId): void
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new RuntimeException('Invalid user status.');
        }

        $stmt = $this->app->ensureDatabase()->prepare('
            UPDATE users
            SET status = :status, updated_at = NOW()
            WHERE id = :user_id
        ');
        $stmt->execute([
            'status' => $status,
            'user_id' => $userId,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('User not found.');
        }

        $currentUser = $this->auth->user();
        if ($currentUser !== null && (int) $currentUser['id'] === $userId) {
            $_SESSION['user_snapshot']['status'] = $status;
        }

        $this->auth->logActivity($adminId, 'admin_user_status_updated', 'Set user #' . $userId . ' to ' . $status . '.');
    }

    public function deleteUser(int $userId, int $adminId): void
    {
        if ($userId === $adminId) {
            throw new RuntimeException('You cannot delete your own admin account.');
        }

        $user = $this->getUserById($userId);

        if ($user === null) {
            throw new RuntimeException('User not found.');
        }

        $stmt = $this->app->ensureDatabase()->prepare('DELETE FROM users WHERE id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        $this->auth->logActivity($adminId, 'admin_user_deleted', 'Deleted user "' . $user['email'] . '".');
    }

    public function listQuizzes(array $filters): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $creatorId = (int) ($filters['creator_id'] ?? 0);
        $status = trim((string) ($filters['status'] ?? ''));
        $difficulty = trim((string) ($filters['difficulty'] ?? ''));
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $sortBy = trim((string) ($filters['sort'] ?? 'created_at'));
        $sortDir = trim((string) ($filters['dir'] ?? 'desc'));

        $allowedSorts = [
            'title' => 'q.title',
            'creator' => 'u.full_name',
            'difficulty' => 'q.difficulty',
            'status' => 'q.status',
            'created_at' => 'q.created_at',
            'updated_at' => 'q.updated_at',
        ];
        $allowedStatuses = ['active', 'inactive', 'draft'];
        $allowedDifficulties = ['Easy', 'Medium', 'Hard'];
        $sortSql = $allowedSorts[$sortBy] ?? $allowedSorts['created_at'];
        $dirSql = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';

        $sql = '
            SELECT q.id, q.title, q.topic, q.difficulty, q.question_count, q.timer_per_question,
                   q.passing_rate, q.status, q.share_code, q.created_at, q.updated_at,
                   u.id AS creator_id, u.full_name AS creator_name,
                   COALESCE(qs.total_attempts, 0) AS total_attempts
            FROM quizzes q
            INNER JOIN users u ON u.id = q.user_id
            LEFT JOIN quiz_statistics qs ON qs.quiz_id = q.id
            WHERE q.date_deleted IS NULL
        ';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (q.title LIKE :search OR q.topic LIKE :search OR u.full_name LIKE :search) ';
            $params['search'] = '%' . $search . '%';
        }

        if ($creatorId > 0) {
            $sql .= ' AND q.user_id = :creator_id ';
            $params['creator_id'] = $creatorId;
        }

        if (in_array($status, $allowedStatuses, true)) {
            $sql .= ' AND q.status = :status ';
            $params['status'] = $status;
        }

        if (in_array($difficulty, $allowedDifficulties, true)) {
            $sql .= ' AND q.difficulty = :difficulty ';
            $params['difficulty'] = $difficulty;
        }

        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) === 1) {
            $sql .= ' AND DATE(q.created_at) >= :date_from ';
            $params['date_from'] = $dateFrom;
        }

        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) === 1) {
            $sql .= ' AND DATE(q.created_at) <= :date_to ';
            $params['date_to'] = $dateTo;
        }

        $sql .= ' ORDER BY ' . $sortSql . ' ' . $dirSql . ', q.id DESC';

        $stmt = $this->app->ensureDatabase()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getQuizById(int $quizId): ?array
    {
        $stmt = $this->app->ensureDatabase()->prepare('
            SELECT q.*, u.full_name AS creator_name, u.email AS creator_email,
                   COALESCE(qs.total_attempts, 0) AS total_attempts,
                   COALESCE(qs.ongoing_attempts, 0) AS ongoing_attempts,
                   COALESCE(qs.completed_attempts, 0) AS completed_attempts,
                   COALESCE(qs.average_score, 0) AS average_score
            FROM quizzes q
            INNER JOIN users u ON u.id = q.user_id
            LEFT JOIN quiz_statistics qs ON qs.quiz_id = q.id
            WHERE q.id = :quiz_id AND q.date_deleted IS NULL
            LIMIT 1
        ');
        $stmt->execute(['quiz_id' => $quizId]);
        $quiz = $stmt->fetch();

        if (!$quiz) {
            return null;
        }

        $quiz['questions'] = $this->getQuizQuestions((int) $quiz['id']);
        $quiz['share_url'] = $this->app->url('take-quiz', ['quiz' => $quiz['share_code']]);

        return $quiz;
    }

    public function getQuizAttempts(int $quizId, int $limit = 12): array
    {
        $stmt = $this->app->ensureDatabase()->prepare(
            '
            SELECT qa.id, taker.full_name AS taker_name, taker.email AS taker_email,
                   qa.score, qa.total_questions, qa.percentage, qa.result, qa.status,
                   qa.started_at, qa.completed_at
            FROM quiz_attempts qa
            INNER JOIN users taker ON taker.id = qa.user_id
            WHERE qa.quiz_id = :quiz_id
            ORDER BY qa.started_at DESC, qa.id DESC
            LIMIT ' . max(1, (int) $limit)
        );
        $stmt->execute(['quiz_id' => $quizId]);

        return $stmt->fetchAll();
    }

    public function updateQuiz(int $quizId, array $payload, int $adminId): void
    {
        $quiz = $this->getQuizById($quizId);

        if ($quiz === null) {
            throw new RuntimeException('Quiz not found.');
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $topic = trim((string) ($payload['topic'] ?? ''));
        $difficulty = ucfirst(strtolower(trim((string) ($payload['difficulty'] ?? 'Medium'))));
        $timer = max(10, min(7200, (int) ($payload['timer_per_question'] ?? 300)));
        $passingRate = max(1, min(100, (int) ($payload['passing_rate'] ?? 70)));

        if ($title === '' || $topic === '') {
            throw new RuntimeException('Quiz title and topic are required.');
        }

        if (!in_array($difficulty, ['Easy', 'Medium', 'Hard'], true)) {
            throw new RuntimeException('Invalid difficulty selected.');
        }

        $questionPayload = $this->normalizeQuestionPayload($payload['questions'] ?? []);

        if ($questionPayload === []) {
            throw new RuntimeException('Add at least one valid question.');
        }

        $pdo = $this->app->ensureDatabase();
        $pdo->beginTransaction();

        try {
            $updateQuizStmt = $pdo->prepare('
                UPDATE quizzes
                SET title = :title,
                    topic = :topic,
                    difficulty = :difficulty,
                    question_count = :question_count,
                    timer_per_question = :timer_per_question,
                    passing_rate = :passing_rate,
                    updated_at = NOW()
                WHERE id = :quiz_id AND date_deleted IS NULL
            ');
            $updateQuizStmt->execute([
                'title' => $title,
                'topic' => $topic,
                'difficulty' => $difficulty,
                'question_count' => count($questionPayload),
                'timer_per_question' => $timer,
                'passing_rate' => $passingRate,
                'quiz_id' => $quizId,
            ]);

            $questionUpdateStmt = $pdo->prepare('
                UPDATE quiz_questions
                SET question_text = :question_text,
                    explanation = :explanation,
                    difficulty = :difficulty,
                    sort_order = :sort_order,
                    updated_at = NOW()
                WHERE id = :question_id AND quiz_id = :quiz_id
            ');
            $questionInsertStmt = $pdo->prepare('
                INSERT INTO quiz_questions (quiz_id, question_text, explanation, difficulty, sort_order, created_at, updated_at)
                VALUES (:quiz_id, :question_text, :explanation, :difficulty, :sort_order, NOW(), NOW())
            ');
            $choiceListStmt = $pdo->prepare('
                SELECT id
                FROM quiz_choices
                WHERE question_id = :question_id
                ORDER BY id ASC
            ');
            $choiceUpdateStmt = $pdo->prepare('
                UPDATE quiz_choices
                SET choice_text = :choice_text,
                    is_correct = :is_correct,
                    updated_at = NOW()
                WHERE id = :choice_id AND question_id = :question_id
            ');
            $choiceInsertStmt = $pdo->prepare('
                INSERT INTO quiz_choices (question_id, choice_text, is_correct, created_at, updated_at)
                VALUES (:question_id, :choice_text, :is_correct, NOW(), NOW())
            ');

            foreach ($questionPayload as $index => $questionData) {
                $questionId = (int) ($questionData['id'] ?? 0);

                if ($questionId > 0) {
                    $questionUpdateStmt->execute([
                        'question_text' => $questionData['question_text'],
                        'explanation' => $questionData['explanation'],
                        'difficulty' => $questionData['difficulty'],
                        'sort_order' => $index + 1,
                        'question_id' => $questionId,
                        'quiz_id' => $quizId,
                    ]);
                } else {
                    $questionInsertStmt->execute([
                        'quiz_id' => $quizId,
                        'question_text' => $questionData['question_text'],
                        'explanation' => $questionData['explanation'],
                        'difficulty' => $questionData['difficulty'],
                        'sort_order' => $index + 1,
                    ]);
                    $questionId = (int) $pdo->lastInsertId();
                }

                $choiceListStmt->execute(['question_id' => $questionId]);
                $existingChoiceIds = array_map('intval', array_column($choiceListStmt->fetchAll(), 'id'));

                foreach ($questionData['choices'] as $choiceIndex => $choiceText) {
                    $isCorrect = $choiceIndex === $questionData['correct_index'] ? 1 : 0;

                    if (isset($existingChoiceIds[$choiceIndex])) {
                        $choiceUpdateStmt->execute([
                            'choice_text' => $choiceText,
                            'is_correct' => $isCorrect,
                            'choice_id' => $existingChoiceIds[$choiceIndex],
                            'question_id' => $questionId,
                        ]);
                    } else {
                        $choiceInsertStmt->execute([
                            'question_id' => $questionId,
                            'choice_text' => $choiceText,
                            'is_correct' => $isCorrect,
                        ]);
                    }
                }
            }

            $pdo->commit();
        } catch (Throwable $err) {
            $pdo->rollBack();
            throw $err;
        }

        $this->auth->logActivity($adminId, 'admin_quiz_updated', 'Updated quiz "' . $title . '".');
    }

    public function updateQuizStatus(int $quizId, string $status, int $adminId): void
    {
        if (!in_array($status, ['active', 'inactive', 'draft'], true)) {
            throw new RuntimeException('Invalid quiz status.');
        }

        $stmt = $this->app->ensureDatabase()->prepare('
            UPDATE quizzes
            SET status = :status, updated_at = NOW()
            WHERE id = :quiz_id AND date_deleted IS NULL
        ');
        $stmt->execute([
            'status' => $status,
            'quiz_id' => $quizId,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Quiz not found.');
        }

        $this->auth->logActivity($adminId, 'admin_quiz_status_updated', 'Set quiz #' . $quizId . ' to ' . $status . '.');
    }

    public function deleteQuiz(int $quizId, int $adminId): void
    {
        $quiz = $this->getQuizById($quizId);

        if ($quiz === null) {
            throw new RuntimeException('Quiz not found.');
        }

        $stmt = $this->app->ensureDatabase()->prepare('
            UPDATE quizzes
            SET date_deleted = NOW(), status = "inactive", updated_at = NOW()
            WHERE id = :quiz_id AND date_deleted IS NULL
        ');
        $stmt->execute(['quiz_id' => $quizId]);

        $this->auth->logActivity($adminId, 'admin_quiz_deleted', 'Deleted quiz "' . $quiz['title'] . '".');
    }

    public function listAttempts(array $filters): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $quizId = (int) ($filters['quiz_id'] ?? 0);
        $creatorId = (int) ($filters['creator_id'] ?? 0);
        $takerId = (int) ($filters['taker_id'] ?? 0);
        $result = trim((string) ($filters['result'] ?? ''));
        $attemptStatus = trim((string) ($filters['attempt_status'] ?? ''));
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $sortBy = trim((string) ($filters['sort'] ?? 'started_at'));
        $sortDir = trim((string) ($filters['dir'] ?? 'desc'));

        $allowedSorts = [
            'quiz' => 'q.title',
            'creator' => 'creator.full_name',
            'taker' => 'taker.full_name',
            'score' => 'qa.score',
            'percentage' => 'qa.percentage',
            'result' => 'COALESCE(qa.result, "")',
            'status' => 'qa.status',
            'started_at' => 'qa.started_at',
            'completed_at' => 'qa.completed_at',
        ];
        $sortSql = $allowedSorts[$sortBy] ?? $allowedSorts['started_at'];
        $dirSql = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';

        $sql = '
            SELECT qa.id, qa.quiz_id, qa.user_id, q.title AS quiz_title, q.topic, q.share_code,
                   creator.id AS creator_id, creator.full_name AS creator_name,
                   taker.id AS taker_id, taker.full_name AS taker_name, taker.email AS taker_email,
                   qa.score, qa.total_questions, qa.percentage, qa.result, qa.status, qa.started_at, qa.completed_at
            FROM quiz_attempts qa
            INNER JOIN quizzes q ON q.id = qa.quiz_id
            INNER JOIN users creator ON creator.id = q.user_id
            INNER JOIN users taker ON taker.id = qa.user_id
            WHERE q.date_deleted IS NULL
        ';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (q.title LIKE :search OR creator.full_name LIKE :search OR taker.full_name LIKE :search OR taker.email LIKE :search) ';
            $params['search'] = '%' . $search . '%';
        }

        if ($quizId > 0) {
            $sql .= ' AND q.id = :quiz_id ';
            $params['quiz_id'] = $quizId;
        }

        if ($creatorId > 0) {
            $sql .= ' AND creator.id = :creator_id ';
            $params['creator_id'] = $creatorId;
        }

        if ($takerId > 0) {
            $sql .= ' AND taker.id = :taker_id ';
            $params['taker_id'] = $takerId;
        }

        if (in_array($result, ['Passed', 'Failed'], true)) {
            $sql .= ' AND qa.result = :result ';
            $params['result'] = $result;
        }

        if (in_array($attemptStatus, ['ongoing', 'completed', 'unfinished'], true)) {
            $sql .= ' AND qa.status = :attempt_status ';
            $params['attempt_status'] = $attemptStatus;
        }

        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) === 1) {
            $sql .= ' AND DATE(qa.started_at) >= :date_from ';
            $params['date_from'] = $dateFrom;
        }

        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) === 1) {
            $sql .= ' AND DATE(qa.started_at) <= :date_to ';
            $params['date_to'] = $dateTo;
        }

        $sql .= ' ORDER BY ' . $sortSql . ' ' . $dirSql . ', qa.id DESC LIMIT 100';

        $stmt = $this->app->ensureDatabase()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getAttemptReview(int $attemptId): ?array
    {
        $pdo = $this->app->ensureDatabase();
        $stmt = $pdo->prepare('
            SELECT qa.*, q.id AS quiz_id, q.title AS quiz_title, q.topic, q.difficulty, q.passing_rate,
                   creator.full_name AS creator_name, taker.full_name AS taker_name, taker.email AS taker_email
            FROM quiz_attempts qa
            INNER JOIN quizzes q ON q.id = qa.quiz_id
            INNER JOIN users creator ON creator.id = q.user_id
            INNER JOIN users taker ON taker.id = qa.user_id
            WHERE qa.id = :attempt_id
            LIMIT 1
        ');
        $stmt->execute(['attempt_id' => $attemptId]);
        $attempt = $stmt->fetch();

        if (!$attempt) {
            return null;
        }

        $questions = $this->getQuizQuestions((int) $attempt['quiz_id']);
        $answerStmt = $pdo->prepare('
            SELECT question_id, selected_choice_id, question_text_snapshot, explanation_snapshot, difficulty_snapshot, choices_snapshot, is_correct
            FROM quiz_answers
            WHERE attempt_id = :attempt_id
        ');
        $answerStmt->execute(['attempt_id' => $attemptId]);
        $answersByQuestion = [];

        foreach ($answerStmt->fetchAll() as $answer) {
            $answersByQuestion[(int) $answer['question_id']] = $answer;
        }

        foreach ($questions as &$question) {
            $answer = $answersByQuestion[(int) $question['id']] ?? null;
            $question['selected_choice_id'] = $answer['selected_choice_id'] ?? null;
            $question['was_correct'] = (int) ($answer['is_correct'] ?? 0) === 1;

            if ($answer !== null) {
                $question = $this->applyAnswerSnapshotToQuestion($question, $answer);
            }
        }

        return [
            'attempt' => $attempt,
            'questions' => $questions,
        ];
    }

    public function userOptions(): array
    {
        $stmt = $this->app->ensureDatabase()->query('
            SELECT id, full_name, email, role
            FROM users
            ORDER BY full_name ASC, id ASC
        ');

        return $stmt->fetchAll();
    }

    public function quizOptions(): array
    {
        $stmt = $this->app->ensureDatabase()->query('
            SELECT q.id, q.title, u.full_name AS creator_name
            FROM quizzes q
            INNER JOIN users u ON u.id = q.user_id
            WHERE q.date_deleted IS NULL
            ORDER BY q.title ASC, q.id ASC
        ');

        return $stmt->fetchAll();
    }

    private function validateUserPayload(string $fullName, string $email, string $password, string $role, string $status, bool $passwordRequired): void
    {
        if ($fullName === '' || $email === '') {
            throw new RuntimeException('Name and email are required.');
        }

        if (!$this->security->validEmail($email)) {
            throw new RuntimeException('Enter a valid email address.');
        }

        if ($passwordRequired && !$this->security->validPassword($password)) {
            throw new RuntimeException('Password must be at least 8 characters.');
        }

        if (!$passwordRequired && $password !== '' && !$this->security->validPassword($password)) {
            throw new RuntimeException('Password must be at least 8 characters.');
        }

        if (!in_array($role, ['admin', 'user'], true)) {
            throw new RuntimeException('Invalid role selected.');
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new RuntimeException('Invalid user status.');
        }
    }

    private function getQuizQuestions(int $quizId): array
    {
        $pdo = $this->app->ensureDatabase();
        $questionStmt = $pdo->prepare('
            SELECT id, question_text, explanation, difficulty, sort_order
            FROM quiz_questions
            WHERE quiz_id = :quiz_id
            ORDER BY sort_order ASC, id ASC
        ');
        $questionStmt->execute(['quiz_id' => $quizId]);
        $questions = $questionStmt->fetchAll();

        foreach ($questions as &$question) {
            $choiceStmt = $pdo->prepare('
                SELECT id, choice_text, is_correct
                FROM quiz_choices
                WHERE question_id = :question_id
                ORDER BY id ASC
            ');
            $choiceStmt->execute(['question_id' => $question['id']]);
            $question['choices'] = $choiceStmt->fetchAll();
        }

        return $questions;
    }

    private function applyAnswerSnapshotToQuestion(array $question, array $answer): array
    {
        if (($answer['question_text_snapshot'] ?? null) !== null && $answer['question_text_snapshot'] !== '') {
            $question['question_text'] = $answer['question_text_snapshot'];
        }

        if (array_key_exists('explanation_snapshot', $answer) && $answer['explanation_snapshot'] !== null) {
            $question['explanation'] = $answer['explanation_snapshot'];
        }

        if (($answer['difficulty_snapshot'] ?? null) !== null && $answer['difficulty_snapshot'] !== '') {
            $question['difficulty'] = $answer['difficulty_snapshot'];
        }

        $choicesSnapshot = json_decode((string) ($answer['choices_snapshot'] ?? ''), true);

        if (is_array($choicesSnapshot) && $choicesSnapshot !== []) {
            $normalizedChoices = [];

            foreach ($choicesSnapshot as $choice) {
                if (!is_array($choice)) {
                    continue;
                }

                $normalizedChoices[] = [
                    'id' => (int) ($choice['id'] ?? 0),
                    'choice_text' => (string) ($choice['choice_text'] ?? ''),
                    'is_correct' => (int) ($choice['is_correct'] ?? 0),
                ];
            }

            if ($normalizedChoices !== []) {
                $question['choices'] = $normalizedChoices;
            }
        }

        return $question;
    }

    private function normalizeQuestionPayload($questions): array
    {
        if (!is_array($questions)) {
            return [];
        }

        $normalized = [];

        foreach ($questions as $question) {
            if (!is_array($question)) {
                continue;
            }

            $questionText = trim((string) ($question['question_text'] ?? ''));
            $explanation = trim((string) ($question['explanation'] ?? ''));
            $difficulty = ucfirst(strtolower(trim((string) ($question['difficulty'] ?? 'Medium'))));
            $correctIndex = (int) ($question['correct_index'] ?? 0);
            $rawChoices = is_array($question['choices'] ?? null) ? $question['choices'] : [];
            $choices = [];

            foreach ($rawChoices as $choiceText) {
                $choiceText = trim((string) $choiceText);

                if ($choiceText !== '') {
                    $choices[] = $choiceText;
                }
            }

            if ($questionText === '' || count($choices) < 2) {
                continue;
            }

            if (!in_array($difficulty, ['Easy', 'Medium', 'Hard'], true)) {
                $difficulty = 'Medium';
            }

            if (!isset($choices[$correctIndex])) {
                $correctIndex = 0;
            }

            $normalized[] = [
                'id' => (int) ($question['id'] ?? 0),
                'question_text' => $questionText,
                'explanation' => $explanation,
                'difficulty' => $difficulty,
                'choices' => $choices,
                'correct_index' => $correctIndex,
            ];
        }

        return $normalized;
    }
}
