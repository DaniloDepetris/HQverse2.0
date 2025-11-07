<?php
require_once 'includes_auth.php';

if(!$auth->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

if(isset($_GET['q']) && !empty($_GET['q'])) {
    $search_term = $_GET['q'];
    $current_user_id = $_SESSION['user_id'];
    
    $users = $auth->searchUsersWithFollow($search_term, $current_user_id);
    
    header('Content-Type: application/json');
    echo json_encode($users);
} else {
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>
