<?php
require_once 'includes_auth.php';

if(!$auth->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

if(isset($_GET['q']) && !empty($_GET['q'])) {
    $search_term = trim($_GET['q']);
    $users = $auth->searchUsersWithFollow($search_term, $_SESSION['user_id']);
    
    header('Content-Type: application/json');
    echo json_encode($users);
} else {
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>
