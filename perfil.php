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

$success = '';
$error = '';
$active_tab = 'profile';

// Processar edição de perfil (apenas para próprio perfil)
if($viewing_own_profile && $_POST && isset($_POST['update_profile'])) {
    $username = $_POST['username'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
    $result = $auth->updateProfile($_SESSION['user_id'], $username, $user_data['email'], $bio);
    if($result === true) {
        $success = "Perfil atualizado com sucesso!";
        $user_data = $auth->getUserData($_SESSION['user_id']);
        $initial = strtoupper(substr($user_data['username'], 0, 1));
    } else {
        $error = $result;
    }
}

// Processar seguir/deseguir
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

// Processar alteração de senha (apenas para próprio perfil)
if($viewing_own_profile && $_POST && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if($new_password !== $confirm_password) {
        $error = "As novas senhas não coincidem!";
    } elseif(strlen($new_password) < 6) {
        $error = "A nova senha deve ter pelo menos 6 caracteres!";
    } else {
        $result = $auth->changePassword($_SESSION['user_id'], $current_password, $new_password);
        if($result === true) {
            $success = "Senha alterada com sucesso!";
        } else {
            $error = $result;
        }
    }
    $active_tab = 'password';
}

// Processar remoção de avatar (apenas para próprio perfil)
if($viewing_own_profile && $_POST && isset($_POST['remove_avatar'])) {
    $result = $auth->removeAvatar($_SESSION['user_id']);
    if($result === true) {
        $success = "Foto de perfil removida com sucesso!";
        $user_data = $auth->getUserData($_SESSION['user_id']);
    } else {
        $error = "Erro ao remover foto de perfil!";
    }
    $active_tab = 'avatar';
}

// Processar exclusão de conta (apenas para próprio perfil)
if($viewing_own_profile && $_POST && isset($_POST['delete_account'])) {
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $result = $auth->changePassword($_SESSION['user_id'], $confirm_password, $confirm_password);
    if($result === true) {
        $result = $auth->deleteUserAccount($_SESSION['user_id'], $_SESSION['user_id']);
        if($result === true) {
            header("Location: login.php?message=conta_excluida");
            exit();
        } else {
            $error = $result;
        }
    } else {
        $error = "Senha incorreta!";
    }
    $active_tab = 'delete';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $viewing_own_profile ? 'Meu Perfil' : 'Perfil de ' . htmlspecialchars($user_data['username']); ?> - HQ Verso</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; transition: background-color 0.3s ease, color 0.3s ease; }
        body { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: #fff; min-height: 100vh; padding: 20px; }
        
        /* Modo Claro */
        body.light-mode {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
        }
        
        .container { max-width: 1000px; margin: 0 auto; }
        header { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; margin-bottom: 30px; }
        .logo { font-size: 28px; font-weight: 800; color: #e94560; text-decoration: none; }
        .back-btn { color: #e94560; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .profile-container { background: rgba(26, 26, 46, 0.8); border-radius: 10px; padding: 30px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3); }
        
        body.light-mode .profile-container {
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .profile-header { display: flex; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e94560; }
        .profile-avatar { width: 120px; height: 120px; border-radius: 50%; background: #e94560; display: flex; align-items: center; justify-content: center; font-size: 48px; font-weight: bold; margin-right: 25px; position: relative; overflow: hidden; border: 3px solid #e94560; }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
        <?php if($viewing_own_profile): ?>
        .avatar-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; cursor: pointer; }
        .profile-avatar:hover .avatar-overlay { opacity: 1; }
        <?php endif; ?>
        
        .profile-info h2 { font-size: 24px; margin-bottom: 5px; }
        .email-info { color: #e94560; font-weight: 500; margin-bottom: 5px; }
        
        .profile-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: rgba(15, 52, 96, 0.3); padding: 20px; border-radius: 8px; text-align: center; cursor: pointer; transition: all 0.3s ease; }
        
        body.light-mode .stat-card {
            background: rgba(233, 69, 96, 0.1);
            color: #333;
        }
        
        .stat-card:hover { background: rgba(15, 52, 96, 0.5); transform: translateY(-2px); }
        
        body.light-mode .stat-card:hover {
            background: rgba(233, 69, 96, 0.2);
        }
        
        .stat-number { font-size: 28px; font-weight: 700; color: #e94560; margin-bottom: 5px; }
        .stat-label { font-size: 14px; opacity: 0.8; }
        
        .tabs { display: flex; margin-bottom: 30px; border-bottom: 1px solid rgba(255, 255, 255, 0.2); }
        
        body.light-mode .tabs {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .tab { padding: 15px 30px; cursor: pointer; font-weight: 600; border-bottom: 3px solid transparent; }
        .tab.active { color: #e94560; border-bottom: 3px solid #e94560; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 5px; background: rgba(255, 255, 255, 0.05); color: #fff; font-size: 16px; }
        
        body.light-mode .form-group input,
        body.light-mode .form-group textarea {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #e2e8f0;
            color: #333;
        }
        
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #e94560; }
        .form-group textarea { height: 100px; resize: vertical; }
        
        .password-toggle { position: relative; }
        .password-toggle i { position: absolute; right: 15px; top: 40px; cursor: pointer; }
        
        .btn { padding: 12px 25px; border-radius: 5px; font-weight: 600; cursor: pointer; border: none; font-size: 16px; text-decoration: none; display: inline-block; transition: all 0.3s ease; }
        .btn-primary { background: #e94560; color: white; }
        .btn-primary:hover { background: #d8345f; }
        .btn-outline { background: transparent; border: 2px solid #e94560; color: #e94560; }
        .btn-outline:hover { background: rgba(233, 69, 96, 0.1); }
        .btn-danger { background: #dc3545; color: white; width: 100%; }
        .btn-sm { padding: 8px 15px; font-size: 14px; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; font-weight: 500; }
        .alert-success { background: rgba(76, 175, 80, 0.2); border: 1px solid #4caf50; color: #4caf50; }
        .alert-error { background: rgba(233, 69, 96, 0.2); border: 1px solid #e94560; color: #e94560; }
        
        .upload-area { border: 2px dashed #e94560; border-radius: 10px; padding: 30px; text-align: center; margin-bottom: 20px; cursor: pointer; transition: all 0.3s ease; }
        .upload-area:hover { background: rgba(233, 69, 96, 0.1); }

        /* Estilos para lista de seguidores/seguindo */
        .follow-list { display: grid; gap: 15px; margin-top: 20px; }
        .follow-item { display: flex; align-items: center; justify-content: space-between; padding: 15px; background: rgba(255, 255, 255, 0.05); border-radius: 8px; transition: all 0.3s ease; }
        
        body.light-mode .follow-item {
            background: rgba(0, 0, 0, 0.05);
        }
        
        .follow-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        body.light-mode .follow-item:hover {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .follow-user { display: flex; align-items: center; gap: 15px; }
        .follow-avatar { width: 50px; height: 50px; border-radius: 50%; background: #e94560; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; }
        .follow-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .follow-info h4 { margin-bottom: 5px; }
        .follow-info p { font-size: 0.8rem; opacity: 0.7; margin: 0; }
        
        .follow-btn-container { margin-top: 20px; text-align: center; }
        
        /* Botão do Tema */
        .theme-toggle {
            position: fixed;
            bottom: 80px;
            right: 20px;
            background: rgba(15, 52, 96, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4);
        }

        body.light-mode .theme-toggle {
            background: rgba(233, 69, 96, 0.8);
            color: white;
        }
        
        @media (max-width: 768px) {
            .profile-header { flex-direction: column; text-align: center; }
            .profile-avatar { margin: 0 auto 15px; }
            .tabs { flex-direction: column; }
            .tab { text-align: center; }
            .follow-item { flex-direction: column; align-items: flex-start; gap: 10px; }
            .follow-user { width: 100%; }
        }
    </style>
</head>
<body>
    <!-- Botão do Tema -->
    <button class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
    </button>
    
    <div class="container">
        <header>
            <a href="comics.php" class="logo">HQ VERSO</a>
            <a href="comics.php" class="back-btn"><i class="fas fa-arrow-left"></i> Voltar</a>
        </header>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="profile-container">
            <!-- CABEÇALHO DO PERFIL -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php if($user_data['avatar'] && file_exists($user_data['avatar'])): ?>
                        <img src="<?php echo $user_data['avatar']; ?>" alt="Avatar">
                    <?php else: ?>
                        <span><?php echo $initial; ?></span>
                    <?php endif; ?>
                    <?php if($viewing_own_profile): ?>
                    <div class="avatar-overlay" onclick="document.getElementById('avatarInput').click()">
                        <i class="fas fa-camera"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user_data['username']); ?></h2>
                    <div class="email-info">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user_data['email']); ?>
                    </div>
                    <p>Membro desde: <?php echo date('d/m/Y', strtotime($user_data['created_at'])); ?></p>
                    
                    <?php if($viewing_own_profile && $user_data['avatar']): ?>
                        <form method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="remove_avatar" value="1">
                            <button type="submit" class="btn btn-outline btn-sm" onclick="return confirm('Remover foto de perfil?')">
                                <i class="fas fa-trash"></i> Remover Foto
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if(!$viewing_own_profile): ?>
                        <form method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="toggle_follow" value="1">
                            <button type="submit" class="btn <?php echo $is_following ? 'btn-outline' : 'btn-primary'; ?>">
                                <i class="fas fa-user-<?php echo $is_following ? 'check' : 'plus'; ?>"></i> 
                                <?php echo $is_following ? 'Seguindo' : 'Seguir'; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if($viewing_own_profile): ?>
            <!-- INPUT OCULTO PARA UPLOAD (apenas para próprio perfil) -->
            <input type="file" id="avatarInput" accept="image/*" style="display: none;">
            <?php endif; ?>

            <!-- ESTATÍSTICAS -->
            <div class="profile-stats">
                <div class="stat-card" onclick="showFollowers()">
                    <div class="stat-number"><?php echo $followers_count; ?></div>
                    <div class="stat-label">Seguidores</div>
                </div>
                <div class="stat-card" onclick="showFollowing()">
                    <div class="stat-number"><?php echo $following_count; ?></div>
                    <div class="stat-label">Seguindo</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">24</div>
                    <div class="stat-label">Quadrinhos Lidos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">8</div>
                    <div class="stat-label">Favoritos</div>
                </div>
            </div>
            
            <!-- ABAS -->
            <div class="tabs">
                <div class="tab <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" data-tab="profile">
                    <i class="fas fa-user-edit"></i> <?php echo $viewing_own_profile ? 'Editar Perfil' : 'Perfil'; ?>
                </div>
                <div class="tab <?php echo $active_tab === 'follow' ? 'active' : ''; ?>" data-tab="follow">
                    <i class="fas fa-users"></i> Seguidores
                </div>
                
                <?php if($viewing_own_profile): ?>
                <div class="tab <?php echo $active_tab === 'avatar' ? 'active' : ''; ?>" data-tab="avatar">
                    <i class="fas fa-camera"></i> Foto do Perfil
                </div>
                <div class="tab <?php echo $active_tab === 'password' ? 'active' : ''; ?>" data-tab="password">
                    <i class="fas fa-lock"></i> Alterar Senha
                </div>
                <div class="tab <?php echo $active_tab === 'delete' ? 'active' : ''; ?>" data-tab="delete">
                    <i class="fas fa-trash-alt"></i> Excluir Conta
                </div>
                <?php endif; ?>
            </div>
            
            <!-- ABA 1: PERFIL -->
            <div class="tab-content <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" id="profile">
                <?php if($viewing_own_profile): ?>
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label for="username">Nome de Usuário</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <div style="padding: 12px; background: rgba(255,255,255,0.05); border-radius: 5px; color: #e94560;">
                            <i class="fas fa-lock"></i> <?php echo htmlspecialchars($user_data['email']); ?>
                            <small style="opacity: 0.7;"> (não pode ser alterado)</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Biografia</label>
                        <textarea id="bio" name="bio" placeholder="Conte um pouco sobre você..."><?php echo htmlspecialchars($user_data['bio'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </form>
                <?php else: ?>
                <div class="form-group">
                    <label>Nome de Usuário</label>
                    <div style="padding: 12px; background: rgba(255,255,255,0.05); border-radius: 5px;">
                        <?php echo htmlspecialchars($user_data['username']); ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <div style="padding: 12px; background: rgba(255,255,255,0.05); border-radius: 5px; color: #e94560;">
                        <i class="fas fa-lock"></i> <?php echo htmlspecialchars($user_data['email']); ?>
                    </div>
                </div>
                
                <!-- CORREÇÃO: Biografia sempre visível para outros usuários -->
                <div class="form-group">
                    <label>Biografia</label>
                    <?php if(!empty($user_data['bio'])): ?>
                        <div style="padding: 12px; background: rgba(255,255,255,0.05); border-radius: 5px; line-height: 1.5;">
                            <?php echo nl2br(htmlspecialchars($user_data['bio'])); ?>
                        </div>
                    <?php else: ?>
                        <div style="padding: 12px; background: rgba(255,255,255,0.05); border-radius: 5px; line-height: 1.5; opacity: 0.7; font-style: italic;">
                            Este usuário ainda não adicionou uma biografia.
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if($viewing_own_profile): ?>
            <!-- ABA 2: FOTO DO PERFIL (apenas para próprio perfil) -->
            <div class="tab-content <?php echo $active_tab === 'avatar' ? 'active' : ''; ?>" id="avatar">
                <div class="upload-area" onclick="document.getElementById('avatarInput').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h3>Clique para escolher uma foto</h3>
                    <p>Formatos: JPG, PNG, GIF, WebP (Máx. 5MB)</p>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <h4 style="margin-bottom: 15px; color: #e94560;">Foto Atual</h4>
                    <div class="profile-avatar" style="margin: 0 auto; width: 100px; height: 100px; font-size: 36px;">
                        <?php if($user_data['avatar'] && file_exists($user_data['avatar'])): ?>
                            <img src="<?php echo $user_data['avatar']; ?>" alt="Avatar atual">
                        <?php else: ?>
                            <span><?php echo $initial; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ABA 3: SEGUIDORES/SEGUINDO -->
            <div class="tab-content <?php echo $active_tab === 'follow' ? 'active' : ''; ?>" id="follow">
                <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <button class="btn btn-outline active" onclick="showFollowers()" id="btnFollowers">
                        <i class="fas fa-user-friends"></i> Seguidores (<?php echo $followers_count; ?>)
                    </button>
                    <button class="btn btn-outline" onclick="showFollowing()" id="btnFollowing">
                        <i class="fas fa-user-check"></i> Seguindo (<?php echo $following_count; ?>)
                    </button>
                </div>
                
                <div id="followersList" class="follow-list">
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-spinner fa-spin"></i> Carregando seguidores...
                    </div>
                </div>
                
                <div id="followingList" class="follow-list" style="display: none;">
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-spinner fa-spin"></i> Carregando usuários seguidos...
                    </div>
                </div>
            </div>
            
            <?php if($viewing_own_profile): ?>
            <!-- ABA 4: ALTERAR SENHA (apenas para próprio perfil) -->
            <div class="tab-content <?php echo $active_tab === 'password' ? 'active' : ''; ?>" id="password">
                <form method="POST">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-group password-toggle">
                        <label for="current_password">Senha Atual</label>
                        <input type="password" id="current_password" name="current_password" required>
                        <i class="fas fa-eye" onclick="togglePassword('current_password', this)"></i>
                    </div>
                    
                    <div class="form-group password-toggle">
                        <label for="new_password">Nova Senha</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <i class="fas fa-eye" onclick="togglePassword('new_password', this)"></i>
                    </div>
                    
                    <div class="form-group password-toggle">
                        <label for="confirm_password">Confirmar Nova Senha</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <i class="fas fa-eye" onclick="togglePassword('confirm_password', this)"></i>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i> Alterar Senha
                    </button>
                </form>
            </div>
            
            <!-- ABA 5: EXCLUIR CONTA (apenas para próprio perfil) -->
            <div class="tab-content <?php echo $active_tab === 'delete' ? 'active' : ''; ?>" id="delete">
                <div class="alert alert-error" style="text-align: left;">
                    <h3 style="margin-bottom: 10px;"><i class="fas fa-exclamation-triangle"></i> Atenção!</h3>
                    <p>Esta ação é <strong>irreversível</strong>. Todos os seus dados serão permanentemente removidos.</p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="delete_account" value="1">
                    
                    <div class="form-group password-toggle">
                        <label for="confirm_password_delete">Digite sua senha para confirmar:</label>
                        <input type="password" id="confirm_password_delete" name="confirm_password" required>
                        <i class="fas fa-eye" onclick="togglePassword('confirm_password_delete', this)"></i>
                    </div>
                    
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir sua conta permanentemente?')">
                        <i class="fas fa-trash-alt"></i> Excluir Minha Conta
                    </button>
                </form>
            </div>
            <?php endif; ?>
            
            <!-- LINKS EXTRAS -->
            <div style="display: flex; gap: 15px; margin-top: 30px;">
                <a href="detection.html" class="btn btn-outline">
                    <i class="fas fa-info-circle"></i> Info do Navegador
                </a>
                <?php if($viewing_own_profile): ?>
                <a href="logout.php" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // SISTEMA DE TEMA
        function setupTheme() {
            const themeToggle = document.getElementById('themeToggle');
            const body = document.body;
            
            const savedTheme = localStorage.getItem('hq-verso-theme');
            if (savedTheme === 'light') {
                body.classList.add('light-mode');
                themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            }
            
            themeToggle.addEventListener('click', () => {
                body.classList.toggle('light-mode');
                
                if (body.classList.contains('light-mode')) {
                    themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                    localStorage.setItem('hq-verso-theme', 'light');
                } else {
                    themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                    localStorage.setItem('hq-verso-theme', 'dark');
                }
            });
        }

        // SISTEMA DE ABAS
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active de todas as tabs
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                // Adiciona active na tab clicada
                this.classList.add('active');
                
                // Mostra o conteúdo correspondente
                const tabName = this.getAttribute('data-tab');
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById(tabName).classList.add('active');

                // Carrega dados de seguidores se for a aba de follow
                if(tabName === 'follow') {
                    loadFollowers();
                }
            });
        });

        <?php if($viewing_own_profile): ?>
        // UPLOAD DE AVATAR (apenas para próprio perfil)
        document.getElementById('avatarInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('avatar', file);

                // Faz o upload
                fetch('upload_avatar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('Foto de perfil atualizada com sucesso!');
                        location.reload(); // Recarrega a página
                    } else {
                        alert('Erro: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Erro no upload. Tente novamente.');
                });
            }
        });
        <?php endif; ?>

        // ALTERNAR VISIBILIDADE DA SENHA
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // FUNÇÕES PARA SEGUIDORES/SEGUINDO
        function showFollowers() {
            document.getElementById('btnFollowers').classList.add('active');
            document.getElementById('btnFollowing').classList.remove('active');
            document.getElementById('followersList').style.display = 'grid';
            document.getElementById('followingList').style.display = 'none';
            loadFollowers();
        }

        function showFollowing() {
            document.getElementById('btnFollowers').classList.remove('active');
            document.getElementById('btnFollowing').classList.add('active');
            document.getElementById('followersList').style.display = 'none';
            document.getElementById('followingList').style.display = 'grid';
            loadFollowing();
        }

        function loadFollowers() {
            fetch('get_follow_data.php?type=followers&user_id=<?php echo $profile_user_id; ?>')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('followersList');
                    if(data.length > 0) {
                        container.innerHTML = data.map(user => `
                            <div class="follow-item">
                                <div class="follow-user">
                                    <div class="follow-avatar">
                                        ${user.avatar ? `<img src="${user.avatar}" alt="${user.username}">` : user.username.charAt(0).toUpperCase()}
                                    </div>
                                    <div class="follow-info">
                                        <h4>${user.username}</h4>
                                        <p>${user.followers_count} seguidores</p>
                                    </div>
                                </div>
                                <button class="btn btn-outline btn-sm" onclick="viewProfile(${user.id})">
                                    <i class="fas fa-eye"></i> Ver Perfil
                                </button>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<div style="text-align: center; padding: 40px; opacity: 0.7;"><i class="fas fa-users"></i><p>Nenhum seguidor ainda</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    document.getElementById('followersList').innerHTML = '<div style="text-align: center; padding: 40px; color: #e94560;"><i class="fas fa-exclamation-triangle"></i><p>Erro ao carregar seguidores</p></div>';
                });
        }

        function loadFollowing() {
            fetch('get_follow_data.php?type=following&user_id=<?php echo $profile_user_id; ?>')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('followingList');
                    if(data.length > 0) {
                        container.innerHTML = data.map(user => `
                            <div class="follow-item">
                                <div class="follow-user">
                                    <div class="follow-avatar">
                                        ${user.avatar ? `<img src="${user.avatar}" alt="${user.username}">` : user.username.charAt(0).toUpperCase()}
                                    </div>
                                    <div class="follow-info">
                                        <h4>${user.username}</h4>
                                        <p>${user.followers_count} seguidores</p>
                                    </div>
                                </div>
                                <button class="btn btn-outline btn-sm" onclick="viewProfile(${user.id})">
                                    <i class="fas fa-eye"></i> Ver Perfil
                                </button>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<div style="text-align: center; padding: 40px; opacity: 0.7;"><i class="fas fa-user-check"></i><p>Não está seguindo ninguém</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    document.getElementById('followingList').innerHTML = '<div style="text-align: center; padding: 40px; color: #e94560;"><i class="fas fa-exclamation-triangle"></i><p>Erro ao carregar lista</p></div>';
                });
        }

        function viewProfile(userId) {
            window.location.href = `perfil.php?user_id=${userId}`;
        }

        // INICIALIZAÇÃO
        document.addEventListener('DOMContentLoaded', function() {
            setupTheme();
            
            // Carrega seguidores se a aba estiver ativa
            if(document.getElementById('follow').classList.contains('active')) {
                loadFollowers();
            }
        });
    </script>
</body>
</html>
