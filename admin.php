<?php
require_once 'includes_auth.php';

if(!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header("Location: comics.php");
    exit();
}

$success = '';
$error = '';
$search_results = [];
$search_term = '';

// Processar formulário de adicionar quadrinho
if($_POST && isset($_POST['add_comic'])) {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $cover_url = $_POST['cover_url'] ?? '';
    $price = $_POST['price'] ?? 0;
    $page_count = $_POST['page_count'] ?? 0;
    $publisher_id = $_POST['publisher_id'] ?? 1;
    $categories = $_POST['categories'] ?? [];

    if(!empty($title) && !empty($description)) {
        $result = addComic($title, $description, $cover_url, $price, $page_count, $publisher_id, $categories);
        if($result) {
            $success = "Quadrinho adicionado com sucesso!";
        } else {
            $error = "Erro ao adicionar quadrinho!";
        }
    } else {
        $error = "Preencha todos os campos obrigatórios!";
    }
}

// Processar pesquisa de usuários
if($_POST && isset($_POST['search_users'])) {
    $search_term = $_POST['search_term'] ?? '';
    if(!empty($search_term)) {
        $search_results = $auth->searchUsers($search_term);
        if(empty($search_results)) {
            $error = "Nenhum usuário encontrado para: " . htmlspecialchars($search_term);
        }
    } else {
        $error = "Digite um nome ou email para pesquisar!";
    }
}

// Processar atualização de status de report
if($_POST && isset($_POST['update_report_status'])) {
    $report_id = $_POST['report_id'] ?? '';
    $status = $_POST['status'] ?? '';
    
    if(!empty($report_id) && !empty($status)) {
        $result = updateReportStatus($report_id, $status, $_SESSION['user_id']);
        if($result === true) {
            $success = "Status do report atualizado com sucesso!";
        } else {
            $error = $result;
        }
    }
}

// Função para banir usuário (admin)
function banUser($user_id, $banned_by, $reason = '') {
    require_once 'config_database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        // Verificar se não é auto-banimento
        if($user_id == $banned_by) {
            return "Você não pode banir sua própria conta!";
        }
        
        // Usar a função do Auth para banir
        require_once 'includes_auth.php';
        $auth = new Auth();
        $result = $auth->banUser($user_id, $banned_by, $reason);
        
        return $result;
        
    } catch(PDOException $e) {
        return "Erro ao banir usuário: " . $e->getMessage();
    }
}

// Processar banimento de usuário
if($_POST && isset($_POST['ban_user'])) {
    $user_id = $_POST['user_id'] ?? '';
    $ban_reason = $_POST['ban_reason'] ?? '';
    
    if(!empty($user_id)) {
        $result = banUser($user_id, $_SESSION['user_id'], $ban_reason);
        if($result === true) {
            $success = "Usuário banido permanentemente com sucesso!";
            // Limpar resultados da pesquisa
            $search_results = [];
            $search_term = '';
        } else {
            $error = $result;
        }
    } else {
        $error = "ID do usuário não especificado!";
    }
}

// Função para atualizar status do report
function updateReportStatus($report_id, $status, $admin_id) {
    require_once 'config_database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        $query = "UPDATE user_reports SET status = :status, admin_id = :admin_id, updated_at = NOW() WHERE id = :report_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":admin_id", $admin_id);
        $stmt->bindParam(":report_id", $report_id);
        
        return $stmt->execute();
        
    } catch(PDOException $e) {
        return "Erro ao atualizar report: " . $e->getMessage();
    }
}

// Função para adicionar quadrinho
function addComic($title, $description, $cover_url, $price, $page_count, $publisher_id, $categories) {
    require_once 'config_database.php';
    $database = new Database();
    $conn = $database->getConnection();

    try {
        // Inserir o quadrinho
        $query = "INSERT INTO comics (title, author_id, publisher_id, cover, description, price, page_count, is_published, status) 
                  VALUES (:title, :author_id, :publisher_id, :cover, :description, :price, :page_count, TRUE, 'published')";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":author_id", $_SESSION['user_id']);
        $stmt->bindParam(":publisher_id", $publisher_id);
        $stmt->bindParam(":cover", $cover_url);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":price", $price);
        $stmt->bindParam(":page_count", $page_count);
        
        if($stmt->execute()) {
            $comic_id = $conn->lastInsertId();
            
            // Adicionar categorias
            if(!empty($categories)) {
                foreach($categories as $category_id) {
                    $cat_query = "INSERT INTO comic_categories (comic_id, category_id) VALUES (:comic_id, :category_id)";
                    $cat_stmt = $conn->prepare($cat_query);
                    $cat_stmt->bindParam(":comic_id", $comic_id);
                    $cat_stmt->bindParam(":category_id", $category_id);
                    $cat_stmt->execute();
                }
            }
            
            return true;
        }
    } catch(PDOException $e) {
        error_log("Erro ao adicionar quadrinho: " . $e->getMessage());
    }
    
    return false;
}

// Buscar dados para os selects
function getPublishers() {
    require_once 'config_database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        $stmt = $conn->query("SELECT id, name FROM publishers ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getCategories() {
    require_once 'config_database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        $stmt = $conn->query("SELECT id, name FROM categories ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getComics() {
    require_once 'config_database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        $query = "SELECT c.*, u.username as author_name, 
                         GROUP_CONCAT(cat.name SEPARATOR ', ') as categories
                  FROM comics c 
                  LEFT JOIN users u ON c.author_id = u.id 
                  LEFT JOIN comic_categories cc ON c.id = cc.comic_id 
                  LEFT JOIN categories cat ON cc.category_id = cat.id 
                  GROUP BY c.id 
                  ORDER BY c.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getPendingReports() {
    require_once 'config_database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        $query = "SELECT ur.*, 
                         ru.username as reported_username,
                         ru.email as reported_email,
                         ru.role as reported_role,
                         rep.username as reporter_username,
                         rep.email as reporter_email
                  FROM user_reports ur
                  JOIN users ru ON ur.reported_user_id = ru.id
                  JOIN users rep ON ur.reporter_user_id = rep.id
                  WHERE ur.status = 'pending'
                  ORDER BY ur.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getAllReports() {
    require_once 'config_database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        $query = "SELECT ur.*, 
                         ru.username as reported_username,
                         ru.email as reported_email,
                         rep.username as reporter_username,
                         u.username as admin_name
                  FROM user_reports ur
                  JOIN users ru ON ur.reported_user_id = ru.id
                  JOIN users rep ON ur.reporter_user_id = rep.id
                  LEFT JOIN users u ON ur.admin_id = u.id
                  ORDER BY ur.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

$publishers = getPublishers();
$categories = getCategories();
$comics = getComics();
$users = $auth->getAllUsersForAdmin();
$banned_users = $auth->getBannedUsers();
$pending_reports = getPendingReports();
$all_reports = getAllReports();
$unread_notifications = $auth->getUnreadAdminNotifications();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - HQ Verso</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            min-height: 100vh;
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e94560;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #e94560;
            text-decoration: none;
        }
        
        .admin-nav {
            display: flex;
            gap: 20px;
        }
        
        .nav-btn {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .nav-btn:hover, .nav-btn.active {
            background: #e94560;
        }
        
        .admin-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .form-section, .list-section {
            background: rgba(255, 255, 255, 0.05);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #e94560;
            border-bottom: 2px solid #e94560;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 16px;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .form-group select[multiple] {
            height: 120px;
        }
        
        .checkbox-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #e94560;
            color: white;
        }
        
        .btn-primary:hover {
            background: #d8345f;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #0f3460;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #1a4a7a;
        }
        
        .comics-list, .users-list, .banned-users-list, .reports-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .comic-item, .user-item, .banned-user-item, .report-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            border-left: 4px solid #e94560;
            transition: all 0.3s ease;
        }

        .user-item {
            border-left: 4px solid #0f3460;
        }

        .banned-user-item {
            border-left: 4px solid #dc3545;
        }

        .report-item {
            border-left: 4px solid #ffc107;
        }

        .user-item:hover, .banned-user-item:hover, .report-item:hover {
            background: rgba(255, 255, 255, 0.08);
        }
        
        .comic-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #e94560;
        }

        .user-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #fff;
        }

        .banned-user-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #dc3545;
        }
        
        .comic-meta, .user-meta, .banned-user-meta {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        
        .comic-categories {
            font-size: 0.8rem;
            opacity: 0.6;
        }
        
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-top: 4px solid #e94560;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #e94560;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid #4caf50;
            color: #4caf50;
        }
        
        .alert-error {
            background: rgba(233, 69, 96, 0.2);
            border: 1px solid #e94560;
            color: #e94560;
        }
        
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: #e94560;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            font-size: 0.9rem;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-warning {
            background: #ffc107;
            color: #000;
            padding: 8px 15px;
            font-size: 0.9rem;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.9rem;
        }

        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-form input {
            flex: 1;
        }

        .ban-reason {
            margin-top: 10px;
            padding: 10px;
            background: rgba(220, 53, 69, 0.1);
            border-radius: 5px;
            border-left: 3px solid #dc3545;
        }

        .report-reason {
            margin-top: 10px;
            padding: 10px;
            background: rgba(255, 193, 7, 0.1);
            border-radius: 5px;
            border-left: 3px solid #ffc107;
        }

        .no-results {
            text-align: center;
            padding: 20px;
            opacity: 0.7;
            font-style: italic;
        }

        .admin-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .admin-tab {
            padding: 12px 24px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            border-bottom: 3px solid transparent;
            position: relative;
        }

        .admin-tab.active {
            color: #e94560;
            border-bottom: 3px solid #e94560;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        .notification-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .report-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 10px;
        }

        .status-pending {
            background: #ffc107;
            color: #000;
        }

        .status-reviewed {
            background: #17a2b8;
            color: white;
        }

        .status-resolved {
            background: #28a745;
            color: white;
        }

        .status-dismissed {
            background: #6c757d;
            color: white;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .admin-content {
                grid-template-columns: 1fr;
            }
            
            .checkbox-group {
                grid-template-columns: 1fr;
            }

            .search-form {
                flex-direction: column;
            }

            .admin-tabs {
                flex-wrap: wrap;
            }

            .admin-tab {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <a href="comics.php" class="logo">HQ VERSO - ADMIN</a>
            <div class="admin-nav">
                <a href="comics.php" class="nav-btn">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
                <a href="logout.php" class="nav-btn">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($comics); ?></div>
                <div class="stat-label">Quadrinhos Cadastrados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($users); ?></div>
                <div class="stat-label">Usuários Registrados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($banned_users); ?></div>
                <div class="stat-label">Usuários Banidos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($pending_reports); ?></div>
                <div class="stat-label">Denúncias Pendentes</div>
            </div>
        </div>

        <!-- Sistema de Tabs -->
        <div class="admin-tabs">
            <div class="admin-tab active" data-tab="comics">Gerenciar Quadrinhos</div>
            <div class="admin-tab" data-tab="users">Banir Usuários</div>
            <div class="admin-tab" data-tab="banned">Usuários Banidos</div>
            <div class="admin-tab" data-tab="reports">
                Denúncias
                <?php if(count($pending_reports) > 0): ?>
                    <span class="notification-badge"><?php echo count($pending_reports); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Quadrinhos -->
        <div class="tab-content active" id="comics">
            <div class="admin-content">
                <div class="form-section">
                    <h2 class="section-title">Adicionar Novo Quadrinho</h2>
                    <form method="POST">
                        <input type="hidden" name="add_comic" value="1">
                        
                        <div class="form-group">
                            <label for="title">Título do Quadrinho *</label>
                            <input type="text" id="title" name="title" required placeholder="Ex: Batman: O Cavaleiro das Trevas">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Descrição *</label>
                            <textarea id="description" name="description" required placeholder="Descrição do quadrinho..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="cover_url">URL da Capa</label>
                            <input type="url" id="cover_url" name="cover_url" placeholder="https://exemplo.com/capa.jpg">
                        </div>
                        
                        <div class="form-group">
                            <label for="publisher_id">Editora</label>
                            <select id="publisher_id" name="publisher_id">
                                <?php foreach($publishers as $publisher): ?>
                                    <option value="<?php echo $publisher['id']; ?>"><?php echo htmlspecialchars($publisher['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Preço (R$)</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" value="29.90">
                        </div>
                        
                        <div class="form-group">
                            <label for="page_count">Número de Páginas</label>
                            <input type="number" id="page_count" name="page_count" min="1" value="100">
                        </div>
                        
                        <div class="form-group">
                            <label>Categorias</label>
                            <div class="checkbox-group">
                                <?php foreach($categories as $category): ?>
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="cat_<?php echo $category['id']; ?>" 
                                               name="categories[]" value="<?php echo $category['id']; ?>">
                                        <label for="cat_<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Adicionar Quadrinho
                        </button>
                    </form>
                </div>
                
                <div class="list-section">
                    <h2 class="section-title">Quadrinhos Cadastrados</h2>
                    <div class="comics-list">
                        <?php if(empty($comics)): ?>
                            <p style="text-align: center; opacity: 0.7;">Nenhum quadrinho cadastrado ainda.</p>
                        <?php else: ?>
                            <?php foreach($comics as $comic): ?>
                                <div class="comic-item">
                                    <div class="comic-title"><?php echo htmlspecialchars($comic['title']); ?></div>
                                    <div class="comic-meta">
                                        por <?php echo htmlspecialchars($comic['author_name']); ?> | 
                                        R$ <?php echo number_format($comic['price'], 2, ',', '.'); ?> | 
                                        <?php echo $comic['page_count']; ?> páginas
                                    </div>
                                    <div class="comic-categories">
                                        Categorias: <?php echo $comic['categories'] ? htmlspecialchars($comic['categories']) : 'Nenhuma'; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Banir Usuários -->
        <div class="tab-content" id="users">
            <div class="admin-content">
                <div class="form-section">
                    <h2 class="section-title">Pesquisar Usuários para Banir</h2>
                    <form method="POST" class="search-form">
                        <input type="hidden" name="search_users" value="1">
                        <input type="text" name="search_term" placeholder="Digite nome de usuário ou email..." 
                               value="<?php echo htmlspecialchars($search_term); ?>" required>
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-search"></i> Pesquisar
                        </button>
                    </form>

                    <?php if(!empty($search_results)): ?>
                        <h3 style="margin: 20px 0 10px 0; color: #e94560;">Resultados da Pesquisa:</h3>
                        <div class="users-list">
                            <?php foreach($search_results as $user): ?>
                                <div class="user-item">
                                    <div class="user-name" style="color: <?php echo $user['role'] === 'admin' ? '#e94560' : '#fff'; ?>;">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                        <?php if($user['role'] === 'admin'): ?>
                                            <span style="background: #e94560; color: white; padding: 2px 8px; border-radius: 3px; font-size: 0.8rem; margin-left: 10px;">ADMIN</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-meta">
                                        Email: <?php echo htmlspecialchars($user['email']); ?> | 
                                        Cadastro: <?php echo date('d/m/Y', strtotime($user['created_at'])); ?> |
                                        Quadrinhos: <?php echo $user['comics_count']; ?>
                                    </div>
                                    <?php if($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" style="margin-top: 10px;" 
                                              onsubmit="return confirmBanUser('<?php echo htmlspecialchars($user['username']); ?>', this)">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <div class="form-group">
                                                <label for="ban_reason_<?php echo $user['id']; ?>" style="font-size: 0.9rem;">Motivo do banimento:</label>
                                                <textarea id="ban_reason_<?php echo $user['id']; ?>" name="ban_reason" 
                                                          placeholder="Opcional: informe o motivo do banimento..." 
                                                          style="font-size: 0.9rem; height: 60px;"></textarea>
                                            </div>
                                            <button type="submit" name="ban_user" class="btn btn-danger">
                                                <i class="fas fa-ban"></i> Banir Permanentemente
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <div style="font-size: 0.8rem; opacity: 0.6; margin-top: 5px;">
                                            <i class="fas fa-info-circle"></i> Esta é sua conta
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif($_POST && isset($_POST['search_users'])): ?>
                        <div class="no-results">
                            <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                            <p>Nenhum usuário encontrado para "<?php echo htmlspecialchars($search_term); ?>"</p>
                        </div>
                    <?php else: ?>
                        <div class="no-results">
                            <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                            <p>Digite um nome de usuário ou email para pesquisar</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="list-section">
                    <h2 class="section-title">Todos os Usuários</h2>
                    <div class="users-list">
                        <?php if(empty($users)): ?>
                            <p style="text-align: center; opacity: 0.7;">Nenhum usuário cadastrado.</p>
                        <?php else: ?>
                            <?php foreach($users as $user): ?>
                                <div class="user-item">
                                    <div class="user-name" style="color: <?php echo $user['role'] === 'admin' ? '#e94560' : '#fff'; ?>;">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                        <?php if($user['role'] === 'admin'): ?>
                                            <span style="background: #e94560; color: white; padding: 2px 8px; border-radius: 3px; font-size: 0.8rem; margin-left: 10px;">ADMIN</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-meta">
                                        Email: <?php echo htmlspecialchars($user['email']); ?> | 
                                        Cadastro: <?php echo date('d/m/Y', strtotime($user['created_at'])); ?> |
                                        Quadrinhos: <?php echo $user['comics_count']; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Usuários Banidos -->
        <div class="tab-content" id="banned">
            <div class="list-section">
                <h2 class="section-title">Usuários Banidos Permanentemente</h2>
                <div class="banned-users-list">
                    <?php if(empty($banned_users)): ?>
                        <p style="text-align: center; opacity: 0.7;">Nenhum usuário banido.</p>
                    <?php else: ?>
                        <?php foreach($banned_users as $banned): ?>
                            <div class="banned-user-item">
                                <div class="banned-user-name">
                                    <i class="fas fa-ban"></i> <?php echo htmlspecialchars($banned['username']); ?>
                                </div>
                                <div class="banned-user-meta">
                                    Email: <?php echo htmlspecialchars($banned['email']); ?> | 
                                    Banido em: <?php echo date('d/m/Y H:i', strtotime($banned['banned_at'])); ?> |
                                    Por: <?php echo htmlspecialchars($banned['banned_by_name']); ?>
                                </div>
                                <?php if($banned['reason']): ?>
                                    <div class="ban-reason">
                                        <strong>Motivo:</strong> <?php echo htmlspecialchars($banned['reason']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tab Denúncias -->
        <div class="tab-content" id="reports">
            <div class="admin-content">
                <div class="form-section">
                    <h2 class="section-title">Denúncias Pendentes</h2>
                    <div class="reports-list">
                        <?php if(empty($pending_reports)): ?>
                            <p style="text-align: center; opacity: 0.7;">Nenhuma denúncia pendente.</p>
                        <?php else: ?>
                            <?php foreach($pending_reports as $report): ?>
                                <div class="report-item">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                        <div>
                                            <h4 style="color: #ffc107; margin-bottom: 5px;">
                                                <i class="fas fa-flag"></i> Denúncia #<?php echo $report['id']; ?>
                                                <span class="report-status status-pending">PENDENTE</span>
                                            </h4>
                                            <p style="opacity: 0.8; font-size: 0.9rem;">
                                                Reportado por: <strong><?php echo htmlspecialchars($report['reporter_username']); ?></strong>
                                                (<?php echo htmlspecialchars($report['reporter_email']); ?>)
                                                em <?php echo date('d/m/Y H:i', strtotime($report['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <p><strong>Usuário Reportado:</strong> 
                                           <a href="perfil.php?user_id=<?php echo $report['reported_user_id']; ?>" target="_blank" style="color: #e94560;">
                                           <?php echo htmlspecialchars($report['reported_username']); ?>
                                           </a>
                                           (<?php echo htmlspecialchars($report['reported_email']); ?>)
                                           <?php if($report['reported_role'] === 'admin'): ?>
                                               <span style="background: #e94560; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.7rem; margin-left: 5px;">ADMIN</span>
                                           <?php endif; ?>
                                        </p>
                                        <p><strong>Motivo:</strong> <?php echo htmlspecialchars($report['reason']); ?></p>
                                        <?php if($report['description']): ?>
                                            <div class="report-reason">
                                                <strong>Descrição:</strong> <?php echo nl2br(htmlspecialchars($report['description'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                        <button class="btn btn-primary btn-sm" 
                                                onclick="viewUserProfile(<?php echo $report['reported_user_id']; ?>)">
                                            <i class="fas fa-eye"></i> Ver Perfil
                                        </button>
                                        <button class="btn btn-danger btn-sm" 
                                                onclick="banReportedUser(<?php echo $report['id']; ?>, <?php echo $report['reported_user_id']; ?>, '<?php echo htmlspecialchars($report['reported_username']); ?>')">
                                            <i class="fas fa-ban"></i> Banir
                                        </button>
                                        <button class="btn btn-warning btn-sm" 
                                                onclick="markReportAsReviewed(<?php echo $report['id']; ?>)">
                                            <i class="fas fa-check"></i> Marcar como Revisado
                                        </button>
                                        <button class="btn btn-secondary btn-sm" 
                                                onclick="dismissReport(<?php echo $report['id']; ?>)">
                                            <i class="fas fa-times"></i> Ignorar
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="list-section">
                    <h2 class="section-title">Todas as Denúncias</h2>
                    <div class="reports-list">
                        <?php if(empty($all_reports)): ?>
                            <p style="text-align: center; opacity: 0.7;">Nenhuma denúncia registrada.</p>
                        <?php else: ?>
                            <?php foreach($all_reports as $report): ?>
                                <div class="report-item">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                        <div>
                                            <h4 style="color: #ffc107; margin-bottom: 5px;">
                                                <i class="fas fa-flag"></i> Denúncia #<?php echo $report['id']; ?>
                                                <span class="report-status status-<?php echo $report['status']; ?>">
                                                    <?php echo strtoupper($report['status']); ?>
                                                </span>
                                            </h4>
                                            <p style="opacity: 0.8; font-size: 0.9rem;">
                                                Reportado por: <strong><?php echo htmlspecialchars($report['reporter_username']); ?></strong>
                                                em <?php echo date('d/m/Y H:i', strtotime($report['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <p><strong>Usuário Reportado:</strong> 
                                           <?php echo htmlspecialchars($report['reported_username']); ?>
                                           (<?php echo htmlspecialchars($report['reported_email']); ?>)
                                        </p>
                                        <p><strong>Motivo:</strong> <?php echo htmlspecialchars($report['reason']); ?></p>
                                        <?php if($report['description']): ?>
                                            <div class="report-reason">
                                                <strong>Descrição:</strong> <?php echo nl2br(htmlspecialchars($report['description'])); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if($report['admin_name']): ?>
                                            <p style="font-size: 0.8rem; opacity: 0.7; margin-top: 5px;">
                                                Processado por: <?php echo htmlspecialchars($report['admin_name']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <a href="comics.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Voltar para a Loja
        </a>
    </div>

    <script>
        // Preview da capa
        document.getElementById('cover_url').addEventListener('input', function(e) {
            const url = e.target.value;
            if(url) {
                console.log('URL da capa:', url);
            }
        });
        
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            
            if(!title || !description) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios!');
                return false;
            }
        });
        
        // Confirmação para banir usuário
        function confirmBanUser(username, form) {
            const reason = form.querySelector('textarea[name="ban_reason"]').value;
            let message = `ATENÇÃO: Você está prestes a BANIR PERMANENTEMENTE o usuário "${username}".\n\n`;
            message += `Esta ação:\n`;
            message += `• Excluirá permanentemente a conta\n`;
            message += `• Removerá todos os quadrinhos do usuário\n`;
            message += `• Impedirá que ele crie nova conta com mesmo email/nome\n`;
            message += `• Não poderá ser desfeita\n\n`;
            
            if(reason) {
                message += `Motivo: ${reason}\n\n`;
            }
            
            message += `Tem certeza que deseja continuar?`;
            
            return confirm(message);
        }

        // Sistema de Tabs
        document.querySelectorAll('.admin-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Remover active de todas as tabs
                document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
                // Adicionar active na tab clicada
                tab.classList.add('active');
                
                // Mostrar conteúdo correspondente
                const tabName = tab.getAttribute('data-tab');
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById(tabName).classList.add('active');
            });
        });

        // Funções para gerenciar denúncias
        function viewUserProfile(userId) {
            window.open(`perfil.php?user_id=${userId}`, '_blank');
        }

        function banReportedUser(reportId, userId, username) {
            const reason = prompt(`Digite o motivo do banimento para ${username}:`);
            if(reason !== null && reason.trim() !== '') {
                if(confirm(`Banir permanentemente ${username}? Esta ação não pode ser desfeita.`)) {
                    // Fazer requisição para banir o usuário
                    const formData = new FormData();
                    formData.append('ban_user', '1');
                    formData.append('user_id', userId);
                    formData.append('ban_reason', reason);
                    
                    fetch('admin.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(() => {
                        // Marcar report como resolvido
                        markReportAsResolved(reportId, 'resolved');
                        alert('Usuário banido com sucesso!');
                        location.reload();
                    })
                    .catch(error => {
                        alert('Erro ao banir usuário: ' + error);
                    });
                }
            } else if(reason !== null) {
                alert('Por favor, informe o motivo do banimento.');
            }
        }

        function markReportAsReviewed(reportId) {
            if(confirm('Marcar esta denúncia como revisada?')) {
                updateReportStatus(reportId, 'reviewed');
            }
        }

        function dismissReport(reportId) {
            if(confirm('Ignorar esta denúncia?')) {
                updateReportStatus(reportId, 'dismissed');
            }
        }

        function markReportAsResolved(reportId, status = 'resolved') {
            updateReportStatus(reportId, status);
        }

        function updateReportStatus(reportId, status) {
            const formData = new FormData();
            formData.append('update_report_status', '1');
            formData.append('report_id', reportId);
            formData.append('status', status);
            
            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if(response.ok) {
                    location.reload();
                } else {
                    alert('Erro ao atualizar status do report');
                }
            })
            .catch(error => {
                alert('Erro: ' + error);
            });
        }
        
        console.log('Painel Admin carregado com sucesso!');
    </script>
</body>
</html>
