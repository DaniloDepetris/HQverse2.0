<?php
require_once 'includes_auth.php';

header('Content-Type: application/json');

if(!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

if($_POST && isset($_POST['conversation_id']) && isset($_POST['message_content'])) {
    $conversation_id = intval($_POST['conversation_id']);
    $content = trim($_POST['message_content']);
    $user_id = $_SESSION['user_id'];
    
    // Verificar se usuário pode acessar a conversa
    if($auth->canAccessConversation($conversation_id, $user_id)) {
        $result = $auth->sendMessage($conversation_id, $user_id, $content);
        if($result === true) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $result]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
}
?>
