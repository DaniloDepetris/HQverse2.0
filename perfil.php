<?php
require_once 'includes_auth.php';

if(!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_data = $auth->getUserData($_SESSION['user_id']);
$initial = strtoupper(substr($user_data['username'], 0, 1));

$success = '';
$error = '';
$active_tab = 'profile'; // profile ou password

// Processar edição de perfil
if($_POST && isset($_POST['update_profile'])) {
    $username = $_POST['username'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
    $result = $auth->updateProfile($_SESSION['user_id'], $username, $user_data['email'], $bio);
    if($result === true) {
        $success = "Perfil atualizado com sucesso!";
        $user_data = $auth->getUserData($_SESSION['user_id']); // Recarregar dados
        $initial = strtoupper(substr($user_data['username'], 0, 1));
    } else {
        $error = $result;
    }
    $active_tab = 'profile';
}

// Processar alteração de senha
if($_POST && isset($_POST['change_password'])) {
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - HQ Verso</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            min-height: 100vh;
            padding: 20px;
        }
        
        /* Modo Claro */
        body.light-mode {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #e94560;
            text-decoration: none;
            letter-spacing: 1px;
        }
        
        .back-btn {
            color: #e94560;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        body.light-mode .back-btn {
            color: #e94560;
        }
        
        body.light-mode .logo {
            color: #e94560;
        }
        
        .profile-container {
            background: rgba(26, 26, 46, 0.8);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        body.light-mode .profile-container {
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e94560;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #e94560;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: bold;
            margin-right: 20px;
            flex-shrink: 0;
        }
        
        .profile-info h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .profile-info p {
            opacity: 0.8;
            margin-bottom: 5px;
        }
        
        .email-info {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #e94560;
            font-weight: 500;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(15, 52, 96, 0.3);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        body.light-mode .stat-card {
            background: rgba(233, 69, 96, 0.1);
            color: #333;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #e94560;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        body.light-mode .tabs {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .tab {
            padding: 15px 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {
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
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 14px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-size: 16px;
            transition: border 0.3s ease;
        }
        
        body.light-mode .form-group input,
        body.light-mode .form-group textarea {
            background: rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
            color: #333;
        }
        
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #e94560;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle i {
            position: absolute;
            right: 15px;
            top: 45px;
            cursor: pointer;
            color: #718096;
        }
        
        .email-display {
            padding: 14px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            color: #e94560;
            font-weight: 500;
        }
        
        body.light-mode .email-display {
            background: rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
            color: #e94560;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            border: none;
            font-size: 16px;
        }
        
        .btn-primary {
            background: #e94560;
            color: white;
        }
        
        .btn-primary:hover {
            background: #d8345f;
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #e94560;
            color: #e94560;
        }
        
        body.light-mode .btn-outline {
            color: #e94560;
        }
        
        .btn-outline:hover {
            background: rgba(233, 69, 96, 0.1);
        }
        
        .theme-toggle {
            position: fixed;
            top: 20px;
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
        
        .profile-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-avatar {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .tab {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
    </button>
    
    <div class="container">
        <header>
            <a href="comics.php" class="logo">HQ VERSO</a>
            <a href="comics.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </header>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar"><?php echo $initial; ?></div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user_data['username']); ?></h2>
                    <div class="email-info">
                        <i class="fas fa-envelope"></i>
                        <?php echo htmlspecialchars($user_data['email']); ?>
                    </div>
                    <p>Membro desde: <?php echo date('d/m/Y', strtotime($user_data['created_at'])); ?></p>
                </div>
            </div>
            
            <div class="profile-stats">
                <div class="stat-card">
                    <div class="stat-number">24</div>
                    <div class="stat-label">Quadrinhos Lidos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">8</div>
                    <div class="stat-label">Favoritos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">15</div>
                    <div class="stat-label">Dias de Leitura</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">3</div>
                    <div class="stat-label">Reviews</div>
                </div>
            </div>
            
            <div class="tabs">
                <div class="tab <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" data-tab="profile">
                    <i class="fas fa-user-edit"></i> Editar Perfil
                </div>
                <div class="tab <?php echo $active_tab === 'password' ? 'active' : ''; ?>" data-tab="password">
                    <i class="fas fa-lock"></i> Alterar Senha
                </div>
            </div>
            
            <div class="tab-content <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" id="profile">
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label for="username">Nome de Usuário</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="email-display">
                            <i class="fas fa-lock"></i>
                            <?php echo htmlspecialchars($user_data['email']); ?>
                            <small style="opacity: 0.7; margin-left: 10px;">(O email não pode ser alterado)</small>
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
            </div>
            
            <div class="tab-content <?php echo $active_tab === 'password' ? 'active' : ''; ?>" id="password">
                <form method="POST">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-group password-toggle">
                        <label for="current_password">Senha Atual</label>
                        <input type="password" id="current_password" name="current_password" required>
                        <i class="fas fa-eye" id="toggleCurrentPassword"></i>
                    </div>
                    
                    <div class="form-group password-toggle">
                        <label for="new_password">Nova Senha</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <i class="fas fa-eye" id="toggleNewPassword"></i>
                    </div>
                    
                    <div class="form-group password-toggle">
                        <label for="confirm_password">Confirmar Nova Senha</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <i class="fas fa-eye" id="toggleConfirmPassword"></i>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i> Alterar Senha
                    </button>
                </form>
            </div>
            
            <div class="profile-actions">
                <a href="detection.html" class="btn btn-outline">
                    <i class="fas fa-info-circle"></i> Info do Navegador
                </a>
                <a href="logout.php" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </div>

    <script>
        // Sistema de Tema
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

        // Sistema de Tabs
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Remover active de todas as tabs
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
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

        // Alternar visibilidade da senha
        document.getElementById('toggleCurrentPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('current_password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
        
        document.getElementById('toggleNewPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('new_password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('confirm_password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        // Validação do formulário de senha
        document.querySelector('form[action*="change_password"]')?.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('As novas senhas não coincidem!');
                return false;
            }
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('A nova senha deve ter pelo menos 6 caracteres!');
                return false;
            }
        });

        console.log('Perfil carregado com sucesso!');
    </script>
</body>
</html>