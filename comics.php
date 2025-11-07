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
    <link rel="stylesheet" href="style.css">
    <style>
        /* Modal de detalhe do quadrinho */
        .comic-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.7);display:none;align-items:center;justify-content:center;z-index:9999;padding:20px}
        .comic-modal{background:#0f1724;color:#fff;max-width:1000px;width:100%;border-radius:8px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.6);display:flex;gap:20px}
        .comic-modal .modal-cover{width:320px;flex:0 0 320px;height:480px;object-fit:cover;background:#111}
        .comic-modal .modal-body{padding:24px;flex:1;display:flex;flex-direction:column}
        .comic-modal .modal-title{font-size:1.5rem;margin:0 0 6px}
        .comic-modal .modal-meta{color:#9aa3b2;margin-bottom:12px}
        .comic-modal .modal-description{flex:1;line-height:1.5;color:#d1d9e6}
        .comic-modal .modal-actions{display:flex;gap:10px;margin-top:16px}
        .comic-modal .btn{padding:10px 14px;border-radius:6px;border:none;cursor:pointer}
        .comic-modal .btn-primary{background:#e94560;color:#fff}
        .comic-modal .btn-outline{background:transparent;color:#e94560;border:1px solid rgba(233,69,96,0.25)}
        .comic-modal-close{position:absolute;right:18px;top:14px;background:transparent;border:none;color:#fff;font-size:20px;cursor:pointer}
        @media(max-width:800px){.comic-modal{flex-direction:column}.comic-modal .modal-cover{width:100%;height:300px;flex:auto}}
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
            <h3 class="section-title">Continuar Lendo</h3>
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

    <!-- Modal de detalhe do quadrinho -->
    <div id="comicModalOverlay" class="comic-modal-overlay" aria-hidden="true" role="dialog" aria-labelledby="comicModalTitle">
        <div class="comic-modal" role="document">
            <button class="comic-modal-close" id="comicModalClose" aria-label="Fechar">&times;</button>
            <img src="" alt="Capa" class="modal-cover" id="comicModalCover">
            <div class="modal-body">
                <h2 id="comicModalTitle" class="modal-title">Título do Quadrinho</h2>
                <div id="comicModalMeta" class="modal-meta">Meta / Autor / Ano</div>
                <p id="comicModalDescription" class="modal-description">Sinopse do quadrinho será exibida aqui.</p>
                <div class="modal-actions">
                    <button id="comicModalRead" class="btn btn-primary">Ler agora</button>
                    <button id="comicModalAdd" class="btn btn-outline">Adicionar à minha lista</button>
                </div>
            </div>
        </div>
    </div>

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
                ,
                {
                    id: 19,
                    title: "Homem de Ferro: Extremis",
                    cover: "https://m.media-amazon.com/images/I/81bGs636lzL.jpg",
                    meta: "Extremis",
                    categories: ["super-herois"],
                    description: "A história que redefiniu o Homem de Ferro moderno."
                },
                {
                    id: 20,
                    title: "Capitão América: O Soldado Invernal",
                    cover: "https://m.media-amazon.com/images/I/611wcUISMmL._UF1000,1000_QL80_.jpg",
                    meta: "2005",
                    categories: ["super-herois"],
                    description: "Um thriller político que coloca o Capitão América contra inimigos internos."
                },
                {
                    id: 21,
                    title: "Thor: Deus do Trovão",
                    cover: "https://m.media-amazon.com/images/I/91JTRo6EFcL._UF1000,1000_QL80_.jpg",
                    meta: "2012",
                    categories: ["super-herois"],
                    description: "Uma saga épica do Deus do Trovão através dos tempos."
                },
                {
                    id: 22,
                    title: "Doutor Estranho: O Juramento",
                    cover: "https://m.media-amazon.com/images/I/91DcEu1b-rL.jpg",
                    meta: "2006",
                    categories: ["super-herois"],
                    description: "Uma história íntima e sombria do Mago Supremo."
                },
                {
                    id: 23,
                    title: "Pantera Negra: Rei do Wakanda",
                    cover: "https://d14d9vp3wdof84.cloudfront.net/image/589816272436/image_v8bl17fqv95mf8v1jd9k8lrp5r/-S897-FWEBP",
                    meta: "2016",
                    categories: ["super-herois"],
                    description: "A jornada do rei e herói de Wakanda."
                },
                {
                    id: 24,
                    title: "Batman: Silêncio",
                    cover: "https://lh3.googleusercontent.com/proxy/9y2rp6F2x4dSCvFkZoz847oXtBE8IP0mscS0W0SkYpRtdub4qCQRCzj-Qwfgd4BWQq6EtqSmr7edCB_rNckNCs8pGT8jFx0HdMknRmb_1EmPWIb5zuujbw",
                    meta: "Jeph Loeb",
                    categories: ["super-herois", "classicos"],
                    description: "Uma intensa história de Batman escrita por Jeph Loeb."
                }
                ,
                {
                    id: 25,
                    title: "Mulher-Maravilha: Deuses e Mortais",
                    cover: "https://cdn.awsli.com.br/600x450/1668/1668242/produto/162790896905299a6a0.jpg",
                    meta: "1987",
                    categories: ["super-herois", "classicos"],
                    description: "A reinvenção da origem da Mulher-Maravilha por George Pérez."
                },
                {
                    id: 26,
                    title: "Flashpoint",
                    cover: "https://m.media-amazon.com/images/I/91dXNvO2fML.jpg",
                    meta: "2011",
                    categories: ["super-herois", "classicos"],
                    description: "Uma linha temporal alternativa que altera o universo DC."
                },
                {
                    id: 27,
                    title: "Arqueiro Verde: Ano Um",
                    cover: "https://rika.vtexassets.com/arquivos/ids/219835/-herois_panini-arqueiro-verde-ano-um.jpg?v=635316153891630000",
                    meta: "2007",
                    categories: ["super-herois", "classicos"],
                    description: "A origem moderna do Arqueiro Verde."
                }
            ],
            "super-herois": [1, 2, 7, 9, 10, 14, 16, 17, 19, 20, 21, 22, 23, 24, 25, 26, 27],
            "manga": [11, 12, 13, 14],
            "graphic-novels": [2, 3, 4, 5, 6, 8, 15, 18],
            "classicos": [1, 2, 3, 4, 5, 6, 9, 10, 11, 16, 17, 24, 25, 26, 27],
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

            // garantir que o container e setas existam para este carrossel
            ensureCarouselContainersAndNavs();
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
            // sincronizar progresso salvo no localStorage antes de filtrar
            loadProgressFromLocalStorage();

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

        // Carrega progressos salvos no localStorage (chave: hq-progress-<id>) e atualiza comicsData
        function loadProgressFromLocalStorage() {
            if (!window || !window.localStorage) return;
            if (!Array.isArray(comicsData.all)) return;

            comicsData.all.forEach(comic => {
                try {
                    const key = 'hq-progress-' + comic.id;
                    const val = localStorage.getItem(key);
                    if (val !== null) {
                        const n = Number(val);
                        if (!Number.isNaN(n)) {
                            comic.progress = n;
                        }
                    }
                } catch (err) {
                    // ignore
                }
            });
        }

        // Fetch progress from server and merge into comicsData.all (fallback to localStorage kept)
        async function loadProgressFromServer() {
            if (!window.fetch) return;
            try {
                const res = await fetch('get_all_progress.php', { credentials: 'same-origin' });
                if (!res.ok) throw new Error('server error');
                const data = await res.json();
                if (data && Array.isArray(data.progresses)) {
                    const map = {};
                    data.progresses.forEach(p => { map[Number(p.comic_id)] = Number(p.progress_pct); });
                    // apply to comicsData
                    comicsData.all.forEach(comic => {
                        const serverVal = map[Number(comic.id)];
                        if (typeof serverVal !== 'undefined') {
                            comic.progress = serverVal;
                        } else {
                            // fallback to any localStorage value
                            const key = 'hq-progress-' + comic.id;
                            const localVal = localStorage.getItem(key);
                            if (localVal !== null && !Number.isNaN(Number(localVal))) {
                                comic.progress = Number(localVal);
                            }
                        }
                    });
                }
            } catch (err) {
                // network/server failed, use localStorage fallback
                loadProgressFromLocalStorage();
            }
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

        // ---- Modal de detalhe do quadrinho ----
        function openComicModal(comic) {
            const overlay = document.getElementById('comicModalOverlay');
            if (!overlay || !comic) return;
            const cover = document.getElementById('comicModalCover');
            const title = document.getElementById('comicModalTitle');
            const meta = document.getElementById('comicModalMeta');
            const desc = document.getElementById('comicModalDescription');
            const readBtn = document.getElementById('comicModalRead');
            const addBtn = document.getElementById('comicModalAdd');

            cover.src = comic.cover || '';
            cover.alt = `Capa do quadrinho ${comic.title || ''}`;
            title.textContent = comic.title || '';
            meta.textContent = comic.meta || '';
            desc.textContent = comic.description || 'Sem sinopse disponível.';

            // ações dos botões (simples handlers, podem ser expandidos)
            readBtn.onclick = function() {
                // redirecionar para o leitor (página estática) com id do quadrinho
                window.location.href = `leitor.html?comic=${encodeURIComponent(comic.id)}`;
            };

            addBtn.onclick = function() {
                // ação de adicionar à lista (apenas feedback visual aqui)
                addBtn.textContent = 'Adicionado';
                addBtn.disabled = true;
            };

            overlay.style.display = 'flex';
            overlay.setAttribute('aria-hidden', 'false');
            // foco para acessibilidade
            setTimeout(() => {
                readBtn.focus();
            }, 120);
        }

        function closeComicModal() {
            const overlay = document.getElementById('comicModalOverlay');
            if (!overlay) return;
            overlay.style.display = 'none';
            overlay.setAttribute('aria-hidden', 'true');
        }

        function setupComicModalHandlers() {
            // abre modal ao clicar em um card (event delegation)
            document.body.addEventListener('click', function(e) {
                // Prioridade: se clicou no botão 'Ler agora' dentro do card, ir diretamente para o leitor
                const readTrigger = e.target.closest('.read-now') || e.target.closest('.read-badge');
                if (readTrigger) {
                    const cardForRead = readTrigger.closest('.comic-card');
                    if (cardForRead) {
                        const idForRead = parseInt(cardForRead.getAttribute('data-id'), 10);
                        if (idForRead) {
                            window.location.href = `leitor.html?comic=${encodeURIComponent(idForRead)}`;
                            return;
                        }
                    }
                }

                // Caso contrário, tratar clique no card para abrir modal (ignorando botões internos)
                const card = e.target.closest('.comic-card');
                if (!card) return;
                if (e.target.closest('.card-actions') || e.target.tagName === 'BUTTON') return;

                const id = parseInt(card.getAttribute('data-id'), 10);
                if (!id) return;
                const comic = comicsData.all.find(c => c.id === id);
                if (comic) {
                    openComicModal(comic);
                }
            });

            // fechar pelo botão e clicando fora
            const overlay = document.getElementById('comicModalOverlay');
            const closeBtn = document.getElementById('comicModalClose');
            if (closeBtn) closeBtn.addEventListener('click', closeComicModal);
            if (overlay) {
                overlay.addEventListener('click', function(evt) {
                    if (evt.target === overlay) closeComicModal();
                });
            }

            // fechar com ESC
            window.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeComicModal();
            });
        }

        // Garante que cada .comics-carousel esteja dentro de um .carousel-container
        // e que existam botões prev/next nas laterais (funciona para carrosseis dinâmicos)
        function ensureCarouselContainersAndNavs() {
            document.querySelectorAll('.comics-carousel').forEach((carousel, idx) => {
                // garantir id único
                if (!carousel.id) {
                    carousel.id = 'comics_carousel_' + idx + '_' + Math.random().toString(36).substr(2,5);
                }

                let container = carousel.closest('.carousel-container');
                if (!container) {
                    // criar wrapper e mover o carousel para dentro
                    container = document.createElement('div');
                    container.className = 'carousel-container';
                    carousel.parentNode.insertBefore(container, carousel);
                    container.appendChild(carousel);
                }

                // cria botão anterior se não existir
                if (!container.querySelector('.carousel-nav.prev')) {
                    const prev = document.createElement('button');
                    prev.className = 'carousel-nav prev';
                    prev.setAttribute('aria-label', 'Anterior');
                    prev.innerHTML = '<i class="fas fa-chevron-left" aria-hidden="true"></i>';
                    prev.addEventListener('click', () => {
                        const amount = Math.round(carousel.clientWidth * 0.8) || 300;
                        scrollCarousel(carousel.id, -amount);
                    });
                    container.appendChild(prev);
                }

                // cria botão próximo se não existir
                if (!container.querySelector('.carousel-nav.next')) {
                    const next = document.createElement('button');
                    next.className = 'carousel-nav next';
                    next.setAttribute('aria-label', 'Próximo');
                    next.innerHTML = '<i class="fas fa-chevron-right" aria-hidden="true"></i>';
                    next.addEventListener('click', () => {
                        const amount = Math.round(carousel.clientWidth * 0.8) || 300;
                        scrollCarousel(carousel.id, amount);
                    });
                    container.appendChild(next);
                }
            });
        }

        // Modificar a função initializePage para incluir a navegação do carrossel
        async function initializePage() {
            setupTheme();
            setupCategoryFilter();
            setupSearch();

            // primeiro tentar carregar progresso do servidor; se falhar, fallback no loadProgressFromLocalStorage()
            await loadProgressFromServer();

            updateAllSections('all');
            applyCardHoverEffects();
            // garantir wrappers e botões de navegação antes de configurar handlers
            ensureCarouselContainersAndNavs();
            setupCarouselNavigation();
            initComicsCarouselScroll();
            // configurar handlers do modal de detalhe
            setupComicModalHandlers();

            console.log('Sistema de quadrinhos inicializado com carrossel!');
        }

    // Inicializar quando a página carregar
    document.addEventListener('DOMContentLoaded', initializePage);

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
</body>
</html>
