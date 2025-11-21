<?php
require_once 'includes_auth.php';
header('Content-Type: application/json');

if (!isset($_SESSION)) session_start();

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não logado']);
    exit();
}

// Ler dados JSON do corpo da requisição
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

$comicId = $input['comicId'] ?? 0;
$comment = trim($input['comment'] ?? '');
$containsSpoilers = $input['containsSpoilers'] ?? false;
$parentCommentId = $input['parentCommentId'] ?? null;

if (!$comicId || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

if (strlen($comment) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Comentário muito longo (máximo 1000 caracteres)']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($parentCommentId) {
        // É uma resposta
        $query = "INSERT INTO comic_comments (user_id, comic_id, comment, contains_spoilers, parent_comment_id) 
                  VALUES (:user_id, :comic_id, :comment, :contains_spoilers, :parent_comment_id)";
    } else {
        // É um comentário principal
        $query = "INSERT INTO comic_comments (user_id, comic_id, comment, contains_spoilers) 
                  VALUES (:user_id, :comic_id, :comment, :contains_spoilers)";
    }
    
    $stmt = $conn->prepare($query);
    $userId = $_SESSION['user_id'];
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':comic_id', $comicId);
    $stmt->bindParam(':comment', $comment);
    $stmt->bindParam(':contains_spoilers', $containsSpoilers, PDO::PARAM_BOOL);
    
    if ($parentCommentId) {
        $stmt->bindParam(':parent_comment_id', $parentCommentId);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Comentário enviado com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar comentário']);
    }
    
} catch (PDOException $e) {
    error_log("Erro ao salvar comentário: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
?>
