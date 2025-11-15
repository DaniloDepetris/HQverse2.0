<?php

ini_set('memory_limit', '2048M');  // Aumentei para 2GB
ini_set('post_max_size', '1000M'); // Aumentei para 1GB  
ini_set('upload_max_filesize', '1000M'); // Aumentei para 1GB
ini_set('max_execution_time', 6000); // 10 minutos
ini_set('max_input_time', 6000); // 10 minutos
ini_set('max_file_uploads', 500); // Permite mais arquivos


require_once 'includes_auth.php';

// ============ PROCESSAMENTO DE PDF CONVERTIDO ============
if(isset($_POST['processed_pdf']) && $_POST['processed_pdf'] == '1') {
    error_log("=== INICIANDO PROCESSAMENTO PDF ===");
    error_log("Dados POST: " . print_r($_POST, true));
    error_log("Files recebidos: " . print_r($_FILES, true));
    
    // Configurar para JSON
    header('Content-Type: application/json');
    
    try {
        if(!$auth->isLoggedIn()) {
            throw new Exception('Não autorizado');
        }

        if(!$auth->isCreator($_SESSION['user_id'])) {
            throw new Exception('Apenas criadores podem publicar quadrinhos');
        }
        
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categories = $_POST['categories'] ?? [];
        $is_premium = isset($_POST['is_premium']) ? 1 : 0;
        $price = $is_premium ? floatval($_POST['price'] ?? 0) : 0;
        
        error_log("Validando dados: Título=$title, Categorias=" . count($categories));
        
        // VALIDAÇÕES BÁSICAS
        if(empty($title)) throw new Exception('O título é obrigatório!');
        if(empty($description)) throw new Exception('A descrição é obrigatória!');
        if(empty($categories)) throw new Exception('Selecione pelo menos uma categoria!');
        if($is_premium && $price <= 0) throw new Exception('Para quadrinhos premium, o preço deve ser maior que zero!');
        
        // PROCESSAR CAPA
        $cover_path = null;
        if(isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            error_log("Processando capa...");
            $cover_result = $auth->uploadComicCover($_FILES['cover'], $_SESSION['user_id']);
            if($cover_result['success']) {
                $cover_path = $cover_result['path'];
                error_log("Capa uploadada: $cover_path");
            } else {
                throw new Exception($cover_result['message']);
            }
        } else {
            throw new Exception('A capa é obrigatória!');
        }
        
        // CRIAR QUADRINHO
        error_log("Criando quadrinho no banco...");
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
        
        if(!$result['success']) {
            throw new Exception($result['message']);
        }
        
        $comic_id = $result['comic_id'];
        error_log("Quadrinho criado com ID: $comic_id");
        
        // PROCESSAR PÁGINAS
        if(isset($_FILES['pages']) && !empty($_FILES['pages']['name'][0])) {
            error_log("Processando " . count($_FILES['pages']['name']) . " páginas...");
            
            // Ordenar páginas numericamente
            $pages = [];
            foreach($_FILES['pages']['name'] as $index => $name) {
                $pages[] = [
                    'name' => $name,
                    'tmp_name' => $_FILES['pages']['tmp_name'][$index],
                    'type' => $_FILES['pages']['type'][$index],
                    'size' => $_FILES['pages']['size'][$index],
                    'error' => $_FILES['pages']['error'][$index]
                ];
            }
            
            // Ordenar páginas pelo número
            usort($pages, function($a, $b) {
                preg_match('/page_(\d+)\./', $a['name'], $matchesA);
                preg_match('/page_(\d+)\./', $b['name'], $matchesB);
                $numA = isset($matchesA[1]) ? (int)$matchesA[1] : 0;
                $numB = isset($matchesB[1]) ? (int)$matchesB[1] : 0;
                return $numA - $numB;
            });
            
            // Reconstruir $_FILES['pages'] na ordem correta
            $orderedFiles = [
                'name' => [],
                'type' => [],
                'tmp_name' => [],
                'error' => [],
                'size' => []
            ];
            
            foreach($pages as $page) {
                $orderedFiles['name'][] = $page['name'];
                $orderedFiles['type'][] = $page['type'];
                $orderedFiles['tmp_name'][] = $page['tmp_name'];
                $orderedFiles['error'][] = $page['error'];
                $orderedFiles['size'][] = $page['size'];
            }
            
            $_FILES['pages'] = $orderedFiles;
            
            $pages_result = $auth->uploadComicPages($_FILES['pages'], $comic_id, $_SESSION['user_id']);
            
            if($pages_result['success']) {
                // Atualizar contagem de páginas
                $auth->updateComicPageCount($comic_id, $pages_result['page_count']);
                error_log("Páginas processadas: " . $pages_result['page_count']);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Quadrinho criado com sucesso! ' . $pages_result['page_count'] . ' página(s) carregada(s)!',
                    'comic_id' => $comic_id
                ]);
                exit();
            } else {
                throw new Exception('Erro nas páginas: ' . $pages_result['message']);
            }
        } else {
            throw new Exception('Nenhuma página foi recebida!');
        }
        
    } catch (Exception $e) {
        error_log("ERRO NO PROCESSAMENTO PDF: " . $e->getMessage());
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
        exit();
    }
}
// ============ FIM DO PROCESSAMENTO DE PDF ============

// Processamento normal do formulário (não-PDF)
if($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['processed_pdf'])) {
    if(!$auth->isLoggedIn()) {
        header("Location: login.php");
        exit();
    }

    // Verificar se o usuário é criador
    if(!$auth->isCreator($_SESSION['user_id'])) {
        header("Location: perfil.php");
        exit();
    }

    $success = '';
    $error = '';

    // Processar adição de nova categoria
    if(isset($_POST['add_category'])) {
        $category_name = trim($_POST['category_name'] ?? '');
        $category_description = trim($_POST['category_description'] ?? '');
        
        if(empty($category_name)) {
            $error = "O nome da categoria é obrigatório!";
        } else {
            $result = $auth->addCategory($category_name, $category_description);
            if($result['success']) {
                $success = $result['message'];
                // Recarregar categorias
                $categories = $auth->getAllCategories();
            } else {
                $error = $result['message'];
            }
        }
    }

    // Processar upload do quadrinho (páginas individuais)
    if(isset($_POST['upload_comic'])) {
        error_log("=== PROCESSANDO UPLOAD NORMAL (PÁGINAS INDIVIDUAIS) ===");
        
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categories = $_POST['categories'] ?? [];
        $is_premium = isset($_POST['is_premium']) ? 1 : 0;
        $price = $is_premium ? floatval($_POST['price'] ?? 0) : 0;
        $upload_type = $_POST['upload_type'] ?? 'pages';
        
        error_log("Dados recebidos - Título: $title, Categorias: " . count($categories));
        
        // Validar dados
        if(empty($title)) {
            $error = "O título é obrigatório!";
        } elseif(empty($description)) {
            $error = "A descrição é obrigatória!";
        } elseif(empty($categories)) {
            $error = "Selecione pelo menos uma categoria!";
        } elseif($is_premium && $price <= 0) {
            $error = "Para quadrinhos premium, o preço deve ser maior que zero!";
        } else {
            // Processar upload da capa
            $cover_path = null;
            if(isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                $cover_result = $auth->uploadComicCover($_FILES['cover'], $_SESSION['user_id']);
                if($cover_result['success']) {
                    $cover_path = $cover_result['path'];
                    error_log("Capa uploadada: $cover_path");
                } else {
                    $error = $cover_result['message'];
                }
            } else {
                $error = "A capa é obrigatória!";
            }
            
            if(!$error) {
                error_log("Criando quadrinho no banco...");
                error_log("Dados do quadrinho:");
                error_log("- Título: " . $title);
                error_log("- Author ID: " . $_SESSION['user_id']);
                error_log("- Descrição: " . $description);
                error_log("- Cover: " . $cover_path);
                error_log("- Premium: " . $is_premium);
                error_log("- Preço: " . $price);
                error_log("- Categorias: " . print_r($categories, true));

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

                if($result['success']) {
                    $success = $result['message'];
                    $_SESSION['success_message'] = $success;
                    
                    // PROCESSAR PÁGINAS INDIVIDUAIS
                    if(isset($_FILES['pages']) && !empty($_FILES['pages']['name'][0])) {
                        $pages_result = $auth->uploadComicPages($_FILES['pages'], $result['comic_id'], $_SESSION['user_id']);
                        
                        if($pages_result['success']) {
                            $auth->updateComicPageCount($result['comic_id'], $pages_result['page_count']);
                            $success .= " " . $pages_result['page_count'] . " página(s) carregada(s)!";
                        } else {
                            $error = $pages_result['message'];
                            // Se deu erro nas páginas, ainda assim redireciona mas mostra erro
                        }
                    }
                    
                    if(!$error) {
                        $_SESSION['success_message'] = $success;
                        header("Location: comic.php?id=" . $result['comic_id']);
                        exit();
                    }
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
}

// Se chegou até aqui, é uma requisição GET ou precisa mostrar o formulário
if(!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Verificar se o usuário é criador
if(!$auth->isCreator($_SESSION['user_id'])) {
    header("Location: perfil.php");
    exit();
}

// Obter categorias disponíveis
$categories = $auth->getAllCategories();

// Mostrar mensagem de sucesso da sessão se existir
if(isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Inicializar variáveis se não existirem
$success = $success ?? '';
$error = $error ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicar Quadrinho - HQ Verso</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #1a1a2e;
            --bg-secondary: #16213e;
            --bg-card: rgba(26, 26, 46, 0.7);
            --text-primary: #e0e0e0;
            --text-secondary: #b0b0b0;
            --accent-color: #e94560;
            --accent-hover: #d8345f;
            --border-color: rgba(255, 255, 255, 0.1);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        [data-theme="light"] {
            --bg-primary: #f8f9fa;
            --bg-secondary: #e9ecef;
            --bg-card: rgba(255, 255, 255, 0.9);
            --text-primary: #2c3e50;
            --text-secondary: #6c757d;
            --accent-color: #e94560;
            --accent-hover: #d8345f;
            --border-color: rgba(0, 0, 0, 0.1);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        
        body { 
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%); 
            color: var(--text-primary); 
            min-height: 100vh; 
            padding: 20px; 
            line-height: 1.5;
        }
        
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
        }
        
        header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 20px 0; 
            margin-bottom: 30px; 
            border-bottom: 1px solid var(--border-color);
        }
        
        .logo { 
            font-size: 2.5rem; 
            font-weight: 800; 
            color: var(--accent-color); 
            text-decoration: none;
            background: linear-gradient(45deg, var(--accent-color), #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .back-btn { 
            background: rgba(233, 69, 96, 0.1);
            color: var(--accent-color); 
            text-decoration: none; 
            font-weight: 600; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            padding: 12px 24px;
            border-radius: 10px;
            border: 2px solid rgba(233, 69, 96, 0.3);
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: var(--accent-color);
            color: white;
            transform: translateX(-5px);
        }

        .upload-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
        }

        .upload-title {
            color: var(--accent-color);
            font-size: 2rem;
            margin-bottom: 10px;
            text-align: center;
        }

        .upload-subtitle {
            text-align: center;
            margin-bottom: 30px;
            opacity: 0.8;
        }

        .form-group { 
            margin-bottom: 25px; 
        }
        
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600;
            color: var(--accent-color);
        }
        
        .form-group input, 
        .form-group textarea,
        .form-group select { 
            width: 100%; 
            padding: 12px 15px; 
            border: 1px solid var(--border-color); 
            border-radius: 8px; 
            background: rgba(255, 255, 255, 0.08); 
            color: var(--text-primary); 
            font-size: 1rem; 
            transition: all 0.3s ease;
        }

        [data-theme="light"] .form-group input,
        [data-theme="light"] .form-group textarea,
        [data-theme="light"] .form-group select {
            background: rgba(0, 0, 0, 0.05);
        }
        
        .form-group input:focus, 
        .form-group textarea:focus,
        .form-group select:focus { 
            outline: none; 
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.2);
        }
        
        .form-group textarea { 
            height: 120px; 
            resize: vertical; 
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .price-group {
            display: none;
            margin-top: 15px;
        }

        .price-group.active {
            display: block;
        }

        .file-upload {
            border: 2px dashed var(--border-color);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }

        .file-upload:hover {
            border-color: var(--accent-color);
            background: rgba(233, 69, 96, 0.05);
        }

        .file-upload i {
            font-size: 3rem;
            color: var(--accent-color);
            margin-bottom: 15px;
        }

        .file-preview {
            margin-top: 20px;
            display: none;
        }

        .file-preview.active {
            display: block;
        }

        .preview-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .preview-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }

        .btn { 
            padding: 15px 30px; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer; 
            border: none; 
            font-size: 1rem; 
            text-decoration: none; 
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, var(--accent-color) 0%, var(--accent-hover) 100%);
            color: white; 
            box-shadow: 0 4px 15px rgba(233, 69, 96, 0.3);
        }
        
        .btn-primary:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(233, 69, 96, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .btn-full {
            width: 100%;
        }

        .alert { 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 10px; 
            text-align: center; 
            font-weight: 500;
            border-left: 4px solid;
        }
        
        .alert-success { 
            background: rgba(76, 175, 80, 0.15); 
            border-color: #4caf50; 
            color: #4caf50; 
        }
        
        .alert-error { 
            background: rgba(233, 69, 96, 0.15); 
            border-color: var(--accent-color); 
            color: var(--accent-color); 
        }

        .upload-tips {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }

        .upload-tips h3 {
            color: var(--accent-color);
            margin-bottom: 15px;
        }

        .tip-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .tip-item i {
            color: var(--accent-color);
            width: 20px;
        }

        .upload-type-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .upload-type-option {
            flex: 1;
            min-width: 200px;
        }
        
        .upload-type-option input[type="radio"] {
            display: none;
        }
        
        .upload-type-label {
            display: block;
            padding: 20px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .upload-type-label:hover {
            border-color: var(--accent-color);
            background: rgba(233, 69, 96, 0.05);
        }
        
        .upload-type-option input[type="radio"]:checked + .upload-type-label {
            border-color: var(--accent-color);
            background: rgba(233, 69, 96, 0.1);
            box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.2);
        }
        
        .upload-type-icon {
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-bottom: 10px;
        }
        
        .upload-section {
            display: none;
        }
        
        .upload-section.active {
            display: block;
        }
        
        .pdf-info {
            margin-top: 10px;
            padding: 10px;
            background: rgba(76, 175, 80, 0.1);
            border-radius: 5px;
            border-left: 4px solid #4caf50;
        }

        .pdf-processing {
            background: rgba(33, 150, 243, 0.1);
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }

        .progress-bar {
            width: 100%;
            height: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            margin: 15px 0;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-color), #2196f3);
            border-radius: 10px;
            transition: width 0.3s ease;
            width: 0%;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: var(--bg-card);
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .modal-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            color: var(--accent-color);
            font-size: 1.5rem;
            margin: 0;
        }

        .close-modal {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
        }

        .close-modal:hover {
            color: var(--accent-color);
        }

        .categories-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
        }

        .category-checkbox {
            display: none;
        }

        .category-label {
            display: block;
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .category-checkbox:checked + .category-label {
            background: rgba(233, 69, 96, 0.2);
            border-color: var(--accent-color);
            color: var(--accent-color);
        }

        .category-label:hover {
            border-color: var(--accent-color);
        }

        .selected-categories {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            min-height: 60px;
        }

        .selected-category {
            background: rgba(233, 69, 96, 0.2);
            color: var(--accent-color);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .remove-category {
            background: none;
            border: none;
            color: var(--accent-color);
            cursor: pointer;
            font-size: 1rem;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .no-categories {
            color: var(--text-secondary);
            font-style: italic;
            text-align: center;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .upload-card {
                padding: 20px;
            }
            
            .upload-title {
                font-size: 1.5rem;
            }
            
            header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .upload-type-selector {
                flex-direction: column;
            }

            .categories-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <a href="comics.php" class="logo">HQ VERSO</a>
            <a href="comics.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> 
                <span>Voltar para Quadrinhos</span>
            </a>
        </header>
        
        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="upload-card">
            <h1 class="upload-title">
                <i class="fas fa-upload"></i> Publicar Quadrinho
            </h1>
            <p class="upload-subtitle">Compartilhe sua história com o mundo!</p>
            
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="upload_comic" value="1">
                
                <div class="form-group">
                    <label for="title">Título do Quadrinho *</label>
                    <input type="text" id="title" name="title" 
                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                           required
                           placeholder="Ex: As Aventuras do Super-Herói">
                </div>
                
                <div class="form-group">
                    <label for="description">Descrição *</label>
                    <textarea id="description" name="description" 
                              required
                              placeholder="Descreva a história do seu quadrinho..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="categories">Categorias *</label>
                    <div class="selected-categories" id="selectedCategories">
                        <div class="no-categories">Nenhuma categoria selecionada</div>
                    </div>
                    <!-- Inputs hidden para categorias -->
                    <div id="categoriesHiddenInputs"></div>
                    <button type="button" class="btn btn-secondary btn-full" onclick="openCategoryModal()">
                        <i class="fas fa-plus"></i> Selecionar Categorias
                    </button>
                    <small style="color: var(--text-secondary); display: block; margin-top: 8px;">
                        Selecione as categorias que melhor descrevem seu quadrinho
                    </small>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="is_premium" name="is_premium" value="1"
                           <?php echo (isset($_POST['is_premium']) && $_POST['is_premium']) ? 'checked' : ''; ?>>
                    <label for="is_premium" style="margin: 0;">Quadrinho Premium (Pago)</label>
                </div>
                
                <div class="form-group price-group <?php echo (isset($_POST['is_premium']) && $_POST['is_premium']) ? 'active' : ''; ?>" id="priceGroup">
                    <label for="price">Preço (R$) *</label>
                    <input type="number" id="price" name="price" 
                           value="<?php echo htmlspecialchars($_POST['price'] ?? '0.00'); ?>" 
                           min="0" step="0.01"
                           placeholder="0.00">
                </div>
                
                <div class="form-group">
                    <label for="cover">Capa do Quadrinho *</label>
                    <div class="file-upload" onclick="document.getElementById('cover').click()">
                        <i class="fas fa-image"></i>
                        <h3>Clique para selecionar a capa</h3>
                        <p>Formatos: JPG, PNG, GIF, WebP (Máx. 5MB)</p>
                        <input type="file" id="cover" name="cover" accept="image/*" style="display: none;" required>
                    </div>
                    <div class="file-preview" id="coverPreview"></div>
                </div>
                
                <!-- Seletor de tipo de upload -->
                <div class="form-group">
                    <label>Método de Upload *</label>
                    <div class="upload-type-selector">
                        <div class="upload-type-option">
                            <input type="radio" id="upload_pages" name="upload_type" value="pages" 
                                   <?php echo (!isset($_POST['upload_type']) || $_POST['upload_type'] === 'pages') ? 'checked' : ''; ?>>
                            <label for="upload_pages" class="upload-type-label">
                                <div class="upload-type-icon">
                                    <i class="fas fa-file-image"></i>
                                </div>
                                <h3>Páginas Individuais</h3>
                                <p>Upload de imagens separadas</p>
                            </label>
                        </div>
                        
                        <div class="upload-type-option">
                            <input type="radio" id="upload_pdf" name="upload_type" value="pdf"
                                   <?php echo (isset($_POST['upload_type']) && $_POST['upload_type'] === 'pdf') ? 'checked' : ''; ?>>
                            <label for="upload_pdf" class="upload-type-label">
                                <div class="upload-type-icon">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <h3>Arquivo PDF</h3>
                                <p>Upload de um único arquivo PDF</p>
                                <small style="color: #4caf50;"><i class="fas fa-check"></i> Disponível</small>
                            </label>
                        </div>
                    </div>
                    
                    <div class="pdf-info">
                        <i class="fas fa-info-circle" style="color: #4caf50;"></i>
                        <strong>Upload por PDF disponível!</strong> Seu PDF será convertido automaticamente em páginas no seu navegador.
                    </div>
                </div>
                
                <!-- Seção de páginas individuais -->
                <div class="upload-section <?php echo (!isset($_POST['upload_type']) || $_POST['upload_type'] === 'pages') ? 'active' : ''; ?>" id="pagesSection">
                    <div class="form-group">
                        <label for="pages">Páginas do Quadrinho *</label>
                        <div class="file-upload" onclick="document.getElementById('pages').click()">
                            <i class="fas fa-file-image"></i>
                            <h3>Clique para selecionar as páginas</h3>
                            <p>Selecione múltiplas imagens. Formatos: JPG, PNG, GIF, WebP (Máx. 5MB cada)</p>
                            <input type="file" id="pages" name="pages[]" accept="image/*" multiple 
                                   style="display: none;" >
                        </div>
                        <div class="file-preview" id="pagesPreview"></div>
                    </div>
                </div>
                
                <!-- Seção de PDF -->
                <div class="upload-section <?php echo (isset($_POST['upload_type']) && $_POST['upload_type'] === 'pdf') ? 'active' : ''; ?>" id="pdfSection">
                    <div class="form-group">
                        <label for="pdf_file">Arquivo PDF do Quadrinho *</label>
                        <div class="file-upload" onclick="document.getElementById('pdf_file').click()">
                            <i class="fas fa-file-pdf"></i>
                            <h3>Clique para selecionar o PDF</h3>
                            <p>Selecione um arquivo PDF contendo todas as páginas (Máx. 20MB)</p>
                            <input type="file" id="pdf_file" name="pdf_file" accept=".pdf" 
                                   style="display: none;">
                        </div>
                        <div class="file-preview" id="pdfPreview"></div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full" id="submitBtn">
                    <i class="fas fa-rocket"></i> Publicar Quadrinho
                </button>
            </form>
            
            <div class="upload-tips">
                <h3><i class="fas fa-lightbulb"></i> Dicas para uma boa publicação:</h3>
                <div class="tip-item">
                    <i class="fas fa-check"></i>
                    <span>Use imagens de alta qualidade (recomendado: 700x1000 pixels)</span>
                </div>
                <div class="tip-item">
                    <i class="fas fa-check"></i>
                    <span>Mantenha o tamanho das páginas consistente</span>
                </div>
                <div class="tip-item">
                    <i class="fas fa-check"></i>
                    <span>Para PDF: certifique-se de que todas as páginas estão na orientação correta</span>
                </div>
                <div class="tip-item">
                    <i class="fas fa-check"></i>
                    <span>Escreva uma descrição atraente para chamar leitores</span>
                </div>
                <div class="tip-item">
                    <i class="fas fa-check"></i>
                    <span>Escolha categorias relevantes para sua história</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Categorias -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Selecionar Categorias</h3>
                <button class="close-modal" onclick="closeCategoryModal()">&times;</button>
            </div>
            
            <div class="form-group">
                <button type="button" class="btn btn-secondary" onclick="openAddCategoryModal()" style="margin-bottom: 15px;">
                    <i class="fas fa-plus"></i> Adicionar Nova Categoria
                </button>
                
                <div class="categories-container" id="categoriesList">
                    <?php foreach($categories as $category): ?>
                        <div class="category-item">
                            <input type="checkbox" class="category-checkbox" id="category_<?php echo $category['id']; ?>" 
                                   value="<?php echo $category['id']; ?>" data-name="<?php echo htmlspecialchars($category['name']); ?>">
                            <label for="category_<?php echo $category['id']; ?>" class="category-label">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="button" class="btn btn-primary btn-full" onclick="saveCategories()">
                <i class="fas fa-check"></i> Confirmar Seleção
            </button>
        </div>
    </div>

    <!-- Modal para Adicionar Nova Categoria -->
    <div id="addCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Adicionar Nova Categoria</h3>
                <button class="close-modal" onclick="closeAddCategoryModal()">&times;</button>
            </div>
            
            <form id="addCategoryForm" method="POST">
                <input type="hidden" name="add_category" value="1">
                
                <div class="form-group">
                    <label for="category_name">Nome da Categoria *</label>
                    <input type="text" id="category_name" name="category_name" required 
                           placeholder="Ex: Ação, Aventura, Romance...">
                </div>
                
                <div class="form-group">
                    <label for="category_description">Descrição (Opcional)</label>
                    <textarea id="category_description" name="category_description" 
                              placeholder="Descreva esta categoria..."></textarea>
                </div>
                
                <div class="form-group" style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="closeAddCategoryModal()">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-plus"></i> Adicionar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Incluir PDF.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    
    <script>
        // Configurar PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

        // Variáveis globais
        let selectedCategories = [];

        // Alternar visibilidade do campo de preço
        document.getElementById('is_premium').addEventListener('change', function() {
            const priceGroup = document.getElementById('priceGroup');
            const priceInput = document.getElementById('price');
            
            if(this.checked) {
                priceGroup.classList.add('active');
                priceInput.required = true;
            } else {
                priceGroup.classList.remove('active');
                priceInput.required = false;
                priceInput.value = '0.00';
            }
        });

        // Alternar entre métodos de upload
        document.querySelectorAll('input[name="upload_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const pagesSection = document.getElementById('pagesSection');
                const pdfSection = document.getElementById('pdfSection');
                
                if(this.value === 'pages') {
                    pagesSection.classList.add('active');
                    pdfSection.classList.remove('active');
                } else if(this.value === 'pdf') {
                    pagesSection.classList.remove('active');
                    pdfSection.classList.add('active');
                }
            });
        });

        // Funções do Modal de Categorias
        function openCategoryModal() {
            document.getElementById('categoryModal').style.display = 'block';
            // Marcar categorias já selecionadas
            selectedCategories.forEach(catId => {
                const checkbox = document.getElementById('category_' + catId);
                if(checkbox) checkbox.checked = true;
            });
        }

        function closeCategoryModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }

        function openAddCategoryModal() {
            closeCategoryModal();
            document.getElementById('addCategoryModal').style.display = 'block';
        }

        function closeAddCategoryModal() {
            document.getElementById('addCategoryModal').style.display = 'none';
            document.getElementById('addCategoryForm').reset();
        }

        function saveCategories() {
            // Coletar categorias selecionadas
            selectedCategories = [];
            const checkboxes = document.querySelectorAll('.category-checkbox:checked');
            
            checkboxes.forEach(checkbox => {
                selectedCategories.push(checkbox.value);
            });
            
            // Atualizar display
            updateSelectedCategoriesDisplay();
            
            // Fechar modal
            closeCategoryModal();
        }

        function updateSelectedCategoriesDisplay() {
            const container = document.getElementById('selectedCategories');
            const hiddenInputsContainer = document.getElementById('categoriesHiddenInputs');
            
            // Limpar containers
            container.innerHTML = '';
            hiddenInputsContainer.innerHTML = '';
            
            if(selectedCategories.length === 0) {
                container.innerHTML = '<div class="no-categories">Nenhuma categoria selecionada</div>';
                return;
            }
            
            // Adicionar categorias selecionadas e inputs hidden
            selectedCategories.forEach(catId => {
                const checkbox = document.getElementById('category_' + catId);
                if(checkbox) {
                    const categoryName = checkbox.getAttribute('data-name');
                    
                    // Adicionar ao display
                    const categoryElement = document.createElement('div');
                    categoryElement.className = 'selected-category';
                    categoryElement.innerHTML = `
                        ${categoryName}
                        <button type="button" class="remove-category" onclick="removeCategory('${catId}')">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    container.appendChild(categoryElement);
                    
                    // Adicionar input hidden
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'categories[]';
                    hiddenInput.value = catId;
                    hiddenInputsContainer.appendChild(hiddenInput);
                }
            });
        }

        function removeCategory(catId) {
            selectedCategories = selectedCategories.filter(id => id !== catId);
            updateSelectedCategoriesDisplay();
        }

        // Preview da capa
        document.getElementById('cover').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('coverPreview');
            
            if(file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <div class="preview-item">
                            <img src="${e.target.result}" alt="Preview da capa">
                            <div>
                                <strong>${file.name}</strong>
                                <div>${(file.size / 1024 / 1024).toFixed(2)} MB</div>
                            </div>
                        </div>
                    `;
                    preview.classList.add('active');
                };
                reader.readAsDataURL(file);
            } else {
                preview.classList.remove('active');
                preview.innerHTML = '';
            }
        });

        // Preview das páginas
        document.getElementById('pages').addEventListener('change', function(e) {
            const files = e.target.files;
            const preview = document.getElementById('pagesPreview');
            
            if(files.length > 0) {
                preview.innerHTML = `<h4>${files.length} página(s) selecionada(s):</h4>`;
                
                for(let i = 0; i < Math.min(files.length, 5); i++) {
                    const file = files[i];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'preview-item';
                        previewItem.innerHTML = `
                            <img src="${e.target.result}" alt="Preview da página">
                            <div>
                                <strong>${file.name}</strong>
                                <div>${(file.size / 1024 / 1024).toFixed(2)} MB</div>
                            </div>
                        `;
                        preview.appendChild(previewItem);
                    };
                    
                    reader.readAsDataURL(file);
                }
                
                if(files.length > 5) {
                    const extraInfo = document.createElement('p');
                    extraInfo.textContent = `+ ${files.length - 5} outra(s) página(s)`;
                    preview.appendChild(extraInfo);
                }
                
                preview.classList.add('active');
            } else {
                preview.classList.remove('active');
                preview.innerHTML = '';
            }
        });

        // Preview do PDF
        document.getElementById('pdf_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('pdfPreview');
            
            if(file) {
                preview.innerHTML = `
                    <div class="preview-item">
                        <i class="fas fa-file-pdf" style="font-size: 3rem; color: #e94560;"></i>
                        <div>
                            <strong>${file.name}</strong>
                            <div>${(file.size / 1024 / 1024).toFixed(2)} MB</div>
                        </div>
                    </div>
                `;
                preview.classList.add('active');
            } else {
                preview.classList.remove('active');
                preview.innerHTML = '';
            }
        });

        // Processar PDF no cliente
        async function processPDFInBrowser(pdfFile) {
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            
            // Criar elemento de processamento
            const processingElement = document.createElement('div');
            processingElement.className = 'pdf-processing';
            processingElement.innerHTML = `
                <h3><i class="fas fa-spinner fa-spin"></i> Processando PDF...</h3>
                <p>Aguarde enquanto convertemos o PDF.</p>
                <div class="progress-bar">
                    <div class="progress-fill" id="pdfProgress"></div>
                </div>
                <p id="progressText">0% concluído</p>
            `;
            
            document.querySelector('.upload-card').insertBefore(processingElement, document.querySelector('.upload-tips'));
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
            submitBtn.disabled = true;
            
            try {
                console.log("Iniciando processamento do PDF...");
                
                const arrayBuffer = await pdfFile.arrayBuffer();
                const pdf = await pdfjsLib.getDocument(arrayBuffer).promise;
                const pageCount = pdf.numPages;
                
                console.log(`PDF carregado com ${pageCount} páginas`);
                
                const convertedPages = [];
                
                // PROCESSAR PÁGINAS
                for (let pageNum = 1; pageNum <= pageCount; pageNum++) {
                    console.log(`Processando página ${pageNum}/${pageCount}`);
                    const page = await pdf.getPage(pageNum);
                    
                    const viewport = page.getViewport({ scale: 1.0 });
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    
                    await page.render({
                        canvasContext: context,
                        viewport: viewport
                    }).promise;
                    
                    const blob = await new Promise(resolve => {
                        canvas.toBlob(resolve, 'image/jpeg', 0.8);
                    });
                    
                    convertedPages.push(blob);
                    
                    const progress = (pageNum / pageCount) * 100;
                    document.getElementById('pdfProgress').style.width = progress + '%';
                    document.getElementById('progressText').textContent = 
                        `${Math.round(progress)}% processado (${pageNum}/${pageCount} páginas)`;
                }
                
                console.log(`PDF convertido: ${convertedPages.length} páginas`);
                
                // CRIAR FORM DATA
                const formData = new FormData();
                formData.append('processed_pdf', '1');
                formData.append('title', document.getElementById('title').value);
                formData.append('description', document.getElementById('description').value);
                
                // Adicionar categorias
                selectedCategories.forEach(catId => {
                    formData.append('categories[]', catId);
                });
                
                // Adicionar outros campos
                if(document.getElementById('is_premium').checked) {
                    formData.append('is_premium', '1');
                    formData.append('price', document.getElementById('price').value);
                }
                
                // Adicionar capa
                const cover = document.getElementById('cover').files[0];
                if (cover) {
                    formData.append('cover', cover);
                } else {
                    throw new Error('Capa é obrigatória');
                }
                
                // Adicionar páginas
                convertedPages.forEach((blob, index) => {
                    const file = new File([blob], `page_${index + 1}.jpg`, { 
                        type: 'image/jpeg',
                        lastModified: new Date().getTime()
                    });
                    formData.append('pages[]', file);
                });
                
                console.log("Enviando para o servidor...");
                processingElement.querySelector('p').textContent = 'Enviando para o servidor...';
                
                // ENVIAR
                const response = await fetch('upload_comic.php', {
                    method: 'POST',
                    body: formData
                });

                const responseText = await response.text();
                console.log("Resposta do servidor:", responseText);

                // Tentar extrair JSON
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (e1) {
                    try {
                        const jsonMatch = responseText.match(/\{[\s\S]*\}/);
                        if (jsonMatch) {
                            result = JSON.parse(jsonMatch[0]);
                        } else {
                            throw new Error('JSON não encontrado');
                        }
                    } catch (e2) {
                        if (responseText.includes('sucesso') || responseText.includes('comic_id')) {
                            const comicIdMatch = responseText.match(/comic_id["']?\s*:\s*["']?([^"'\s]+)/);
                            result = {
                                success: true,
                                message: 'Quadrinho criado com sucesso',
                                comic_id: comicIdMatch ? comicIdMatch[1] : 'unknown'
                            };
                        } else {
                            throw new Error('Não foi possível entender a resposta do servidor');
                        }
                    }
                }
                
                if(result.success) {
                    processingElement.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> 
                            <strong>Quadrinho publicado com sucesso!</strong><br>
                            ${convertedPages.length} página(s) processada(s)<br>
                            <small>ID: ${result.comic_id}</small>
                        </div>
                        <div class="text-center" style="margin-top: 15px;">
                            <a href="comic.php?id=${result.comic_id}" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Ver Quadrinho
                            </a>
                        </div>
                    `;
                    
                    // Redirecionar após 3 segundos
                    setTimeout(() => {
                        if(result.comic_id && result.comic_id !== 'unknown') {
                            window.location.href = 'comic.php?id=' + result.comic_id;
                        }
                    }, 3000);
                    
                } else {
                    throw new Error(result.message || 'Erro ao publicar quadrinho');
                }
                
            } catch (error) {
                console.error('Erro ao processar PDF:', error);
                
                processingElement.innerHTML = `
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>Erro:</strong> ${error.message}
                    </div>
                    <div style="margin-top: 15px;">
                        <button onclick="window.location.reload()" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Tentar Novamente
                        </button>
                    </div>
                `;
                
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }

        // Validação do formulário
        document.getElementById('uploadForm').addEventListener('submit', async function(e) {
            const uploadType = document.querySelector('input[name="upload_type"]:checked').value;
            const pdfFile = document.getElementById('pdf_file').files[0];
            
            // Validar categorias
            if(selectedCategories.length === 0) {
                e.preventDefault();
                alert('Por favor, selecione pelo menos uma categoria!');
                return false;
            }
            
            // Se for upload por PDF, processar no cliente
            if (uploadType === 'pdf' && pdfFile) {
                e.preventDefault();
                await processPDFInBrowser(pdfFile);
                return false;
            }
            
            // Validação normal para páginas individuais
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            const cover = document.getElementById('cover').files[0];
            const pages = document.getElementById('pages').files;
            const isPremium = document.getElementById('is_premium').checked;
            const price = document.getElementById('price').value;
            const submitBtn = document.getElementById('submitBtn');
            
            // Validações básicas
            if(!title) {
                e.preventDefault();
                alert('Por favor, insira um título para o quadrinho!');
                return false;
            }
            
            if(!description) {
                e.preventDefault();
                alert('Por favor, insira uma descrição para o quadrinho!');
                return false;
            }
            
            if(isPremium && (!price || parseFloat(price) <= 0)) {
                e.preventDefault();
                alert('Para quadrinhos premium, o preço deve ser maior que zero!');
                return false;
            }
            
            if(!cover) {
                e.preventDefault();
                alert('Por favor, selecione uma imagem para a capa!');
                return false;
            }
            
            if(uploadType === 'pages' && pages.length === 0) {
                e.preventDefault();
                alert('Por favor, selecione pelo menos uma página!');
                return false;
            }
            
            // Validar tamanho máximo dos arquivos
            const maxImageSize = 5 * 1024 * 1024;
            const maxPDFSize = 20 * 1024 * 1024;
            
            if(cover.size > maxImageSize) {
                e.preventDefault();
                alert('A imagem da capa é muito grande! O tamanho máximo é 5MB.');
                return false;
            }
            
            if(uploadType === 'pages') {
                for(let i = 0; i < pages.length; i++) {
                    if(pages[i].size > maxImageSize) {
                        e.preventDefault();
                        alert(`A página "${pages[i].name}" é muito grande! O tamanho máximo é 5MB por arquivo.`);
                        return false;
                    }
                }
            } else if(uploadType === 'pdf' && !pdfFile) {
                e.preventDefault();
                alert('Por favor, selecione um arquivo PDF!');
                return false;
            } else if(uploadType === 'pdf' && pdfFile.size > maxPDFSize) {
                e.preventDefault();
                alert('O arquivo PDF é muito grande! O tamanho máximo é 20MB.');
                return false;
            }
            
            // Mostrar loading para envio normal
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publicando...';
            submitBtn.disabled = true;
        });

        // Arrastar e soltar arquivos
        document.querySelectorAll('.file-upload').forEach(uploadArea => {
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--accent-color)';
                this.style.background = 'rgba(233, 69, 96, 0.1)';
            });
            
            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--border-color)';
                this.style.background = '';
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--border-color)';
                this.style.background = '';
                
                const files = e.dataTransfer.files;
                if(files.length > 0) {
                    const input = this.parentElement.querySelector('input[type="file"]');
                    if(input) {
                        if(input.multiple) {
                            const currentFiles = Array.from(input.files);
                            const newFiles = Array.from(files);
                            const allFiles = [...currentFiles, ...newFiles];
                            
                            const dt = new DataTransfer();
                            allFiles.forEach(file => dt.items.add(file));
                            input.files = dt.files;
                        } else {
                            input.files = files;
                        }
                        
                        const event = new Event('change');
                        input.dispatchEvent(event);
                    }
                }
            });
        });

        // Fechar modais ao clicar fora
        window.addEventListener('click', function(e) {
            const categoryModal = document.getElementById('categoryModal');
            const addCategoryModal = document.getElementById('addCategoryModal');
            
            if(e.target === categoryModal) {
                closeCategoryModal();
            }
            if(e.target === addCategoryModal) {
                closeAddCategoryModal();
            }
        });
    </script>
</body>
</html>
