<?php
require_once 'includes_auth.php';

if(!$auth->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['avatar'];
    
    // Validar arquivo
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if(!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Tipo de arquivo não suportado. Use JPG, PNG, GIF ou WebP.']);
        exit();
    }
    
    if($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'Arquivo muito grande. Máximo 5MB.']);
        exit();
    }
    
    if($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Erro no upload do arquivo.']);
        exit();
    }
    
    // Criar diretório de avatares se não existir
    $upload_dir = 'uploads/avatars/';
    if(!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Gerar nome único para o arquivo
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_name = 'avatar_' . $user_id . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $file_name;
    
    // Mover arquivo
    if(move_uploaded_file($file['tmp_name'], $file_path)) {
        // Atualizar no banco de dados
        $result = $auth->updateAvatar($user_id, $file_path, $file['name'], $file['size'], $file['type']);
        
        if($result) {
            echo json_encode(['success' => true, 'message' => 'Avatar atualizado com sucesso!', 'avatar_path' => $file_path]);
        } else {
            // Se falhou no BD, remove o arquivo
            unlink($file_path);
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar avatar no banco de dados.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar arquivo.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado.']);
}
?>
