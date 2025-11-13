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
    $userId = $_SESSION['user_id'];
    
    $query = "SELECT 
                cc.*,
                u.username,
                u.avatar,
                COUNT(DISTINCT cl.id) as like_count,
                EXISTS(
                    SELECT 1 FROM comment_likes 
                    WHERE comment_id = cc.id AND user_id = :user_id
                ) as user_liked
              FROM comic_comments cc
              JOIN users u ON cc.user_id = u.id
              LEFT JOIN comment_likes cl ON cc.id = cl.comment_id
              WHERE cc.comic_id = :comic_id 
                AND cc.is_approved = TRUE
              GROUP BY cc.id
              ORDER BY 
                COALESCE(cc.parent_comment_id, cc.id) ASC,
                cc.created_at ASC
              LIMIT 100";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':comic_id', $comicId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'comments' => $comments
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar comentários: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro no servidor: ' . $e->getMessage(),
        'comments' => []
    ]);
}
?>
