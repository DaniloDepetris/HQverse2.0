<?php
require_once 'includes_auth.php';
header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não logado']);
    exit();
}

$comicId = $_GET['comic_id'] ?? 0;

if (!$comicId) {
    echo json_encode(['success' => false, 'message' => 'ID do quadrinho não especificado']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Buscar estatísticas gerais
    $statsQuery = "SELECT 
                    COUNT(*) as total_ratings,
                    AVG(rating) as average_rating
                   FROM comic_ratings 
                   WHERE comic_id = :comic_id";
    $statsStmt = $conn->prepare($statsQuery);
    $statsStmt->bindParam(':comic_id', $comicId);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Buscar avaliação do usuário atual
    $userQuery = "SELECT rating FROM comic_ratings WHERE user_id = :user_id AND comic_id = :comic_id";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bindParam(':user_id', $_SESSION['user_id']);
    $userStmt->bindParam(':comic_id', $comicId);
    $userStmt->execute();
    $userRating = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'total_ratings' => $stats['total_ratings'] ?? 0,
        'average_rating' => $stats['average_rating'] ? round($stats['average_rating'], 1) : 0,
        'user_rating' => $userRating['rating'] ?? null
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no servidor']);
}
?>
