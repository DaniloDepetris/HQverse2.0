<?php
require_once 'includes_auth.php';

if(!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Obter conversas do usuário
$conversations = $auth->getUserConversations($user_id);

// Processar envio de nova mensagem
if($_POST && isset($_POST['send_message'])) {
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    $content = trim($_POST['message_content'] ?? '');
    
    if($conversation_id && !empty($content)) {
        // Verificar se usuário pode acessar a conversa
        if($auth->canAccessConversation($conversation_id, $user_id)) {
            $result = $auth->sendMessage($conversation_id, $user_id, $content);
            if($result === true) {
                $success = "Mensagem enviada com sucesso!";
            } else {
                $error = $result;
            }
        } else {
            $error = "Você não tem permissão para enviar mensagens nesta conversa!";
        }
    } else {
        $error = "Por favor, digite uma mensagem!";
    }
}

// Iniciar nova conversa
if($_POST && isset($_POST['start_conversation'])) {
    $target_user_id = intval($_POST['target_user_id'] ?? 0);
    
    if($target_user_id && $target_user_id != $user_id) {
        // Verificar se usuário existe
        $target_user = $auth->getUserData($target_user_id);
        if($target_user) {
            // Criar ou obter conversa existente
            $conversation_id = $auth->getOrCreateConversation($user_id, $target_user_id);
            if($conversation_id) {
                // Redirecionar para a conversa
                header("Location: messages.php?conversation_id=" . $conversation_id);
                exit();
            } else {
                $error = "Erro ao iniciar conversa!";
            }
        } else {
            $error = "Usuário não encontrado!";
        }
    } else {
        $error = "ID de usuário inválido!";
    }
}

// Obter conversa atual
$current_conversation = null;
$current_messages = [];
$other_user = null;

if(isset($_GET['conversation_id']) && !empty($_GET['conversation_id'])) {
    $conversation_id = intval($_GET['conversation_id']);
    
    if($auth->canAccessConversation($conversation_id, $user_id)) {
        $current_conversation = $conversation_id;
        $current_messages = $auth->getMessages($conversation_id, 100);
        $other_user = $auth->getOtherUserInConversation($conversation_id, $user_id);
        
        // Marcar mensagens como lidas
        $auth->markMessagesAsRead($conversation_id, $user_id);
    } else {
        $error = "Você não tem permissão para acessar esta conversa!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensagens - HQ Verso</title>
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
        }
        
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
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
        }
        
        .back-btn:hover {
            background: var(--accent-color);
            color: white;
            transform: translateX(-5px);
        }

        .messages-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
            height: 70vh;
            background: var(--bg-card);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .conversations-sidebar {
            background: rgba(15, 52, 96, 0.3);
            border-right: 1px solid var(--border-color);
            overflow-y: auto;
        }

        [data-theme="light"] .conversations-sidebar {
            background: rgba(233, 69, 96, 0.05);
        }

        .conversations-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .conversations-header h3 {
            color: var(--accent-color);
            font-size: 1.2rem;
        }

        .new-conversation-btn {
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .new-conversation-btn:hover {
            transform: scale(1.1);
            background: var(--accent-hover);
        }

        .conversation-list {
            padding: 10px;
        }

        .conversation-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .conversation-item:hover {
            background: rgba(233, 69, 96, 0.1);
            border-color: rgba(233, 69, 96, 0.3);
        }

        .conversation-item.active {
            background: rgba(233, 69, 96, 0.2);
            border-color: var(--accent-color);
        }

        .conversation-avatar {
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
        }

        .conversation-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .conversation-info {
            flex: 1;
            min-width: 0;
        }

        .conversation-name {
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conversation-preview {
            font-size: 0.8rem;
            opacity: 0.7;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conversation-meta {
            text-align: right;
            font-size: 0.7rem;
            opacity: 0.6;
        }

        .unread-badge {
            background: var(--accent-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
            margin-top: 5px;
        }

        .chat-area {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .chat-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(15, 52, 96, 0.2);
        }

        [data-theme="light"] .chat-header {
            background: rgba(233, 69, 96, 0.05);
        }

        .chat-user-avatar {
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
        }

        .chat-user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .chat-user-info h4 {
            color: var(--accent-color);
            margin-bottom: 5px;
        }

        .chat-user-info p {
            font-size: 0.8rem;
            opacity: 0.7;
        }

        .messages-area {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message {
            max-width: 70%;
            padding: 15px;
            border-radius: 15px;
            position: relative;
            animation: messageSlide 0.3s ease;
        }

        @keyframes messageSlide {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .message.sent {
            align-self: flex-end;
            background: linear-gradient(135deg, var(--accent-color) 0%, var(--accent-hover) 100%);
            color: white;
            border-bottom-right-radius: 5px;
        }

        .message.received {
            align-self: flex-start;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            border-bottom-left-radius: 5px;
        }

        [data-theme="light"] .message.received {
            background: rgba(0, 0, 0, 0.05);
        }

        .message-content {
            word-wrap: break-word;
            line-height: 1.4;
        }

        .message-time {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 5px;
            text-align: right;
        }

        .message-input-area {
            padding: 20px;
            border-top: 1px solid var(--border-color);
            background: rgba(15, 52, 96, 0.2);
        }

        [data-theme="light"] .message-input-area {
            background: rgba(233, 69, 96, 0.05);
        }

        .message-form {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .message-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-primary);
            resize: none;
            min-height: 50px;
            max-height: 120px;
            font-family: inherit;
        }

        [data-theme="light"] .message-input {
            background: rgba(0, 0, 0, 0.05);
        }

        .message-input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.2);
        }

        .send-btn {
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
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .send-btn:hover {
            background: var(--accent-hover);
            transform: scale(1.1);
        }

        .send-btn:disabled {
            background: var(--text-secondary);
            cursor: not-allowed;
            transform: none;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            opacity: 0.7;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: var(--accent-color);
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: var(--accent-color);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            text-align: center;
            font-weight: 500;
            border-left: 4px solid;
            animation: slideIn 0.4s ease;
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

        /* Modal para nova conversa */
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

        .search-users {
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        [data-theme="light"] .search-input {
            background: rgba(0, 0, 0, 0.05);
        }

        .search-results {
            max-height: 300px;
            overflow-y: auto;
            margin-top: 15px;
        }

        .search-user-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .search-user-item:hover {
            background: rgba(233, 69, 96, 0.1);
            border-color: rgba(233, 69, 96, 0.3);
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

        @media (max-width: 768px) {
            .messages-container {
                grid-template-columns: 1fr;
                height: 80vh;
            }
            
            .conversations-sidebar {
                display: none;
            }
            
            .message {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon" id="themeIcon"></i>
    </button>

    <div class="container">
        <header>
            <a href="comics.php" class="logo">HQ VERSO</a>
            <a href="comics.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> 
                <span>Voltar para Quadrinhos</span>
            </a>
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
        
        <div class="messages-container">
            <!-- Sidebar de Conversas -->
            <div class="conversations-sidebar">
                <div class="conversations-header">
                    <h3>Conversas</h3>
                    <button class="new-conversation-btn" onclick="openNewConversationModal()">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                
                <div class="conversation-list">
                    <?php if(!empty($conversations)): ?>
                        <?php foreach($conversations as $conv): ?>
                            <a href="messages.php?conversation_id=<?php echo $conv['id']; ?>" 
                               class="conversation-item <?php echo $current_conversation == $conv['id'] ? 'active' : ''; ?>">
                                <div class="conversation-avatar">
                                    <?php if($conv['other_avatar'] && file_exists($conv['other_avatar'])): ?>
                                        <img src="<?php echo $conv['other_avatar']; ?>" alt="Avatar">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($conv['other_username'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="conversation-info">
                                    <div class="conversation-name">
                                        <?php echo htmlspecialchars($conv['other_username']); ?>
                                    </div>
                                    <div class="conversation-preview">
                                        <?php 
                                        if($conv['last_message']) {
                                            echo strlen($conv['last_message']) > 30 
                                                ? substr($conv['last_message'], 0, 30) . '...' 
                                                : $conv['last_message'];
                                        } else {
                                            echo 'Nenhuma mensagem ainda';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="conversation-meta">
                                    <div><?php echo date('H:i', strtotime($conv['last_message_at'])); ?></div>
                                    <?php if($conv['unread_count'] > 0): ?>
                                        <div class="unread-badge"><?php echo $conv['unread_count']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-comments"></i>
                            <h3>Nenhuma conversa</h3>
                            <p>Inicie uma conversa com outros usuários!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Área de Chat -->
            <div class="chat-area">
                <?php if($current_conversation && $other_user): ?>
                    <div class="chat-header">
                        <div class="chat-user-avatar">
                            <?php if($other_user['other_avatar'] && file_exists($other_user['other_avatar'])): ?>
                                <img src="<?php echo $other_user['other_avatar']; ?>" alt="Avatar">
                            <?php else: ?>
                                <?php echo strtoupper(substr($other_user['other_username'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="chat-user-info">
                            <h4><?php echo htmlspecialchars($other_user['other_username']); ?></h4>
                            <p>Conversando agora</p>
                        </div>
                    </div>
                    
                    <div class="messages-area" id="messagesArea">
                        <?php if(!empty($current_messages)): ?>
                            <?php foreach($current_messages as $message): ?>
                                <div class="message <?php echo $message['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                                    <div class="message-content">
                                        <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                                    </div>
                                    <div class="message-time">
                                        <?php echo date('H:i', strtotime($message['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-comment-slash"></i>
                                <h3>Nenhuma mensagem ainda</h3>
                                <p>Envie a primeira mensagem para iniciar a conversa!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="message-input-area">
                        <form method="POST" class="message-form" id="messageForm">
                            <input type="hidden" name="conversation_id" value="<?php echo $current_conversation; ?>">
                            <textarea name="message_content" class="message-input" placeholder="Digite sua mensagem..." 
                                      required id="messageInput"></textarea>
                            <button type="submit" name="send_message" class="send-btn" id="sendBtn">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <h3>Selecione uma conversa</h3>
                        <p>Escolha uma conversa da lista ou inicie uma nova!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para Nova Conversa -->
    <div class="modal" id="newConversationModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Nova Conversa</h3>
                <button class="close-modal" onclick="closeNewConversationModal()">&times;</button>
            </div>
            
            <div class="search-users">
                <input type="text" class="search-input" placeholder="Buscar usuários..." id="userSearch">
                <div class="search-results" id="searchResults"></div>
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

        // Sistema de busca de usuários
        document.getElementById('userSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.trim();
            
            if(searchTerm.length < 2) {
                document.getElementById('searchResults').innerHTML = '';
                return;
            }
            
            fetch('search_users.php?q=' + encodeURIComponent(searchTerm))
                .then(response => response.json())
                .then(users => {
                    const resultsContainer = document.getElementById('searchResults');
                    resultsContainer.innerHTML = '';
                    
                    if(users.length > 0) {
                        users.forEach(user => {
                            const userItem = document.createElement('div');
                            userItem.className = 'search-user-item';
                            userItem.innerHTML = `
                                <div class="conversation-avatar">
                                    ${user.avatar && user.avatar !== '' ? 
                                        `<img src="${user.avatar}" alt="Avatar">` : 
                                        user.username.charAt(0).toUpperCase()}
                                </div>
                                <div class="conversation-info">
                                    <div class="conversation-name">${user.username}</div>
                                    <div class="conversation-preview">${user.email}</div>
                                </div>
                            `;
                            userItem.onclick = function() {
                                startConversation(user.id);
                            };
                            resultsContainer.appendChild(userItem);
                        });
                    } else {
                        resultsContainer.innerHTML = '<div style="text-align: center; padding: 20px; opacity: 0.7;">Nenhum usuário encontrado</div>';
                    }
                })
                .catch(error => {
                    console.error('Erro na busca:', error);
                });
        });

        function startConversation(userId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'target_user_id';
            input.value = userId;
            
            const submit = document.createElement('input');
            submit.type = 'hidden';
            submit.name = 'start_conversation';
            submit.value = '1';
            
            form.appendChild(input);
            form.appendChild(submit);
            document.body.appendChild(form);
            form.submit();
        }

        function openNewConversationModal() {
            document.getElementById('newConversationModal').style.display = 'flex';
        }

        function closeNewConversationModal() {
            document.getElementById('newConversationModal').style.display = 'none';
            document.getElementById('userSearch').value = '';
            document.getElementById('searchResults').innerHTML = '';
        }

        document.getElementById('newConversationModal').addEventListener('click', function(e) {
            if(e.target === this) {
                closeNewConversationModal();
            }
        });

        // Auto-scroll para baixo na área de mensagens
        const messagesArea = document.getElementById('messagesArea');
        if(messagesArea) {
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }

        // Envio de mensagem com AJAX
        const messageForm = document.getElementById('messageForm');
        if(messageForm) {
            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const sendBtn = document.getElementById('sendBtn');
                const messageInput = document.getElementById('messageInput');
                
                sendBtn.disabled = true;
                sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                fetch('send_message.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert('Erro: ' + data.message);
                        sendBtn.disabled = false;
                        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
                    }
                })
                .catch(error => {
                    alert('Erro ao enviar mensagem');
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
                });
            });
        }

        // Auto-focus no input de mensagem
        const messageInput = document.getElementById('messageInput');
        if(messageInput) {
            messageInput.focus();
            
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }

        // Inicializar tema quando o DOM carregar
        document.addEventListener('DOMContentLoaded', initializeTheme);
    </script>
</body>
</html>
