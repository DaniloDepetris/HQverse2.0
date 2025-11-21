<?php
require_once 'includes_auth.php';

if(!$auth->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

if($_POST && isset($_POST['user_id']) && isset($_POST['action'])) {
    $target_user_id = $_POST['user_id'];
    $action = $_POST['action'];
    $current_user_id = $_SESSION['user_id'];
    
    header('Content-Type: application/json');
    
    if($action === 'follow') {
        $result = $auth->followUser($current_user_id, $target_user_id);
        if($result === true) {
            echo json_encode(['success' => true, 'message' => 'Usuário seguido com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => $result]);
        }
    } elseif($action === 'unfollow') {
        $result = $auth->unfollowUser($current_user_id, $target_user_id);
        if($result === true) {
            echo json_encode(['success' => true, 'message' => 'Deixou de seguir o usuário!']);
        } else {
            echo json_encode(['success' => false, 'message' => $result]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Dados insuficientes']);
}
?>
