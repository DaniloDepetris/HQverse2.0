<?php

// Aumentar MUITO os limites para arquivos grandes
ini_set('memory_limit', '2048M');  // Aumentei para 2GB
ini_set('post_max_size', '1000M'); // Aumentei para 1GB  
ini_set('upload_max_filesize', '1000M'); // Aumentei para 1GB
ini_set('max_execution_time', 600); // 10 minutos
ini_set('max_input_time', 600); // 10 minutos
ini_set('max_file_uploads', 500); // Permite mais arquivos

error_log("=== PROCESS_PDF_UPLOAD.PHP ACESSADO ===");

require_once 'includes_auth.php';

// Configurar para JSON
header('Content-Type: application/json');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

// Verificar se está logado
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

// Verificar se é criador
if(!$auth->isCreator($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Apenas criadores podem publicar quadrinhos']);
    exit();
}

try {
    error_log("Iniciando processamento de PDF...");
    
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categories = $_POST['categories'] ?? [];
    $is_premium = isset($_POST['is_premium']) ? 1 : 0;
    $price = $is_premium ? floatval($_POST['price'] ?? 0) : 0;
    
    error_log("Dados recebidos - Título: $title, Categorias: " . count($categories));
    error_log("Files recebidos - Cover: " . ($_FILES['cover']['name'] ?? 'N/A') . ", Pages: " . (isset($_FILES['pages']) ? count($_FILES['pages']['name']) : 0));

    // Validar dados
    if (empty($title)) {
        throw new Exception('O título é obrigatório!');
    }
    
    if (empty($description)) {
        throw new Exception('A descrição é obrigatória!');
    }
    
    if (empty($categories)) {
        throw new Exception('Selecione pelo menos uma categoria!');
    }
    
    if ($is_premium && $price <= 0) {
        throw new Exception('Para quadrinhos premium, o preço deve ser maior que zero!');
    }
    
    // Processar upload da capa
    $cover_path = null;
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $cover_result = $auth->uploadComicCover($_FILES['cover'], $_SESSION['user_id']);
        if ($cover_result['success']) {
            $cover_path = $cover_result['path'];
            error_log("Capa uploadada com sucesso: $cover_path");
        } else {
            throw new Exception($cover_result['message']);
        }
    } else {
        throw new Exception('A capa é obrigatória!');
    }
    
    // DEBUG: Verificar se os métodos existem
    if (!method_exists($auth, 'createComic')) {
        throw new Exception('Método createComic não existe na classe Auth');
    }
    
    // Criar o quadrinho
    error_log("Chamando createComic...");
    $result = $auth->createComic([
        'title' => $title,
        'author_id' => $_SESSION['user_id'],
        'description' => $description,
        'cover' => $cover_path,
        'is_premium' => $is_premium,
        'price' => $price,
        'categories' => $categories,
        'page_count' => 0
    ]);
    
    error_log("Resultado createComic: " . print_r($result, true));
    
    if (!$result['success']) {
        throw new Exception($result['message']);
    }
    
    $comic_id = $result['comic_id'];
    error_log("Quadrinho criado com ID: $comic_id");
    
    // Processar upload das páginas convertidas
    if (isset($_FILES['pages']) && !empty($_FILES['pages']['name'][0])) {
        error_log("Processando páginas convertidas...");
        
        if (!method_exists($auth, 'uploadComicPages')) {
            throw new Exception('Método uploadComicPages não existe na classe Auth');
        }
        
        $pages_result = $auth->uploadComicPages($_FILES['pages'], $comic_id, $_SESSION['user_id']);
        
        if ($pages_result['success']) {
            // Atualizar contagem de páginas
            if (method_exists($auth, 'updateComicPageCount')) {
                $auth->updateComicPageCount($comic_id, $pages_result['page_count']);
            }
            error_log("Páginas processadas com sucesso: " . $pages_result['page_count']);
            
            echo json_encode([
                'success' => true, 
                'message' => $result['message'] . ' ' . $pages_result['message'],
                'comic_id' => $comic_id
            ]);
            exit();
        } else {
            throw new Exception($result['message'] . ' ' . $pages_result['message']);
        }
    } else {
        throw new Exception($result['message'] . ' Nenhuma página foi recebida.');
    }
    
} catch (Exception $e) {
    error_log("Erro no processamento PDF: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
    exit();
}
