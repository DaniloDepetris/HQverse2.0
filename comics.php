<?php
require_once 'includes_auth.php';

if(!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_data = $auth->getUserData($_SESSION['user_id']);
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
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            min-height: 100vh;
            padding: 0;
            margin: 0;
        }
        
        /* Modo Claro */
        body.light-mode {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
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
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .search-bar {
            display: flex;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            padding: 8px 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        body.light-mode .search-bar {
            background: rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        
        .search-bar input {
            background: transparent;
            border: none;
            color: white;
            padding: 5px 10px;
            width: 250px;
            outline: none;
        }
        
        body.light-mode .search-bar input {
            color: #333;
        }
        
        .search-bar input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        body.light-mode .search-bar input::placeholder {
            color: #718096;
        }
        
        .search-bar button {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            padding: 5px;
        }
        
        body.light-mode .search-bar button {
            color: #718096;
        }
        
        .user-avatar {
            position: relative;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #e94560;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .user-avatar:hover {
            transform: scale(1.05);
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(26, 26, 46, 0.95);
            border-radius: 8px;
            padding: 10px 0;
            min-width: 200px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            display: none;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        body.light-mode .user-dropdown {
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .user-dropdown a {
            display: block;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        
        body.light-mode .user-dropdown a {
            color: #333;
        }
        
        .user-dropdown a:hover {
            background: rgba(233, 69, 96, 0.2);
        }
        
        .user-avatar:hover .user-dropdown {
            display: block;
        }
        
        .categories {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .category {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        body.light-mode .category {
            background: rgba(0, 0, 0, 0.05);
            color: #4a5568;
        }
        
        .category.active {
            background: #e94560;
            color: white;
        }
        
        .category:hover {
            background: rgba(233, 69, 96, 0.7);
        }
        
        .comic-counter {
            text-align: center;
            margin: 10px 0 20px 0;
            font-size: 14px;
            opacity: 0.8;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        body.light-mode .comic-counter {
            color: #4a5568;
        }
        
        .featured-comic {
            position: relative;
            height: 500px;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 40px;
            background: linear-gradient(135deg, #0f3460 0%, #1a1a2e 100%);
            background-image: url('https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT_1OXqNiK7qA0H461zQkADUkI6EwQIiExA3w&s');
            background-size: cover;
            background-position: center;
        }
        
        .featured-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.9));
            padding: 40px;
            color: white;
        }
        
        .featured-title {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .featured-description {
            font-size: 1.1rem;
            margin-bottom: 20px;
            opacity: 0.9;
            max-width: 600px;
        }
        
        .featured-actions {
            display: flex;
            gap: 15px;
        }
        
        .comics-section {
            margin-bottom: 50px;
        }
        
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        body.light-mode .section-title {
            color: #2d3748;
        }
        
        /* CARDS DE QUADRINHOS - ESTILO UNIFICADO */
        .comics-carousel {
            display: flex;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding: 10px;
            gap: 15px;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .comics-carousel::-webkit-scrollbar {
            display: none;
        }

        .comic-card {
            position: relative;
            width: 200px;
            flex-shrink: 0;
            border-radius: 12px;
            overflow: visible;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .comic-cover {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(4,22,47,0.6);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        /* Overlay inferior estilo Netflix */
        .comic-card .card-bottom {
            position: absolute;
            left: 12px;
            right: 12px;
            bottom: 12px;
            background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.8) 100%);
            padding: 12px;
            border-radius: 8px;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .comic-card:hover .card-bottom {
            opacity: 1;
        }

        .comic-card .card-bottom .title-meta {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
        }

        .comic-card .card-bottom .comic-title {
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.2;
            margin: 0;
        }

        .comic-card .card-bottom .comic-meta {
            font-size: 0.85rem;
            opacity: 0.85;
            margin: 0;
        }

        /* Badge "Ler agora" */
        .comic-card .read-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: #e94560;
            color: white;
            padding: 8px 10px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
            box-shadow: 0 6px 18px rgba(0,0,0,0.35);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .comic-card:hover .read-badge {
            opacity: 1;
        }

        /* Botões de ação */
        .card-actions {
            padding: 0;
        }

        .card-actions .btn-outline {
            background: rgba(255, 255, 255, 0.9);
            color: #e94560;
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .card-actions .btn-outline:hover {
            background: #e94560;
            color: white;
            transform: scale(1.1);
        }

        /* Efeitos hover */
        .comic-card:hover {
            transform: translateY(-8px) scale(1.02);
            z-index: 5;
        }

        .comic-card:hover .comic-cover {
            box-shadow: 0 15px 40px rgba(4,22,47,0.8);
        }

        body.light-mode .comic-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        /* Barra de progresso */
        .progress {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 0 0 10px 10px;
        }

        body.light-mode .progress {
            background: rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            height: 100%;
            background: #e94560;
            transition: width 0.3s ease;
            border-radius: 0 0 10px 10px;
        }

        /* Remove o info antigo */
        .comic-info {
            display: none !important;
        }

        /* BOTÕES GERAIS */
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            border: none;
            font-size: 0.9rem;
            text-align: center;
        }
        
        .btn-primary {
            background: #e94560;
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            background: #d8345f;
        }
        
        .btn-play {
            background: #e94560;
            color: white;
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
        
        /* CARROSSEL COM SETAS */
        .carousel-container {
            position: relative;
            margin: 0 -10px;
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: rgba(233, 69, 96, 0.9);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 100;
            opacity: 0;
            visibility: hidden;
        }

        .carousel-container:hover .carousel-nav {
            opacity: 1;
            visibility: visible;
        }

        .carousel-nav:hover {
            background: #e94560;
            transform: translateY(-50%) scale(1.1);
        }

        .carousel-nav.prev {
            left: 10px;
        }

        .carousel-nav.next {
            right: 10px;
        }

        .carousel-nav:disabled {
            background: rgba(255, 255, 255, 0.3);
            cursor: not-allowed;
            opacity: 0.5;
        }

        body.light-mode .carousel-nav:disabled {
            background: rgba(0, 0, 0, 0.2);
        }

        /* Seção Continuar Lendo */
        .continue-row .comic-card {
            min-width: 200px;
            flex-shrink: 0;
        }
        
        .browser-info-link {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(15, 52, 96, 0.8);
            color: white;
            padding: 12px 15px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        body.light-mode .browser-info-link {
            background: rgba(233, 69, 96, 0.8);
            color: white;
        }
        
        .browser-info-link:hover {
            background: rgba(15, 52, 96, 1);
            transform: translateY(-2px);
        }
        
        body.light-mode .browser-info-link:hover {
            background: rgba(233, 69, 96, 1);
        }
        
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
        
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        .no-comics {
            text-align: center;
            padding: 40px;
            opacity: 0.7;
            font-style: italic;
        }
        
        /* RESPONSIVIDADE */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 15px;
            }
            
            .header-right {
                width: 100%;
                justify-content: space-between;
            }
            
            .search-bar input {
                width: 150px;
            }
            
            .featured-comic {
                height: 300px;
            }
            
            .featured-title {
                font-size: 1.8rem;
            }
            
            .featured-description {
                font-size: 1rem;
            }
            
            .comic-card {
                width: 160px;
            }
            
            .comic-cover {
                height: 240px;
            }

            .carousel-nav {
                width: 35px;
                height: 35px;
                font-size: 14px;
                opacity: 1 !important;
                visibility: visible !important;
            }

            .carousel-nav.prev {
                left: 5px;
            }

            .carousel-nav.next {
                right: 5px;
            }
        }
</style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
    </button>
    
    <div class="container comics-page" id="comicsPage">
        <header>
            <div class="header-left">
                <a href="#" class="logo">HQ VERSO</a>
            </div>
            <div class="header-right">
                <div class="search-bar">
                    <input type="text" placeholder="Buscar quadrinhos..." aria-label="Buscar quadrinhos">
                    <button type="button" aria-label="Buscar"><i class="fas fa-search" aria-hidden="true"></i><span class="sr-only">Buscar</span></button>
                </div>
                <div class="user-avatar" id="userAvatar" aria-label="Menu do usuário" tabindex="0">
                    <span class="avatar-letter"><?php echo strtoupper(substr($user_data['username'], 0, 1)); ?></span>
                    <div class="user-dropdown" role="menu" aria-hidden="true">
                        <a href="perfil.php">Meu Perfil</a>
                        <?php if($auth->isAdmin()): ?>
                            <a href="admin.php">Painel Admin</a>
                        <?php endif; ?>
                        <a href="detection.html">Informações do Navegador</a>
                        <a href="logout.php" id="logoutLink">Sair</a>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="categories">
            <div class="category active" data-category="all">Todos</div>
            <div class="category" data-category="super-herois">Super-heróis</div>
            <div class="category" data-category="manga">Mangá</div>
            <div class="category" data-category="graphic-novels">Graphic Novels</div>
            <div class="category" data-category="classicos">Clássicos</div>
            <div class="category" data-category="indie">Indie</div>
            <div class="category" data-category="favoritos">Favoritos</div>
        </div>
        
        <div class="featured-comic" style="background-image: url('https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT_1OXqNiK7qA0H461zQkADUkI6EwQIiExA3w&s')">
            <div class="featured-overlay">
                <h2 class="featured-title">Batman: O Cavaleiro das Trevas</h2>
                <p class="featured-description">A obra-prima de Frank Miller que redefiniu o Batman para sempre.</p>
                <div class="featured-actions">
                    <button class="btn btn-play"><i class="fas fa-play"></i> Ler agora</button>
                    <button class="btn btn-outline"><i class="fas fa-plus"></i> Minha lista</button>
                </div>
            </div>
        </div>
        
        <div class="comics-section continue-row">
            <h3 class="section-title">Continuar Assistindo</h3>
            <div class="carousel-container">
                <div class="comics-carousel" id="continueReading">
                    <div class="comic-card" data-id="1" data-progress="20">
                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTg9az9jtGfQoj20u_4hUEeTQXvXvX9i0W2KPKw&s" alt="Capa do quadrinho Homem-Aranha: A Última Caçada" class="comic-cover" onerror="this.dataset.broken='1';this.src='https://via.placeholder.com/380x580?text=Imagem+Indispon%C3%ADvel';">
                        <div class="comic-info">
                            <div class="comic-title">Homem-Aranha: A Última Caçada</div>
                            <div class="comic-meta">Capítulo 3</div>
                        </div>
                        <div class="card-actions">
                            <button class="btn btn-primary read-now" data-id="1" type="button">Ler agora</button>
                        </div>
                        <div class="progress"><div class="progress-bar" style="width:20%"></div></div>
                    </div>
                    <div class="comic-card" data-id="2" data-progress="45">
                        <img src="https://upload.wikimedia.org/wikipedia/pt/d/d0/Watchmen.jpg" alt="Capa do quadrinho Watchmen" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">Watchmen</div>
                            <div class="comic-meta">Página 45</div>
                        </div>
                        <div class="card-actions">
                            <button class="btn btn-primary read-now" data-id="2" type="button">Ler agora</button>
                        </div>
                        <div class="progress"><div class="progress-bar" style="width:45%"></div></div>
                    </div>
                    <div class="comic-card" data-id="3" data-progress="60">
                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcReNzUJRFsrqKb-Y4UIxg-jUSPYg-4ermAT3w&s" alt="Capa do quadrinho" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">Sandman: Prelúdios e Noturnos</div>
                            <div class="comic-meta">Volume 1</div>
                        </div>
                        <div class="card-actions">
                            <button class="btn btn-primary read-now" data-id="3" type="button">Ler agora</button>
                        </div>
                        <div class="progress"><div class="progress-bar" style="width:60%"></div></div>
                    </div>
                    <div class="comic-card" data-id="4" data-progress="50">
                        <img src="https://m.media-amazon.com/images/I/711dLCQ6kuL._UF1000,1000_QL80_.jpg" alt="Capa do quadrinho" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">V de Vingança</div>
                            <div class="comic-meta">50% lido</div>
                        </div>
                        <div class="card-actions">
                            <button class="btn btn-primary read-now" data-id="4" type="button">Ler agora</button>
                        </div>
                        <div class="progress"><div class="progress-bar" style="width:50%"></div></div>
                    </div>
                    <div class="comic-card" data-id="5" data-progress="10">
                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSmC8oS-dR6n1EW5YxEnvL0mPaz13taAKsbvQ&s" alt="Capa do quadrinho" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">X-Men: Fênix Negra</div>
                            <div class="comic-meta">Edição 135</div>
                        </div>
                        <div class="card-actions">
                            <button class="btn btn-primary read-now" data-id="5" type="button">Ler agora</button>
                        </div>
                        <div class="progress"><div class="progress-bar" style="width:10%"></div></div>
                    </div>
                </div>
                <button class="carousel-nav prev" onclick="scrollCarousel('continueReading', -300)">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                </button>
                <button class="carousel-nav next" onclick="scrollCarousel('continueReading', 300)">
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
            <!-- Info panel that shows current centered card title/meta and progress -->
            <div class="continue-info" id="continueInfo">
                <div class="info-inner">
                    <div class="info-text">
                        <div class="info-title" id="infoTitle">Homem-Aranha: A Última Caçada</div>
                        <div class="info-meta" id="infoMeta">Capítulo 3</div>
                    </div>
                    <div class="info-progress">
                        <div class="progress-track">
                            <div class="progress-indicator" id="infoProgress" style="width:20%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="comics-section">
            <h3 class="section-title">Recomendados para você</h3>
            <div class="carousel-container">
                <div class="comics-carousel" id="recommendedComics">
                    <div class="comic-card">
                        <img src="https://m.media-amazon.com/images/I/916IgqQ-54L.jpg" alt="Capa do quadrinho" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">Maus</div>
                            <div class="comic-meta">Art Spiegelman</div>
                        </div>
                    </div>
                    <div class="comic-card">
                        <img src="https://m.media-amazon.com/images/I/814zhAWOKBL._UF1000,1000_QL80_.jpg" alt="Capa do quadrinho" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">Persépolis</div>
                            <div class="comic-meta">Marjane Satrapi</div>
                        </div>
                    </div>
                    <div class="comic-card">
                        <img src="https://images.tcdn.com.br/img/img_prod/1119494/hellboy_omnibus_vol_3_1709745_1_a6e86cfa8f53f219f4d0d0b0c4f79558.jpg" alt="Capa do quadrinho" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">Hellboy. Caçada Selvagem</div>
                            <div class="comic-meta">Mike Mignola</div>
                        </div>
                    </div>
                    <div class="comic-card">
                        <img src="https://m.media-amazon.com/images/I/81s49EEptML.jpg" alt="Capa do quadrinho" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">Saga</div>
                            <div class="comic-meta">Brian K. Vaughan</div>
                        </div>
                    </div>
                    <div class="comic-card">
                        <img src="https://m.media-amazon.com/images/I/81bGs636lzL.jpg" alt="Capa do quadrinho" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">Monstress</div>
                            <div class="comic-meta">Marjorie Liu</div>
                        </div>
                    </div>
                </div>
                <button class="carousel-nav prev" onclick="scrollCarousel('recommendedComics', -300)">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                </button>
                <button class="carousel-nav next" onclick="scrollCarousel('recommendedComics', 300)">
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        
        <div class="comics-section">
            <h3 class="section-title">Clássicos da DC</h3>
            <div class="carousel-container">
                <div class="comics-carousel" id="dcClassics">
                    <div class="comic-card">
                        <img src="https://m.media-amazon.com/images/I/91wpPruCKrL._UF1000,1000_QL80_.jpg" alt="Capa do quadrinho" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">Superman: Terra Um</div>
                            <div class="comic-meta">2010</div>
                        </div>
                    </div>
                    <div class="comic-card">
                        <img src="https://super.abril.com.br/wp-content/uploads/2018/07/torredebabel.jpg" alt="Capa do quadrinho" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">Liga da Justiça: A Torre de Babel</div>
                            <div class="comic-meta">2000</div>
                        </div>
                    </div>
                    <div class="comic-card">
                        <img src="https://cdn.awsli.com.br/600x450/1668/1668242/produto/162790896905299a6a0.jpg" alt="Capa do quadrinho" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">Mulher-Maravilha: Deuses e Mortais</div>
                            <div class="comic-meta">1987</div>
                        </div>
                    </div>
                    <div class="comic-card">
                        <img src="https://m.media-amazon.com/images/I/91dXNvO2fML.jpg" alt="Capa do quadrinho" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">Flashpoint</div>
                            <div class="comic-meta">2011</div>
                        </div>
                    </div>
                    <div class="comic-card">
                        <img src="https://rika.vtexassets.com/arquivos/ids/219835/-herois_panini-arqueiro-verde-ano-um.jpg?v=635316153891630000" alt="Capa do quadrinho" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">Arqueiro Verde: Ano Um</div>
                            <div class="comic-meta">2007</div>
                        </div>
                    </div>
                </div>
                <button class="carousel-nav prev" onclick="scrollCarousel('dcClassics', -300)">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                </button>
                <button class="carousel-nav next" onclick="scrollCarousel('dcClassics', 300)">
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        
        <div class="comics-section">
            <h3 class="section-title">Marvel Essentials</h3>
            <div class="carousel-container">
                <div class="comics-carousel" id="popularManga">
                    <div class="comic-card">
                        <img src="https://m.media-amazon.com/images/I/81bGs636lzL.jpg" alt="Capa do quadrinho" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">Homem de Ferro: Extremis</div>
                        </div>
                    </div>
                    <div class="comic-card">
                        <img src="https://m.media-amazon.com/images/I/611wcUISMmL._UF1000,1000_QL80_.jpg" alt="Capa do quadrinho" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">Capitão América: O Soldado Invernal</div>
                            <div class="comic-meta">2005</div>
                        </div>
                    </div>
                    <div class="comic-card">
                        <img src="https://m.media-amazon.com/images/I/91JTRo6EFcL._UF1000,1000_QL80_.jpg" alt="Capa do quadrinho" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">Thor: Deus do Trovão</div>
                            <div class="comic-meta">2012</div>
                        </div>
                    </div>
                    <div class="comic-card">
                        <img src="https://m.media-amazon.com/images/I/91DcEu1b-rL.jpg" alt="Capa do quadrinho" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">Doutor Estranho: O Juramento</div>
                            <div class="comic-meta">2006</div>
                        </div>
                    </div>
                    <div class="comic-card">
                        <img src="https://d14d9vp3wdof84.cloudfront.net/image/589816272436/image_v8bl17fqv95mf8v1jd9k8lrp5r/-S897-FWEBP" alt="Capa do quadrinho" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">Pantera Negra: Rei do Wakanda</div>
                            <div class="comic-meta">2016</div>
                        </div>
                    </div>
                    <div class="comic-card">
                        <img src="https://lh3.googleusercontent.com/proxy/9y2rp6F2x4dSCvFkZoz847oXtBE8IP0mscS0W0SkYpRtdub4qCQRCzj-Qwfgd4BWQq6EtqSmr7edCB_rNckNCs8pGT8jFx0HdMknRmb_1EmPWIb5zuujbw" alt="Batman: Silêncio" class="comic-cover">
                        <div class="comic-info">
                            <div class="comic-title">Batman: Silêncio</div>
                            <div class="comic-meta">Jeph Loeb</div>
                        </div>
                    </div>
                </div>
                <button class="carousel-nav prev" onclick="scrollCarousel('popularManga', -300)">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                </button>
                <button class="carousel-nav next" onclick="scrollCarousel('popularManga', 300)">
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>

    <a href="detection.html" class="browser-info-link">
        <i class="fas fa-info-circle"></i> Info Navegador
    </a>

    <script>
        // Dados completos dos quadrinhos
        const comicsData = {
            "all": [
                {
                    id: 1,
                    title: "Homem-Aranha: A Última Caçada",
                    cover: "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTg9az9jtGfQoj20u_4hUEeTQXvXvX9i0W2KPKw&s",
                    meta: "Capítulo 3",
                    progress: 20,
                    categories: ["super-herois", "classicos"],
                    description: "A clássica história onde o Homem-Aranha enfrenta seu maior desafio."
                },
                {
                    id: 2,
                    title: "Watchmen",
                    cover: "https://upload.wikimedia.org/wikipedia/pt/d/d0/Watchmen.jpg",
                    meta: "Página 45",
                    progress: 45,
                    categories: ["super-herois", "graphic-novels", "classicos"],
                    description: "A revolucionária graphic novel que questiona a natureza dos super-heróis."
                },
                {
                    id: 3,
                    title: "Sandman: Prelúdios e Noturnos",
                    cover: "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcReNzUJRFsrqKb-Y4UIxg-jUSPYg-4ermAT3w&s",
                    meta: "Volume 1",
                    progress: 60,
                    categories: ["graphic-novels", "classicos"],
                    description: "A primeira coleção da aclamada série de Neil Gaiman."
                },
                {
                    id: 4,
                    title: "V de Vingança",
                    cover: "https://m.media-amazon.com/images/I/711dLCQ6kuL._UF1000,1000_QL80_.jpg",
                    meta: "50% lido",
                    progress: 50,
                    categories: ["graphic-novels", "classicos"],
                    description: "A distópica graphic novel sobre anarquia e liberdade."
                },
                {
                    id: 5,
                    title: "Maus",
                    cover: "https://m.media-amazon.com/images/I/916IgqQ-54L.jpg",
                    meta: "Art Spiegelman",
                    categories: ["graphic-novels", "classicos"],
                    description: "A premiada graphic novel sobre o Holocausto."
                },
                {
                    id: 6,
                    title: "Persépolis",
                    cover: "https://via.placeholder.com/200x300?text=Persepolis",
                    meta: "Marjane Satrapi",
                    categories: ["graphic-novels", "classicos"],
                    description: "A autobiografia em quadrinhos sobre o Irã revolucionário."
                },
                {
                    id: 7,
                    title: "Hellboy Omnibus Vol 3",
                    cover: "https://images.tcdn.com.br/img/img_prod/1119494/hellboy_omnibus_vol_3_1709745_1_a6e86cfa8f53f219f4d0d0b0c4f79558.jpg",
                    meta: "Mike Mignola",
                    categories: ["super-herois", "indie"],
                    description: "As primeiras aventuras do demônio herói."
                },
                {
                    id: 8,
                    title: "Saga",
                    cover: "https://m.media-amazon.com/images/I/81s49EEptML.jpg",
                    meta: "Brian K. Vaughan",
                    categories: ["graphic-novels", "indie"],
                    description: "A épica space opera de ficção científica."
                },
                {
                    id: 9,
                    title: "Superman: Terra Um",
                    cover: "https://m.media-amazon.com/images/I/91wpPruCKrL._UF1000,1000_QL80_.jpg",
                    meta: "2010",
                    categories: ["super-herois", "classicos"],
                    description: "Uma reinterpretação moderna do Homem de Aço."
                },
                {
                    id: 10,
                    title: "Liga da Justiça: A Torre de Babel",
                    cover: "https://super.abril.com.br/wp-content/uploads/2018/07/torredebabel.jpg",
                    meta: "2000",
                    categories: ["super-herois", "classicos"],
                    description: "Quando Batman se torna a maior ameaça da Liga."
                },
                {
                    id: 11,
                    title: "Akira",
                    cover: "https://m.media-amazon.com/images/I/81K1+Z+Yf+L.jpg",
                    meta: "Katsuhiro Otomo",
                    categories: ["manga", "classicos"],
                    description: "A épica cyberpunk que revolucionou os mangás."
                },
                {
                    id: 12,
                    title: "Death Note",
                    cover: "https://m.media-amazon.com/images/I/81MZ6eFQsfL.jpg",
                    meta: "Tsugumi Ohba",
                    categories: ["manga"],
                    description: "Um estudante genius encontra um caderno que pode matar pessoas."
                },
                {
                    id: 13,
                    title: "Attack on Titan",
                    cover: "https://m.media-amazon.com/images/I/81d6e+kN5+L.jpg",
                    meta: "Hajime Isayama",
                    categories: ["manga"],
                    description: "Humanidade luta pela sobrevivência contra titãs gigantes."
                },
                {
                    id: 14,
                    title: "One-Punch Man",
                    cover: "https://m.media-amazon.com/images/I/81I1+-+0R0L.jpg",
                    meta: "ONE",
                    categories: ["manga", "super-herois"],
                    description: "Um herói tão forte que derrota qualquer inimigo com um só soco."
                },
                {
                    id: 15,
                    title: "Scott Pilgrim",
                    cover: "https://m.media-amazon.com/images/I/81K1+Z+Yf+L.jpg",
                    meta: "Bryan Lee O'Malley",
                    categories: ["indie", "graphic-novels"],
                    description: "Um baixista deve derrotar os 7 ex-namorados malvados de sua amada."
                },
                {
                    id: 16,
                    title: "Batman: Ano Um",
                    cover: "https://m.media-amazon.com/images/I/81zK5OjR5aL.jpg",
                    meta: "Frank Miller",
                    categories: ["super-herois", "classicos"],
                    description: "A origem definitiva do Cavaleiro das Trevas."
                },
                {
                    id: 17,
                    title: "X-Men: Fênix Negra",
                    cover: "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSmC8oS-dR6n1EW5YxEnvL0mPaz13taAKsbvQ&s",
                    meta: "Chris Claremont",
                    categories: ["super-herois", "classicos"],
                    description: "A épica saga onde Jean Grey se torna a Fênix Negra."
                },
                {
                    id: 18,
                    title: "Monstress",
                    cover: "https://m.media-amazon.com/images/I/81bGs636lzL.jpg",
                    meta: "Marjorie Liu",
                    categories: ["graphic-novels", "indie"],
                    description: "Fantasia sombria em um mundo de guerra e monstros."
                }
            ],
            "super-herois": [1, 2, 7, 9, 10, 14, 16, 17],
            "manga": [11, 12, 13, 14],
            "graphic-novels": [2, 3, 4, 5, 6, 8, 15, 18],
            "classicos": [1, 2, 3, 4, 5, 6, 9, 10, 11, 16, 17],
            "indie": [7, 8, 15, 18],
            "favoritos": [1, 3, 5, 8, 11, 14, 16]
        };

        // Função para criar card de quadrinho (garante layout horizontal)
        function createComicCard(comic, showProgress = false) {
    const placeholder = `https://via.placeholder.com/200x300/1a1a2e/e94560?text=${encodeURIComponent((comic.title||'').substring(0, 15))}`;
    const categories = Array.isArray(comic.categories) ? comic.categories.join(',') : '';
    const meta = comic.meta || '';

    return `
        <div class="comic-card" data-id="${comic.id}" data-categories="${categories}">
            <span class="read-badge">Ler agora</span>
            <img src="${comic.cover || placeholder}" 
                 alt="Capa do quadrinho ${comic.title || ''}" 
                 class="comic-cover"
                 onerror="this.src='${placeholder}'">
            <div class="card-bottom">
                <div class="title-meta">
                    <div class="comic-title">${comic.title || ''}</div>
                    <div class="comic-meta">${meta}</div>
                </div>
                <div class="card-actions">
                    <button class="btn btn-outline add-list" title="Adicionar à lista">+</button>
                </div>
            </div>
            ${showProgress && comic.progress ? `
            <div class="progress"><div class="progress-bar" style="width:${comic.progress}%"></div></div>
            ` : ''}
        </div>
    `;
}

        // Função para filtrar quadrinhos por categoria
        function filterComicsByCategory(category) {
            const allComics = comicsData.all;
            let filteredComics = [];
            
            if (category === 'all') {
                filteredComics = allComics;
            } else if (category === 'favoritos') {
                const favoriteIds = comicsData.favoritos;
                filteredComics = allComics.filter(comic => favoriteIds.includes(comic.id));
            } else {
                const categoryIds = comicsData[category] || [];
                filteredComics = allComics.filter(comic => categoryIds.includes(comic.id));
            }
            
            return filteredComics;
        }

        // Função para renderizar quadrinhos em uma seção (garante layout horizontal)
        function renderComicsInSection(sectionId, comics, showProgress = false) {
            const section = document.getElementById(sectionId);
            if (!section) return;

            if (!comics || comics.length === 0) {
                section.innerHTML = '<div class="no-comics">Nenhum quadrinho encontrado</div>';
                return;
            }

            section.innerHTML = '';

            comics.forEach(comic => {
                section.innerHTML += createComicCard(comic, showProgress);
            });

            applyCardHoverEffects();
            // inicializa o comportamento de scroll horizontal após inserir os cards
            initComicsCarouselScroll();
            // atualizar navegação (setas)
            updateCarouselNavVisibility(sectionId);
        }

        // Função para atualizar visibilidade das setas
        function updateCarouselNavVisibility(carouselId) {
            const carousel = document.getElementById(carouselId);
            if (!carousel) return;
            const container = carousel.closest('.carousel-container');
            if (!container) return;
            const prevBtn = container.querySelector('.carousel-nav.prev');
            const nextBtn = container.querySelector('.carousel-nav.next');

            if (!prevBtn || !nextBtn) return;

            // Sempre mostrar setas se houver conteúdo
            if (carousel.children.length > 0) {
                prevBtn.style.opacity = '0.7';
                prevBtn.style.visibility = 'visible';
                nextBtn.style.opacity = '0.7';
                nextBtn.style.visibility = 'visible';
            } else {
                prevBtn.style.opacity = '';
                prevBtn.style.visibility = '';
                nextBtn.style.opacity = '';
                nextBtn.style.visibility = '';
            }
        }

        // Adicionar event listeners para as setas com verificação de fim
        function setupCarouselNavigation() {
            document.querySelectorAll('.carousel-container').forEach(container => {
                const carousel = container.querySelector('.comics-carousel');
                const prevBtn = container.querySelector('.carousel-nav.prev');
                const nextBtn = container.querySelector('.carousel-nav.next');
                if (!carousel) return;

                // Verificar estado inicial
                updateNavButtons(carousel, prevBtn, nextBtn);

                // Atualizar durante o scroll
                carousel.addEventListener('scroll', () => {
                    updateNavButtons(carousel, prevBtn, nextBtn);
                });
            });
        }

        function updateNavButtons(carousel, prevBtn, nextBtn) {
            if (!carousel || !prevBtn || !nextBtn) return;
            const scrollLeft = carousel.scrollLeft;
            const scrollWidth = carousel.scrollWidth;
            const clientWidth = carousel.clientWidth;

            // Botão anterior
            if (scrollLeft <= 10) {
                prevBtn.disabled = true;
                prevBtn.style.opacity = '0.3';
            } else {
                prevBtn.disabled = false;
                prevBtn.style.opacity = '0.9';
            }

            // Botão próximo
            if (scrollLeft + clientWidth >= scrollWidth - 10) {
                nextBtn.disabled = true;
                nextBtn.style.opacity = '0.3';
            } else {
                nextBtn.disabled = false;
                nextBtn.style.opacity = '0.9';
            }
        }

        // Função para atualizar todas as seções baseado na categoria
        function updateAllSections(category) {
            const filteredComics = filterComicsByCategory(category);
            
            // Seção Continuar Lendo - quadrinhos com progresso
            const continueReadingComics = filteredComics.filter(comic => comic.progress && comic.progress > 0);
            renderComicsInSection('continueReading', continueReadingComics.slice(0, 4), true);
            
            // Seção Recomendados - seleção variada
            const recommendedComics = filteredComics.slice(0, 4);
            renderComicsInSection('recommendedComics', recommendedComics);
            
            // Seção Clássicos DC - super-heróis da DC
            const dcComics = filteredComics.filter(comic => 
                comic.categories.includes('super-herois') && 
                (comic.title.includes('Batman') || comic.title.includes('Superman') || comic.title.includes('Liga'))
            );
            renderComicsInSection('dcClassics', dcComics.slice(0, 3));
            
            // Seção Mangás Populares
            const mangaComics = filteredComics.filter(comic => 
                comic.categories.includes('manga')
            );
            renderComicsInSection('popularManga', mangaComics.slice(0, 4));
            
            // Seção Graphic Novels
            const graphicNovels = filteredComics.filter(comic => 
                comic.categories.includes('graphic-novels')
            );
            renderComicsInSection('graphicNovels', graphicNovels.slice(0, 4));
            
            // Atualizar contador
            updateComicCount(filteredComics.length);
        }

        // Função para atualizar contador de quadrinhos
        function updateComicCount(count) {
            const counter = document.getElementById('comicCounter');
            if (counter) {
                counter.textContent = `${count} quadrinhos encontrados`;
            }
        }

        // Função para aplicar efeitos hover nos cards
        function applyCardHoverEffects() {
            document.querySelectorAll('.comic-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        }

        // nova função: scroll horizontal com arrastar e roda do mouse traduzida para horizontal
        function initComicsCarouselScroll() {
            const carousels = document.querySelectorAll('.comics-carousel');
            if (!carousels.length) return;

            carousels.forEach(carousel => {
                // evita re-inicializar múltiplas vezes
                if (carousel.dataset.scrollInit === '1') return;
                carousel.dataset.scrollInit = '1';

                let isDown = false;
                let startX = 0;
                let scrollLeft = 0;

                // mouse drag
                carousel.addEventListener('mousedown', (e) => {
                    isDown = true;
                    carousel.classList.add('dragging');
                    startX = e.pageX - carousel.offsetLeft;
                    scrollLeft = carousel.scrollLeft;
                    e.preventDefault();
                });

                window.addEventListener('mouseup', () => {
                    isDown = false;
                    carousel.classList.remove('dragging');
                });

                carousel.addEventListener('mouseleave', () => {
                    isDown = false;
                    carousel.classList.remove('dragging');
                });

                carousel.addEventListener('mousemove', (e) => {
                    if (!isDown) return;
                    const x = e.pageX - carousel.offsetLeft;
                    const walk = (x - startX); // ajuste velocidade aqui
                    carousel.scrollLeft = scrollLeft - walk;
                });

                // touch drag (mobile)
                let touchStartX = 0;
                let touchStartScroll = 0;

                carousel.addEventListener('touchstart', (e) => {
                    touchStartX = e.touches[0].pageX;
                    touchStartScroll = carousel.scrollLeft;
                }, { passive: true });

                carousel.addEventListener('touchmove', (e) => {
                    const x = e.touches[0].pageX;
                    const walk = (x - touchStartX);
                    carousel.scrollLeft = touchStartScroll - walk;
                }, { passive: true });

                // wheel -> horizontal scroll
                carousel.addEventListener('wheel', (e) => {
                    const delta = e.deltaY !== 0 ? e.deltaY : e.deltaX;
                    carousel.scrollLeft += delta;
                    e.preventDefault();
                }, { passive: false });
            });
        }

        // Sistema de filtro por categoria
        function setupCategoryFilter() {
            document.querySelectorAll('.category').forEach(category => {
                category.addEventListener('click', function() {
                    // Atualizar categoria ativa
                    document.querySelectorAll('.category').forEach(cat => cat.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Obter categoria
                    const categoryType = this.getAttribute('data-category');
                    
                    // Animação de transição
                    document.querySelectorAll('.comics-carousel').forEach(carousel => {
                        carousel.style.opacity = '0.7';
                        setTimeout(() => {
                            carousel.style.opacity = '1';
                        }, 300);
                    });
                    
                    // Atualizar quadrinhos
                    updateAllSections(categoryType);
                });
            });
        }

        // Sistema de busca
        function setupSearch() {
            const searchInput = document.querySelector('.search-bar input');
            const searchButton = document.querySelector('.search-bar button');
            
            searchButton.addEventListener('click', performSearch);
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
            
            function performSearch() {
                const searchTerm = searchInput.value.trim().toLowerCase();
                if (searchTerm) {
                    const allComics = comicsData.all;
                    const filteredComics = allComics.filter(comic => 
                        comic.title.toLowerCase().includes(searchTerm) ||
                        comic.description.toLowerCase().includes(searchTerm) ||
                        comic.meta.toLowerCase().includes(searchTerm)
                    );
                    
                    // Atualizar todas as seções com os resultados da busca
                    const continueReadingComics = filteredComics.filter(comic => comic.progress && comic.progress > 0);
                    renderComicsInSection('continueReading', continueReadingComics.slice(0, 4), true);
                    renderComicsInSection('recommendedComics', filteredComics.slice(0, 4));
                    renderComicsInSection('dcClassics', filteredComics.slice(0, 3));
                    renderComicsInSection('popularManga', filteredComics.slice(0, 4));
                    renderComicsInSection('graphicNovels', filteredComics.slice(0, 4));
                    
                    updateComicCount(filteredComics.length);
                    
                    // Mostrar mensagem de busca
                    document.getElementById('comicCounter').textContent = `${filteredComics.length} resultados para "${searchTerm}"`;
                }
            }
        }

        // Sistema de Tema
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

        // Nova função para rolar o carrossel
        function scrollCarousel(carouselId, amount) {
            const carousel = document.getElementById(carouselId);
            if (carousel) {
                carousel.scrollBy({ left: amount, behavior: 'smooth' });
            }
        }

        // Modificar a função initializePage para incluir a navegação do carrossel
        function initializePage() {
            setupTheme();
            setupCategoryFilter();
            setupSearch();
            updateAllSections('all');
            applyCardHoverEffects();
            setupCarouselNavigation();
            initComicsCarouselScroll();

            console.log('Sistema de quadrinhos inicializado com carrossel!');
        }

        // Inicializar quando a página carregar
        document.addEventListener('DOMContentLoaded', initializePage);

        <script>
// HQ Verso - Sistema de inicialização (Tudo em um arquivo)
document.addEventListener('DOMContentLoaded', function() {
    // Dados dos quadrinhos adicionais
    const additionalComics = {
        "all": [
            {
                id: 101,
                title: "Akira",
                cover: "https://m.media-amazon.com/images/I/81K1+Z+Yf+L.jpg",
                meta: "Katsuhiro Otomo",
                categories: ["manga", "classicos"],
                description: "A épica cyberpunk que revolucionou os mangás."
            },
            {
                id: 102,
                title: "Death Note",
                cover: "https://m.media-amazon.com/images/I/81MZ6eFQsfL.jpg",
                meta: "Tsugumi Ohba",
                categories: ["manga"],
                description: "Um estudante genius encontra um caderno que pode matar pessoas."
            },
            {
                id: 103,
                title: "Attack on Titan",
                cover: "https://m.media-amazon.com/images/I/81d6e+kN5+L.jpg",
                meta: "Hajime Isayama",
                categories: ["manga"],
                description: "Humanidade luta pela sobrevivência contra titãs gigantes."
            },
            {
                id: 104,
                title: "One-Punch Man",
                cover: "https://m.media-amazon.com/images/I/81I1+-+0R0L.jpg",
                meta: "ONE",
                categories: ["manga", "super-herois"],
                description: "Um herói tão forte que derrota qualquer inimigo com um só soco."
            },
            {
                id: 105,
                title: "Scott Pilgrim",
                cover: "https://m.media-amazon.com/images/I/81K1+Z+Yf+L.jpg",
                meta: "Bryan Lee O'Malley",
                categories: ["indie", "graphic-novels"],
                description: "Um baixista deve derrotar os 7 ex-namorados malvados de sua amada."
            },
            {
                id: 106,
                title: "Batman: Ano Um",
                cover: "https://m.media-amazon.com/images/I/81zK5OjR5aL.jpg",
                meta: "Frank Miller",
                categories: ["super-herois", "classicos"],
                description: "A origem definitiva do Cavaleiro das Trevas."
            }
        ]
    };

    // Função para criar card no novo estilo
    function createNewStyleComicCard(comic) {
        const placeholder = `https://via.placeholder.com/200x300/1a1a2e/e94560?text=${encodeURIComponent(comic.title.substring(0, 15))}`;
        
        return `
            <div class="comic-card" data-id="${comic.id}">
                <span class="read-badge">Ler agora</span>
                <img src="${comic.cover || placeholder}" 
                     alt="Capa do quadrinho ${comic.title}" 
                     class="comic-cover"
                     onerror="this.src='${placeholder}'">
                <div class="card-bottom">
                    <div class="title-meta">
                        <div class="comic-title">${comic.title}</div>
                        <div class="comic-meta">${comic.meta}</div>
                    </div>
                    <div class="card-actions">
                        <button class="btn btn-outline add-list" title="Adicionar à lista">+</button>
                    </div>
                </div>
            </div>
        `;
    }

    // Atualizar cards existentes para o novo estilo
    function updateExistingCards() {
        document.querySelectorAll('.comic-card').forEach(card => {
            const titleElement = card.querySelector('.comic-title');
            const metaElement = card.querySelector('.comic-meta');
            const coverElement = card.querySelector('.comic-cover');
            const actionsElement = card.querySelector('.card-actions');
            
            if (titleElement && metaElement && coverElement) {
                const title = titleElement.textContent;
                const meta = metaElement.textContent;
                const coverSrc = coverElement.src;
                const progress = card.getAttribute('data-progress');
                
                // Salva os botões originais se existirem
                let actionsHTML = '';
                if (actionsElement) {
                    // Converte botões primários para o novo estilo
                    const primaryButtons = actionsElement.querySelectorAll('.btn-primary');
                    primaryButtons.forEach(btn => {
                        btn.className = 'btn btn-outline';
                        btn.innerHTML = '+';
                        btn.title = 'Adicionar à lista';
                    });
                    actionsHTML = actionsElement.innerHTML;
                } else {
                    actionsHTML = '<button class="btn btn-outline add-list" title="Adicionar à lista">+</button>';
                }
                
                // Cria o novo card
                const newCardHTML = `
                    <span class="read-badge">Ler agora</span>
                    <img src="${coverSrc}" alt="Capa do quadrinho ${title}" class="comic-cover">
                    <div class="card-bottom">
                        <div class="title-meta">
                            <div class="comic-title">${title}</div>
                            <div class="comic-meta">${meta}</div>
                        </div>
                        <div class="card-actions">
                            ${actionsHTML}
                        </div>
                    </div>
                    ${progress ? `<div class="progress"><div class="progress-bar" style="width:${progress}%"></div></div>` : ''}
                `;
                
                // Mantém os atributos originais e atualiza o conteúdo
                const originalAttributes = {};
                for (let attr of card.attributes) {
                    originalAttributes[attr.name] = attr.value;
                }
                
                card.innerHTML = newCardHTML;
                
                // Restaura atributos
                for (let attr in originalAttributes) {
                    card.setAttribute(attr, originalAttributes[attr]);
                }
            }
        });
    }

    // Adicionar quadrinhos às seções existentes
    function addComicsToSections() {
        // Adiciona à seção "Recomendados para você"
        const recommendedSection = document.querySelector('.comics-section:nth-child(4) .comics-carousel');
        if (recommendedSection && recommendedSection.children.length > 0) {
            additionalComics.all.slice(0, 2).forEach(comic => {
                recommendedSection.innerHTML += createNewStyleComicCard(comic);
            });
        }

        // Adiciona à seção "Clássicos da DC"
        const dcSection = document.querySelector('.comics-section:nth-child(5) .comics-carousel');
        if (dcSection && dcSection.children.length > 0) {
            const batmanComic = additionalComics.all.find(comic => 
                comic.title.includes('Batman')
            );
            if (batmanComic) {
                dcSection.innerHTML += createNewStyleComicCard(batmanComic);
            }
        }

        // Adiciona à seção "Marvel Essentials"
        const marvelSection = document.querySelector('.comics-section:nth-child(6) .comics-carousel');
        if (marvelSection && marvelSection.children.length > 0) {
            additionalComics.all.slice(2, 5).forEach(comic => {
                marvelSection.innerHTML += createNewStyleComicCard(comic);
            });
        }
    }

    // Inicializar tudo
    function initHQVerso() {
        console.log('Inicializando HQ Verso...');
        updateExistingCards();
        setTimeout(addComicsToSections, 100);
        console.log('HQ Verso - Sistema de quadrinhos inicializado!');
    }

    // Aguarda um pouco para garantir que a página carregou completamente
    setTimeout(initHQVerso, 500);
});
</script>
    </script>
</body>
</html>