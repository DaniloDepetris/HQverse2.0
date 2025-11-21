<?php
require_once 'includes_auth.php';

// Ensure session is active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Generate CSRF token if not present
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        // Fallback if random_bytes not available
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

if ($auth->isLoggedIn()) {
    header("Location: comics.php");
    exit();
}

$error = '';
$success = '';
$name = '';
$email = '';

// Use explicit request method check
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic CSRF validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Requisição inválida (verificação de segurança falhou).';
    } else {
        if (isset($_POST['login'])) {
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            $result = $auth->login($email, $password);
            if ($result === true) {
                // Prevent session fixation
                session_regenerate_id(true);
                header("Location: comics.php");
                exit();
            } else {
                $error = $result;
            }
        } elseif (isset($_POST['signup'])) {
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

            // Server-side validation
            if (empty($name)) {
                $error = 'Nome de usuário é obrigatório.';
            } elseif (empty($email)) {
                $error = 'Email é obrigatório.';
            } elseif ($password !== $confirm) {
                $error = 'As senhas não coincidem.';
            } elseif (strlen($password) < 6) {
                $error = 'A senha deve ter pelo menos 6 caracteres.';
            } else {
                // Tentar fazer o cadastro
                $result = $auth->register($name, $email, $password);
                
                if ($result === true) {
                    $success = "🎉 Cadastro realizado com sucesso! Faça login para continuar.";
                    $name = ''; // Limpar apenas o nome
                    // Manter o email para facilitar o login
                } else {
                    $error = $result;
                }
            }
        }
    }
}

// Debug: Verificar se há mensagens
error_log("Success message: " . $success);
error_log("Error message: " . $error);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HQ Verso - Login e Cadastro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        :root {
            --primary-color: #e94560;
            --primary-dark: #d8345f;
            --secondary-color: #0f3460;
            --dark-bg: #0f0f1a;
            --card-bg: rgba(21, 21, 36, 0.95);
            --text-primary: #ffffff;
            --text-secondary: #b0b0c0;
            --border-color: rgba(255, 255, 255, 0.1);
            --shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }
        
        body {
            background: 
                linear-gradient(
                    135deg,
                    rgba(15, 15, 26, 0.95) 0%,
                    rgba(26, 26, 46, 0.92) 50%,
                    rgba(15, 52, 96, 0.85) 100%
                ),
                url('uploads/fundo_login.jpg');
            background-position: center center;
            background-repeat: no-repeat;
            background-size: cover;
            background-attachment: fixed;
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        .auth-wrapper {
            display: flex;
            width: 100%;
            max-width: 1000px;
            min-height: 600px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            overflow: hidden;
            position: relative;
        }

        .auth-hero {
            flex: 1;
            background: linear-gradient(135deg, var(--secondary-color) 0%, #1a1a2e 100%);
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .auth-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 400px;
        }

        .auth-hero h1 {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--text-primary);
            font-weight: 800;
            letter-spacing: -1px;
            background: linear-gradient(45deg, var(--primary-color), #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .auth-hero p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .hero-features {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 40px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .feature-item i {
            font-size: 24px;
            color: var(--primary-color);
            width: 40px;
            text-align: center;
        }

        .feature-text h3 {
            font-size: 1rem;
            margin-bottom: 5px;
            color: var(--text-primary);
        }

        .feature-text p {
            font-size: 0.9rem;
            opacity: 0.8;
            margin: 0;
        }

        .auth-forms {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            max-width: 450px;
        }

        .alert {
            padding: 16px 20px;
            margin-bottom: 25px;
            border-radius: 12px;
            text-align: center;
            font-weight: 500;
            font-size: 14px;
            border: 1px solid;
            backdrop-filter: blur(10px);
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-error {
            background: rgba(233, 69, 96, 0.15);
            border-color: var(--primary-color);
            color: var(--primary-color);
            border-left: 4px solid var(--primary-color);
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border-color: #4caf50;
            color: #4caf50;
            border-left: 4px solid #4caf50;
            border-right: 4px solid #4caf50;
            position: relative;
            overflow: hidden;
        }

        .alert-success::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(76, 175, 80, 0.1), transparent);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { left: -100%; }
            20% { left: 100%; }
            100% { left: 100%; }
        }

        .alert-success i {
            color: #4caf50;
            margin-right: 8px;
            animation: bounce 1s infinite alternate;
        }

        @keyframes bounce {
            from { transform: translateY(0); }
            to { transform: translateY(-3px); }
        }

        .forms-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .forms-header h2 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: var(--text-primary);
        }

        .forms-header p {
            color: var(--text-secondary);
            font-size: 1rem;
        }
        
        .tabs {
            display: flex;
            background: rgba(15, 52, 96, 0.3);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .tab {
            flex: 1;
            padding: 18px;
            text-align: center;
            background: transparent;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 15px;
            color: var(--text-secondary);
            position: relative;
        }
        
        .tab.active {
            color: var(--text-primary);
            background: rgba(233, 69, 96, 0.1);
        }
        
        .tab:hover {
            color: var(--text-primary);
            background: rgba(233, 69, 96, 0.05);
        }
        
        .tab-content {
            display: none;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 13px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            font-size: 15px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.1);
        }
        
        .form-group input::placeholder {
            color: var(--text-secondary);
            opacity: 0.6;
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(233, 69, 96, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #c7224e 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(233, 69, 96, 0.4);
        }
        
        .separator {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 25px 0;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
        }
        
        .separator::before,
        .separator::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border-color);
        }
        
        .separator::before {
            margin-right: 12px;
        }
        
        .separator::after {
            margin-left: 12px;
        }
        
        .social-login {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .facebook {
            background: linear-gradient(135deg, #3b5998, #2d4373);
        }
        
        .google {
            background: linear-gradient(135deg, #db4437, #c23321);
        }
        
        .twitter {
            background: linear-gradient(135deg, #1da1f2, #0d8bd9);
        }
        
        .social-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: var(--text-secondary);
            line-height: 1.5;
        }
        
        .auth-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .auth-footer a:hover {
            color: var(--text-primary);
            text-decoration: underline;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle i {
            position: absolute;
            right: 18px;
            top: 45px;
            cursor: pointer;
            color: var(--text-secondary);
            transition: color 0.3s ease;
            padding: 6px;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .password-toggle i:hover {
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.05);
        }

        .loading {
            pointer-events: none;
            opacity: 0.7;
            position: relative;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 18px;
            height: 18px;
            margin: -9px 0 0 -9px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .success-celebration {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(76, 175, 80, 0.05));
            border-radius: 15px;
            border: 2px dashed #4caf50;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        .success-celebration i {
            font-size: 3rem;
            color: #4caf50;
            margin-bottom: 10px;
            display: block;
        }

        .success-celebration h3 {
            color: #4caf50;
            margin-bottom: 10px;
            font-size: 1.4rem;
        }

        @media (max-width: 968px) {
            .auth-wrapper {
                flex-direction: column;
                max-width: 500px;
                min-height: auto;
            }

            .auth-hero {
                padding: 40px 30px;
            }

            .auth-hero h1 {
                font-size: 2.5rem;
            }

            .hero-features {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
            }

            .feature-item {
                flex: 1;
                min-width: 200px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 15px;
            }

            .auth-wrapper {
                border-radius: 16px;
            }

            .auth-hero {
                padding: 30px 20px;
            }

            .auth-hero h1 {
                font-size: 2rem;
            }

            .auth-forms {
                padding: 30px 25px;
            }

            .forms-header h2 {
                font-size: 1.5rem;
            }

            .hero-features {
                flex-direction: column;
            }

            .feature-item {
                min-width: auto;
            }
        }

        .bg-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            background: var(--primary-color);
            border-radius: 50%;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
    </style>
</head>
<body>
    <div class="bg-particles" id="particles"></div>
    
    <div class="auth-wrapper">
        <div class="auth-hero">
            <div class="hero-content">
                <h1>HQ VERSO</h1>
                <p>Descubra um universo infinito de histórias em quadrinhos</p>
                
                <div class="hero-features">
                    <div class="feature-item">
                        <i class="fas fa-book-open"></i>
                        <div class="feature-text">
                            <h3>+10.000 HQs</h3>
                            <p>Coleção completa</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-users"></i>
                        <div class="feature-text">
                            <h3>Comunidade</h3>
                            <p>Fãs de quadrinhos</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-mobile-alt"></i>
                        <div class="feature-text">
                            <h3>Multiplataforma</h3>
                            <p>Acesse de qualquer lugar</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="auth-forms">
            <div class="forms-header">
                <h2>Bem-vindo de volta!</h2>
                <p>Entre na sua conta ou crie uma nova</p>
            </div>
            
            <?php if($success): ?>
                <div class="success-celebration">
                    <i class="fas fa-party-horn"></i>
                    <h3>🎉 Parabéns! 🎉</h3>
                    <p>Conta criada com sucesso!</p>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <div class="tabs">
                <div class="tab active" data-tab="login">Entrar</div>
                <div class="tab" data-tab="signup">Cadastrar</div>
            </div>
            
            <div class="tab-content active" id="login">
                <form method="POST" id="loginForm">
                    <input type="hidden" name="login" value="1">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    
                    <div class="form-group">
                        <label for="loginEmail"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="loginEmail" name="email" placeholder="seu@email.com" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    
                    <div class="form-group password-toggle">
                        <label for="loginPassword"><i class="fas fa-lock"></i> Senha</label>
                        <input type="password" id="loginPassword" name="password" placeholder="Sua senha" required>
                        <i class="fas fa-eye" id="toggleLoginPassword"></i>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="loginBtn">
                        <i class="fas fa-sign-in-alt"></i> Entrar na Comunidade
                    </button>
                </form>
                
                <div class="separator">ou entre com</div>
                
                <div class="social-login">
                    <div class="social-btn facebook" title="Entrar com Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </div>
                    <div class="social-btn google" title="Entrar com Google">
                        <i class="fab fa-google"></i>
                    </div>
                    <div class="social-btn twitter" title="Entrar com Twitter">
                        <i class="fab fa-twitter"></i>
                    </div>
                </div>
                
                <div class="auth-footer">
                    Ao continuar, você concorda com os <a href="#">Termos de Uso</a> e a <a href="#">Política de Privacidade</a> do HQ Verso.
                </div>
            </div>
            
            <div class="tab-content" id="signup">
                <form method="POST" id="signupForm">
                    <input type="hidden" name="signup" value="1">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    
                    <div class="form-group">
                        <label for="signupName"><i class="fas fa-user"></i> Nome de usuário</label>
                        <input type="text" id="signupName" name="name" placeholder="Seu nome de usuário" value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="signupEmail"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="signupEmail" name="email" placeholder="seu@email.com" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    
                    <div class="form-group password-toggle">
                        <label for="signupPassword"><i class="fas fa-lock"></i> Senha</label>
                        <input type="password" id="signupPassword" name="password" placeholder="Crie uma senha forte" required>
                        <i class="fas fa-eye" id="toggleSignupPassword"></i>
                    </div>
                    
                    <div class="form-group password-toggle">
                        <label for="signupConfirmPassword"><i class="fas fa-lock"></i> Confirmar senha</label>
                        <input type="password" id="signupConfirmPassword" name="confirm_password" placeholder="Digite sua senha novamente" required>
                        <i class="fas fa-eye" id="toggleSignupConfirmPassword"></i>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="signupBtn">
                        <i class="fas fa-user-plus"></i> Criar Minha Conta
                    </button>
                </form>
                
                <div class="separator">ou cadastre-se com</div>
                
                <div class="social-login">
                    <div class="social-btn facebook" title="Cadastrar com Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </div>
                    <div class="social-btn google" title="Cadastrar com Google">
                        <i class="fab fa-google"></i>
                    </div>
                    <div class="social-btn twitter" title="Cadastrar com Twitter">
                        <i class="fab fa-twitter"></i>
                    </div>
                </div>
                
                <div class="auth-footer">
                    Ao continuar, você concorda com os <a href="#">Termos de Uso</a> e a <a href="#">Política de Privacidade</a> do HQ Verso.
                </div>
            </div>
        </div>
    </div>

    <script>
        // Criar partículas de fundo
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 15;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                const size = Math.random() * 6 + 2;
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                const delay = Math.random() * 5;
                const duration = Math.random() * 10 + 10;
                
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${posX}%`;
                particle.style.top = `${posY}%`;
                particle.style.animationDelay = `${delay}s`;
                particle.style.animationDuration = `${duration}s`;
                
                particlesContainer.appendChild(particle);
            }
        }

        // Sistema de abas
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                const tabName = tab.getAttribute('data-tab');
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById(tabName).classList.add('active');
            });
        });
        
        // Toggle de senha
        document.getElementById('toggleLoginPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('loginPassword');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        document.getElementById('toggleSignupPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('signupPassword');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        document.getElementById('toggleSignupConfirmPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('signupConfirmPassword');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        // Validação do formulário de cadastro
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const password = document.getElementById('signupPassword').value;
            const confirmPassword = document.getElementById('signupConfirmPassword').value;
            const signupBtn = document.getElementById('signupBtn');
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('❌ As senhas não coincidem!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('❌ A senha deve ter pelo menos 6 caracteres!');
                return false;
            }
            
            signupBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando conta...';
            signupBtn.classList.add('loading');
        });
        
        // Loading no formulário de login
        document.getElementById('loginForm').addEventListener('submit', function() {
            const loginBtn = document.getElementById('loginBtn');
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entrando...';
            loginBtn.classList.add('loading');
        });
        
        // Processar sucesso de cadastro
        function processSignupSuccess() {
            if(document.querySelector('.alert-success')) {
                // Preencher o email no formulário de login
                const signupEmail = document.getElementById('signupEmail').value;
                if(signupEmail) {
                    document.getElementById('loginEmail').value = signupEmail;
                }
                
                // Mudar para a aba de login automaticamente após 2 segundos
                setTimeout(() => {
                    document.querySelector('[data-tab="login"]').click();
                    
                    // Adicionar foco no campo de email do login
                    setTimeout(() => {
                        document.getElementById('loginEmail').focus();
                    }, 500);
                }, 2000);
            }
        }
        
        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            processSignupSuccess();
        });
    </script>
</body>
</html>
