<?php
require_once 'includes_auth.php';

if(!$auth->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

$type = $_GET['type'] ?? 'followers';
$user_id = $_GET['user_id'] ?? $_SESSION['user_id'];

header('Content-Type: application/json');

if($type === 'followers') {
    $followers = $auth->getFollowers($user_id);
    echo json_encode($followers);
} elseif($type === 'following') {
    $following = $auth->getFollowing($user_id);
    echo json_encode($following);
} else {
    echo json_encode([]);
}
?>
