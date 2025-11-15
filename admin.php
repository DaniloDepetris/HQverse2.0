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

// Processar remoção de quadrinho
if($_POST && isset($_POST['delete_comic'])) {
    $comic_id = $_POST['comic_id'] ?? '';
    
    if(!empty($comic_id)) {
        $result = deleteComic($comic_id, $_SESSION['user_id']);
        if($result === true) {
            $success = "Quadrinho removido com sucesso!";
            // Recarregar a lista de quadrinhos
            $comics = getComics();
        } else {
            $error = $result;
        }
    } else {
        $error = "ID do quadrinho não especificado!";
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

// Processar solicitação de criador
if($_POST && isset($_POST['process_creator_request'])) {
    $request_id = $_POST['request_id'] ?? '';
    $action = $_POST['action'] ?? ''; // 'approve' ou 'reject'
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    if(!empty($request_id) && !empty($action)) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $result = $auth->processCreatorRequest($request_id, $status, $_SESSION['user_id'], $admin_notes);
        
        if($result === true) {
            $success = "Solicitação " . ($action === 'approve' ? 'aprovada' : 'rejeitada') . " com sucesso!";
        } else {
            $error = $result;
        }
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

// Função para deletar quadrinho - VERSÃO CORRIGIDA
function deleteComic($comic_id, $admin_id) {
    require_once 'config_database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        // Verificar se o quadrinho existe
        $check_query = "SELECT id, title FROM comics WHERE id = :comic_id";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bindParam(":comic_id", $comic_id);
        $check_stmt->execute();
        
        if($check_stmt->rowCount() === 0) {
            return "Quadrinho não encontrado!";
        }
        
        $comic = $check_stmt->fetch(PDO::FETCH_ASSOC);
        $comic_title = $comic['title'];
        
        // Iniciar transação
        $conn->beginTransaction();
        
        // 1. Deletar comic_categories
        $delete_categories = $conn->prepare("DELETE FROM comic_categories WHERE comic_id = :comic_id");
        $delete_categories->bindParam(":comic_id", $comic_id);
        $delete_categories->execute();
        
        // 2. Deletar comic_pages
        $delete_pages = $conn->prepare("DELETE FROM comic_pages WHERE comic_id = :comic_id");
        $delete_pages->bindParam(":comic_id", $comic_id);
        $delete_pages->execute();
        
        // 3. Deletar favorites
        $delete_favorites = $conn->prepare("DELETE FROM favorites WHERE comic_id = :comic_id");
        $delete_favorites->bindParam(":comic_id", $comic_id);
        $delete_favorites->execute();
        
        // 4. Deletar reviews
        $delete_reviews = $conn->prepare("DELETE FROM reviews WHERE comic_id = :comic_id");
        $delete_reviews->bindParam(":comic_id", $comic_id);
        $delete_reviews->execute();
        
        // 5. Deletar reading_progress
        $delete_progress = $conn->prepare("DELETE FROM reading_progress WHERE comic_id = :comic_id");
        $delete_progress->bindParam(":comic_id", $comic_id);
        $delete_progress->execute();
        
        // 6. Deletar comic_comments
        $delete_comments = $conn->prepare("DELETE FROM comic_comments WHERE comic_id = :comic_id");
        $delete_comments->bindParam(":comic_id", $comic_id);
        $delete_comments->execute();
        
        // 7. Finalmente deletar o quadrinho
        $delete_comic = $conn->prepare("DELETE FROM comics WHERE id = :comic_id");
        $delete_comic->bindParam(":comic_id", $comic_id);
        $delete_comic->execute();
        
        // Commit da transação
        $conn->commit();
        
        // Log para debug
        error_log("Quadrinho deletado com sucesso: ID $comic_id - '$comic_title'");
        return true;
        
    } catch (PDOException $e) {
        // Rollback em caso de erro
        $conn->rollBack();
        error_log("Erro ao deletar quadrinho ID $comic_id: " . $e->getMessage());
        return "Erro ao remover quadrinho: " . $e->getMessage();
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

// Obter estatísticas do sistema
function getSystemStats() {
    require_once 'config_database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        $stats = [];
        
        // Total de usuários
        $query = "SELECT COUNT(*) as total FROM users";
        $stmt = $conn->query($query);
        $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de quadrinhos
        $query = "SELECT COUNT(*) as total FROM comics";
        $stmt = $conn->query($query);
        $stats['total_comics'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de reports pendentes
        $query = "SELECT COUNT(*) as total FROM user_reports WHERE status = 'pending'";
        $stmt = $conn->query($query);
        $stats['pending_reports'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de usuários banidos
        $query = "SELECT COUNT(*) as total FROM banned_users";
        $stmt = $conn->query($query);
        $stats['banned_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de solicitações de criador pendentes
        $query = "SELECT COUNT(*) as total FROM creator_requests WHERE status = 'pending'";
        $stmt = $conn->query($query);
        $stats['pending_creator_requests'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Usuários novos hoje
        $query = "SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()";
        $stmt = $conn->query($query);
        $stats['new_users_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return $stats;
        
    } catch(PDOException $e) {
        return [
            'total_users' => 0,
            'total_comics' => 0,
            'pending_reports' => 0,
            'banned_users' => 0,
            'pending_creator_requests' => 0,
            'new_users_today' => 0
        ];
    }
}

$comics = getComics();
$users = $auth->getAllUsersForAdmin();
$banned_users = $auth->getBannedUsers();
$pending_reports = getPendingReports();
$all_reports = getAllReports();
$creator_requests = $auth->getPendingCreatorRequests();
$all_creator_requests = $auth->getAllCreatorRequests();
$system_stats = getSystemStats();
$unread_notifications = $auth->getUnreadAdminNotifications();

// Verificar se deve manter a aba ativa após POST
$active_tab = 'dashboard';
if($_POST && isset($_POST['search_users'])) {
    $active_tab = 'users';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - HQ Verso</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* [MANTENHA TODO O CSS ANTERIOR - É O MESMO] */
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

        [data-theme="light"] {
            --bg-primary: #f8f9fa;
            --bg-secondary: #e9ecef;
            --bg-card: rgba(255, 255, 255, 0.95);
            --text-primary: #2c3e50;
            --text-secondary: #6c757d;
            --accent-color: #e94560;
            --accent-hover: #d8345f;
            --border-color: rgba(0, 0, 0, 0.1);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        [data-theme="light"] body {
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            color: var(--text-primary);
        }

        [data-theme="light"] .form-section,
        [data-theme="light"] .list-section {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
        }

        [data-theme="light"] .form-group input,
        [data-theme="light"] .form-group textarea,
        [data-theme="light"] .form-group select {
            background: rgba(0, 0, 0, 0.05);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        [data-theme="light"] .comic-item,
        [data-theme="light"] .user-item,
        [data-theme="light"] .banned-user-item,
        [data-theme="light"] .report-item,
        [data-theme="light"] .creator-request-item {
            background: rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }

        [data-theme="light"] .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
        }

        [data-theme="light"] .nav-btn {
            background: rgba(0, 0, 0, 0.1);
            color: var(--text-primary);
        }

        [data-theme="light"] .nav-btn:hover,
        [data-theme="light"] .nav-btn.active {
            background: var(--accent-color);
            color: white;
        }

        [data-theme="light"] .back-btn {
            background: rgba(0, 0, 0, 0.1);
            color: var(--text-primary);
        }

        [data-theme="light"] .back-btn:hover {
            background: var(--accent-color);
            color: white;
        }

        [data-theme="light"] .admin-tabs {
            border-bottom: 1px solid var(--border-color);
        }

        [data-theme="light"] .ban-reason {
            background: rgba(220, 53, 69, 0.1);
            border-left: 3px solid #dc3545;
        }

        [data-theme="light"] .report-reason {
            background: rgba(255, 193, 7, 0.1);
            border-left: 3px solid #ffc107;
        }

        [data-theme="light"] .creator-request-info {
            background: rgba(0, 123, 255, 0.1);
            border-left: 3px solid #007bff;
        }
        
        .admin-container {
            max-width: 1400px;
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

        .theme-toggle {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            background: #667eea;
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

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }
        
        .comics-list, .users-list, .banned-users-list, .reports-list, .creator-requests-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .comic-item, .user-item, .banned-user-item, .report-item, .creator-request-item {
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

        .creator-request-item {
            border-left: 4px solid #17a2b8;
        }

        .user-item:hover, .banned-user-item:hover, .report-item:hover, .creator-request-item:hover {
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
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
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

        .stat-trend {
            font-size: 0.8rem;
            margin-top: 5px;
        }

        .trend-up {
            color: #28a745;
        }

        .trend-down {
            color: #dc3545;
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

        .btn-info {
            background: #17a2b8;
            color: white;
            padding: 8px 15px;
            font-size: 0.9rem;
        }

        .btn-info:hover {
            background: #138496;
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

        .creator-request-info {
            margin-top: 10px;
            padding: 10px;
            background: rgba(0, 123, 255, 0.1);
            border-radius: 5px;
            border-left: 3px solid #007bff;
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
            flex-wrap: wrap;
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

        .creator-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 10px;
        }

        .status-approved {
            background: #28a745;
            color: white;
        }

        .status-rejected {
            background: #dc3545;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            margin-top: 10px;
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

            .stats-section {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons .btn {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .admin-container {
                padding: 10px;
            }
            
            .admin-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .admin-nav {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <a href="comics.php" class="logo">HQ VERSO - PAINEL ADMIN</a>
            <div class="admin-nav">
                <a href="comics.php" class="nav-btn">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
                <button class="theme-toggle" id="themeToggle" title="Alternar Tema">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>
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
                <div class="stat-number"><?php echo $system_stats['total_users']; ?></div>
                <div class="stat-label">Total de Usuários</div>
                <div class="stat-trend trend-up">
                    <i class="fas fa-user-plus"></i> +<?php echo $system_stats['new_users_today']; ?> hoje
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $system_stats['total_comics']; ?></div>
                <div class="stat-label">Quadrinhos Cadastrados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $system_stats['banned_users']; ?></div>
                <div class="stat-label">Usuários Banidos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $system_stats['pending_reports']; ?></div>
                <div class="stat-label">Denúncias Pendentes</div>
                <?php if($system_stats['pending_reports'] > 0): ?>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-exclamation-triangle"></i> Atenção
                    </div>
                <?php endif; ?>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $system_stats['pending_creator_requests']; ?></div>
                <div class="stat-label">Solicitações Criador</div>
                <?php if($system_stats['pending_creator_requests'] > 0): ?>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-clock"></i> Pendentes
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sistema de Tabs -->
        <div class="admin-tabs">
            <div class="admin-tab <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>" data-tab="dashboard">Dashboard</div>
            <div class="admin-tab <?php echo $active_tab === 'comics' ? 'active' : ''; ?>" data-tab="comics">Gerenciar Quadrinhos</div>
            <div class="admin-tab <?php echo $active_tab === 'users' ? 'active' : ''; ?>" data-tab="users">Gerenciar Usuários</div>
            <div class="admin-tab" data-tab="banned">Usuários Banidos</div>
            <div class="admin-tab" data-tab="reports">
                Denúncias
                <?php if(count($pending_reports) > 0): ?>
                    <span class="notification-badge"><?php echo count($pending_reports); ?></span>
                <?php endif; ?>
            </div>
            <div class="admin-tab" data-tab="creator-requests">
                Solicitações Criador
                <?php if(count($creator_requests) > 0): ?>
                    <span class="notification-badge"><?php echo count($creator_requests); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Dashboard -->
        <div class="tab-content <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>" id="dashboard">
            <div class="admin-content">
                <div class="form-section">
                    <h2 class="section-title">Visão Geral do Sistema</h2>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div style="background: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 2rem; color: #e94560; margin-bottom: 10px;">
                                <i class="fas fa-users"></i>
                            </div>
                            <div style="font-weight: 600;">Comunidade Ativa</div>
                            <div style="font-size: 0.9rem; opacity: 0.8;"><?php echo $system_stats['total_users']; ?> usuários registrados</div>
                        </div>
                        <div style="background: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 10px; text-align: center;">
                            <div style="font-size: 2rem; color: #28a745; margin-bottom: 10px;">
                                <i class="fas fa-book"></i>
                            </div>
                            <div style="font-weight: 600;">Conteúdo</div>
                            <div style="font-size: 0.9rem; opacity: 0.8;"><?php echo $system_stats['total_comics']; ?> quadrinhos publicados</div>
                        </div>
                    </div>

                    <h3 style="margin: 20px 0 10px 0; color: #e94560;">Ações Rápidas</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <button class="btn btn-primary" onclick="switchTab('comics')">
                            <i class="fas fa-book"></i> Gerenciar Quadrinhos
                        </button>
                        <button class="btn btn-warning" onclick="switchTab('reports')">
                            <i class="fas fa-flag"></i> Ver Denúncias
                        </button>
                        <button class="btn btn-info" onclick="switchTab('creator-requests')">
                            <i class="fas fa-palette"></i> Solicitações Criador
                        </button>
                        <button class="btn btn-secondary" onclick="switchTab('users')">
                            <i class="fas fa-search"></i> Pesquisar Usuários
                        </button>
                    </div>
                </div>
                
                <div class="list-section">
                    <h2 class="section-title">Atividade Recente</h2>
                    <div class="reports-list">
                        <h3 style="color: #ffc107; margin-bottom: 15px;">
                            <i class="fas fa-exclamation-triangle"></i> Denúncias Pendentes
                        </h3>
                        <?php if(empty($pending_reports)): ?>
                            <p style="text-align: center; opacity: 0.7; padding: 20px;">Nenhuma denúncia pendente.</p>
                        <?php else: ?>
                            <?php foreach(array_slice($pending_reports, 0, 3) as $report): ?>
                                <div class="report-item">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h4 style="color: #ffc107; margin-bottom: 5px;">
                                                <?php echo htmlspecialchars($report['reported_username']); ?>
                                                <span class="report-status status-pending">PENDENTE</span>
                                            </h4>
                                            <p style="opacity: 0.8; font-size: 0.9rem;">
                                                Reportado por: <?php echo htmlspecialchars($report['reporter_username']); ?>
                                            </p>
                                        </div>
                                        <button class="btn btn-warning btn-sm" onclick="switchTab('reports')">
                                            <i class="fas fa-eye"></i> Ver
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if(count($pending_reports) > 3): ?>
                                <div style="text-align: center; margin-top: 10px;">
                                    <button class="btn btn-secondary btn-sm" onclick="switchTab('reports')">
                                        Ver todas as <?php echo count($pending_reports); ?> denúncias
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <h3 style="color: #17a2b8; margin: 25px 0 15px 0;">
                            <i class="fas fa-palette"></i> Solicitações de Criador
                        </h3>
                        <?php if(empty($creator_requests)): ?>
                            <p style="text-align: center; opacity: 0.7; padding: 20px;">Nenhuma solicitação pendente.</p>
                        <?php else: ?>
                            <?php foreach(array_slice($creator_requests, 0, 3) as $request): ?>
                                <div class="creator-request-item">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h4 style="color: #17a2b8; margin-bottom: 5px;">
                                                <?php echo htmlspecialchars($request['username']); ?>
                                            </h4>
                                            <p style="opacity: 0.8; font-size: 0.9rem;">
                                                Idade: <?php echo $request['age']; ?> anos
                                            </p>
                                        </div>
                                        <button class="btn btn-info btn-sm" onclick="switchTab('creator-requests')">
                                            <i class="fas fa-eye"></i> Ver
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if(count($creator_requests) > 3): ?>
                                <div style="text-align: center; margin-top: 10px;">
                                    <button class="btn btn-secondary btn-sm" onclick="switchTab('creator-requests')">
                                        Ver todas as <?php echo count($creator_requests); ?> solicitações
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Quadrinhos - APENAS LISTA E REMOÇÃO -->
        <div class="tab-content <?php echo $active_tab === 'comics' ? 'active' : ''; ?>" id="comics">
            <div class="list-section" style="grid-column: 1 / -1;">
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
                                <!-- BOTÃO DE REMOÇÃO -->
                                <form method="POST" style="margin-top: 10px;" 
                                      onsubmit="return confirmDeleteComic('<?php echo htmlspecialchars($comic['title']); ?>')">
                                    <input type="hidden" name="comic_id" value="<?php echo $comic['id']; ?>">
                                    <button type="submit" name="delete_comic" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Remover Quadrinho
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tab Gerenciar Usuários -->
        <div class="tab-content <?php echo $active_tab === 'users' ? 'active' : ''; ?>" id="users">
            <div class="admin-content">
                <div class="form-section">
                    <h2 class="section-title">Pesquisar Usuários</h2>
                    <form method="POST" class="search-form" id="searchUsersForm">
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
                                        <?php elseif($user['role'] === 'creator'): ?>
                                            <span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 3px; font-size: 0.8rem; margin-left: 10px;">CRIADOR</span>
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
  <!-- Tab Gerenciar Usuários -->
        <div class="tab-content <?php echo $active_tab === 'users' ? 'active' : ''; ?>" id="users">
            <div class="admin-content">
                <div class="form-section">
                    <h2 class="section-title">Pesquisar Usuários</h2>
                    <form method="POST" class="search-form" id="searchUsersForm">
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
                                        <?php elseif($user['role'] === 'creator'): ?>
                                            <span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 3px; font-size: 0.8rem; margin-left: 10px;">CRIADOR</span>
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
                                    
                                    <div class="action-buttons">
                                        <button class="btn btn-primary btn-sm" 
                                                onclick="viewUserProfile(<?php echo $report['reported_user_id']; ?>)">
                                            <i class="fas fa-eye"></i> Ver Perfil
                                        </button>
                                        <button class="btn btn-danger btn-sm" 
                                                onclick="banReportedUser(<?php echo $report['id']; ?>, <?php echo $report['reported_user_id']; ?>, '<?php echo htmlspecialchars($report['reported_username']); ?>')">
                                            <i class="fas fa-ban"></i> Banir
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="update_report_status" value="1">
                                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                            <input type="hidden" name="status" value="reviewed">
                                            <button type="submit" class="btn btn-warning btn-sm">
                                                <i class="fas fa-check"></i> Revisado
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="update_report_status" value="1">
                                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                            <input type="hidden" name="status" value="dismissed">
                                            <button type="submit" class="btn btn-secondary btn-sm">
                                                <i class="fas fa-times"></i> Ignorar
                                            </button>
                                        </form>
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

        <!-- Tab Solicitações Criador -->
        <div class="tab-content" id="creator-requests">
            <div class="admin-content">
                <div class="form-section">
                    <h2 class="section-title">Solicitações Pendentes</h2>
                    <div class="creator-requests-list">
                        <?php if(empty($creator_requests)): ?>
                            <p style="text-align: center; opacity: 0.7;">Nenhuma solicitação pendente.</p>
                        <?php else: ?>
                            <?php foreach($creator_requests as $request): ?>
                                <div class="creator-request-item">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                        <div>
                                            <h4 style="color: #17a2b8; margin-bottom: 5px;">
                                                <i class="fas fa-user-plus"></i> Solicitação #<?php echo $request['id']; ?>
                                            </h4>
                                            <p style="opacity: 0.8; font-size: 0.9rem;">
                                                Usuário: <strong><?php echo htmlspecialchars($request['username']); ?></strong>
                                                (<?php echo htmlspecialchars($request['email']); ?>)
                                                em <?php echo date('d/m/Y H:i', strtotime($request['requested_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <p><strong>CPF:</strong> <?php echo htmlspecialchars($request['cpf']); ?></p>
                                        <p><strong>Idade:</strong> <?php echo $request['age']; ?> anos</p>
                                        <p><strong>Endereço:</strong> <?php echo nl2br(htmlspecialchars($request['address'])); ?></p>
                                    </div>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="process_creator_request" value="1">
                                        
                                        <div class="form-group">
                                            <label for="admin_notes_<?php echo $request['id']; ?>" style="font-size: 0.9rem;">Observações (opcional):</label>
                                            <textarea id="admin_notes_<?php echo $request['id']; ?>" name="admin_notes" 
                                                      placeholder="Informe observações sobre a decisão..."
                                                      style="font-size: 0.9rem; height: 60px; width: 100%;"></textarea>
                                        </div>
                                        
                                        <div class="action-buttons">
                                            <button type="submit" name="action" value="approve" class="btn btn-success"
                                                    onclick="return confirm('Aprovar solicitação de criador para <?php echo htmlspecialchars($request['username']); ?>?')">
                                                <i class="fas fa-check"></i> Aprovar
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger"
                                                    onclick="return confirm('Rejeitar solicitação de criador de <?php echo htmlspecialchars($request['username']); ?>?')">
                                                <i class="fas fa-times"></i> Rejeitar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="list-section">
                    <h2 class="section-title">Todas as Solicitações</h2>
                    <div class="creator-requests-list">
                        <?php if(empty($all_creator_requests)): ?>
                            <p style="text-align: center; opacity: 0.7;">Nenhuma solicitação registrada.</p>
                        <?php else: ?>
                            <?php foreach($all_creator_requests as $request): ?>
                                <div class="creator-request-item">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                        <div>
                                            <h4 style="color: #17a2b8; margin-bottom: 5px;">
                                                <i class="fas fa-user-plus"></i> Solicitação #<?php echo $request['id']; ?>
                                                <span class="creator-status status-<?php echo $request['status']; ?>">
                                                    <?php echo strtoupper($request['status']); ?>
                                                </span>
                                            </h4>
                                            <p style="opacity: 0.8; font-size: 0.9rem;">
                                                Usuário: <strong><?php echo htmlspecialchars($request['username']); ?></strong>
                                                em <?php echo date('d/m/Y H:i', strtotime($request['requested_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <p><strong>CPF:</strong> <?php echo htmlspecialchars($request['cpf']); ?></p>
                                        <p><strong>Idade:</strong> <?php echo $request['age']; ?> anos</p>
                                        <p><strong>Status:</strong> 
                                            <span style="color: <?php echo $request['status'] === 'approved' ? '#28a745' : ($request['status'] === 'rejected' ? '#dc3545' : '#ffc107'); ?>;">
                                                <?php echo $request['status'] === 'approved' ? 'Aprovado' : ($request['status'] === 'rejected' ? 'Rejeitado' : 'Pendente'); ?>
                                            </span>
                                        </p>
                                        <?php if($request['admin_notes']): ?>
                                            <div class="creator-request-info">
                                                <strong>Observações:</strong> <?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if($request['admin_name']): ?>
                                            <p style="font-size: 0.8rem; opacity: 0.7; margin-top: 5px;">
                                                Processado por: <?php echo htmlspecialchars($request['admin_name']); ?>
                                                <?php if($request['processed_at']): ?>
                                                    em <?php echo date('d/m/Y H:i', strtotime($request['processed_at'])); ?>
                                                <?php endif; ?>
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
    </div>

    <script>
        // Sistema de Tema
        function initializeTheme() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = document.getElementById('themeIcon');
            
            // Aplicar tema salvo
            document.documentElement.setAttribute('data-theme', savedTheme);
            updateThemeIcon(savedTheme, themeIcon);
            
            // Event listener para alternar tema
            themeToggle.addEventListener('click', () => {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeIcon(newTheme, themeIcon);
            });
        }

        function updateThemeIcon(theme, iconElement) {
            if (theme === 'dark') {
                iconElement.className = 'fas fa-moon';
                iconElement.title = 'Modo Escuro';
            } else {
                iconElement.className = 'fas fa-sun';
                iconElement.title = 'Modo Claro';
            }
        }
        
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

        // Confirmação para deletar quadrinho
        function confirmDeleteComic(comicTitle) {
            let message = `ATENÇÃO: Você está prestes a REMOVER PERMANENTEMENTE o quadrinho "${comicTitle}".\n\n`;
            message += `Esta ação:\n`;
            message += `• Excluirá permanentemente o quadrinho\n`;
            message += `• Removerá todas as páginas e capa\n`;
            message += `• Excluirá todos os comentários e avaliações\n`;
            message += `• Removerá de todas as bibliotecas de usuários\n`;
            message += `• Não poderá ser desfeita\n\n`;
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
                
                // Salvar aba ativa
                sessionStorage.setItem('activeTab', tabName);
            });
        });

        // Função para alternar entre tabs
        function switchTab(tabName) {
            document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            document.querySelector(`.admin-tab[data-tab="${tabName}"]`).classList.add('active');
            document.getElementById(tabName).classList.add('active');
            
            // Salvar aba ativa
            sessionStorage.setItem('activeTab', tabName);
        }

        // Restaurar aba ativa após submit
        document.addEventListener('DOMContentLoaded', function() {
            initializeTheme();
            
            // Restaurar aba ativa do sessionStorage
            const savedTab = sessionStorage.getItem('activeTab');
            if (savedTab) {
                switchTab(savedTab);
            }
            
            // Para formulário de pesquisa de usuários, manter na aba users
            const searchForm = document.getElementById('searchUsersForm');
            if (searchForm) {
                searchForm.addEventListener('submit', function() {
                    sessionStorage.setItem('activeTab', 'users');
                });
            }
        });

        // [MANTENHA AS OUTRAS FUNÇÕES JAVASCRIPT]
        console.log('Painel Admin carregado com sucesso!');
    </script>
</body>
</html>
