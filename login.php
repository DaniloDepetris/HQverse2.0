<?php
require_once 'includes_auth.php';

if($auth->isLoggedIn()) {
    header("Location: comics.php");
    exit();
}

$error = '';
$success = '';

if($_POST) {
    if(isset($_POST['login'])) {
        $result = $auth->login($_POST['email'], $_POST['password']);
        if($result === true) {
            header("Location: comics.php");
            exit();
        } else {
            $error = $result;
        }
    } elseif(isset($_POST['signup'])) {
        $result = $auth->register($_POST['name'], $_POST['email'], $_POST['password']);
        if($result === true) {
            $success = "Cadastro realizado com sucesso! Faça login para continuar.";
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
    <title>HQ Verso - Login e Cadastro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
body {
    background: 
        /* Overlay escuro estilo Netflix para melhor contraste */
        linear-gradient(
            to bottom,
            rgba(0, 0, 0, 0.8) 0%,
            rgba(0, 0, 0, 0.6) 50%,
            rgba(0, 0, 0, 0.8) 100%
        ),
        /* Sua imagem do Superman */
        url('tumblr_667a09c787f3da6959928538a675cc81_5c6dcbf7_1280.jpg') 
        center/cover no-repeat fixed;
    
    color: #fff;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    position: relative;
}

/* Efeito de brilho sutil para destacar o container */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(
            ellipse at center,
            rgba(233, 69, 96, 0.15) 0%,
            rgba(15, 52, 96, 0.15) 50%,
            transparent 70%
        );
    z-index: -1;
    pointer-events: none;
}
        
   .auth-container {
    background-color: rgba(15, 15, 25, 0.92);
    backdrop-filter: blur(12px);
    border-radius: 12px;
    box-shadow: 
        0 20px 40px rgba(0, 0, 0, 0.7),
        0 0 0 1px rgba(255, 255, 255, 0.05);
    width: 100%;
    max-width: 450px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.08);
    position: relative;
    z-index: 1;
}
        
        .alert {
            padding: 15px;
            margin: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: 500;
        }
        
        .alert-error {
            background: rgba(233, 69, 96, 0.2);
            border: 1px solid #e94560;
            color: #e94560;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid #4caf50;
            color: #4caf50;
        }
        
        .auth-header {
            background: #0f3460;
            padding: 30px 20px;
            text-align: center;
        }
        
        .auth-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #e94560;
            font-weight: 800;
            letter-spacing: 1px;
        }
        
        .auth-header p {
            font-size: 16px;
            opacity: 0.8;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #2d3748;
        }
        
        .tab {
            flex: 1;
            padding: 18px;
            text-align: center;
            background: #1a1a2e;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 16px;
        }
        
        .tab.active {
            background: #0f3460;
            color: #e94560;
            border-bottom: 3px solid #e94560;
        }
        
        .tab-content {
            display: none;
            padding: 30px;
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
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px;
            border: 1px solid #2d3748;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-size: 16px;
            transition: border 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #e94560;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
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
            box-shadow: 0 5px 15px rgba(233, 69, 96, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #e94560;
            color: #e94560;
            margin-top: 15px;
        }
        
        .btn-outline:hover {
            background: rgba(233, 69, 96, 0.1);
        }
        
        .separator {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 25px 0;
            color: #718096;
            font-size: 14px;
        }
        
        .separator::before,
        .separator::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #2d3748;
        }
        
        .separator::before {
            margin-right: 10px;
        }
        
        .separator::after {
            margin-left: 10px;
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
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .facebook {
            background: #3b5998;
        }
        
        .google {
            background: #db4437;
        }
        
        .twitter {
            background: #1da1f2;
        }
        
        .social-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .auth-footer {
            text-align: center;
            padding: 20px;
            font-size: 14px;
            background: rgba(15, 52, 96, 0.2);
        }
        
        .auth-footer a {
            color: #e94560;
            text-decoration: none;
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
        
        @media (max-width: 480px) {
            .auth-container {
                border-radius: 0;
            }
            
            .tab-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>HQ VERSO</h1>
            <p>Entre no universo dos quadrinhos</p>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" data-tab="login">Entrar</div>
            <div class="tab" data-tab="signup">Cadastrar</div>
        </div>
        
        <div class="tab-content active" id="login">
            <form method="POST" id="loginForm">
                <input type="hidden" name="login" value="1">
                <div class="form-group">
                    <label for="loginEmail">Email</label>
                    <input type="email" id="loginEmail" name="email" placeholder="seu@email.com" required>
                </div>
                
                <div class="form-group password-toggle">
                    <label for="loginPassword">Senha</label>
                    <input type="password" id="loginPassword" name="password" placeholder="Sua senha" required>
                    <i class="fas fa-eye" id="toggleLoginPassword"></i>
                </div>
                
                <button type="submit" class="btn btn-primary">Entrar</button>
            </form>
            
            <div class="separator">ou entre com</div>
            
            <div class="social-login">
                <div class="social-btn facebook">
                    <i class="fab fa-facebook-f"></i>
                </div>
                <div class="social-btn google">
                    <i class="fab fa-google"></i>
                </div>
                <div class="social-btn twitter">
                    <i class="fab fa-twitter"></i>
                </div>
            </div>
            
            <button class="btn btn-outline" onclick="window.location.href='index.html'">
                <i class="fas fa-arrow-left"></i> Voltar para o início
            </button>
        </div>
        
        <div class="tab-content" id="signup">
            <form method="POST" id="signupForm">
                <input type="hidden" name="signup" value="1">
                <div class="form-group">
                    <label for="signupName">Nome de usuário</label>
                    <input type="text" id="signupName" name="name" placeholder="Seu nome de usuário" required>
                </div>
                
                <div class="form-group">
                    <label for="signupEmail">Email</label>
                    <input type="email" id="signupEmail" name="email" placeholder="seu@email.com" required>
                </div>
                
                <div class="form-group password-toggle">
                    <label for="signupPassword">Senha</label>
                    <input type="password" id="signupPassword" name="password" placeholder="Crie uma senha forte" required>
                    <i class="fas fa-eye" id="toggleSignupPassword"></i>
                </div>
                
                <div class="form-group password-toggle">
                    <label for="signupConfirmPassword">Confirmar senha</label>
                    <input type="password" id="signupConfirmPassword" placeholder="Digite sua senha novamente" required>
                    <i class="fas fa-eye" id="toggleSignupConfirmPassword"></i>
                </div>
                
                <button type="submit" class="btn btn-primary">Criar conta</button>
            </form>
            
            <div class="separator">ou cadastre-se com</div>
            
            <div class="social-login">
                <div class="social-btn facebook">
                    <i class="fab fa-facebook-f"></i>
                </div>
                <div class="social-btn google">
                    <i class="fab fa-google"></i>
                </div>
                <div class="social-btn twitter">
                    <i class="fab fa-twitter"></i>
                </div>
            </div>
            
            <button class="btn btn-outline" onclick="window.location.href='index.html'">
                <i class="fas fa-arrow-left"></i> Voltar para o início
            </button>
        </div>
        
        <div class="auth-footer">
            Ao continuar, você concorda com os <a href="#">Termos de Uso</a> e a <a href="#">Política de Privacidade</a> do HQ Verso.
            <div style="margin-top: 10px;">
                <a href="detection.html" style="color: #e94560; text-decoration: underline;">Ver informações do navegador</a>
            </div>
        </div>
    </div>

    <script>
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
        
        document.getElementById('toggleLoginPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('loginPassword');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
        
        document.getElementById('toggleSignupPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('signupPassword');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
        
        document.getElementById('toggleSignupConfirmPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('signupConfirmPassword');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
        
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const password = document.getElementById('signupPassword').value;
            const confirmPassword = document.getElementById('signupConfirmPassword').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('As senhas não coincidem!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('A senha deve ter pelo menos 6 caracteres!');
                return false;
            }
        });
        
        document.querySelectorAll('.social-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                alert('Funcionalidade de login social será implementada em breve!');
            });
        });
    </script>
</body>

</html>
