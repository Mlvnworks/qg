<?php

class QuizService
{
    private ?PDO $pdo;
    private App $app;
    private Auth $auth;
    private GeminiService $gemini;
    private PusherService $pusher;

    public function __construct(?PDO $pdo, App $app, Auth $auth, GeminiService $gemini, PusherService $pusher)
    {
        $this->pdo = $pdo;
        $this->app = $app;
        $this->auth = $auth;
        $this->gemini = $gemini;
        $this->pusher = $pusher;
    }

    public function dashboardSummary(int $userId): array
    {
        $pdo = $this->app->ensureDatabase();

        $stmt = $pdo->prepare('CALL sp_get_user_dashboard_summary(:user_id)');
        $stmt->execute(['user_id' => $userId]);
        $summary = $stmt->fetch() ?: [];
        $stmt->closeCursor();

        return array_merge([
            'total_quizzes_created' => 0,
            'total_quizzes_taken' => 0,
            'total_quiz_takers' => 0,
            'ongoing_attempts' => 0,
            'completed_attempts' => 0,
            'average_score' => 0,
        ], $summary);
    }

    public function recentQuizzes(int $userId, int $limit = 5): array
    {
        $pdo = $this->app->ensureDatabase();
        $stmt = $pdo->prepare('
            SELECT id, title, difficulty, question_count, share_code, status, created_at
            FROM quizzes
            WHERE user_id = :user_id AND date_deleted IS NULL
            ORDER BY created_at DESC
            LIMIT ' . (int) $limit
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function recentAttempts(int $userId, int $limit = 5): array
    {
        $pdo = $this->app->ensureDatabase();
        $stmt = $pdo->prepare('
            SELECT qa.id, q.title, qa.score, qa.total_questions, qa.percentage, qa.result, qa.status, qa.started_at, qa.completed_at
            FROM quiz_attempts qa
            INNER JOIN quizzes q ON q.id = qa.quiz_id
            WHERE qa.user_id = :user_id
            ORDER BY qa.started_at DESC
            LIMIT ' . (int) $limit
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function getRealtimePanelState(int $userId, int $limit = 8): array
    {
        $pdo = $this->app->ensureDatabase();

        $channelStmt = $pdo->prepare('
            SELECT id, title
            FROM quizzes
            WHERE user_id = :user_id AND date_deleted IS NULL
            ORDER BY created_at DESC
            LIMIT 20
        ');
        $channelStmt->execute(['user_id' => $userId]);
        $channels = $channelStmt->fetchAll();

        $logStmt = $pdo->prepare('
            SELECT rl.id, rl.quiz_id, rl.user_id, rl.event_name, rl.event_data, rl.created_at,
                   q.title AS quiz_title, actor.full_name AS actor_name
            FROM realtime_logs rl
            INNER JOIN quizzes q ON q.id = rl.quiz_id
            LEFT JOIN users actor ON actor.id = rl.user_id
            WHERE q.user_id = :user_id AND q.date_deleted IS NULL
            ORDER BY rl.created_at DESC, rl.id DESC
            LIMIT ' . (int) $limit
        );
        $logStmt->execute(['user_id' => $userId]);

        return [
            'channels' => $channels,
            'cards' => $this->buildRealtimeCards($logStmt->fetchAll()),
        ];
    }

    public function getQuizRealtimeState(int $quizId, int $ownerId, int $limit = 8): array
    {
        $quiz = $this->getQuizById($quizId, $ownerId);

        if ($quiz === null) {
            throw new RuntimeException('Quiz not found.');
        }

        $pdo = $this->app->ensureDatabase();
        $logStmt = $pdo->prepare('
            SELECT rl.id, rl.quiz_id, rl.user_id, rl.event_name, rl.event_data, rl.created_at,
                   q.title AS quiz_title, actor.full_name AS actor_name
            FROM realtime_logs rl
            INNER JOIN quizzes q ON q.id = rl.quiz_id
            LEFT JOIN users actor ON actor.id = rl.user_id
            WHERE rl.quiz_id = :quiz_id
            ORDER BY rl.created_at DESC, rl.id DESC
            LIMIT ' . (int) $limit
        );
        $logStmt->execute(['quiz_id' => $quizId]);

        return [
            'quiz' => $quiz,
            'cards' => $this->buildRealtimeCards($logStmt->fetchAll()),
        ];
    }

    public function listOwnedQuizzes(int $userId): array
    {
        $pdo = $this->app->ensureDatabase();
        $stmt = $pdo->prepare('
            SELECT q.*, qs.total_attempts, qs.completed_attempts, qs.average_score
            FROM quizzes q
            LEFT JOIN quiz_statistics qs ON qs.quiz_id = q.id
            WHERE q.user_id = :user_id AND q.date_deleted IS NULL
            ORDER BY q.created_at DESC
        ');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function getPublicQuizByShareCode(string $shareCode): ?array
    {
        $pdo = $this->app->ensureDatabase();
        $stmt = $pdo->prepare('
            SELECT q.id, q.user_id, q.title, q.topic, q.difficulty, q.question_count, q.timer_per_question, q.passing_rate, q.status, q.share_code, u.full_name AS creator_name
            FROM quizzes q
            INNER JOIN users u ON u.id = q.user_id
            WHERE q.share_code = :share_code AND q.date_deleted IS NULL
            LIMIT 1
        ');
        $stmt->execute(['share_code' => $shareCode]);
        $quiz = $stmt->fetch();

        if (!$quiz) {
            return null;
        }

        $quiz['questions'] = $this->loadQuizQuestions((int) $quiz['id'], (int) $quiz['question_count'], false);

        return $quiz;
    }

    public function getQuizById(int $quizId, int $ownerId): ?array
    {
        $pdo = $this->app->ensureDatabase();
        $stmt = $pdo->prepare('
            SELECT q.*, u.full_name AS creator_name, qs.total_attempts, qs.ongoing_attempts, qs.completed_attempts, qs.average_score
            FROM quizzes q
            INNER JOIN users u ON u.id = q.user_id
            LEFT JOIN quiz_statistics qs ON qs.quiz_id = q.id
            WHERE q.id = :id AND q.user_id = :user_id AND q.date_deleted IS NULL
            LIMIT 1
        ');
        $stmt->execute([
            'id' => $quizId,
            'user_id' => $ownerId,
        ]);
        $quiz = $stmt->fetch();

        if (!$quiz) {
            return null;
        }

        $quiz['questions'] = $this->loadQuizQuestions($quizId, (int) $quiz['question_count'], true);
        $quiz['share_url'] = $this->app->url('take-quiz', ['quiz' => $quiz['share_code']]);

        return $quiz;
    }

    public function startQuizAttempt(string $shareCode, int $userId): array
    {
        $quiz = $this->getPublicQuizByShareCode($shareCode);

        if ($quiz === null) {
            throw new RuntimeException('Quiz not found.');
        }

        if ($quiz['status'] !== 'active') {
            throw new RuntimeException('This quiz is not active right now.');
        }

        $pdo = $this->app->ensureDatabase();
        $attemptStmt = $pdo->prepare('
            INSERT INTO quiz_attempts (quiz_id, user_id, status, score, total_questions, percentage, started_at)
            VALUES (:quiz_id, :user_id, :status, :score, :total_questions, :percentage, NOW())
        ');
        $totalQuestions = count($quiz['questions']);
        $attemptStmt->execute([
            'quiz_id' => (int) $quiz['id'],
            'user_id' => $userId,
            'status' => 'ongoing',
            'score' => 0,
            'total_questions' => $totalQuestions,
            'percentage' => 0,
        ]);
        $attemptId = (int) $pdo->lastInsertId();

        if ($attemptId <= 0) {
            throw new RuntimeException('Failed to start quiz attempt.');
        }

        $actor = $this->auth->user();
        $this->auth->logActivity($userId, 'quiz_attempt_started', 'Started quiz "' . $quiz['title'] . '".');
        $this->pusher->triggerQuizEvent((int) $quiz['id'], 'quiz.attempt.started', [
            'actor_user_id' => $userId,
            'actor_name' => $actor['full_name'] ?? 'Quiz taker',
            'quiz_id' => (int) $quiz['id'],
            'quiz_title' => $quiz['title'],
            'attempt_id' => $attemptId,
            'status' => 'ongoing',
        ]);

        return [
            'attempt_id' => $attemptId,
            'share_code' => $quiz['share_code'],
        ];
    }

    public function getAttemptForUser(int $attemptId, int $userId): ?array
    {
        $pdo = $this->app->ensureDatabase();
        $stmt = $pdo->prepare('
            SELECT qa.*, q.title, q.topic, q.difficulty, q.question_count, q.timer_per_question, q.passing_rate, q.share_code, u.full_name AS creator_name
            FROM quiz_attempts qa
            INNER JOIN quizzes q ON q.id = qa.quiz_id
            INNER JOIN users u ON u.id = q.user_id
            WHERE qa.id = :attempt_id AND qa.user_id = :user_id
            LIMIT 1
        ');
        $stmt->execute([
            'attempt_id' => $attemptId,
            'user_id' => $userId,
        ]);
        $attempt = $stmt->fetch();

        if (!$attempt) {
            return null;
        }

        $attempt['questions'] = $this->loadQuizQuestions((int) $attempt['quiz_id'], (int) $attempt['question_count'], true);

        return $attempt;
    }

    public function updateQuiz(int $quizId, int $ownerId, array $payload): void
    {
        $quiz = $this->getQuizById($quizId, $ownerId);

        if ($quiz === null) {
            throw new RuntimeException('Quiz not found.');
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $topic = trim((string) ($payload['topic'] ?? ''));
        $difficulty = ucfirst(strtolower(trim((string) ($payload['difficulty'] ?? 'Medium'))));
        $questionCount = max(1, min(20, (int) ($payload['question_count'] ?? 10)));
        $timerPerQuestion = max(10, min(600, (int) ($payload['timer_per_question'] ?? 30)));
        $passingRate = max(1, min(100, (int) ($payload['passing_rate'] ?? 70)));

        if ($title === '' || $topic === '') {
            throw new RuntimeException('Quiz title and topic are required.');
        }

        if (!in_array($difficulty, ['Easy', 'Medium', 'Hard'], true)) {
            throw new RuntimeException('Invalid difficulty selected.');
        }

        $pdo = $this->app->ensureDatabase();
        $stmt = $pdo->prepare('
            UPDATE quizzes
            SET title = :title,
                topic = :topic,
                difficulty = :difficulty,
                question_count = :question_count,
                timer_per_question = :timer_per_question,
                passing_rate = :passing_rate,
                updated_at = NOW()
            WHERE id = :quiz_id AND user_id = :user_id AND date_deleted IS NULL
        ');
        $stmt->execute([
            'title' => $title,
            'topic' => $topic,
            'difficulty' => $difficulty,
            'question_count' => $questionCount,
            'timer_per_question' => $timerPerQuestion,
            'passing_rate' => $passingRate,
            'quiz_id' => $quizId,
            'user_id' => $ownerId,
        ]);

        $this->auth->logActivity($ownerId, 'quiz_updated', 'Updated quiz "' . $title . '".');
    }

    public function updateQuizStatus(int $quizId, int $ownerId, string $status): void
    {
        $allowedStatuses = ['active', 'inactive', 'draft'];

        if (!in_array($status, $allowedStatuses, true)) {
            throw new RuntimeException('Invalid quiz status.');
        }

        $pdo = $this->app->ensureDatabase();
        $stmt = $pdo->prepare('
            UPDATE quizzes
            SET status = :status, updated_at = NOW()
            WHERE id = :quiz_id AND user_id = :user_id AND date_deleted IS NULL
        ');
        $stmt->execute([
            'status' => $status,
            'quiz_id' => $quizId,
            'user_id' => $ownerId,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Quiz not found.');
        }

        $this->auth->logActivity($ownerId, 'quiz_status_updated', 'Set quiz #' . $quizId . ' to ' . $status . '.');
    }

    public function softDeleteQuiz(int $quizId, int $ownerId): void
    {
        $pdo = $this->app->ensureDatabase();
        $stmt = $pdo->prepare('
            UPDATE quizzes
            SET date_deleted = NOW(), status = :status, updated_at = NOW()
            WHERE id = :quiz_id AND user_id = :user_id AND date_deleted IS NULL
        ');
        $stmt->execute([
            'status' => 'inactive',
            'quiz_id' => $quizId,
            'user_id' => $ownerId,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Quiz not found or already deleted.');
        }

        $this->auth->logActivity($ownerId, 'quiz_soft_deleted', 'Soft-deleted quiz #' . $quizId . '.');
    }

    public function submitQuizAttempt(int $attemptId, int $userId, array $answers): array
    {
        $attempt = $this->getAttemptForUser($attemptId, $userId);

        if ($attempt === null) {
            throw new RuntimeException('Quiz attempt not found.');
        }

        if ($attempt['status'] !== 'ongoing') {
            throw new RuntimeException('This quiz attempt has already been submitted.');
        }

        $result = $this->finalizeAttempt($attempt, $answers, 'completed');

        return [
            'attempt_id' => $attemptId,
            'share_code' => $attempt['share_code'],
        ];
    }

    public function markAttemptUnfinished(int $attemptId, int $userId, array $answers): array
    {
        $attempt = $this->getAttemptForUser($attemptId, $userId);

        if ($attempt === null) {
            throw new RuntimeException('Quiz attempt not found.');
        }

        if ($attempt['status'] !== 'ongoing') {
            return [
                'attempt_id' => $attemptId,
                'share_code' => $attempt['share_code'],
            ];
        }

        $this->finalizeAttempt($attempt, $answers, 'unfinished');

        return [
            'attempt_id' => $attemptId,
            'share_code' => $attempt['share_code'],
        ];
    }

    private function finalizeAttempt(array $attempt, array $answers, string $finalStatus): array
    {
        if (!in_array($finalStatus, ['completed', 'unfinished'], true)) {
            throw new RuntimeException('Invalid attempt status.');
        }

        $pdo = $this->app->ensureDatabase();
        $pdo->beginTransaction();

        try {
            $deleteStmt = $pdo->prepare('DELETE FROM quiz_answers WHERE attempt_id = :attempt_id');
            $deleteStmt->execute(['attempt_id' => (int) $attempt['id']]);

            $insertStmt = $pdo->prepare('
                INSERT INTO quiz_answers (
                    attempt_id,
                    question_id,
                    selected_choice_id,
                    question_text_snapshot,
                    explanation_snapshot,
                    difficulty_snapshot,
                    choices_snapshot,
                    is_correct,
                    answered_at
                )
                VALUES (
                    :attempt_id,
                    :question_id,
                    :selected_choice_id,
                    :question_text_snapshot,
                    :explanation_snapshot,
                    :difficulty_snapshot,
                    :choices_snapshot,
                    :is_correct,
                    NOW()
                )
            ');
            $answeredCount = 0;
            $correctCount = 0;

            foreach ($attempt['questions'] as $question) {
                $selectedChoiceId = isset($answers[$question['id']]) ? (int) $answers[$question['id']] : null;
                $isCorrect = 0;
                $validatedChoiceId = null;

                foreach ($question['choices'] as $choice) {
                    if ($selectedChoiceId !== null && (int) $choice['id'] === $selectedChoiceId) {
                        $validatedChoiceId = $selectedChoiceId;
                        $isCorrect = (int) $choice['is_correct'] === 1 ? 1 : 0;
                        break;
                    }
                }

                $choiceSnapshots = [];

                foreach ($question['choices'] as $choice) {
                    $choiceSnapshots[] = [
                        'id' => (int) $choice['id'],
                        'choice_text' => (string) $choice['choice_text'],
                        'is_correct' => (int) ($choice['is_correct'] ?? 0),
                    ];
                }

                $insertStmt->execute([
                    'attempt_id' => (int) $attempt['id'],
                    'question_id' => (int) $question['id'],
                    'selected_choice_id' => $validatedChoiceId,
                    'question_text_snapshot' => (string) $question['question_text'],
                    'explanation_snapshot' => (string) ($question['explanation'] ?? ''),
                    'difficulty_snapshot' => (string) ($question['difficulty'] ?? ''),
                    'choices_snapshot' => json_encode($choiceSnapshots, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'is_correct' => $isCorrect,
                ]);

                if ($validatedChoiceId !== null) {
                    $answeredCount++;
                    $correctCount += $isCorrect;
                }
            }

            if ($finalStatus === 'completed') {
                $scoreStmt = $pdo->prepare('CALL sp_compute_quiz_score(:attempt_id)');
                $scoreStmt->execute(['attempt_id' => (int) $attempt['id']]);
                $result = $scoreStmt->fetch() ?: [];
                $scoreStmt->closeCursor();
            } else {
                $gradedTotal = max(0, $answeredCount);
                $percentage = $gradedTotal > 0 ? round(($correctCount / $gradedTotal) * 100, 2) : 0;

                $updateStmt = $pdo->prepare('
                    UPDATE quiz_attempts
                    SET status = :status,
                        score = :score,
                        total_questions = :total_questions,
                        percentage = :percentage,
                        result = NULL,
                        completed_at = NOW()
                    WHERE id = :attempt_id
                ');
                $updateStmt->execute([
                    'status' => 'unfinished',
                    'score' => $correctCount,
                    'total_questions' => $gradedTotal,
                    'percentage' => $percentage,
                    'attempt_id' => (int) $attempt['id'],
                ]);

                $result = [
                    'score' => $correctCount,
                    'total_questions' => $gradedTotal,
                    'percentage' => $percentage,
                    'result' => null,
                ];
            }

            $pdo->commit();
        } catch (Throwable $err) {
            $pdo->rollBack();
            throw $err;
        }

        $actor = $this->auth->user();

        if ($finalStatus === 'completed') {
            $this->auth->logActivity((int) $attempt['user_id'], 'quiz_attempt_completed', 'Submitted quiz "' . $attempt['title'] . '".');
            $this->pusher->triggerQuizEvent((int) $attempt['quiz_id'], 'quiz.attempt.completed', [
                'actor_user_id' => (int) $attempt['user_id'],
                'actor_name' => $actor['full_name'] ?? 'Quiz taker',
                'quiz_id' => (int) $attempt['quiz_id'],
                'quiz_title' => $attempt['title'],
                'attempt_id' => (int) $attempt['id'],
                'score' => (int) ($result['score'] ?? 0),
                'percentage' => (float) ($result['percentage'] ?? 0),
                'result' => $result['result'] ?? null,
                'status' => 'completed',
            ]);
        } else {
            $this->auth->logActivity((int) $attempt['user_id'], 'quiz_attempt_unfinished', 'Left quiz "' . $attempt['title'] . '" unfinished.');
            $this->pusher->triggerQuizEvent((int) $attempt['quiz_id'], 'quiz.attempt.unfinished', [
                'actor_user_id' => (int) $attempt['user_id'],
                'actor_name' => $actor['full_name'] ?? 'Quiz taker',
                'quiz_id' => (int) $attempt['quiz_id'],
                'quiz_title' => $attempt['title'],
                'attempt_id' => (int) $attempt['id'],
                'score' => (int) ($result['score'] ?? 0),
                'percentage' => (float) ($result['percentage'] ?? 0),
                'result' => null,
                'status' => 'unfinished',
            ]);
        }

        return $result;
    }

    public function getAttemptResult(int $attemptId, int $userId): ?array
    {
        $attempt = $this->getAttemptForUser($attemptId, $userId);

        if ($attempt === null) {
            return null;
        }

        $answerStmt = $this->app->ensureDatabase()->prepare('
            SELECT question_id, selected_choice_id, question_text_snapshot, explanation_snapshot, difficulty_snapshot, choices_snapshot, is_correct
            FROM quiz_answers
            WHERE attempt_id = :attempt_id
        ');
        $answerStmt->execute(['attempt_id' => $attemptId]);
        $answers = $answerStmt->fetchAll();
        $answersByQuestion = [];

        foreach ($answers as $answer) {
            $answersByQuestion[(int) $answer['question_id']] = $answer;
        }

        foreach ($attempt['questions'] as &$question) {
            $answer = $answersByQuestion[(int) $question['id']] ?? null;
            $question['selected_choice_id'] = $answer['selected_choice_id'] ?? null;
            $question['was_correct'] = (int) ($answer['is_correct'] ?? 0) === 1;

            if ($answer !== null) {
                $question = $this->applyAnswerSnapshotToQuestion($question, $answer);
            }
        }

        return $attempt;
    }

    public function getOwnerQuizAttempts(int $quizId, int $ownerId, string $sortBy = 'started_at', string $sortDir = 'desc'): array
    {
        $quiz = $this->getQuizById($quizId, $ownerId);

        if ($quiz === null) {
            throw new RuntimeException('Quiz not found.');
        }

        $allowedSorts = [
            'taker' => 'taker.full_name',
            'score' => 'qa.score',
            'percentage' => 'qa.percentage',
            'remark' => 'COALESCE(qa.result, qa.status)',
            'started_at' => 'qa.started_at',
            'completed_at' => 'qa.completed_at',
        ];
        $normalizedSortBy = array_key_exists($sortBy, $allowedSorts) ? $sortBy : 'started_at';
        $normalizedSortDir = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';
        $orderBySql = $allowedSorts[$normalizedSortBy] . ' ' . $normalizedSortDir . ', qa.id DESC';

        $pdo = $this->app->ensureDatabase();
        $stmt = $pdo->prepare("
            SELECT qa.id, qa.score, qa.total_questions, qa.percentage, qa.result, qa.status, qa.started_at, qa.completed_at,
                   taker.full_name AS taker_name, taker.email AS taker_email
            FROM quiz_attempts qa
            INNER JOIN users taker ON taker.id = qa.user_id
            WHERE qa.quiz_id = :quiz_id
            ORDER BY {$orderBySql}
        ");
        $stmt->execute(['quiz_id' => $quizId]);

        return [
            'quiz' => $quiz,
            'attempts' => $stmt->fetchAll(),
        ];
    }

    public function getOwnerAttemptReview(int $quizId, int $attemptId, int $ownerId): ?array
    {
        $quiz = $this->getQuizById($quizId, $ownerId);

        if ($quiz === null) {
            return null;
        }

        $pdo = $this->app->ensureDatabase();
        $stmt = $pdo->prepare('
            SELECT qa.*, taker.full_name AS taker_name, taker.email AS taker_email
            FROM quiz_attempts qa
            INNER JOIN users taker ON taker.id = qa.user_id
            WHERE qa.id = :attempt_id AND qa.quiz_id = :quiz_id
            LIMIT 1
        ');
        $stmt->execute([
            'attempt_id' => $attemptId,
            'quiz_id' => $quizId,
        ]);
        $attempt = $stmt->fetch();

        if (!$attempt) {
            return null;
        }

        $answerStmt = $pdo->prepare('
            SELECT question_id, selected_choice_id, question_text_snapshot, explanation_snapshot, difficulty_snapshot, choices_snapshot, is_correct
            FROM quiz_answers
            WHERE attempt_id = :attempt_id
        ');
        $answerStmt->execute(['attempt_id' => $attemptId]);
        $answers = $answerStmt->fetchAll();
        $answersByQuestion = [];

        foreach ($answers as $answer) {
            $answersByQuestion[(int) $answer['question_id']] = $answer;
        }

        foreach ($quiz['questions'] as &$question) {
            $answer = $answersByQuestion[(int) $question['id']] ?? null;
            $question['selected_choice_id'] = $answer['selected_choice_id'] ?? null;
            $question['was_correct'] = (int) ($answer['is_correct'] ?? 0) === 1;

            if ($answer !== null) {
                $question = $this->applyAnswerSnapshotToQuestion($question, $answer);
            }
        }

        return [
            'quiz' => $quiz,
            'attempt' => $attempt,
            'questions' => $quiz['questions'],
        ];
    }

    public function createQuiz(array $payload, array $filePayload): int
    {
        $user = $this->auth->user();
        if ($user === null) {
            throw new RuntimeException('Authentication required.');
        }

        $title = trim($payload['title'] ?? '');
        $topic = trim($payload['topic'] ?? '');
        $difficulty = ucfirst(strtolower(trim($payload['difficulty'] ?? 'Medium')));
        $questionCount = max(1, min(20, (int) ($payload['question_count'] ?? 10)));
        $timerPerQuestion = max(10, min(600, (int) ($payload['timer_per_question'] ?? 30)));
        $passingRate = max(1, min(100, (int) ($payload['passing_rate'] ?? 70)));

        if ($title === '' || $topic === '') {
            throw new RuntimeException('Quiz title and topic are required.');
        }

        $allowedDifficulties = ['Easy', 'Medium', 'Hard'];
        if (!in_array($difficulty, $allowedDifficulties, true)) {
            throw new RuntimeException('Invalid difficulty selected.');
        }

        $pdo = $this->app->ensureDatabase();
        $sourceText = null;
        $uploadRecord = null;
        $uploadedExtension = null;
        $sourceFileForGemini = null;

        if (!$this->gemini->isConfigured()) {
            throw new RuntimeException('Real quiz generation requires Gemini API. Add a valid GEMINI_API_KEY in .env before creating quizzes.');
        }

        if (isset($filePayload['source_file']) && (int) ($filePayload['source_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploadedExtension = strtolower(pathinfo((string) ($filePayload['source_file']['name'] ?? ''), PATHINFO_EXTENSION));
            $uploadRecord = $this->storeUpload((int) $user['id'], $filePayload['source_file']);
            $sourceText = $uploadRecord['extracted_text'];
            $sourceFileForGemini = [
                'absolute_path' => $uploadRecord['absolute_path'],
                'mime_type' => $uploadRecord['mime_type'],
                'original_filename' => $uploadRecord['original_filename'],
                'extension' => $uploadedExtension,
            ];

            $isInlineImage = in_array($sourceFileForGemini['mime_type'], ['image/png', 'image/jpeg', 'image/webp'], true);
            $hasAnySourceText = is_string($sourceText) && mb_strlen(trim($sourceText)) >= 40;

            if (!$isInlineImage && !$hasAnySourceText) {
                throw new RuntimeException('The uploaded file does not contain enough readable source content yet. Use TXT, DOCX, PPTX, or a clearer PDF/image.');
            }
        }

        $questions = $this->gemini->generateQuestions($topic, $difficulty, $questionCount, $sourceText, $sourceFileForGemini);

        if (count($questions) === 0) {
            $geminiError = $this->gemini->getLastError();

            if ($geminiError !== '') {
                throw new RuntimeException('Quiz generation failed: ' . $geminiError);
            }

            throw new RuntimeException('Quiz generation failed. Check your Gemini API key, model, and network access, then try again.');
        }

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('CALL sp_create_quiz(:user_id, :title, :topic, :difficulty, :question_count, :timer_per_question, :passing_rate, :share_code)');
            $shareCode = $this->app->generateShareCode();
            $stmt->execute([
                'user_id' => (int) $user['id'],
                'title' => $title,
                'topic' => $topic,
                'difficulty' => $difficulty,
                'question_count' => count($questions),
                'timer_per_question' => $timerPerQuestion,
                'passing_rate' => $passingRate,
                'share_code' => $shareCode,
            ]);
            $quizRow = $stmt->fetch();
            $stmt->closeCursor();

            $quizId = (int) ($quizRow['quiz_id'] ?? 0);
            if ($quizId <= 0) {
                $quizId = (int) $pdo->lastInsertId();
            }

            if ($uploadRecord !== null) {
                $uploadStmt = $pdo->prepare('UPDATE uploaded_files SET quiz_id = :quiz_id WHERE id = :id');
                $uploadStmt->execute([
                    'quiz_id' => $quizId,
                    'id' => $uploadRecord['id'],
                ]);
            }

            $questionStmt = $pdo->prepare('
                INSERT INTO quiz_questions (quiz_id, question_text, explanation, difficulty, sort_order, created_at, updated_at)
                VALUES (:quiz_id, :question_text, :explanation, :difficulty, :sort_order, NOW(), NOW())
            ');
            $choiceStmt = $pdo->prepare('
                INSERT INTO quiz_choices (question_id, choice_text, is_correct, created_at, updated_at)
                VALUES (:question_id, :choice_text, :is_correct, NOW(), NOW())
            ');

            foreach ($questions as $index => $question) {
                $questionStmt->execute([
                    'quiz_id' => $quizId,
                    'question_text' => $question['question'],
                    'explanation' => $question['explanation'],
                    'difficulty' => ucfirst(strtolower($question['difficulty'])),
                    'sort_order' => $index + 1,
                ]);

                $questionId = (int) $pdo->lastInsertId();

                foreach ($question['choices'] as $choiceText) {
                    $choiceStmt->execute([
                        'question_id' => $questionId,
                        'choice_text' => $choiceText,
                        'is_correct' => $choiceText === $question['correct_answer'] ? 1 : 0,
                    ]);
                }
            }

            $pdo->commit();
        } catch (Throwable $err) {
            $pdo->rollBack();
            throw $err;
        }

        $this->auth->logActivity((int) $user['id'], 'quiz_created', 'Created quiz "' . $title . '".');
        $this->pusher->triggerQuizEvent($quizId, 'quiz.created', [
            'actor_user_id' => (int) $user['id'],
            'actor_name' => $user['full_name'] ?? 'Quiz owner',
            'quiz_id' => $quizId,
            'title' => $title,
            'quiz_title' => $title,
        ]);

        return $quizId;
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

    private function loadQuizQuestions(int $quizId, int $questionLimit, bool $includeCorrectFlag): array
    {
        $pdo = $this->app->ensureDatabase();
        $limit = max(1, min(50, $questionLimit));
        $questionStmt = $pdo->prepare("
            SELECT id, question_text, explanation, difficulty, sort_order
            FROM quiz_questions
            WHERE quiz_id = :quiz_id
            ORDER BY sort_order ASC, id ASC
            LIMIT {$limit}
        ");
        $questionStmt->execute(['quiz_id' => $quizId]);
        $questions = $questionStmt->fetchAll();

        foreach ($questions as &$question) {
            $choiceStmt = $pdo->prepare('
                SELECT id, choice_text' . ($includeCorrectFlag ? ', is_correct' : '') . '
                FROM quiz_choices
                WHERE question_id = :question_id
                ORDER BY id ASC
            ');
            $choiceStmt->execute(['question_id' => $question['id']]);
            $question['choices'] = $choiceStmt->fetchAll();
        }

        return $questions;
    }

    private function buildRealtimeCards(array $rows): array
    {
        $cards = [
            'latest_taker' => [
                'value' => 'Awaiting activity',
                'meta' => 'Newest taker appears here.',
            ],
            'completion_status' => [
                'value' => 'No active session',
                'meta' => 'Waiting for quiz activity.',
            ],
            'last_score' => [
                'value' => '0%',
                'meta' => 'Latest submitted result.',
            ],
            'events' => [],
        ];

        foreach ($rows as $row) {
            $payload = json_decode((string) ($row['event_data'] ?? ''), true);
            $payload = is_array($payload) ? $payload : [];
            $eventName = (string) ($row['event_name'] ?? '');
            $quizTitle = (string) ($payload['quiz_title'] ?? $row['quiz_title'] ?? 'Quiz');
            $actorName = trim((string) ($payload['actor_name'] ?? $row['actor_name'] ?? 'A user'));
            $summary = $this->describeRealtimeEvent($eventName, $actorName, $quizTitle, $payload);

            $cards['events'][] = [
                'event_name' => $eventName,
                'label' => $this->labelRealtimeEvent($eventName),
                'actor_name' => $actorName,
                'quiz_title' => $quizTitle,
                'summary' => $summary,
                'created_at' => $row['created_at'] ?? null,
            ];

            if ($cards['latest_taker']['value'] === 'Awaiting activity' && in_array($eventName, ['quiz.attempt.started', 'quiz.attempt.completed', 'quiz.attempt.unfinished', 'quiz.test.ping'], true)) {
                $cards['latest_taker'] = [
                    'value' => $actorName,
                    'meta' => $quizTitle,
                ];
            }

            if ($cards['completion_status']['value'] === 'No active session') {
                if ($eventName === 'quiz.attempt.started') {
                    $cards['completion_status'] = [
                        'value' => 'Ongoing',
                        'meta' => $actorName . ' started ' . $quizTitle . '.',
                    ];
                } elseif ($eventName === 'quiz.attempt.completed') {
                    $cards['completion_status'] = [
                        'value' => 'Completed',
                        'meta' => $actorName . ' submitted ' . $quizTitle . '.',
                    ];
                } elseif ($eventName === 'quiz.attempt.unfinished') {
                    $cards['completion_status'] = [
                        'value' => 'Unfinished',
                        'meta' => $actorName . ' left ' . $quizTitle . '.',
                    ];
                } elseif ($eventName === 'quiz.test.ping') {
                    $cards['completion_status'] = [
                        'value' => 'Live Signal',
                        'meta' => 'Manual Pusher test was sent.',
                    ];
                }
            }

            if ($cards['last_score']['value'] === '0%' && $eventName === 'quiz.attempt.completed') {
                $scoreValue = isset($payload['percentage']) ? number_format((float) $payload['percentage'], 2) . '%' : 'Scored';
                $resultLabel = trim((string) ($payload['result'] ?? 'Completed'));
                $cards['last_score'] = [
                    'value' => $scoreValue,
                    'meta' => $resultLabel === '' ? 'Latest submitted result.' : $resultLabel,
                ];
            }
        }

        return $cards;
    }

    private function labelRealtimeEvent(string $eventName): string
    {
        return match ($eventName) {
            'quiz.created' => 'Quiz Created',
            'quiz.attempt.started' => 'Attempt Started',
            'quiz.attempt.completed' => 'Attempt Submitted',
            'quiz.attempt.unfinished' => 'Attempt Unfinished',
            'quiz.test.ping' => 'Live Test',
            default => 'Activity',
        };
    }

    private function describeRealtimeEvent(string $eventName, string $actorName, string $quizTitle, array $payload): string
    {
        return match ($eventName) {
            'quiz.created' => $actorName . ' created ' . $quizTitle . '.',
            'quiz.attempt.started' => $actorName . ' started ' . $quizTitle . '.',
            'quiz.attempt.completed' => $actorName . ' submitted ' . $quizTitle . ' with ' . number_format((float) ($payload['percentage'] ?? 0), 2) . '%.',
            'quiz.attempt.unfinished' => $actorName . ' left ' . $quizTitle . ' unfinished.',
            'quiz.test.ping' => $actorName . ' sent a live Pusher test for ' . $quizTitle . '.',
            default => $actorName . ' generated a new realtime event on ' . $quizTitle . '.',
        };
    }

    private function storeUpload(int $userId, array $file): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The uploaded file could not be processed.');
        }

        $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'png', 'jpg', 'jpeg', 'webp', 'txt'];
        $originalName = (string) ($file['name'] ?? 'upload');
        $size = (int) ($file['size'] ?? 0);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mimeType = $this->detectMimeType((string) ($file['tmp_name'] ?? ''), $extension);

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('Unsupported file type.');
        }

        if ($size <= 0 || $size > 10 * 1024 * 1024) {
            throw new RuntimeException('File size must be 10MB or less.');
        }

        if (!is_dir(APP_UPLOAD_DIR) && !mkdir(APP_UPLOAD_DIR, 0775, true) && !is_dir(APP_UPLOAD_DIR)) {
            throw new RuntimeException('Upload directory could not be created.');
        }

        if (!is_writable(APP_UPLOAD_DIR)) {
            @chmod(APP_UPLOAD_DIR, 0777);
        }

        if (!is_writable(APP_UPLOAD_DIR)) {
            throw new RuntimeException('Upload directory is not writable. Update permissions for the uploads folder.');
        }

        $safeFilename = bin2hex(random_bytes(16)) . '.' . $extension;
        $storedPath = APP_UPLOAD_DIR . '/' . $safeFilename;

        if (!move_uploaded_file($file['tmp_name'], $storedPath)) {
            throw new RuntimeException('Failed to store uploaded file. The web server may not have permission to write into uploads/.');
        }

        $excerpt = $this->extractSourceExcerpt($storedPath, $extension, $originalName);

        $pdo = $this->app->ensureDatabase();
        $stmt = $pdo->prepare('
            INSERT INTO uploaded_files (user_id, quiz_id, original_filename, stored_filename, file_path, file_type, file_size, extracted_text, uploaded_at)
            VALUES (:user_id, NULL, :original_filename, :stored_filename, :file_path, :file_type, :file_size, :extracted_text, NOW())
        ');
        $stmt->execute([
            'user_id' => $userId,
            'original_filename' => $originalName,
            'stored_filename' => $safeFilename,
            'file_path' => 'uploads/' . $safeFilename,
            'file_type' => $extension,
            'file_size' => $size,
            'extracted_text' => mb_substr($excerpt, 0, 8000),
        ]);

        return [
            'id' => (int) $pdo->lastInsertId(),
            'extracted_text' => $excerpt,
            'absolute_path' => $storedPath,
            'mime_type' => $mimeType,
            'original_filename' => $originalName,
        ];
    }

    private function extractSourceExcerpt(string $storedPath, string $extension, string $originalName): string
    {
        $excerpt = match ($extension) {
            'txt' => (string) @file_get_contents($storedPath),
            'docx' => $this->extractDocxText($storedPath),
            'pptx' => $this->extractPptxText($storedPath),
            'pdf' => $this->extractPdfText($storedPath),
            default => '',
        };

        $excerpt = $this->normalizeSourceText($excerpt);

        if ($excerpt !== '') {
            return $excerpt;
        }

        if (in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            return '';
        }

        return 'Uploaded source file: ' . preg_replace('/[^a-zA-Z0-9.\-_ ]/', '', $originalName);
    }

    private function extractDocxText(string $path): string
    {
        if (!class_exists('ZipArchive')) {
            return '';
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        if ($xml === '') {
            return '';
        }

        return html_entity_decode(strip_tags(str_replace('</w:p>', "\n", $xml)), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function extractPptxText(string $path): string
    {
        if (!class_exists('ZipArchive')) {
            return '';
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return '';
        }

        $slides = [];

        for ($index = 1; $index <= $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index - 1);

            if (!is_string($name) || !preg_match('#^ppt/slides/slide\d+\.xml$#', $name)) {
                continue;
            }

            $xml = $zip->getFromName($name);

            if (is_string($xml) && $xml !== '') {
                $slides[] = html_entity_decode(strip_tags(str_replace('</a:p>', "\n", $xml)), ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
        }

        $zip->close();

        return implode("\n", $slides);
    }

    private function extractPdfText(string $path): string
    {
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($path);
                $parsedText = $this->normalizeSourceText((string) $pdf->getText());

                if ($parsedText !== '') {
                    return $parsedText;
                }
            } catch (Throwable $err) {
                // Fall back to lightweight extraction below when the parser cannot read the file.
            }
        }

        $raw = @file_get_contents($path);

        if (!is_string($raw) || $raw === '') {
            return '';
        }

        $segments = [];

        $extractPdfTextOperators = function (string $content) use (&$segments): void {
            if (preg_match_all('/\(([^()]*)\)\s*Tj/', $content, $matches)) {
                foreach ($matches[1] as $match) {
                    $segments[] = $match;
                }
            }

            if (preg_match_all('/\[(.*?)\]\s*TJ/s', $content, $matches)) {
                foreach ($matches[1] as $match) {
                    if (preg_match_all('/\(([^()]*)\)/', $match, $chunkMatches)) {
                        foreach ($chunkMatches[1] as $chunk) {
                            $segments[] = $chunk;
                        }
                    }
                }
            }
        };

        $extractPdfTextOperators($raw);

        if (preg_match_all('/stream\s*(.*?)\s*endstream/s', $raw, $streamMatches)) {
            foreach ($streamMatches[1] as $stream) {
                $decodedStream = @gzuncompress($stream);

                if (!is_string($decodedStream) || $decodedStream === '') {
                    $decodedStream = @gzdecode($stream);
                }

                if (!is_string($decodedStream) || $decodedStream === '') {
                    $decodedStream = @gzinflate($stream);
                }

                if (is_string($decodedStream) && $decodedStream !== '') {
                    $extractPdfTextOperators($decodedStream);
                }
            }
        }

        if ($segments !== []) {
            return implode(' ', $segments);
        }

        if (preg_match_all('/[A-Za-z][A-Za-z0-9,\.\-\(\)\/ ]{8,}/', $raw, $plainMatches)) {
            return implode(' ', $plainMatches[0]);
        }

        return '';
    }

    private function normalizeSourceText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim((string) $text);
    }

    private function detectMimeType(string $tmpPath, string $extension): string
    {
        if ($tmpPath !== '' && is_file($tmpPath) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo !== false) {
                $detected = finfo_file($finfo, $tmpPath);
                finfo_close($finfo);

                if (is_string($detected) && $detected !== '') {
                    return $detected;
                }
            }
        }

        return match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'txt' => 'text/plain',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            default => 'application/octet-stream',
        };
    }
}
