<?php
require_once 'includes_auth.php';

if(!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$comic_id = $_GET['comic_id'] ?? 0;
if(!$comic_id) {
    header("Location: comics.php");
    exit();
}

// Buscar dados do quadrinho
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "SELECT id, title, cover FROM comics WHERE id = :comic_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':comic_id', $comic_id);
    $stmt->execute();
    $comic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$comic) {
        header("Location: comics.php");
        exit();
    }
} catch(PDOException $e) {
    error_log("Erro ao buscar quadrinho: " . $e->getMessage());
    header("Location: comics.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Comentários - <?php echo htmlspecialchars($comic['title']); ?> - HQ Verso</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
            --bg-card: rgba(255, 255, 255, 0.95);
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            color: var(--text-primary);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--bg-card);
            border-radius: 15px;
            border: 1px solid var(--border-color);
        }
        
        .comic-cover {
            width: 120px;
            height: 180px;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .comic-info h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--accent-color);
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--accent-color);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
        }
        
        /* Modal de Spoiler Alert */
        .spoiler-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
        
        .spoiler-content {
            background: var(--bg-primary);
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            max-width: 500px;
            border: 2px solid var(--accent-color);
        }
        
        .spoiler-icon {
            font-size: 4rem;
            color: var(--accent-color);
            margin-bottom: 20px;
        }
        
        .spoiler-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: var(--accent-color);
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--accent-color);
            color: var(--accent-color);
        }
        
        /* Sistema de Avaliação */
        .rating-section {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }
        
        .rating-stars {
            display: flex;
            gap: 5px;
            margin: 15px 0;
        }
        
        .star {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        
        .star:hover,
        .star.active {
            color: #ffd700;
        }
        
        .rating-stats {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 20px;
        }
        
        .average-rating {
            font-size: 2.5rem;
            font-weight: bold;
            color: #ffd700;
        }
        
        /* Sistema de Comentários */
        .comments-section {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
        }
        
        .comment-form {
            margin-bottom: 30px;
        }
        
        .comment-textarea {
            width: 100%;
            min-height: 120px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 1rem;
            resize: vertical;
        }
        
        [data-theme="light"] .comment-textarea {
            background: rgba(0, 0, 0, 0.05);
        }
        
        .comment-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }
        
        .spoiler-warning {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: var(--accent-color);
        }
        
        .submit-btn {
            padding: 10px 25px;
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .comment {
            background: var(--bg-card);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .comment.reply {
            margin-left: 40px;
            background: rgba(255, 255, 255, 0.03);
            border-left: 3px solid var(--accent-color);
        }
        
        [data-theme="light"] .comment.reply {
            background: rgba(0, 0, 0, 0.03);
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }
        
        .user-info:hover {
            opacity: 0.8;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            flex-shrink: 0;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .spoiler-badge {
            background: var(--accent-color);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .spoiler-content {
            background: #2d3748;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid var(--accent-color);
        }
        
        [data-theme="light"] .spoiler-content {
            background: #f8f9fa;
        }
        
        .spoiler-toggle {
            background: none;
            border: none;
            color: var(--accent-color);
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .comment-actions {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid var(--border-color);
        }
        
        .comment-action {
            background: none;
            border: none;
            color: var(--accent-color);
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s ease;
        }
        
        .comment-action:hover {
            color: #ff6b81;
        }
        
        .reply-form {
            margin-top: 15px;
            padding: 15px;
            background: var(--bg-card);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        
        .reply-textarea {
            width: 100%;
            min-height: 80px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.9rem;
            resize: vertical;
            margin-bottom: 10px;
        }
        
        [data-theme="light"] .reply-textarea {
            background: rgba(0, 0, 0, 0.05);
        }
        
        .reply-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .cancel-reply {
            background: none;
            border: none;
            color: #888;
            cursor: pointer;
        }
        
        .no-comments {
            text-align: center;
            padding: 40px;
            color: #888;
            font-style: italic;
        }

        /* Estilos para o sistema de likes */
        .like-btn {
            position: relative;
            transition: all 0.3s ease;
        }

        .like-btn.liked {
            color: var(--accent-color) !important;
        }

        .like-btn.loading {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .like-btn:hover:not(.loading) {
            transform: scale(1.05);
        }

        .like-count {
            margin-left: 5px;
            font-weight: 600;
            transition: transform 0.2s ease;
        }

        /* Animação de pulso para novos likes */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .like-btn.pulse {
            animation: pulse 0.3s ease;
        }

        .replies-section {
            margin-top: 15px;
            padding-left: 20px;
            border-left: 2px solid rgba(233, 69, 96, 0.3);
        }

        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--accent-color);
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
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
            background: var(--accent-hover);
        }
    </style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon" id="themeIcon"></i>
    </button>

    <!-- Modal de Alerta de Spoiler -->
    <div class="spoiler-modal" id="spoilerModal">
        <div class="spoiler-content">
            <div class="spoiler-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Atenção: Área de Spoilers!</h2>
            <p>Os comentários abaixo podem conter revelações sobre a história, personagens ou finais deste quadrinho.</p>
            <p><strong>Se você ainda não leu, recomendo voltar depois!</strong></p>
            <div class="spoiler-buttons">
                <button class="btn btn-outline" id="goBackBtn">
                    <i class="fas fa-arrow-left"></i> Voltar para Ler
                </button>
                <button class="btn btn-primary" id="proceedBtn">
                    <i class="fas fa-eye"></i> Prosseguir mesmo assim
                </button>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <img src="<?php echo htmlspecialchars($comic['cover']); ?>" 
                 alt="Capa de <?php echo htmlspecialchars($comic['title']); ?>" 
                 class="comic-cover"
                 onerror="this.src='https://via.placeholder.com/120x180/1a1a2e/e94560?text=Capa'">
            <div class="comic-info">
                <h1><?php echo htmlspecialchars($comic['title']); ?></h1>
                <a href="leitor.html?comic=<?php echo $comic_id; ?>&title=<?php echo urlencode($comic['title']); ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Voltar para Leitura
                </a>
            </div>
        </div>

        <!-- Seção de Avaliação -->
        <div class="rating-section">
            <h2><i class="fas fa-star"></i> Avalie este Quadrinho</h2>
            <div class="rating-stars" id="ratingStars">
                <span class="star" data-rating="1">★</span>
                <span class="star" data-rating="2">★</span>
                <span class="star" data-rating="3">★</span>
                <span class="star" data-rating="4">★</span>
                <span class="star" data-rating="5">★</span>
            </div>
            <div class="rating-stats">
                <div class="average-rating" id="averageRating">-</div>
                <div class="rating-count" id="ratingCount">0 avaliações</div>
            </div>
        </div>

        <!-- Seção de Comentários -->
        <div class="comments-section">
            <h2><i class="fas fa-comments"></i> Comentários</h2>
            
            <!-- Formulário de Comentário Principal -->
            <form class="comment-form" id="commentForm">
                <textarea class="comment-textarea" 
                          placeholder="Compartilhe sua opinião sobre este quadrinho..." 
                          id="commentText"></textarea>
                <div class="comment-options">
                    <label class="spoiler-warning">
                        <input type="checkbox" id="containsSpoilers">
                        <i class="fas fa-exclamation-triangle"></i>
                        Contém spoilers
                    </label>
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Enviar Comentário
                    </button>
                </div>
            </form>

            <!-- Lista de Comentários -->
            <div id="commentsList">
                <div class="no-comments">Carregando comentários...</div>
            </div>
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
        } else {
            iconElement.className = 'fas fa-sun';
        }
    }

    // Variáveis globais
    const comicId = <?php echo $comic_id; ?>;
    let userRating = 0;
    let spoilerAccepted = localStorage.getItem(`spoiler_accepted_${comicId}`);
    let replyingTo = null;

    // Elementos DOM
    const spoilerModal = document.getElementById('spoilerModal');
    const goBackBtn = document.getElementById('goBackBtn');
    const proceedBtn = document.getElementById('proceedBtn');
    const ratingStars = document.getElementById('ratingStars');
    const averageRating = document.getElementById('averageRating');
    const ratingCount = document.getElementById('ratingCount');
    const commentForm = document.getElementById('commentForm');
    const commentText = document.getElementById('commentText');
    const containsSpoilers = document.getElementById('containsSpoilers');
    const commentsList = document.getElementById('commentsList');

    // Mostrar modal de spoiler se não aceitou ainda
    if (!spoilerAccepted) {
        spoilerModal.style.display = 'flex';
    }

    // Event Listeners do Modal
    goBackBtn.addEventListener('click', () => {
        window.history.back();
    });

    proceedBtn.addEventListener('click', () => {
        localStorage.setItem(`spoiler_accepted_${comicId}`, 'true');
        spoilerModal.style.display = 'none';
    });

    // Sistema de Avaliação
    ratingStars.addEventListener('click', (e) => {
        if (e.target.classList.contains('star')) {
            const rating = parseInt(e.target.dataset.rating);
            userRating = rating;
            updateStars(rating);
            saveRating(rating);
        }
    });

    ratingStars.addEventListener('mouseover', (e) => {
        if (e.target.classList.contains('star')) {
            const rating = parseInt(e.target.dataset.rating);
            updateStars(rating);
        }
    });

    ratingStars.addEventListener('mouseout', () => {
        updateStars(userRating);
    });

    function updateStars(rating) {
        const stars = ratingStars.querySelectorAll('.star');
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }

    // Salvar avaliação
    async function saveRating(rating) {
        try {
            const response = await fetch('save_rating.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    comicId: comicId,
                    rating: rating
                })
            });

            const data = await response.json();
            if (data.success) {
                loadRatingStats();
            } else {
                alert('Erro ao salvar avaliação: ' + data.message);
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao salvar avaliação');
        }
    }

    // Carregar estatísticas de avaliação
    async function loadRatingStats() {
        try {
            const response = await fetch(`get_rating_stats.php?comic_id=${comicId}`);
            const data = await response.json();

            if (data.success) {
                averageRating.textContent = data.average_rating.toFixed(1);
                ratingCount.textContent = `${data.total_ratings} avaliação${data.total_ratings !== 1 ? 'es' : ''}`;
                
                // Carregar avaliação do usuário atual
                if (data.user_rating) {
                    userRating = data.user_rating;
                    updateStars(userRating);
                }
            }
        } catch (error) {
            console.error('Erro ao carregar avaliações:', error);
        }
    }

    // Sistema de Comentários
    commentForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const comment = commentText.value.trim();
        const hasSpoilers = containsSpoilers.checked;

        if (!comment) {
            alert('Por favor, escreva um comentário');
            return;
        }

        try {
            const response = await fetch('save_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    comicId: comicId,
                    comment: comment,
                    containsSpoilers: hasSpoilers,
                    parentCommentId: replyingTo
                })
            });

            const data = await response.json();
            if (data.success) {
                commentText.value = '';
                containsSpoilers.checked = false;
                replyingTo = null;
                commentText.placeholder = "Compartilhe sua opinião sobre este quadrinho...";
                loadComments();
                
                showNotification('Comentário enviado com sucesso!', 'success');
            } else {
                alert('Erro ao enviar comentário: ' + data.message);
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao enviar comentário');
        }
    });

    // Carregar comentários
    async function loadComments() {
        try {
            showLoading('Carregando comentários...');
            const response = await fetch(`get_comments.php?comic_id=${comicId}`);
            const data = await response.json();

            if (data.success) {
                displayComments(data.comments);
                initializeLikeStates();
            } else {
                commentsList.innerHTML = '<div class="no-comments">Erro ao carregar comentários</div>';
            }
        } catch (error) {
            console.error('Erro ao carregar comentários:', error);
            commentsList.innerHTML = '<div class="no-comments">Erro ao carregar comentários</div>';
        }
    }

    // Exibir comentários
    function displayComments(comments) {
        if (comments.length === 0) {
            commentsList.innerHTML = '<div class="no-comments">Seja o primeiro a comentar!</div>';
            return;
        }

        const mainComments = comments.filter(comment => !comment.parent_comment_id);
        const replies = comments.filter(comment => comment.parent_comment_id);

        commentsList.innerHTML = mainComments.map(comment => `
            <div class="comment" data-comment-id="${comment.id}">
                <div class="comment-header">
                    <div class="user-info" onclick="viewProfile(${comment.user_id})">
                        <div class="user-avatar">
                            ${comment.avatar ? 
                                `<img src="${comment.avatar}" alt="${comment.username}">` : 
                                `<span>${comment.username.charAt(0).toUpperCase()}</span>`
                            }
                        </div>
                        <div>
                            <strong>${comment.username}</strong>
                            <div style="font-size: 0.8rem; color: #888;">${formatDate(comment.created_at)}</div>
                        </div>
                    </div>
                    ${comment.contains_spoilers ? '<span class="spoiler-badge"><i class="fas fa-exclamation-triangle"></i> SPOILER</span>' : ''}
                </div>
                ${comment.contains_spoilers ? `
                    <div class="spoiler-content">
                        <button class="spoiler-toggle" onclick="toggleSpoiler(this)">
                            <i class="fas fa-eye"></i>
                            <span>Mostrar conteúdo com spoiler</span>
                        </button>
                        <div class="spoiler-text" style="display: none;">${comment.comment}</div>
                    </div>
                ` : `
                    <div class="comment-text">${comment.comment}</div>
                `}
                
                <div class="comment-actions">
                    <button class="comment-action" onclick="replyToComment(${comment.id}, '${comment.username}')">
                        <i class="fas fa-reply"></i> Responder
                    </button>
                    <button class="comment-action like-btn ${comment.user_liked ? 'liked' : ''}" 
                            onclick="likeComment(${comment.id}, this)"
                            data-comment-id="${comment.id}"
                            title="${comment.user_liked ? 'Remover curtida' : 'Curtir comentário'}">
                        <i class="${comment.user_liked ? 'fas' : 'far'} fa-thumbs-up"></i>
                        <span class="like-count">${comment.like_count || 0}</span>
                    </button>
                </div>

                <!-- Respostas -->
                ${getRepliesForComment(comment.id, replies)}
            </div>
        `).join('');

        document.querySelectorAll('.like-btn.liked').forEach(btn => {
            btn.style.color = 'var(--accent-color)';
        });
    }

    // Obter respostas para um comentário
    function getRepliesForComment(commentId, replies) {
        const commentReplies = replies.filter(reply => reply.parent_comment_id == commentId);
        
        if (commentReplies.length === 0) return '';

        return `
            <div class="replies-section">
                ${commentReplies.map(reply => `
                    <div class="comment reply" data-comment-id="${reply.id}">
                        <div class="comment-header">
                            <div class="user-info" onclick="viewProfile(${reply.user_id})">
                                <div class="user-avatar">
                                    ${reply.avatar ? 
                                        `<img src="${reply.avatar}" alt="${reply.username}">` : 
                                        `<span>${reply.username.charAt(0).toUpperCase()}</span>`
                                    }
                                </div>
                                <div>
                                    <strong>${reply.username}</strong>
                                    <div style="font-size: 0.8rem; color: #888;">${formatDate(reply.created_at)}</div>
                                </div>
                            </div>
                            ${reply.contains_spoilers ? '<span class="spoiler-badge"><i class="fas fa-exclamation-triangle"></i> SPOILER</span>' : ''}
                        </div>
                        ${reply.contains_spoilers ? `
                            <div class="spoiler-content">
                                <button class="spoiler-toggle" onclick="toggleSpoiler(this)">
                                    <i class="fas fa-eye"></i>
                                    <span>Mostrar conteúdo com spoiler</span>
                                </button>
                                <div class="spoiler-text" style="display: none;">${reply.comment}</div>
                            </div>
                        ` : `
                            <div class="comment-text">${reply.comment}</div>
                        `}
                        
                        <div class="comment-actions">
                            <button class="comment-action" onclick="replyToComment(${reply.id}, '${reply.username}')">
                                <i class="fas fa-reply"></i> Responder
                            </button>
                            <button class="comment-action like-btn ${reply.user_liked ? 'liked' : ''}" 
                                    onclick="likeComment(${reply.id}, this)"
                                    data-comment-id="${reply.id}"
                                    title="${reply.user_liked ? 'Remover curtida' : 'Curtir comentário'}">
                                <i class="${reply.user_liked ? 'fas' : 'far'} fa-thumbs-up"></i>
                                <span class="like-count">${reply.like_count || 0}</span>
                            </button>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    // Sistema de Likes
    async function likeComment(commentId, button) {
        try {
            if (button.classList.contains('loading')) return;
            
            button.classList.add('loading');
            const icon = button.querySelector('i');
            const countSpan = button.querySelector('.like-count');
            
            icon.className = 'fas fa-spinner fa-spin';

            const response = await fetch('like_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    commentId: commentId,
                    action: button.classList.contains('liked') ? 'unlike' : 'like'
                })
            });

            const data = await response.json();
            
            if (data.success) {
                if (data.liked) {
                    button.classList.add('liked');
                    icon.className = 'fas fa-thumbs-up';
                    button.style.color = 'var(--accent-color)';
                    button.title = 'Remover curtida';
                    
                    button.classList.add('pulse');
                    setTimeout(() => button.classList.remove('pulse'), 300);
                } else {
                    button.classList.remove('liked');
                    icon.className = 'far fa-thumbs-up';
                    button.style.color = '';
                    button.title = 'Curtir comentário';
                }
                
                if (countSpan) {
                    countSpan.textContent = data.likeCount;
                    
                    countSpan.style.transform = 'scale(1.3)';
                    setTimeout(() => {
                        countSpan.style.transform = 'scale(1)';
                    }, 200);
                }
                
                button.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    button.style.transform = 'scale(1)';
                }, 150);
                
                showNotification(data.message, 'success');
                
            } else {
                showNotification(data.message, 'error');
                icon.className = button.classList.contains('liked') ? 'fas fa-thumbs-up' : 'far fa-thumbs-up';
            }
            
        } catch (error) {
            console.error('Erro ao curtir:', error);
            showNotification('Erro ao curtir comentário', 'error');
        } finally {
            button.classList.remove('loading');
        }
    }

    // Inicializar estados de like
    function initializeLikeStates() {
        document.querySelectorAll('.like-btn').forEach(button => {
            const isLiked = button.classList.contains('liked');
            
            if (isLiked) {
                button.style.color = 'var(--accent-color)';
            }
        });
    }

    // Responder a um comentário
    function replyToComment(commentId, username) {
        replyingTo = commentId;
        commentText.placeholder = `Respondendo a ${username}...`;
        commentText.focus();
        
        commentForm.style.border = '2px solid var(--accent-color)';
        commentForm.style.borderRadius = '10px';
        commentForm.style.padding = '15px';
        commentForm.style.background = 'rgba(233, 69, 96, 0.05)';
        
        setTimeout(() => {
            if (commentText.value === '') {
                resetReplyForm();
            }
        }, 5000);
        
        commentText.addEventListener('input', function clearHighlight() {
            if (this.value !== '') {
                resetReplyForm();
                this.removeEventListener('input', clearHighlight);
            }
        });
    }

    // Resetar formulário de resposta
    function resetReplyForm() {
        commentForm.style.border = '';
        commentForm.style.borderRadius = '';
        commentForm.style.padding = '';
        commentForm.style.background = '';
    }

    // Cancelar resposta
    function cancelReply() {
        replyingTo = null;
        commentText.placeholder = "Compartilhe sua opinião sobre este quadrinho...";
        resetReplyForm();
    }

    // Ver perfil do usuário
    function viewProfile(userId) {
        window.location.href = `perfil.php?user_id=${userId}`;
    }

    // Alternar visibilidade de spoilers
    function toggleSpoiler(button) {
        const spoilerText = button.nextElementSibling;
        const isHidden = spoilerText.style.display === 'none';
        
        spoilerText.style.display = isHidden ? 'block' : 'none';
        button.innerHTML = isHidden ? 
            '<i class="fas fa-eye-slash"></i><span>Ocultar conteúdo</span>' : 
            '<i class="fas fa-eye"></i><span>Mostrar conteúdo com spoiler</span>';
    }

    // Formatar data
    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / (1000 * 60));
        const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

        if (diffMins < 1) {
            return 'Agora mesmo';
        } else if (diffMins < 60) {
            return `Há ${diffMins} minuto${diffMins !== 1 ? 's' : ''}`;
        } else if (diffHours < 24) {
            return `Há ${diffHours} hora${diffHours !== 1 ? 's' : ''}`;
        } else if (diffDays < 7) {
            return `Há ${diffDays} dia${diffDays !== 1 ? 's' : ''}`;
        } else {
            return date.toLocaleDateString('pt-BR') + ' às ' + date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
        }
    }

    // Mostrar notificação
    function showNotification(message, type = 'info') {
        const existingNotification = document.querySelector('.notification');
        if (existingNotification) {
            existingNotification.remove();
        }

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            z-index: 10001;
            animation: slideIn 0.3s ease;
            max-width: 300px;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }
        }, 3000);
    }

    // Mostrar loading
    function showLoading(message = 'Carregando...') {
        commentsList.innerHTML = `
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <div>${message}</div>
            </div>
        `;
    }

    // Adicionar estilos CSS dinamicamente para animações
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .loading-state {
            text-align: center;
            padding: 40px;
            color: #888;
        }
        
        .loading-state i {
            font-size: 2rem;
            margin-bottom: 15px;
            color: var(--accent-color);
        }
        
        .like-btn.loading {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .like-btn.pulse {
            animation: pulse 0.3s ease;
        }
        
        .like-btn:hover:not(.loading) {
            transform: scale(1.05);
        }
        
        .like-count {
            margin-left: 5px;
            font-weight: 600;
            transition: transform 0.2s ease;
        }
    `;
    document.head.appendChild(style);

    // Inicializar
    document.addEventListener('DOMContentLoaded', () => {
        initializeTheme();
        loadRatingStats();
        loadComments();
        
        const commentOptions = document.querySelector('.comment-options');
        if (commentOptions && !document.getElementById('cancelReplyBtn')) {
            const cancelBtn = document.createElement('button');
            cancelBtn.id = 'cancelReplyBtn';
            cancelBtn.type = 'button';
            cancelBtn.className = 'cancel-reply';
            cancelBtn.innerHTML = '<i class="fas fa-times"></i> Cancelar Resposta';
            cancelBtn.style.display = 'none';
            cancelBtn.onclick = cancelReply;
            commentOptions.appendChild(cancelBtn);
        }
    });

    // Observar mudanças no textarea para mostrar/ocultar botão de cancelar
    commentText.addEventListener('focus', () => {
        if (replyingTo) {
            const cancelBtn = document.getElementById('cancelReplyBtn');
            if (cancelBtn) cancelBtn.style.display = 'block';
        }
    });

    commentText.addEventListener('blur', () => {
        if (!commentText.value && replyingTo) {
            const cancelBtn = document.getElementById('cancelReplyBtn');
            if (cancelBtn) cancelBtn.style.display = 'block';
        }
    });
    </script>
</body>
</html>
