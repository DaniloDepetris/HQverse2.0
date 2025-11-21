<?php
require_once 'includes_auth.php';
header('Content-Type: application/json');

if (!isset($_SESSION)) session_start();

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não logado']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$commentId = $input['commentId'] ?? 0;
$action = $input['action'] ?? 'like'; // 'like' ou 'unlike'

if (!$commentId) {
    echo json_encode(['success' => false, 'message' => 'ID do comentário não especificado']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    $userId = $_SESSION['user_id'];

    if ($action === 'like') {
        // Verificar se já curtiu
        $checkQuery = "SELECT id FROM comment_likes WHERE user_id = :user_id AND comment_id = :comment_id";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(':user_id', $userId);
        $checkStmt->bindParam(':comment_id', $commentId);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Você já curtiu este comentário']);
            exit();
        }

        // Adicionar like
        $query = "INSERT INTO comment_likes (user_id, comment_id) VALUES (:user_id, :comment_id)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':comment_id', $commentId);
        
        if ($stmt->execute()) {
            // Buscar contagem atualizada
            $countQuery = "SELECT COUNT(*) as like_count FROM comment_likes WHERE comment_id = :comment_id";
            $countStmt = $conn->prepare($countQuery);
            $countStmt->bindParam(':comment_id', $commentId);
            $countStmt->execute();
            $likeCount = $countStmt->fetch(PDO::FETCH_ASSOC)['like_count'];

            echo json_encode([
                'success' => true, 
                'message' => 'Comentário curtido!',
                'likeCount' => $likeCount,
                'liked' => true
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao curtir comentário']);
        }

    } elseif ($action === 'unlike') {
        // Remover like
        $query = "DELETE FROM comment_likes WHERE user_id = :user_id AND comment_id = :comment_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':comment_id', $commentId);
        
        if ($stmt->execute()) {
            // Buscar contagem atualizada
            $countQuery = "SELECT COUNT(*) as like_count FROM comment_likes WHERE comment_id = :comment_id";
            $countStmt = $conn->prepare($countQuery);
            $countStmt->bindParam(':comment_id', $commentId);
            $countStmt->execute();
            $likeCount = $countStmt->fetch(PDO::FETCH_ASSOC)['like_count'];

            echo json_encode([
                'success' => true, 
                'message' => 'Like removido!',
                'likeCount' => $likeCount,
                'liked' => false
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao remover like']);
        }
    }

} catch (PDOException $e) {
    error_log("Erro no sistema de likes: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
?>
