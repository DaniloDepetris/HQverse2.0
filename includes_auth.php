<?php
session_start();
require_once 'includes_auth.php';

if(!$auth->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

// Debug: verificar se está recebendo o arquivo
error_log("Upload iniciado - User ID: " . $_SESSION['user_id']);

$response = ['success' => false, 'message' => ''];

if($_FILES && isset($_FILES['avatar'])) {
    $user_id = $_SESSION['user_id'];
    $avatar = $_FILES['avatar'];
    
    error_log("Arquivo recebido: " . $avatar['name'] . " - Tamanho: " . $avatar['size'] . " - Erro: " . $avatar['error']);
    
    // Configurações do upload
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    $upload_dir = 'uploads/avatars/';
    
    // Criar diretório se não existir
    if(!is_dir($upload_dir)) {
        if(!mkdir($upload_dir, 0755, true)) {
            $response['message'] = 'Erro ao criar diretório de upload.';
            error_log("Erro ao criar diretório: " . $upload_dir);
        }
    }
    
    // Verificar permissões do diretório
    if(!is_writable($upload_dir)) {
        $response['message'] = 'Diretório sem permissão de escrita.';
        error_log("Diretório sem permissão: " . $upload_dir);
    }
    
    // Validar arquivo
    if($avatar['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'Arquivo muito grande (configuração do servidor)',
            UPLOAD_ERR_FORM_SIZE => 'Arquivo muito grande (formulário)',
            UPLOAD_ERR_PARTIAL => 'Upload parcialmente feito',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não existe',
            UPLOAD_ERR_CANT_WRITE => 'Erro ao escrever no disco',
            UPLOAD_ERR_EXTENSION => 'Extensão não permitida'
        ];
        $response['message'] = $error_messages[$avatar['error']] ?? 'Erro desconhecido no upload';
        error_log("Erro upload: " . $avatar['error'] . " - " . $response['message']);
        
    } elseif(!in_array($avatar['type'], $allowed_types)) {
        $response['message'] = 'Tipo de arquivo não permitido. Use JPEG, PNG, GIF ou WebP. Recebido: ' . $avatar['type'];
        error_log("Tipo não permitido: " . $avatar['type']);
        
    } elseif($avatar['size'] > $max_size) {
        $response['message'] = 'Arquivo muito grande. Máximo 5MB. Tamanho: ' . round($avatar['size'] / 1024 / 1024, 2) . 'MB';
        error_log("Arquivo grande: " . $avatar['size']);
        
    } else {
        // Gerar nome
        // para o arquivo
        $file_extension = strtolower(pathinfo($avatar['name'], PATHINFO_EXTENSION));
        $file_name = 'avatar_' . $user_id . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        error_log("Tentando mover para: " . $file_path);
        
        // Mover arquivo
        if(move_uploaded_file($avatar['tmp_name'], $file_path)) {
            error_log("Arquivo movido com sucesso: " . $file_path);
            
            // Verificar se arquivo foi criado
            if(!file_exists($file_path)) {
                $response['message'] = 'Arquivo não foi criado após upload.';
                error_log("Arquivo não existe após move_uploaded_file: " . $file_path);
            } else {
                // Atualizar banco de dados
                $result = $auth->updateAvatar($user_id, $file_path, $avatar['name'], $avatar['size'], $avatar['type']);
                
                if($result) {
                    $response['success'] = true;
                    $response['message'] = 'Foto de perfil atualizada com sucesso!';
                    $response['avatar_url'] = $file_path;
                    error_log("Avatar atualizado no banco com sucesso");
                } else {
                    $response['message'] = 'Erro ao atualizar banco de dados.';
                    // Remover arquivo em caso de erro
                    if(file_exists($file_path)) {
                        unlink($file_path);
                    }
                    error_log("Erro ao atualizar banco");
                }
            }
        } else {
            $response['message'] = 'Erro ao salvar arquivo no servidor.';
            error_log("Erro no move_uploaded_file. tmp_name: " . $avatar['tmp_name'] . " -> " . $file_path);
            
            // Verificar se arquivo temporário existe
            if(!file_exists($avatar['tmp_name'])) {
                error_log("Arquivo temporário não existe: " . $avatar['tmp_name']);
            }
        }
    }
} else {
    $response['message'] = 'Nenhum arquivo enviado.';
    error_log("Nenhum arquivo no _FILES");
}

error_log("Resposta final: " . json_encode($response));
header('Content-Type: application/json');
echo json_encode($response);
?>
