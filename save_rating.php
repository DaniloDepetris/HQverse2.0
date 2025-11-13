<?php
require_once 'includes_auth.php';
header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não logado']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$comicId = $input['comicId'] ?? 0;
$rating = $input['rating'] ?? 0;

if (!$comicId || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "INSERT INTO comic_ratings (user_id, comic_id, rating) 
              VALUES (:user_id, :comic_id, :rating)
              ON DUPLICATE KEY UPDATE rating = :rating, updated_at = NOW()";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':comic_id', $comicId);
    $stmt->bindParam(':rating', $rating);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar avaliação']);
    }
    
} catch (PDOException $e) {
    error_log("Erro ao salvar avaliação: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no servidor']);
}
?>
