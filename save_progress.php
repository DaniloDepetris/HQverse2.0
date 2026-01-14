<?php
require_once 'includes_auth.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION)) session_start();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'not_authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$comicId = isset($input['comicId']) ? (int)$input['comicId'] : 0;
$progress = isset($input['progress']) ? (int)$input['progress'] : 0;

if ($comicId <= 0 || $progress < 0 || $progress > 100) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_parameters']);
    exit;
}

// Configure DB connection - update these credentials if needed
$dsn = 'mysql:host=127.0.0.1;dbname=hqsql;charset=utf8mb4';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // upsert progress
    $sql = "INSERT INTO user_progress (user_id, comic_id, progress_pct) VALUES (:user, :comic, :progress)
            ON DUPLICATE KEY UPDATE progress_pct = :progress, last_read_at = NOW()";
    $stmt = $pdo->prepare($sql);
    $userId = $_SESSION['user_id'];
    $stmt->execute([':user' => $userId, ':comic' => $comicId, ':progress' => $progress]);

    echo json_encode(['ok' => true, 'comicId' => $comicId, 'progress' => $progress]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db_error', 'msg' => $e->getMessage()]);
    exit;
}
