get_comic_progress<?php
require_once 'includes_auth.php';
header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não logado']);
    exit();
}

$comic_id = $_GET['comic_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if (!$comic_id) {
    echo json_encode(['success' => false, 'message' => 'ID do quadrinho não especificado']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "SELECT progress_pct, last_read_at FROM user_progress WHERE user_id = :user_id AND comic_id = :comic_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':comic_id', $comic_id);
    $stmt->execute();
    
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'progress' => $progress ?: ['progress_pct' => 0, 'last_read_at' => null]
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar progresso: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar progresso'
    ]);
}
?>
