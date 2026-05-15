CREATE DATABASE IF NOT EXISTS questra_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE questra_db;

DROP TABLE IF EXISTS realtime_logs;
DROP TABLE IF EXISTS quiz_statistics;
DROP TABLE IF EXISTS uploaded_files;
DROP TABLE IF EXISTS quiz_answers;
DROP TABLE IF EXISTS quiz_attempts;
DROP TABLE IF EXISTS quiz_choices;
DROP TABLE IF EXISTS quiz_questions;
DROP TABLE IF EXISTS quizzes;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    google_id VARCHAR(191) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE quizzes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    topic VARCHAR(255) NOT NULL,
    difficulty ENUM('Easy', 'Medium', 'Hard') NOT NULL DEFAULT 'Medium',
    question_count INT NOT NULL DEFAULT 10,
    timer_per_question INT NOT NULL DEFAULT 30,
    passing_rate INT NOT NULL DEFAULT 70,
    status ENUM('active', 'inactive', 'draft') NOT NULL DEFAULT 'active',
    share_code VARCHAR(32) NOT NULL UNIQUE,
    date_deleted DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_quizzes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE quiz_questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED NOT NULL,
    question_text TEXT NOT NULL,
    explanation TEXT DEFAULT NULL,
    difficulty ENUM('Easy', 'Medium', 'Hard') NOT NULL DEFAULT 'Medium',
    sort_order INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_quiz_questions_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

CREATE TABLE quiz_choices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id INT UNSIGNED NOT NULL,
    choice_text VARCHAR(255) NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_quiz_choices_question FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
);

CREATE TABLE quiz_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    status ENUM('ongoing', 'completed', 'unfinished') NOT NULL DEFAULT 'ongoing',
    score INT NOT NULL DEFAULT 0,
    total_questions INT NOT NULL DEFAULT 0,
    percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
    result ENUM('Passed', 'Failed') DEFAULT NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_quiz_attempts_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    CONSTRAINT fk_quiz_attempts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE quiz_answers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    selected_choice_id INT UNSIGNED DEFAULT NULL,
    question_text_snapshot TEXT DEFAULT NULL,
    explanation_snapshot TEXT DEFAULT NULL,
    difficulty_snapshot VARCHAR(20) DEFAULT NULL,
    choices_snapshot JSON DEFAULT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    answered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_quiz_answers_attempt FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
    CONSTRAINT fk_quiz_answers_question FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
    CONSTRAINT fk_quiz_answers_choice FOREIGN KEY (selected_choice_id) REFERENCES quiz_choices(id) ON DELETE SET NULL
);

CREATE TABLE uploaded_files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    quiz_id INT UNSIGNED DEFAULT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(20) NOT NULL,
    file_size BIGINT NOT NULL,
    extracted_text LONGTEXT DEFAULT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_uploaded_files_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_uploaded_files_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE SET NULL
);

CREATE TABLE activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(80) NOT NULL,
    description TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE quiz_statistics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED NOT NULL UNIQUE,
    total_attempts INT NOT NULL DEFAULT 0,
    ongoing_attempts INT NOT NULL DEFAULT 0,
    completed_attempts INT NOT NULL DEFAULT 0,
    average_score DECIMAL(5,2) NOT NULL DEFAULT 0,
    passing_count INT NOT NULL DEFAULT 0,
    failing_count INT NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_quiz_statistics_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

CREATE TABLE realtime_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED DEFAULT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    event_name VARCHAR(100) NOT NULL,
    event_data JSON DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_realtime_logs_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE SET NULL,
    CONSTRAINT fk_realtime_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_quizzes_user_id ON quizzes(user_id);
CREATE INDEX idx_quizzes_share_code ON quizzes(share_code);
CREATE INDEX idx_quizzes_date_deleted ON quizzes(date_deleted);
CREATE INDEX idx_questions_quiz_id ON quiz_questions(quiz_id);
CREATE INDEX idx_attempts_quiz_id ON quiz_attempts(quiz_id);
CREATE INDEX idx_attempts_user_id ON quiz_attempts(user_id);
CREATE INDEX idx_activity_logs_user_id ON activity_logs(user_id);

INSERT INTO users (full_name, email, password_hash, role, status, created_at, updated_at)
VALUES (
    'Questra Administrator',
    'admin@questra.local',
    '$2y$12$QXPjJrJd.3oeoQTCNx8jr.h9AtfCsPL04UdIXcSz28ubglGg3wsfK',
    'admin',
    'active',
    NOW(),
    NOW()
);

DELIMITER $$

CREATE PROCEDURE sp_create_quiz(
    IN p_user_id INT,
    IN p_title VARCHAR(180),
    IN p_topic VARCHAR(255),
    IN p_difficulty VARCHAR(10),
    IN p_question_count INT,
    IN p_timer_per_question INT,
    IN p_passing_rate INT,
    IN p_share_code VARCHAR(32)
)
BEGIN
    INSERT INTO quizzes (
        user_id, title, topic, difficulty, question_count, timer_per_question, passing_rate, status, share_code, created_at, updated_at
    ) VALUES (
        p_user_id, p_title, p_topic, p_difficulty, p_question_count, p_timer_per_question, p_passing_rate, 'active', p_share_code, NOW(), NOW()
    );

    SELECT LAST_INSERT_ID() AS quiz_id;
END $$

CREATE PROCEDURE sp_save_quiz_attempt(
    IN p_quiz_id INT,
    IN p_user_id INT
)
BEGIN
    DECLARE v_total_questions INT DEFAULT 0;
    DECLARE v_question_limit INT DEFAULT 0;

    SELECT question_count INTO v_question_limit
    FROM quizzes
    WHERE id = p_quiz_id
    LIMIT 1;

    SELECT COUNT(*) INTO v_total_questions
    FROM (
        SELECT id
        FROM quiz_questions
        WHERE quiz_id = p_quiz_id
        ORDER BY sort_order ASC, id ASC
        LIMIT v_question_limit
    ) AS limited_questions;

    INSERT INTO quiz_attempts (quiz_id, user_id, status, score, total_questions, percentage, started_at)
    VALUES (p_quiz_id, p_user_id, 'ongoing', 0, v_total_questions, 0, NOW());

    SELECT LAST_INSERT_ID() AS attempt_id;
END $$

CREATE PROCEDURE sp_compute_quiz_score(IN p_attempt_id INT)
BEGIN
    DECLARE v_score INT DEFAULT 0;
    DECLARE v_total INT DEFAULT 0;
    DECLARE v_rate INT DEFAULT 0;
    DECLARE v_percentage DECIMAL(5,2) DEFAULT 0;
    DECLARE v_quiz_id INT DEFAULT 0;

    SELECT qa.quiz_id, SUM(CASE WHEN ans.is_correct = 1 THEN 1 ELSE 0 END), qa.total_questions
    INTO v_quiz_id, v_score, v_total
    FROM quiz_attempts qa
    LEFT JOIN quiz_answers ans ON ans.attempt_id = qa.id
    WHERE qa.id = p_attempt_id
    GROUP BY qa.id;

    SELECT passing_rate INTO v_rate FROM quizzes WHERE id = v_quiz_id;

    IF v_total > 0 THEN
        SET v_percentage = (v_score / v_total) * 100;
    END IF;

    UPDATE quiz_attempts
    SET score = v_score,
        percentage = v_percentage,
        status = 'completed',
        result = IF(v_percentage >= v_rate, 'Passed', 'Failed'),
        completed_at = NOW()
    WHERE id = p_attempt_id;

    SELECT score, total_questions, percentage, result FROM quiz_attempts WHERE id = p_attempt_id;
END $$

CREATE PROCEDURE sp_get_user_dashboard_summary(IN p_user_id INT)
BEGIN
    SELECT
        (SELECT COUNT(*) FROM quizzes WHERE user_id = p_user_id) AS total_quizzes_created,
        (SELECT COUNT(*) FROM quiz_attempts WHERE user_id = p_user_id) AS total_quizzes_taken,
        (SELECT COUNT(*) FROM quiz_attempts qa INNER JOIN quizzes q ON q.id = qa.quiz_id WHERE q.user_id = p_user_id) AS total_quiz_takers,
        (SELECT COUNT(*) FROM quiz_attempts qa INNER JOIN quizzes q ON q.id = qa.quiz_id WHERE q.user_id = p_user_id AND qa.status = 'ongoing') AS ongoing_attempts,
        (SELECT COUNT(*) FROM quiz_attempts qa INNER JOIN quizzes q ON q.id = qa.quiz_id WHERE q.user_id = p_user_id AND qa.status = 'completed') AS completed_attempts,
        COALESCE((SELECT AVG(qa.percentage) FROM quiz_attempts qa INNER JOIN quizzes q ON q.id = qa.quiz_id WHERE q.user_id = p_user_id AND qa.status = 'completed'), 0) AS average_score;
END $$

CREATE PROCEDURE sp_get_admin_dashboard_summary()
BEGIN
    SELECT
        (SELECT COUNT(*) FROM users) AS total_users,
        (SELECT COUNT(*) FROM quizzes) AS total_quizzes,
        (SELECT COUNT(*) FROM quiz_attempts) AS total_attempts,
        (SELECT COUNT(*) FROM quizzes WHERE status = 'active') AS active_quizzes;
END $$

CREATE PROCEDURE sp_get_quiz_report_data(IN p_quiz_id INT)
BEGIN
    SELECT
        q.title AS quiz_title,
        u.full_name AS quiz_creator,
        taker.full_name AS taker_name,
        qa.score,
        qa.total_questions,
        qa.percentage,
        qa.result,
        qa.started_at,
        qa.completed_at
    FROM quiz_attempts qa
    INNER JOIN quizzes q ON q.id = qa.quiz_id
    INNER JOIN users u ON u.id = q.user_id
    INNER JOIN users taker ON taker.id = qa.user_id
    WHERE qa.quiz_id = p_quiz_id
    ORDER BY qa.started_at DESC;
END $$

CREATE TRIGGER trg_after_user_registration
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, description, created_at)
    VALUES (NEW.id, 'user_registered', CONCAT('New user registered: ', NEW.email), NOW());
END $$

CREATE TRIGGER trg_after_quiz_creation
AFTER INSERT ON quizzes
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, description, created_at)
    VALUES (NEW.user_id, 'quiz_created', CONCAT('Quiz created: ', NEW.title), NOW());

    INSERT INTO quiz_statistics (quiz_id, total_attempts, ongoing_attempts, completed_attempts, average_score, passing_count, failing_count, updated_at)
    VALUES (NEW.id, 0, 0, 0, 0, 0, 0, NOW());
END $$

CREATE TRIGGER trg_before_quiz_attempt_insert_validate_status
BEFORE INSERT ON quiz_attempts
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(10);
    SELECT status INTO v_status FROM quizzes WHERE id = NEW.quiz_id LIMIT 1;

    IF v_status IS NULL OR v_status <> 'active' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Quiz is not active.';
    END IF;
END $$

CREATE TRIGGER trg_after_quiz_attempt_start
AFTER INSERT ON quiz_attempts
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, description, created_at)
    VALUES (NEW.user_id, 'quiz_attempt_started', CONCAT('Attempt started for quiz #', NEW.quiz_id), NOW());

    UPDATE quiz_statistics
    SET total_attempts = total_attempts + 1,
        ongoing_attempts = ongoing_attempts + 1,
        updated_at = NOW()
    WHERE quiz_id = NEW.quiz_id;
END $$

CREATE TRIGGER trg_after_quiz_attempt_completion
AFTER UPDATE ON quiz_attempts
FOR EACH ROW
BEGIN
    IF OLD.status <> 'completed' AND NEW.status = 'completed' THEN
        INSERT INTO activity_logs (user_id, action, description, created_at)
        VALUES (NEW.user_id, 'quiz_attempt_completed', CONCAT('Attempt completed for quiz #', NEW.quiz_id), NOW());

        UPDATE quiz_statistics
        SET ongoing_attempts = GREATEST(ongoing_attempts - 1, 0),
            completed_attempts = completed_attempts + 1,
            average_score = (
                SELECT COALESCE(AVG(percentage), 0)
                FROM quiz_attempts
                WHERE quiz_id = NEW.quiz_id AND status = 'completed'
            ),
            passing_count = (
                SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = NEW.quiz_id AND result = 'Passed'
            ),
            failing_count = (
                SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = NEW.quiz_id AND result = 'Failed'
            ),
            updated_at = NOW()
        WHERE quiz_id = NEW.quiz_id;
    ELSEIF OLD.status = 'ongoing' AND NEW.status = 'unfinished' THEN
        INSERT INTO activity_logs (user_id, action, description, created_at)
        VALUES (NEW.user_id, 'quiz_attempt_unfinished', CONCAT('Attempt unfinished for quiz #', NEW.quiz_id), NOW());

        UPDATE quiz_statistics
        SET ongoing_attempts = GREATEST(ongoing_attempts - 1, 0),
            updated_at = NOW()
        WHERE quiz_id = NEW.quiz_id;
    END IF;
END $$

CREATE TRIGGER trg_after_quiz_delete_log
AFTER DELETE ON quizzes
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, description, created_at)
    VALUES (OLD.user_id, 'quiz_deleted', CONCAT('Deleted quiz: ', OLD.title), NOW());
END $$

DELIMITER ;
