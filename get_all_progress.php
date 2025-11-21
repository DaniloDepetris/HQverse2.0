<?php
require_once 'includes_auth.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION)) session_start();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'not_authenticated']);
    exit;
}

$dsn = 'mysql:host=127.0.0.1;dbname=hqsql;charset=utf8mb4';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $userId = $_SESSION['user_id'];
    
    // ⭐ ALTERAÇÃO AQUI - ADICIONAR last_read_at
    $stmt = $pdo->prepare('SELECT comic_id, progress_pct, last_read_at FROM user_progress WHERE user_id = :user');
    $stmt->execute([':user' => $userId]);
    $rows = $stmt->fetchAll();

    echo json_encode(['progresses' => $rows]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db_error', 'msg' => $e->getMessage()]);
    exit;
}