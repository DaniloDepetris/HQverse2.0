<?php
session_start();
require_once 'includes_auth.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HQ Verso - Sua plataforma de quadrinhos online</title>
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
            display: flex;
            flex-direction: column;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        .logo {
            font-size: 4rem;
            font-weight: 800;
            color: #e94560;
            text-decoration: none;
            letter-spacing: 2px;
            margin-bottom: 20px;
        }
        
        .tagline {
            font-size: 1.5rem;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        
        .hero-buttons {
            display: flex;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            border: none;
            font-size: 1.1rem;
        }
        
        .btn-primary {
            background: #e94560;
            color: white;
        }
        
        .btn-primary:hover {
            background: #d8345f;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(233, 69, 96, 0.3);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #e94560;
            color: #e94560;
        }
        
        .btn-outline:hover {
            background: rgba(233, 69, 96, 0.1);
            transform: translateY(-3px);
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 60px;
            width: 100%;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: #e94560;
            margin-bottom: 20px;
        }
        
        .feature-title {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: #e94560;
        }
        
        .feature-description {
            opacity: 0.8;
            line-height: 1.6;
        }
        
        .footer {
            margin-top: 60px;
            padding: 20px;
            text-align: center;
            opacity: 0.7;
            font-size: 0.9rem;
        }
        
        .user-status {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        @media (max-width: 768px) {
            .logo {
                font-size: 2.5rem;
            }
            
            .tagline {
                font-size: 1.2rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 200px;
            }
        }
    </style>
</head>
<body>
    <?php if($auth->isLoggedIn()): ?>
        <div class="user-status">
            <span>Olá, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <a href="comics.php" class="btn btn-outline" style="padding: 8px 15px; font-size: 0.9rem;">
                Entrar na Loja
            </a>
        </div>
    <?php endif; ?>
    
    <div class="container">
        <div class="hero-section">
            <a href="comics.php" class="logo">HQ VERSO</a>
            <p class="tagline">Descubra um universo infinito de quadrinhos e graphic novels</p>
            
            <div class="hero-buttons">
                <?php if($auth->isLoggedIn()): ?>
                    <a href="comics.php" class="btn btn-primary">
                        <i class="fas fa-rocket"></i> Explorar Quadrinhos
                    </a>
                    <a href="perfil.php" class="btn btn-outline">
                        <i class="fas fa-user"></i> Meu Perfil
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Fazer Login
                    </a>
                    <a href="login.php" class="btn btn-outline">
                        <i class="fas fa-user-plus"></i> Criar Conta
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <h3 class="feature-title">Leitura Imeriva</h3>
                <p class="feature-description">Leia seus quadrinhos favoritos com uma experiência de leitura otimizada para qualquer dispositivo.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3 class="feature-title">Busca Inteligente</h3>
                <p class="feature-description">Encontre exatamente o que procura com nosso sistema de busca avançado por título, autor ou categoria.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3 class="feature-title">Favoritos</h3>
                <p class="feature-description">Salve seus quadrinhos favoritos e continue de onde parou a qualquer momento.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3 class="feature-title">Totalmente Responsivo</h3>
                <p class="feature-description">Acesse de qualquer dispositivo - computador, tablet ou smartphone.</p>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>HQ Verso © 2024 - Todos os direitos reservados</p>
        <p style="margin-top: 10px; font-size: 0.8rem;">
            <a href="detection.html" style="color: #e94560; text-decoration: none;">
                <i class="fas fa-info-circle"></i> Informações do Navegador
            </a>
        </p>
    </div>

    <script>
        console.log('Página inicial carregada com sucesso!');
        
        // Animação suave para os cards
        document.querySelectorAll('.feature-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 200 * index);
        });
    </script>
</body>
</html>
