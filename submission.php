<?php
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/classes/App.php';
require_once __DIR__ . '/classes/Security.php';
require_once __DIR__ . '/classes/Auth.php';
require_once __DIR__ . '/classes/AdminService.php';
require_once __DIR__ . '/classes/GeminiService.php';
require_once __DIR__ . '/classes/PusherService.php';
require_once __DIR__ . '/classes/QuizService.php';
require_once __DIR__ . '/classes/tools.php';

// ==================== INITIALIZATION ====================
$app = new App($pdo);
$security = new Security($app);
$auth = new Auth($pdo, $app, $security);
$adminService = new AdminService($pdo, $app, $auth, $security);
$gemini = new GeminiService();
$pusherService = new PusherService($pdo);
$quizService = new QuizService($pdo, $app, $auth, $gemini, $pusherService);
$tools = new Tools($connection, $app);

// ==================== SUBMISSION HANDLERS ====================
$submissionFiles = glob(__DIR__ . '/submissions/*.php') ?: [];
sort($submissionFiles);

foreach ($submissionFiles as $submissionFile) {
    require $submissionFile;
}
