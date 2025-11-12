<?php
require_once 'includes_auth.php';

if(!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Verificar se está visualizando outro perfil ou o próprio
$viewing_own_profile = true;
$profile_user_id = $_SESSION['user_id'];

if(isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $requested_user_id = intval($_GET['user_id']);
    
    // Não permitir visualizar o próprio perfil via user_id
    if($requested_user_id != $_SESSION['user_id']) {
        $profile_user_id = $requested_user_id;
        $viewing_own_profile = false;
    }
}

// Obter dados do usuário do perfil
$user_data = $auth->getUserData($profile_user_id);
if(!$user_data) {
    header("Location: perfil.php");
    exit();
}

$initial = strtoupper(substr($user_data['username'], 0, 1));

// Obter estatísticas de seguidores
$follow_stats = $auth->getFollowStats($profile_user_id);
$followers_count = $follow_stats['followers_count'];
$following_count = $follow_stats['following_count'];

// Verificar se o usuário atual está seguindo o perfil visualizado
$is_following = false;
if(!$viewing_own_profile) {
    $is_following = $auth->isFollowing($_SESSION['user_id'], $profile_user_id);
}

// Obter lista de seguidores e seguindo
$followers_list = $auth->getFollowers($profile_user_id, 50);
$following_list = $auth->getFollowing($profile_user_id, 50);

// Processar remoção de avatar
if($viewing_own_profile && $_POST && isset($_POST['remove_avatar'])) {
    $result = $auth->removeAvatar($_SESSION['user_id']);
    if($result) {
        $success = "Foto de perfil removida com sucesso!";
        $user_data = $auth->getUserData($_SESSION['user_id']);
        $initial = strtoupper(substr($user_data['username'], 0, 1));
    } else {
        $error = "Erro ao remover foto de perfil!";
    }
}

// Processar alterações do perfil
$success = '';
$error = '';

// Processar edição de perfil
if($viewing_own_profile && $_POST && isset($_POST['update_profile'])) {
    $username = trim($_POST['username'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    if(empty($username)) {
        $error = "O nome de usuário não pode estar vazio!";
    } else {
        $result = $auth->updateProfile($_SESSION['user_id'], $username, $user_data['email'], $bio);
        if($result === true) {
            $success = "Perfil atualizado com sucesso!";
            $user_data = $auth->getUserData($_SESSION['user_id']);
            $initial = strtoupper(substr($user_data['username'], 0, 1));
        } else {
            $error = $result;
        }
    }
}

// Processar seguir/deseguir do perfil principal
if(!$viewing_own_profile && $_POST && isset($_POST['toggle_follow'])) {
    if($is_following) {
        $result = $auth->unfollowUser($_SESSION['user_id'], $profile_user_id);
        if($result === true) {
            $success = "Deixou de seguir " . $user_data['username'] . "!";
            $is_following = false;
            $followers_count--;
        } else {
            $error = $result;
        }
    } else {
        $result = $auth->followUser($_SESSION['user_id'], $profile_user_id);
        if($result === true) {
            $success = "Agora você está seguindo " . $user_data['username'] . "!";
            $is_following = true;
            $followers_count++;
        } else {
            $error = $result;
        }
    }
}

// Processar seguir/deseguir da lista de seguidores
if($_POST && isset($_POST['toggle_follow_user'])) {
    $target_user_id = intval($_POST['target_user_id'] ?? 0);
    $current_action = $_POST['current_action'] ?? ''; // 'follow' ou 'unfollow'
    
    if($target_user_id && $target_user_id != $_SESSION['user_id']) {
        if($current_action === 'follow') {
            $result = $auth->followUser($_SESSION['user_id'], $target_user_id);
            if($result === true) {
                $success = "Agora você está seguindo este usuário!";
            } else {
                $error = $result;
            }
        } else {
            $result = $auth->unfollowUser($_SESSION['user_id'], $target_user_id);
            if($result === true) {
                $success = "Deixou de seguir este usuário!";
            } else {
                $error = $result;
            }
        }
    }
}

// Processar exclusão de conta
if($viewing_own_profile && $_POST && isset($_POST['delete_account'])) {
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if(empty($confirm_password)) {
        $error = "Por favor, digite sua senha para confirmar a exclusão!";
    } else {
        $result = $auth->deleteUserAccount($_SESSION['user_id'], $_SESSION['user_id']);
        if($result === true) {
            header("Location: login.php?message=conta_excluida");
            exit();
        } else {
            $error = $result;
        }
    }
}

// Processar report de usuário
if(!$viewing_own_profile && $_POST && isset($_POST['report_user'])) {
    $reported_user_id = $_POST['reported_user_id'] ?? '';
    $report_reason = $_POST['report_reason'] ?? '';
    $report_description = trim($_POST['report_description'] ?? '');
    
    if(empty($report_reason)) {
        $error = "Por favor, selecione um motivo para o report!";
    } else {
        $result = $auth->reportUser($reported_user_id, $_SESSION['user_id'], $report_reason, $report_description);
        if($result === true) {
            $success = "Usuário reportado com sucesso! Os administradores irão revisar o caso.";
        } else {
            $error = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $viewing_own_profile ? 'Meu Perfil' : 'Perfil de ' . htmlspecialchars($user_data['username']); ?> - HQ Verso</title>
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
            transition: all 0.3s ease; 
        }
        
        body { 
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%); 
            color: var(--text-primary); 
            min-height: 100vh; 
            padding: 20px; 
            line-height: 1.5;
            overflow-x: hidden;
        }
        
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            position: relative;
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
            letter-spacing: -0.5px;
        }
        
        .header-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-end;
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
            font-size: 0.95rem;
        }
        
        .back-btn:hover {
            background: var(--accent-color);
            color: white;
            transform: translateX(-5px);
            border-color: var(--accent-color);
        }

        /* Botões de Ação */
        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            background: linear-gradient(135deg, var(--accent-color) 0%, var(--accent-hover) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(233, 69, 96, 0.3);
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(233, 69, 96, 0.4);
        }

        .theme-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .menu-btn {
            background: linear-gradient(135deg, var(--accent-color) 0%, var(--accent-hover) 100%);
        }

        /* Menu Lateral */
        .side-menu {
            position: fixed;
            top: 0;
            right: -400px;
            width: 350px;
            height: 100vh;
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border-left: 1px solid rgba(233, 69, 96, 0.3);
            padding: 30px;
            overflow-y: auto;
            transition: right 0.4s ease;
            z-index: 999;
            box-shadow: var(--shadow);
        }

        .side-menu.active {
            right: 0;
        }

        .menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .menu-title {
            color: var(--accent-color);
            font-size: 1.4rem;
            font-weight: 700;
        }

        .close-menu {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .close-menu:hover {
            background: rgba(233, 69, 96, 0.2);
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--text-primary);
            text-decoration: none;
        }

        .menu-item:hover {
            background: rgba(233, 69, 96, 0.1);
            transform: translateX(5px);
        }

        .menu-item i {
            width: 20px;
            text-align: center;
            color: var(--accent-color);
        }

        .menu-section {
            margin: 25px 0 15px 0;
            color: var(--accent-color);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding-left: 20px;
        }

        /* Conteúdo Principal do Perfil */
        .profile-main {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .profile-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-color) 0%, var(--accent-hover) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            margin: 0 auto 20px auto;
            position: relative;
            overflow: hidden;
            border: 4px solid var(--accent-color);
            box-shadow: 0 8px 25px rgba(233, 69, 96, 0.3);
            cursor: <?php echo $viewing_own_profile ? 'pointer' : 'default'; ?>;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
            color: white;
            font-size: 1.5rem;
        }

        .profile-avatar:hover .avatar-overlay {
            opacity: 1;
        }

        .remove-avatar-btn {
            margin-top: 10px;
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 2px solid #dc3545;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .remove-avatar-btn:hover {
            background: #dc3545;
            color: white;
            transform: translateY(-2px);
        }

        .profile-name {
            font-size: 1.8rem;
            color: var(--accent-color);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }

        .stat {
            text-align: center;
            padding: 15px;
            background: rgba(15, 52, 96, 0.3);
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        [data-theme="light"] .stat {
            background: rgba(233, 69, 96, 0.1);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--accent-color);
            display: block;
        }

        .stat-label {
            font-size: 0.8rem;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .profile-bio {
            margin-top: 20px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            text-align: left;
            line-height: 1.6;
        }

        [data-theme="light"] .profile-bio {
            background: rgba(0, 0, 0, 0.05);
        }

        .bio-title {
            color: var(--accent-color);
            margin-bottom: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Ações do Perfil */
        .profile-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn { 
            padding: 12px 24px; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer; 
            border: none; 
            font-size: 0.9rem; 
            text-decoration: none; 
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, var(--accent-color) 0%, var(--accent-hover) 100%);
            color: white; 
            box-shadow: 0 4px 15px rgba(233, 69, 96, 0.3);
        }
        
        .btn-primary:hover { 
            background: linear-gradient(135deg, var(--accent-hover) 0%, #c7224e 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(233, 69, 96, 0.4);
        }
        
        .btn-outline { 
            background: transparent; 
            border: 2px solid var(--accent-color); 
            color: var(--accent-color); 
        }
        
        .btn-outline:hover { 
            background: var(--accent-color); 
            color: white;
            transform: translateY(-2px);
        }

        .btn-report { 
            background: rgba(220, 53, 69, 0.1); 
            border: 2px solid #dc3545; 
            color: #dc3545; 
        }
        
        .btn-report:hover { 
            background: rgba(220, 53, 69, 0.2); 
            transform: translateY(-2px);
        }

        /* Modal de Edição */
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.8); 
            z-index: 1000; 
            align-items: center; 
            justify-content: center; 
            padding: 20px;
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content { 
            background: var(--bg-primary); 
            padding: 30px; 
            border-radius: 20px; 
            max-width: 500px; 
            width: 100%; 
            max-height: 80vh; 
            overflow-y: auto; 
            box-shadow: var(--shadow);
            border: 1px solid rgba(233, 69, 96, 0.3);
            animation: scaleIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes scaleIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 25px; 
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .modal-title { 
            color: var(--accent-color); 
            font-size: 1.4rem; 
            font-weight: 700; 
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close-modal { 
            background: none; 
            border: none; 
            color: var(--text-primary); 
            font-size: 1.8rem; 
            cursor: pointer; 
            padding: 5px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .close-modal:hover {
            background: rgba(233, 69, 96, 0.2);
        }

        .form-group { 
            margin-bottom: 20px; 
        }
        
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600;
            color: var(--accent-color);
            font-size: 0.9rem;
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
            font-size: 0.9rem; 
            transition: all 0.3s ease;
        }

        [data-theme="light"] .form-group input,
        [data-theme="light"] .form-group textarea,
        [data-theme="light"] .form-group select {
            background: rgba(0, 0, 0, 0.05);
            color: var(--text-primary);
        }
        
        .form-group input:focus, 
        .form-group textarea:focus,
        .form-group select:focus { 
            outline: none; 
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.2);
            background: rgba(255, 255, 255, 0.12);
        }

        [data-theme="light"] .form-group input:focus,
        [data-theme="light"] .form-group textarea:focus,
        [data-theme="light"] .form-group select:focus {
            background: rgba(0, 0, 0, 0.08);
        }
        
        .form-group textarea { 
            height: 100px; 
            resize: vertical; 
            line-height: 1.5;
        }

        /* Estilos para a lista de seguidores/seguindo */
        .follow-list {
            max-height: 400px;
            overflow-y: auto;
            margin-top: 20px;
        }
        
        .follow-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .follow-tab {
            padding: 12px 24px;
            background: none;
            border: none;
            color: var(--text-primary);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .follow-tab.active {
            color: var(--accent-color);
            border-bottom-color: var(--accent-color);
        }
        
        .follow-tab:hover {
            color: var(--accent-color);
        }
        
        .follow-tab-content {
            display: none;
        }
        
        .follow-tab-content.active {
            display: block;
        }
        
        .follow-user {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            margin-bottom: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        [data-theme="light"] .follow-user {
            background: rgba(0, 0, 0, 0.05);
        }
        
        .follow-user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
        }
        
        .follow-user-info:hover {
            transform: translateX(5px);
        }
        
        .follow-user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-color) 0%, var(--accent-hover) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            color: white;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .follow-user-info:hover .follow-user-avatar {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(233, 69, 96, 0.3);
        }
        
        .follow-user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .follow-user-details h4 {
            color: var(--accent-color);
            margin-bottom: 5px;
        }
        
        .follow-user-details p {
            font-size: 0.8rem;
            opacity: 0.7;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            opacity: 0.7;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--accent-color);
        }

        /* Indicador visual de que é clicável */
        .follow-user-info::after {
            content: "👁️";
            opacity: 0;
            transition: all 0.3s ease;
            margin-left: 10px;
            font-size: 0.8rem;
        }

        .follow-user-info:hover::after {
            opacity: 1;
        }

        /* Alertas */
        .alert { 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 10px; 
            text-align: center; 
            font-weight: 500;
            border-left: 4px solid;
            animation: slideIn 0.4s ease;
            font-size: 0.9rem;
        }
        
        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
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

        /* Animações */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        .shake-animation {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .profile-main {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .side-menu {
                width: 300px;
            }
            
            .profile-avatar {
                width: 120px;
                height: 120px;
                font-size: 2.5rem;
            }
            
            .profile-name {
                font-size: 1.5rem;
            }
            
            header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .header-actions {
                align-items: center;
                width: 100%;
            }

            .action-buttons {
                justify-content: center;
            }

            .follow-user {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .follow-user-info {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }
            
            .profile-card {
                padding: 20px;
            }
            
            .profile-stats {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .stat {
                padding: 12px;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 0.8rem;
            }

            .action-btn {
                width: 45px;
                height: 45px;
                font-size: 1.1rem;
            }

            .follow-tabs {
                flex-direction: column;
            }

            .follow-tab {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Menu Lateral -->
    <div class="side-menu" id="sideMenu">
        <div class="menu-header">
            <h3 class="menu-title">Menu do Perfil</h3>
            <button class="close-menu" id="closeMenu">&times;</button>
        </div>
        
        <?php if($viewing_own_profile): ?>
        <div class="menu-section">Configurações</div>
        <a href="#" class="menu-item" onclick="openEditModal()">
            <i class="fas fa-user-edit"></i>
            <span>Alterar Perfil</span>
        </a>
        
        <div class="menu-section">Rede Social</div>
        <a href="#" class="menu-item" onclick="openFollowersModal()">
            <i class="fas fa-users"></i>
            <span>Seguidores & Seguindo</span>
        </a>
        
        <div class="menu-section">Conta</div>
        <a href="#" class="menu-item" onclick="openDeleteModal()">
            <i class="fas fa-trash-alt"></i>
            <span>Excluir Conta</span>
        </a>
        <?php else: ?>
        <div class="menu-section">Ações</div>
        <a href="#" class="menu-item" onclick="openReportModal()">
            <i class="fas fa-flag"></i>
            <span>Reportar Usuário</span>
        </a>
        <?php endif; ?>
    </div>

    <div class="container">
        <header>
            <a href="comics.php" class="logo">HQ VERSO</a>
            <div class="header-actions">
                <a href="comics.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> 
                    <span>Voltar para Quadrinhos</span>
                </a>
                <div class="action-buttons">
                    <button class="action-btn theme-btn" id="themeToggle" title="Alternar Tema">
                        <i class="fas fa-moon" id="themeIcon"></i>
                    </button>
                    <button class="action-btn menu-btn" id="menuToggle" title="Menu do Perfil">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
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
        
        <!-- Conteúdo Principal do Perfil -->
        <div class="profile-main">
            <!-- Card do Perfil -->
            <div class="profile-card">
                <div class="profile-avatar" <?php if($viewing_own_profile): ?>onclick="document.getElementById('avatarInput').click()"<?php endif; ?>>
                    <?php if($user_data['avatar'] && file_exists($user_data['avatar'])): ?>
                        <img src="<?php echo $user_data['avatar']; ?>" alt="Avatar de <?php echo htmlspecialchars($user_data['username']); ?>">
                    <?php else: ?>
                        <span><?php echo $initial; ?></span>
                    <?php endif; ?>
                    <?php if($viewing_own_profile): ?>
                    <div class="avatar-overlay">
                        <i class="fas fa-camera"></i>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if($viewing_own_profile && $user_data['avatar'] && file_exists($user_data['avatar'])): ?>
                <form method="POST" style="margin-top: 10px;">
                    <input type="hidden" name="remove_avatar" value="1">
                    <button type="submit" class="remove-avatar-btn" onclick="return confirm('Tem certeza que deseja remover sua foto de perfil?')">
                        <i class="fas fa-trash"></i> Remover Foto
                    </button>
                </form>
                <?php endif; ?>
                
                <h2 class="profile-name"><?php echo htmlspecialchars($user_data['username']); ?></h2>
                
                <div class="profile-stats">
                    <div class="stat">
                        <span class="stat-number"><?php echo $followers_count; ?></span>
                        <span class="stat-label">Seguidores</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number"><?php echo $following_count; ?></span>
                        <span class="stat-label">Seguindo</span>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <?php if(!$viewing_own_profile): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="toggle_follow" value="1">
                            <button type="submit" class="btn <?php echo $is_following ? 'btn-outline' : 'btn-primary'; ?>">
                                <i class="fas fa-user-<?php echo $is_following ? 'check' : 'plus'; ?>"></i> 
                                <?php echo $is_following ? 'Seguindo' : 'Seguir'; ?>
                            </button>
                        </form>
                        <button class="btn btn-report" onclick="openReportModal()">
                            <i class="fas fa-flag"></i> Reportar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Bio do Usuário -->
            <div class="profile-card">
                <h3 class="bio-title">
                    <i class="fas fa-edit"></i> Biografia
                </h3>
                <div class="profile-bio">
                    <?php if(!empty($user_data['bio'])): ?>
                        <?php echo nl2br(htmlspecialchars($user_data['bio'])); ?>
                    <?php else: ?>
                        <p style="opacity: 0.7; font-style: italic;">
                            <?php echo $viewing_own_profile ? 'Você ainda não adicionou uma biografia. Clique no botão do menu para editar seu perfil!' : 'Este usuário ainda não adicionou uma biografia.'; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if($viewing_own_profile): ?>
    <!-- INPUT OCULTO PARA UPLOAD -->
    <input type="file" id="avatarInput" accept="image/*" style="display: none;">

    <!-- Modal de Edição do Perfil -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-user-edit"></i> Editar Perfil
                </h3>
                <button class="close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            
            <form method="POST" id="editForm">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="form-group">
                    <label for="username">Nome de Usuário</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($user_data['username']); ?>" 
                           required
                           placeholder="Seu nome de usuário">
                </div>
                
                <div class="form-group">
                    <label for="bio">Biografia</label>
                    <textarea id="bio" name="bio" placeholder="Conte um pouco sobre você..."><?php echo htmlspecialchars($user_data['bio'] ?? ''); ?></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <button type="button" class="btn btn-outline" onclick="closeEditModal()" style="flex: 1;">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Seguidores/Seguindo -->
    <div class="modal" id="followersModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-users"></i> Rede Social
                </h3>
                <button class="close-modal" onclick="closeFollowersModal()">&times;</button>
            </div>
            
            <div class="follow-tabs">
                <button class="follow-tab active" onclick="switchFollowTab('followers')">
                    Seguidores (<?php echo count($followers_list); ?>)
                </button>
                <button class="follow-tab" onclick="switchFollowTab('following')">
                    Seguindo (<?php echo count($following_list); ?>)
                </button>
            </div>
            
            <div class="follow-tab-content active" id="followersTab">
                <div class="follow-list">
                    <?php if(!empty($followers_list)): ?>
                        <?php foreach($followers_list as $follower): ?>
                            <div class="follow-user">
                                <div class="follow-user-info" onclick="viewUserProfile(<?php echo $follower['id']; ?>)">
                                    <div class="follow-user-avatar">
                                        <?php if($follower['avatar'] && file_exists($follower['avatar'])): ?>
                                            <img src="<?php echo $follower['avatar']; ?>" alt="Avatar">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($follower['username'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="follow-user-details">
                                        <h4><?php echo htmlspecialchars($follower['username']); ?></h4>
                                        <p><?php echo $follower['followers_count']; ?> seguidores</p>
                                        <?php if(!empty($follower['bio'])): ?>
                                            <p style="font-size: 0.75rem; opacity: 0.6; margin-top: 5px;">
                                                <?php echo substr(htmlspecialchars($follower['bio']), 0, 50); ?><?php echo strlen($follower['bio']) > 50 ? '...' : ''; ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if($follower['id'] != $_SESSION['user_id']): ?>
                                    <?php $is_following_follower = $auth->isFollowing($_SESSION['user_id'], $follower['id']); ?>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="toggle_follow_user" value="1">
                                        <input type="hidden" name="target_user_id" value="<?php echo $follower['id']; ?>">
                                        <input type="hidden" name="current_action" value="<?php echo $is_following_follower ? 'unfollow' : 'follow'; ?>">
                                        <button type="submit" class="btn <?php echo $is_following_follower ? 'btn-outline' : 'btn-primary'; ?>" style="padding: 8px 16px; font-size: 0.8rem;">
                                            <i class="fas fa-user-<?php echo $is_following_follower ? 'check' : 'plus'; ?>"></i>
                                            <?php echo $is_following_follower ? 'Seguindo' : 'Seguir'; ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-friends"></i>
                            <h4>Nenhum seguidor ainda</h4>
                            <p>Compartilhe seu perfil para ganhar seguidores!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="follow-tab-content" id="followingTab">
                <div class="follow-list">
                    <?php if(!empty($following_list)): ?>
                        <?php foreach($following_list as $following): ?>
                            <div class="follow-user">
                                <div class="follow-user-info" onclick="viewUserProfile(<?php echo $following['id']; ?>)">
                                    <div class="follow-user-avatar">
                                        <?php if($following['avatar'] && file_exists($following['avatar'])): ?>
                                            <img src="<?php echo $following['avatar']; ?>" alt="Avatar">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($following['username'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="follow-user-details">
                                        <h4><?php echo htmlspecialchars($following['username']); ?></h4>
                                        <p><?php echo $following['followers_count']; ?> seguidores</p>
                                        <?php if(!empty($following['bio'])): ?>
                                            <p style="font-size: 0.75rem; opacity: 0.6; margin-top: 5px;">
                                                <?php echo substr(htmlspecialchars($following['bio']), 0, 50); ?><?php echo strlen($following['bio']) > 50 ? '...' : ''; ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if($following['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="toggle_follow_user" value="1">
                                        <input type="hidden" name="target_user_id" value="<?php echo $following['id']; ?>">
                                        <input type="hidden" name="current_action" value="unfollow">
                                        <button type="submit" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.8rem;">
                                            <i class="fas fa-user-times"></i> Deixar de seguir
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h4>Não está seguindo ninguém</h4>
                            <p>Explore a comunidade para encontrar outros fãs de HQ!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Exclusão de Conta -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Excluir Conta
                </h3>
                <button class="close-modal" onclick="closeDeleteModal()">&times;</button>
            </div>
            
            <div style="margin-bottom: 20px; padding: 15px; background: rgba(233, 69, 96, 0.1); border-radius: 10px; border-left: 4px solid #e94560;">
                <p><strong>Atenção!</strong> Esta ação é irreversível. Todos os seus dados serão permanentemente removidos.</p>
            </div>
            
            <form method="POST" id="deleteForm">
                <input type="hidden" name="delete_account" value="1">
                
                <div class="form-group">
                    <label for="confirm_password">Digite sua senha para confirmar:</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           required placeholder="Sua senha atual">
                </div>
                
                <button type="submit" class="btn btn-danger" style="width: 100%; background: #dc3545; border-color: #dc3545;"
                        onclick="return confirm('⚠️ ATENÇÃO!\\n\\nTem certeza ABSOLUTA que deseja excluir sua conta permanentemente?\\n\\nEsta ação não pode ser desfeita!')">
                    <i class="fas fa-trash-alt"></i> Excluir Minha Conta Permanentemente
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if(!$viewing_own_profile): ?>
    <!-- Modal de Report -->
    <div class="modal" id="reportModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-flag"></i> Reportar Usuário
                </h3>
                <button class="close-modal" onclick="closeReportModal()">&times;</button>
            </div>
            
            <form method="POST" id="reportForm">
                <input type="hidden" name="report_user" value="1">
                <input type="hidden" name="reported_user_id" value="<?php echo $profile_user_id; ?>">
                
                <div class="form-group">
                    <label for="report_reason">Motivo do Report</label>
                    <select id="report_reason" name="report_reason" required>
                        <option value="">Selecione um motivo</option>
                        <option value="conteudo_impropio">Conteúdo Impróprio</option>
                        <option value="spam">Spam ou Propaganda</option>
                        <option value="assedio">Assédio ou Bullying</option>
                        <option value="comportamento_ofensivo">Comportamento Ofensivo</option>
                        <option value="perfil_falso">Perfil Falso</option>
                        <option value="roubo_conta">Roubo de Conta</option>
                        <option value="violacao_termos">Violação dos Termos de Uso</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="report_description">Descrição (Opcional)</label>
                    <textarea id="report_description" name="report_description" 
                              placeholder="Forneça mais detalhes sobre o problema..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <button type="button" class="btn btn-outline" onclick="closeReportModal()" style="flex: 1;">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" style="flex: 1; background: #dc3545; border-color: #dc3545;">
                        Enviar Report
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

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
                
                // Animação no botão
                themeToggle.classList.add('pulse-animation');
                setTimeout(() => {
                    themeToggle.classList.remove('pulse-animation');
                }, 1000);
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

        // Função para visualizar perfil de usuário
        function viewUserProfile(userId) {
            // Fechar o modal atual
            closeFollowersModal();
            
            // Redirecionar para o perfil do usuário
            window.location.href = 'perfil.php?user_id=' + userId;
        }

        // Menu Lateral
        const menuToggle = document.getElementById('menuToggle');
        const sideMenu = document.getElementById('sideMenu');
        const closeMenu = document.getElementById('closeMenu');

        menuToggle.addEventListener('click', () => {
            sideMenu.classList.add('active');
            menuToggle.classList.add('pulse-animation');
            setTimeout(() => {
                menuToggle.classList.remove('pulse-animation');
            }, 1000);
        });

        closeMenu.addEventListener('click', () => {
            sideMenu.classList.remove('active');
        });

        // Fechar menu ao clicar fora
        document.addEventListener('click', (e) => {
            if (!sideMenu.contains(e.target) && !menuToggle.contains(e.target)) {
                sideMenu.classList.remove('active');
            }
        });

        // Funções dos Modais
        function openEditModal() {
            document.getElementById('editModal').style.display = 'flex';
            sideMenu.classList.remove('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function openFollowersModal() {
            document.getElementById('followersModal').style.display = 'flex';
            sideMenu.classList.remove('active');
        }

        function closeFollowersModal() {
            document.getElementById('followersModal').style.display = 'none';
        }

        function openDeleteModal() {
            document.getElementById('deleteModal').style.display = 'flex';
            sideMenu.classList.remove('active');
            
            // Adicionar animação de shake
            const deleteBtn = document.querySelector('#deleteModal .btn-danger');
            deleteBtn.classList.add('shake-animation');
            setTimeout(() => {
                deleteBtn.classList.remove('shake-animation');
            }, 500);
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        function openReportModal() {
            document.getElementById('reportModal').style.display = 'flex';
            sideMenu.classList.remove('active');
        }

        function closeReportModal() {
            document.getElementById('reportModal').style.display = 'none';
        }

        // Alternar entre abas de seguidores/seguindo
        function switchFollowTab(tabName) {
            // Remover active de todas as abas
            document.querySelectorAll('.follow-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.follow-tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Adicionar active na aba clicada
            document.querySelector(`.follow-tab[onclick="switchFollowTab('${tabName}')"]`).classList.add('active');
            document.getElementById(tabName + 'Tab').classList.add('active');
        }

        // Fechar modais ao clicar fora
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });

        // Fechar modais com ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.style.display = 'none';
                });
            }
        });

        <?php if($viewing_own_profile): ?>
        // Upload de Avatar
        document.getElementById('avatarInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validar tamanho do arquivo (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('O arquivo é muito grande! Por favor, escolha uma imagem menor que 5MB.');
                    return;
                }

                // Validar tipo do arquivo
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    alert('Tipo de arquivo não suportado! Use apenas JPG, PNG, GIF ou WebP.');
                    return;
                }

                const formData = new FormData();
                formData.append('avatar', file);

                // Mostrar loading
                const avatar = document.querySelector('.profile-avatar');
                const originalContent = avatar.innerHTML;
                avatar.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>';

                // Faz o upload
                fetch('upload_avatar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('✅ Foto de perfil atualizada com sucesso!');
                        location.reload();
                    } else {
                        alert('❌ Erro: ' + data.message);
                        avatar.innerHTML = originalContent;
                    }
                })
                .catch(error => {
                    alert('❌ Erro no upload. Tente novamente.');
                    avatar.innerHTML = originalContent;
                });
            }
        });
        <?php endif; ?>

        // Validação de formulários
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if(submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
                    submitBtn.disabled = true;
                    
                    // Restaurar após 5 segundos (fallback)
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 5000);
                }
            });
        });

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            initializeTheme();
            
            // Adicionar tooltip para indicar que é clicável
            document.querySelectorAll('.follow-user-info').forEach(info => {
                info.title = "Clique para ver o perfil";
            });
        });
    </script>
</body>
</html>
