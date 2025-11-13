<?php
session_start();
require_once 'config_database.php';

class Auth {
    private $conn;
    private $table = 'users';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }


    
// NOVA FUNÇÃO: Iniciar ou obter conversa
public function getOrCreateConversation($user1_id, $user2_id) {
    try {
        // Garantir que user1_id é sempre o menor ID para evitar duplicatas
        $min_id = min($user1_id, $user2_id);
        $max_id = max($user1_id, $user2_id);
        
        $query = "SELECT id FROM conversations 
                 WHERE user1_id = :user1_id AND user2_id = :user2_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user1_id", $min_id);
        $stmt->bindParam(":user2_id", $max_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        } else {
            // Criar nova conversa
            $query = "INSERT INTO conversations (user1_id, user2_id) 
                     VALUES (:user1_id, :user2_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user1_id", $min_id);
            $stmt->bindParam(":user2_id", $max_id);
            
            if($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        }
        
    } catch(PDOException $exception) {
        return false;
    }
}

// NOVA FUNÇÃO: Enviar mensagem
public function sendMessage($conversation_id, $sender_id, $content) {
    try {
        // Validar conteúdo
        $content = trim($content);
        if(empty($content)) {
            return "A mensagem não pode estar vazia!";
        }
        
        if(strlen($content) > 1000) {
            return "A mensagem é muito longa (máximo 1000 caracteres)!";
        }
        
        $query = "INSERT INTO messages (conversation_id, sender_id, content) 
                 VALUES (:conversation_id, :sender_id, :content)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":conversation_id", $conversation_id);
        $stmt->bindParam(":sender_id", $sender_id);
        $stmt->bindParam(":content", $content);
        
        if($stmt->execute()) {
            // Atualizar last_message_at na conversa
            $update_query = "UPDATE conversations SET last_message_at = NOW() 
                           WHERE id = :conversation_id";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(":conversation_id", $conversation_id);
            $update_stmt->execute();
            
            return true;
        }
        return "Erro ao enviar mensagem!";
        
    } catch(PDOException $exception) {
        return "Erro: " . $exception->getMessage();
    }
}

// NOVA FUNÇÃO: Obter mensagens de uma conversa
public function getMessages($conversation_id, $limit = 50, $offset = 0) {
    try {
        $query = "SELECT m.*, u.username, u.avatar 
                 FROM messages m 
                 JOIN users u ON m.sender_id = u.id 
                 WHERE m.conversation_id = :conversation_id 
                 ORDER BY m.created_at DESC 
                 LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":conversation_id", $conversation_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Inverter a ordem para mostrar as mais antigas primeiro
        return array_reverse($messages);
        
    } catch(PDOException $exception) {
        return [];
    }
}

// NOVA FUNÇÃO: Obter conversas do usuário
public function getUserConversations($user_id) {
    try {
        $query = "SELECT c.*, 
                         CASE 
                             WHEN c.user1_id = :user_id THEN u2.id 
                             ELSE u1.id 
                         END as other_user_id,
                         CASE 
                             WHEN c.user1_id = :user_id THEN u2.username 
                             ELSE u1.username 
                         END as other_username,
                         CASE 
                             WHEN c.user1_id = :user_id THEN u2.avatar 
                             ELSE u1.avatar 
                         END as other_avatar,
                         (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
                         (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_id != :user_id AND is_read = 0) as unread_count
                  FROM conversations c
                  JOIN users u1 ON c.user1_id = u1.id
                  JOIN users u2 ON c.user2_id = u2.id
                  WHERE c.user1_id = :user_id OR c.user2_id = :user_id
                  ORDER BY c.last_message_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $exception) {
        return [];
    }
}

// NOVA FUNÇÃO: Marcar mensagens como lidas
public function markMessagesAsRead($conversation_id, $user_id) {
    try {
        $query = "UPDATE messages SET is_read = 1 
                 WHERE conversation_id = :conversation_id 
                 AND sender_id != :user_id 
                 AND is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":conversation_id", $conversation_id);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
        
    } catch(PDOException $exception) {
        return false;
    }
}

// NOVA FUNÇÃO: Verificar se usuário pode acessar a conversa
public function canAccessConversation($conversation_id, $user_id) {
    try {
        $query = "SELECT id FROM conversations 
                 WHERE id = :conversation_id 
                 AND (user1_id = :user_id OR user2_id = :user_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":conversation_id", $conversation_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
        
    } catch(PDOException $exception) {
        return false;
    }
}

// NOVA FUNÇÃO: Obter dados do outro usuário na conversa
public function getOtherUserInConversation($conversation_id, $current_user_id) {
    try {
        $query = "SELECT 
                    CASE 
                        WHEN user1_id = :current_user_id THEN user2_id 
                        ELSE user1_id 
                    END as other_user_id,
                    CASE 
                        WHEN user1_id = :current_user_id THEN u2.username 
                        ELSE u1.username 
                    END as other_username,
                    CASE 
                        WHEN user1_id = :current_user_id THEN u2.avatar 
                        ELSE u1.avatar 
                    END as other_avatar
                  FROM conversations c
                  JOIN users u1 ON c.user1_id = u1.id
                  JOIN users u2 ON c.user2_id = u2.id
                  WHERE c.id = :conversation_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":conversation_id", $conversation_id);
        $stmt->bindParam(":current_user_id", $current_user_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
        
    } catch(PDOException $exception) {
        return false;
    }
}

    // NOVA FUNÇÃO: Reportar usuário
public function reportUser($reported_user_id, $reporter_user_id, $reason, $description = '') {
    try {
        // Verificar se não está reportando a si mesmo
        if($reported_user_id == $reporter_user_id) {
            return "Você não pode se reportar!";
        }

        // Verificar se já reportou este usuário recentemente (evitar spam)
        $query = "SELECT id FROM user_reports 
                 WHERE reporter_user_id = :reporter_id 
                 AND reported_user_id = :reported_id 
                 AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":reporter_id", $reporter_user_id);
        $stmt->bindParam(":reported_id", $reported_user_id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return "Você já reportou este usuário recentemente. Aguarde um pouco antes de reportar novamente.";
        }

        // Inserir report
        $query = "INSERT INTO user_reports (reported_user_id, reporter_user_id, reason, description) 
                 VALUES (:reported_id, :reporter_id, :reason, :description)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":reported_id", $reported_user_id);
        $stmt->bindParam(":reporter_id", $reporter_user_id);
        $stmt->bindParam(":reason", $reason);
        $stmt->bindParam(":description", $description);

        if($stmt->execute()) {
            // Criar notificação para admin
            $this->createAdminNotification(
                'user_report', 
                'Novo usuário reportado', 
                "O usuário ID {$reported_user_id} foi reportado por ID {$reporter_user_id}",
                $reported_user_id,
                'user'
            );
            return true;
        }
        return "Erro ao reportar usuário!";

    } catch(PDOException $exception) {
        return "Erro: " . $exception->getMessage();
    }
}

// NOVA FUNÇÃO: Criar notificação para admin
public function createAdminNotification($type, $title, $message, $related_id = null, $related_type = null) {
    try {
        $query = "INSERT INTO admin_notifications (type, title, message, related_id, related_type) 
                 VALUES (:type, :title, :message, :related_id, :related_type)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":type", $type);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":message", $message);
        $stmt->bindParam(":related_id", $related_id);
        $stmt->bindParam(":related_type", $related_type);

        return $stmt->execute();

    } catch(PDOException $exception) {
        return false;
    }
}

// NOVA FUNÇÃO: Obter notificações não lidas para admin
public function getUnreadAdminNotifications($limit = 10) {
    try {
        $query = "SELECT * FROM admin_notifications 
                 WHERE is_read = 0 
                 ORDER BY created_at DESC 
                 LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch(PDOException $exception) {
        return [];
    }
}

// NOVA FUNÇÃO: Obter todos os reports pendentes
public function getPendingReports() {
    try {
        $query = "SELECT ur.*, 
                         ru.username as reported_username,
                         ru.email as reported_email,
                         rep.username as reporter_username
                  FROM user_reports ur
                  JOIN users ru ON ur.reported_user_id = ru.id
                  JOIN users rep ON ur.reporter_user_id = rep.id
                  WHERE ur.status = 'pending'
                  ORDER BY ur.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch(PDOException $exception) {
        return [];
    }
}

    // NOVA FUNÇÃO: Seguir usuário automaticamente após cadastro
    public function autoFollowAfterRegister($new_user_id, $target_username = 'Juan Taborda') {
        try {
            // Buscar o ID do usuário alvo
            $query = "SELECT id FROM users WHERE username = :username";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $target_username);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
                $target_user_id = $target_user['id'];
                
                // Seguir o usuário
                return $this->followUser($new_user_id, $target_user_id);
            }
            return false;
            
        } catch(PDOException $exception) {
            return false;
        }
    }

    // ATUALIZADA: Função register com follow automático
    public function register($username, $email, $password) {
        try {
            // VERIFICAÇÃO DE BANIMENTO - Verificar primeiro se está banido
            $banned = $this->isUserBanned($email, $username);
            if($banned) {
                $banned_date = date('d/m/Y H:i', strtotime($banned['banned_at']));
                return "Este email ou nome de usuário está permanentemente banido. Motivo: " . 
                       ($banned['reason'] ?: 'Não especificado') . 
                       " (Banido em: $banned_date)";
            }

            // Verificar se usuário ou email já existem
            $query = "SELECT id FROM " . $this->table . " WHERE username = :username OR email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":email", $email);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                return "Usuário ou email já cadastrado!";
            }

            // Inserir novo usuário
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO " . $this->table . " 
                     (username, email, password, created_at) 
                     VALUES (:username, :email, :password, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password", $hashed_password);

            if($stmt->execute()) {
                // Obter o ID do novo usuário
                $new_user_id = $this->conn->lastInsertId();
                
                // Seguir automaticamente o usuário "Juan Taborda"
                $this->autoFollowAfterRegister($new_user_id);
                
                return true;
            }
            return "Erro ao cadastrar usuário!";

        } catch(PDOException $exception) {
            return "Erro: " . $exception->getMessage();
        }
    }

    public function searchUsersWithFollow($search_term, $current_user_id = null) {
    try {
        $query = "SELECT 
                    u.id, u.username, u.email, u.avatar, u.role, u.bio,
                    (SELECT COUNT(*) FROM user_follows WHERE following_id = u.id) as followers_count,
                    (SELECT COUNT(*) FROM comics WHERE author_id = u.id) as comics_count,
                    (SELECT COUNT(*) FROM user_follows WHERE follower_id = :current_user_id AND following_id = u.id) as is_following
                  FROM users u 
                  WHERE u.username LIKE :search OR u.email LIKE :search
                  ORDER BY u.username";
        
        $stmt = $this->conn->prepare($query);
        $search_term = "%$search_term%";
        $stmt->bindParam(":search", $search_term);
        $stmt->bindParam(":current_user_id", $current_user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $exception) {
        return [];
    }
}

    // NOVA FUNÇÃO: Seguir usuário
    public function followUser($follower_id, $following_id) {
        try {
            // Verificar se não é o próprio usuário
            if($follower_id == $following_id) {
                return "Você não pode seguir a si mesmo!";
            }

            // Verificar se já está seguindo
            $query = "SELECT id FROM user_follows WHERE follower_id = :follower_id AND following_id = :following_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":follower_id", $follower_id);
            $stmt->bindParam(":following_id", $following_id);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                return "Você já está seguindo este usuário!";
            }

            // Inserir follow
            $query = "INSERT INTO user_follows (follower_id, following_id) VALUES (:follower_id, :following_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":follower_id", $follower_id);
            $stmt->bindParam(":following_id", $following_id);

            if($stmt->execute()) {
                return true;
            }
            return "Erro ao seguir usuário!";

        } catch(PDOException $exception) {
            return "Erro: " . $exception->getMessage();
        }
    }

    // NOVA FUNÇÃO: Deixar de seguir usuário
    public function unfollowUser($follower_id, $following_id) {
        try {
            $query = "DELETE FROM user_follows WHERE follower_id = :follower_id AND following_id = :following_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":follower_id", $follower_id);
            $stmt->bindParam(":following_id", $following_id);

            if($stmt->execute()) {
                return true;
            }
            return "Erro ao deixar de seguir usuário!";

        } catch(PDOException $exception) {
            return "Erro: " . $exception->getMessage();
        }
    }

    // NOVA FUNÇÃO: Verificar se está seguindo
    public function isFollowing($follower_id, $following_id) {
        try {
            $query = "SELECT id FROM user_follows WHERE follower_id = :follower_id AND following_id = :following_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":follower_id", $follower_id);
            $stmt->bindParam(":following_id", $following_id);
            $stmt->execute();

            return $stmt->rowCount() > 0;

        } catch(PDOException $exception) {
            return false;
        }
    }

    // NOVA FUNÇÃO: Obter estatísticas de seguidores
    public function getFollowStats($user_id) {
        try {
            $query = "SELECT 
                        (SELECT COUNT(*) FROM user_follows WHERE following_id = :user_id) as followers_count,
                        (SELECT COUNT(*) FROM user_follows WHERE follower_id = :user_id) as following_count";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch(PDOException $exception) {
            return ['followers_count' => 0, 'following_count' => 0];
        }
    }

    // NOVA FUNÇÃO: Obter lista de seguidores
    public function getFollowers($user_id, $limit = 20) {
        try {
            $query = "SELECT u.id, u.username, u.avatar, u.bio, u.role,
                             (SELECT COUNT(*) FROM user_follows uf2 WHERE uf2.following_id = u.id) as followers_count
                      FROM user_follows uf
                      JOIN users u ON uf.follower_id = u.id
                      WHERE uf.following_id = :user_id
                      ORDER BY uf.created_at DESC
                      LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch(PDOException $exception) {
            return [];
        }
    }

    // NOVA FUNÇÃO: Obter lista de usuários seguindo
    public function getFollowing($user_id, $limit = 20) {
        try {
            $query = "SELECT u.id, u.username, u.avatar, u.bio, u.role,
                             (SELECT COUNT(*) FROM user_follows uf2 WHERE uf2.following_id = u.id) as followers_count
                      FROM user_follows uf
                      JOIN users u ON uf.following_id = u.id
                      WHERE uf.follower_id = :user_id
                      ORDER BY uf.created_at DESC
                      LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch(PDOException $exception) {
            return [];
        }
    }

    public function login($email, $password) {
        try {
            $query = "SELECT id, username, email, password, role FROM " . $this->table . " 
                     WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();

            if($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if(password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    return true;
                }
            }
            return "Email ou senha incorretos!";

        } catch(PDOException $exception) {
            return "Erro: " . $exception->getMessage();
        }
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public function logout() {
        session_destroy();
        header("Location: login.php");
        exit();
    }

    public function getUserData($user_id) {
        try {
            $query = "SELECT id, username, email, avatar, bio, role, created_at 
                     FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $user_id);
            $stmt->execute();

            if($stmt->rowCount() == 1) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            return false;

        } catch(PDOException $exception) {
            return false;
        }
    }

    public function getAllUsers() {
        try {
            $query = "SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            return [];
        }
    }

    // CORREÇÃO: Função updateProfile corrigida
    public function updateProfile($user_id, $username, $email, $bio = '') {
        try {
            // Verificar se o username ou email já existem (excluindo o usuário atual)
            $query = "SELECT id FROM " . $this->table . " 
                     WHERE (username = :username OR email = :email) AND id != :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":id", $user_id);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                return "Username ou email já está em uso!";
            }

            // Atualizar perfil - CORREÇÃO: adicionado updated_at
            $query = "UPDATE " . $this->table . " 
                     SET username = :username, email = :email, bio = :bio, updated_at = NOW() 
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":bio", $bio);
            $stmt->bindParam(":id", $user_id);

            if($stmt->execute()) {
                // Atualizar sessão
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                return true;
            }
            return "Erro ao atualizar perfil!";

        } catch(PDOException $exception) {
            return "Erro: " . $exception->getMessage();
        }
    }

    // CORREÇÃO: Função changePassword corrigida
    public function changePassword($user_id, $current_password, $new_password) {
        try {
            // Buscar usuário
            $query = "SELECT id, password FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $user_id);
            $stmt->execute();

            if($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verificar senha atual
                if(password_verify($current_password, $user['password'])) {
                    // Atualizar senha
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $query = "UPDATE " . $this->table . " 
                             SET password = :password, updated_at = NOW() 
                             WHERE id = :id";
                    
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(":password", $hashed_password);
                    $stmt->bindParam(":id", $user_id);

                    if($stmt->execute()) {
                        return true;
                    }
                    return "Erro ao atualizar senha!";
                } else {
                    return "Senha atual incorreta!";
                }
            }
            return "Usuário não encontrado!";

        } catch(PDOException $exception) {
            return "Erro: " . $exception->getMessage();
        }
    }

    // FUNÇÃO: Excluir conta do usuário
    public function deleteUserAccount($user_id, $current_user_id = null) {
        try {
            // Verificar se é o próprio usuário ou um admin
            if($current_user_id && $current_user_id != $user_id) {
                $current_user = $this->getUserData($current_user_id);
                if($current_user['role'] !== 'admin') {
                    return "Você não tem permissão para excluir esta conta!";
                }
            }

            // Iniciar transação para garantir que todas as exclusões sejam feitas
            $this->conn->beginTransaction();

            // Excluir dados relacionados nas outras tabelas
            $tables = [
                'reactions', 'posts', 'topics', 'comic_comments', 'reviews', 
                'favorites', 'reading_progress', 'user_library', 'transactions',
                'comic_collaborators', 'comic_drafts', 'user_uploads',
                'comic_categories', 'comic_pages', 'comics'
            ];

            foreach($tables as $table) {
                // Para comics, só excluir se o usuário for o autor
                if($table === 'comics') {
                    $query = "DELETE FROM $table WHERE author_id = :user_id";
                } else {
                    // Verificar se a tabela tem user_id ou author_id
                    $columns = $this->getTableColumns($table);
                    if(in_array('user_id', $columns)) {
                        $query = "DELETE FROM $table WHERE user_id = :user_id";
                    } elseif(in_array('author_id', $columns)) {
                        $query = "DELETE FROM $table WHERE author_id = :user_id";
                    } else {
                        continue;
                    }
                }
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":user_id", $user_id);
                $stmt->execute();
            }

            // Finalmente excluir o usuário
            $query = "DELETE FROM users WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();

            $this->conn->commit();

            // Se o usuário está excluindo a própria conta, fazer logout
            if($current_user_id == $user_id) {
                session_destroy();
            }

            return true;

        } catch(PDOException $exception) {
            $this->conn->rollBack();
            return "Erro ao excluir conta: " . $exception->getMessage();
        }
    }

    // Função auxiliar para obter colunas da tabela
    private function getTableColumns($table) {
        $query = "SHOW COLUMNS FROM $table";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $columns;
    }

    // NOVA FUNÇÃO: Verificar se usuário está banido
    public function isUserBanned($email, $username = '') {
        try {
            $query = "SELECT id, email, username, reason, banned_at, banned_by 
                     FROM banned_users 
                     WHERE email = :email OR username = :username";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":username", $username);
            $stmt->execute();

            if($stmt->rowCount() > 0) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            return false;

        } catch(PDOException $exception) {
            return false;
        }
    }

    // NOVA FUNÇÃO: Banir usuário permanentemente
    public function banUser($user_id, $banned_by, $reason = '') {
        try {
            // Primeiro obter dados do usuário a ser banido
            $user_data = $this->getUserData($user_id);
            if(!$user_data) {
                return "Usuário não encontrado!";
            }

            // Verificar se já está banido
            $already_banned = $this->isUserBanned($user_data['email'], $user_data['username']);
            if($already_banned) {
                return "Este usuário já está banido!";
            }

            // Inserir na tabela de banidos
            $query = "INSERT INTO banned_users (email, username, reason, banned_by, is_permanent) 
                     VALUES (:email, :username, :reason, :banned_by, TRUE)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $user_data['email']);
            $stmt->bindParam(":username", $user_data['username']);
            $stmt->bindParam(":reason", $reason);
            $stmt->bindParam(":banned_by", $banned_by);

            if($stmt->execute()) {
                // Agora excluir a conta do usuário
                return $this->deleteUserAccount($user_id, $banned_by);
            }
            return "Erro ao banir usuário!";

        } catch(PDOException $exception) {
            return "Erro: " . $exception->getMessage();
        }
    }

    // NOVA FUNÇÃO: Pesquisar usuários
    public function searchUsers($search_term) {
        try {
            $query = "SELECT id, username, email, role, created_at,
                             (SELECT COUNT(*) FROM comics WHERE author_id = users.id) as comics_count
                      FROM users 
                      WHERE username LIKE :search OR email LIKE :search
                      ORDER BY username";
            $stmt = $this->conn->prepare($query);
            $search_term = "%$search_term%";
            $stmt->bindParam(":search", $search_term);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            return [];
        }
    }

    // NOVA FUNÇÃO: Obter lista de usuários banidos
    public function getBannedUsers() {
        try {
            $query = "SELECT bu.*, u.username as banned_by_name 
                     FROM banned_users bu 
                     LEFT JOIN users u ON bu.banned_by = u.id 
                     ORDER BY bu.banned_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            return [];
        }
    }

    // NOVA FUNÇÃO: Obter todos os usuários para admin
    public function getAllUsersForAdmin() {
        try {
            $query = "SELECT id, username, email, role, created_at,
                             (SELECT COUNT(*) FROM comics WHERE author_id = users.id) as comics_count
                      FROM users 
                      ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            return [];
        }
    }

    // NOVA FUNÇÃO: Atualizar avatar (sistema de arquivos)
    public function updateAvatar($user_id, $avatar_path, $file_name, $file_size, $mime_type) {
        try {
            // Primeiro remover avatar anterior se existir
            $user_data = $this->getUserData($user_id);
            if($user_data['avatar'] && file_exists($user_data['avatar'])) {
                unlink($user_data['avatar']);
            }

            $query = "UPDATE users SET avatar = :avatar, 
                     avatar_file_name = :file_name, 
                     avatar_file_size = :file_size, 
                     avatar_mime_type = :mime_type,
                     avatar_updated_at = NOW() 
                     WHERE id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":avatar", $avatar_path);
            $stmt->bindParam(":file_name", $file_name);
            $stmt->bindParam(":file_size", $file_size);
            $stmt->bindParam(":mime_type", $mime_type);
            $stmt->bindParam(":user_id", $user_id);

            if($stmt->execute()) {
                return true;
            }
            return false;

        } catch(PDOException $exception) {
            return false;
        }
    }

    // NOVA FUNÇÃO: Remover avatar
    public function removeAvatar($user_id) {
        try {
            // Primeiro obter o caminho do avatar atual
            $user_data = $this->getUserData($user_id);
            $current_avatar = $user_data['avatar'];
            
            // Remover arquivo físico se existir
            if($current_avatar && file_exists($current_avatar)) {
                unlink($current_avatar);
            }
            
            // Atualizar banco de dados
            $query = "UPDATE users SET avatar = NULL, 
                     avatar_file_name = NULL, 
                     avatar_file_size = NULL, 
                     avatar_mime_type = NULL,
                     avatar_updated_at = NOW() 
                     WHERE id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);

            if($stmt->execute()) {
                return true;
            }
            return false;

        } catch(PDOException $exception) {
            return false;
        }
    }

        /**
     * Solicitar conta de criador
     */
    public function requestCreatorAccount($user_id, $cpf, $address, $age) {
        try {
            // Validar dados
            if (empty($cpf) || empty($address) || empty($age)) {
                return "Todos os campos são obrigatórios!";
            }

            // Validar CPF
            if (!$this->validateCPF($cpf)) {
                return "CPF inválido!";
            }

            // Validar idade (mínimo 18 anos)
            if ($age < 18) {
                return "É necessário ter pelo menos 18 anos para ser criador!";
            }

            // Verificar se já existe solicitação pendente
            $check_query = "SELECT id FROM creator_requests WHERE user_id = :user_id AND status = 'pending'";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(":user_id", $user_id);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                return "Você já tem uma solicitação de conta criador pendente!";
            }

            // Verificar se CPF já está em uso
            $cpf_query = "SELECT id FROM creator_requests WHERE cpf = :cpf AND status = 'approved'";
            $cpf_stmt = $this->conn->prepare($cpf_query);
            $cpf_stmt->bindParam(":cpf", $cpf);
            $cpf_stmt->execute();

            if ($cpf_stmt->rowCount() > 0) {
                return "Este CPF já está associado a uma conta criador ativa!";
            }

            // Inserir solicitação
            $query = "INSERT INTO creator_requests (user_id, cpf, address, age, status, requested_at) 
                      VALUES (:user_id, :cpf, :address, :age, 'pending', NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":cpf", $cpf);
            $stmt->bindParam(":address", $address);
            $stmt->bindParam(":age", $age);

            if ($stmt->execute()) {
                // Criar notificação para admin
                $this->createAdminNotification(
                    'new_creator_request',
                    'Nova solicitação de conta criador',
                    "O usuário ID $user_id solicitou uma conta de criador",
                    $user_id,
                    'user'
                );

                return true;
            }

            return "Erro ao processar solicitação!";

        } catch(PDOException $e) {
            error_log("Erro ao solicitar conta criador: " . $e->getMessage());
            return "Erro interno do sistema!";
        }
    }

    /**
     * Validar CPF
     */
    private function validateCPF($cpf) {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        // Verifica se tem 11 dígitos
        if (strlen($cpf) != 11) {
            return false;
        }
        
        // Verifica se não é uma sequência de números iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        // Calcula e verifica primeiro dígito verificador
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Obter status da solicitação de criador
     */
    public function getCreatorRequestStatus($user_id) {
        try {
            $query = "SELECT status, admin_notes, processed_at 
                      FROM creator_requests 
                      WHERE user_id = :user_id 
                      ORDER BY requested_at DESC 
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {
            error_log("Erro ao obter status da solicitação: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obter todas as solicitações de criador pendentes (para admin)
     */
    public function getPendingCreatorRequests() {
        try {
            $query = "SELECT cr.*, u.username, u.email 
                      FROM creator_requests cr
                      JOIN users u ON cr.user_id = u.id
                      WHERE cr.status = 'pending'
                      ORDER BY cr.requested_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {
            error_log("Erro ao obter solicitações pendentes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Aprovar ou rejeitar solicitação de criador (admin)
     */
    public function processCreatorRequest($request_id, $status, $admin_id, $admin_notes = '') {
        try {
            // Obter dados da solicitação
            $query = "SELECT user_id FROM creator_requests WHERE id = :request_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":request_id", $request_id);
            $stmt->execute();
            
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$request) {
                return "Solicitação não encontrada!";
            }

            $user_id = $request['user_id'];

            // Atualizar status da solicitação
            $update_query = "UPDATE creator_requests 
                            SET status = :status, admin_id = :admin_id, 
                                admin_notes = :admin_notes, processed_at = NOW()
                            WHERE id = :request_id";
            
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(":status", $status);
            $update_stmt->bindParam(":admin_id", $admin_id);
            $update_stmt->bindParam(":admin_notes", $admin_notes);
            $update_stmt->bindParam(":request_id", $request_id);

            if (!$update_stmt->execute()) {
                return "Erro ao atualizar solicitação!";
            }

            // Se aprovado, atualizar role do usuário
            if ($status === 'approved') {
                $user_query = "UPDATE users SET role = 'creator' WHERE id = :user_id";
                $user_stmt = $this->conn->prepare($user_query);
                $user_stmt->bindParam(":user_id", $user_id);
                
                if (!$user_stmt->execute()) {
                    return "Erro ao atualizar role do usuário!";
                }
            }

            return true;

        } catch(PDOException $e) {
            error_log("Erro ao processar solicitação de criador: " . $e->getMessage());
            return "Erro interno do sistema!";
        }
    }

    
    public function getAllCreatorRequests() {
        try {
            $query = "SELECT cr.*, u.username, u.email, 
                             admin.username as admin_name
                      FROM creator_requests cr
                      JOIN users u ON cr.user_id = u.id
                      LEFT JOIN users admin ON cr.admin_id = admin.id
                      ORDER BY cr.requested_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {
            error_log("Erro ao obter todas as solicitações: " . $e->getMessage());
            return [];
        }
    }
    
public function isCreator($user_id) {
    try {
        $query = "SELECT role FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user && ($user['role'] === 'creator' || $user['role'] === 'admin');

    } catch(PDOException $e) {
        error_log("Erro ao verificar role: " . $e->getMessage());
        return false;
    }
}

/**
 * Obter todas as categorias
 */
public function getAllCategories() {
    try {
        $query = "SELECT id, name, description FROM categories ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Erro ao obter categorias: " . $e->getMessage());
        return [];
    }
}

/**
 * Upload da capa do quadrinho
 */
public function uploadComicCover($file, $user_id) {
    try {
        $upload_dir = "uploads/covers/";
        
        // Criar diretório se não existir
        if(!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Validar tipo de arquivo
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if(!in_array($file['type'], $allowed_types)) {
            return ['success' => false, 'message' => 'Tipo de arquivo não suportado! Use apenas JPG, PNG, GIF ou WebP.'];
        }
        
        // Validar tamanho (5MB)
        if($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'Arquivo muito grande! Máximo 5MB.'];
        }
        
        // Gerar nome único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "cover_" . $user_id . "_" . time() . "." . $extension;
        $filepath = $upload_dir . $filename;
        
        if(move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => true, 'path' => $filepath, 'filename' => $filename];
        } else {
            return ['success' => false, 'message' => 'Erro ao fazer upload da capa!'];
        }
        
    } catch(PDOException $e) {
        error_log("Erro no upload da capa: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno no upload!'];
    }
}

/**
 * Upload das páginas do quadrinho
 */
public function uploadComicPages($files, $comic_id, $user_id) {
    try {
        $upload_dir = "uploads/pages/";
        
        // Criar diretório se não existir
        if(!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $uploaded_pages = 0;
        $errors = [];
        
        // Processar cada arquivo
        for($i = 0; $i < count($files['name']); $i++) {
            if($files['error'][$i] === UPLOAD_ERR_OK) {
                // Validar tipo de arquivo
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if(!in_array($files['type'][$i], $allowed_types)) {
                    $errors[] = "Página " . ($i + 1) . ": Tipo de arquivo não suportado";
                    continue;
                }
                
                // Validar tamanho (5MB)
                if($files['size'][$i] > 5 * 1024 * 1024) {
                    $errors[] = "Página " . ($i + 1) . ": Arquivo muito grande (Máx. 5MB)";
                    continue;
                }
                
                // Gerar nome único
                $extension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $filename = "page_" . $comic_id . "_" . ($i + 1) . "_" . time() . "." . $extension;
                $filepath = $upload_dir . $filename;
                
                if(move_uploaded_file($files['tmp_name'][$i], $filepath)) {
                    // Inserir página no banco
                    $page_number = $i + 1;
                    $stmt = $this->conn->prepare("INSERT INTO comic_pages (comic_id, page_number, image_url, title) VALUES (?, ?, ?, ?)");
                    $title = "Página " . $page_number;
                    $stmt->bindParam(1, $comic_id);
                    $stmt->bindParam(2, $page_number);
                    $stmt->bindParam(3, $filepath);
                    $stmt->bindParam(4, $title);
                    
                    if($stmt->execute()) {
                        $uploaded_pages++;
                    } else {
                        $errors[] = "Página " . ($i + 1) . ": Erro ao salvar no banco";
                        // Remover arquivo se falhou ao salvar no banco
                        if(file_exists($filepath)) {
                            unlink($filepath);
                        }
                    }
                } else {
                    $errors[] = "Página " . ($i + 1) . ": Erro no upload";
                }
            } else {
                $errors[] = "Página " . ($i + 1) . ": Erro no arquivo (Código: " . $files['error'][$i] . ")";
            }
        }
        
        if($uploaded_pages > 0) {
            $message = $uploaded_pages . " página(s) carregada(s) com sucesso!";
            if(!empty($errors)) {
                $message .= " Erros: " . implode(", ", $errors);
            }
            return ['success' => true, 'page_count' => $uploaded_pages, 'message' => $message];
        } else {
            return ['success' => false, 'message' => "Nenhuma página foi carregada. Erros: " . implode(", ", $errors)];
        }
        
    } catch(PDOException $e) {
        error_log("Erro no upload das páginas: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro interno no upload das páginas!'];
    }
}

/**
 * Criar quadrinho no banco de dados
 */
public function createComic($data) {
    $this->conn->beginTransaction();
    
    try {
        // Inserir quadrinho
        $stmt = $this->conn->prepare("
            INSERT INTO comics (title, author_id, description, cover, is_premium, price, page_count, status, is_published, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'published', 1, NOW(), NOW())
        ");
        $stmt->bindParam(1, $data['title']);
        $stmt->bindParam(2, $data['author_id']);
        $stmt->bindParam(3, $data['description']);
        $stmt->bindParam(4, $data['cover']);
        $stmt->bindParam(5, $data['is_premium']);
        $stmt->bindParam(6, $data['price']);
        $stmt->bindParam(7, $data['page_count']);
        
        if(!$stmt->execute()) {
            throw new Exception("Erro ao criar quadrinho: " . $stmt->errorInfo()[2]);
        }
        
        $comic_id = $this->conn->lastInsertId();
        
        // Inserir categorias
        foreach($data['categories'] as $category_id) {
            $stmt = $this->conn->prepare("INSERT INTO comic_categories (comic_id, category_id) VALUES (?, ?)");
            $stmt->bindParam(1, $comic_id);
            $stmt->bindParam(2, $category_id);
            if(!$stmt->execute()) {
                throw new Exception("Erro ao adicionar categorias: " . $stmt->errorInfo()[2]);
            }
        }
        
        $this->conn->commit();
        return ['success' => true, 'comic_id' => $comic_id];
        
    } catch (Exception $e) {
        $this->conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Atualizar contagem de páginas do quadrinho
 */
public function updateComicPageCount($comic_id, $page_count) {
    try {
        $stmt = $this->conn->prepare("UPDATE comics SET page_count = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bindParam(1, $page_count);
        $stmt->bindParam(2, $comic_id);
        return $stmt->execute();
        
    } catch(PDOException $e) {
        error_log("Erro ao atualizar contagem de páginas: " . $e->getMessage());
        return false;
    }
}

/**
 * Obter quadrinhos do usuário
 */
public function getUserComics($user_id, $limit = 20) {
    try {
        $query = "SELECT c.*, 
                         COUNT(DISTINCT cp.id) as actual_page_count,
                         COUNT(DISTINCT f.id) as favorites_count,
                         COUNT(DISTINCT r.id) as reviews_count,
                         COALESCE(AVG(r.rating), 0) as avg_rating
                  FROM comics c
                  LEFT JOIN comic_pages cp ON c.id = cp.comic_id
                  LEFT JOIN favorites f ON c.id = f.comic_id
                  LEFT JOIN reviews r ON c.id = r.comic_id
                  WHERE c.author_id = :user_id
                  GROUP BY c.id
                  ORDER BY c.created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Erro ao obter quadrinhos do usuário: " . $e->getMessage());
        return [];
    }
}

/**
 * Obter dados do quadrinho
 */
public function getComicData($comic_id) {
    try {
        $query = "SELECT c.*, u.username as author_name, u.avatar as author_avatar,
                         COUNT(DISTINCT cp.id) as actual_page_count,
                         COUNT(DISTINCT f.id) as favorites_count,
                         COUNT(DISTINCT r.id) as reviews_count,
                         COALESCE(AVG(r.rating), 0) as avg_rating
                  FROM comics c
                  JOIN users u ON c.author_id = u.id
                  LEFT JOIN comic_pages cp ON c.id = cp.comic_id
                  LEFT JOIN favorites f ON c.id = f.comic_id
                  LEFT JOIN reviews r ON c.id = r.comic_id
                  WHERE c.id = :comic_id
                  GROUP BY c.id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":comic_id", $comic_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Erro ao obter dados do quadrinho: " . $e->getMessage());
        return false;
    }
}

/**
 * Obter páginas do quadrinho
 */
public function getComicPages($comic_id) {
    try {
        $query = "SELECT * FROM comic_pages WHERE comic_id = :comic_id ORDER BY page_number";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":comic_id", $comic_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Erro ao obter páginas do quadrinho: " . $e->getMessage());
        return [];
    }
}

/**
 * Obter categorias do quadrinho
 */
public function getComicCategories($comic_id) {
    try {
        $query = "SELECT c.id, c.name 
                  FROM categories c
                  JOIN comic_categories cc ON c.id = cc.category_id
                  WHERE cc.comic_id = :comic_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":comic_id", $comic_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Erro ao obter categorias do quadrinho: " . $e->getMessage());
        return [];
    }
}

/**
 * Deletar quadrinho
 */
public function deleteComic($comic_id, $user_id) {
    $this->conn->beginTransaction();
    
    try {
        // Verificar se o usuário é o autor
        $check_stmt = $this->conn->prepare("SELECT author_id FROM comics WHERE id = ?");
        $check_stmt->bindParam(1, $comic_id);
        $check_stmt->execute();
        
        $comic = $check_stmt->fetch(PDO::FETCH_ASSOC);
        if(!$comic || $comic['author_id'] != $user_id) {
            throw new Exception("Você não tem permissão para excluir este quadrinho!");
        }
        
        // Obter caminhos dos arquivos para exclusão
        $pages_stmt = $this->conn->prepare("SELECT image_url FROM comic_pages WHERE comic_id = ?");
        $pages_stmt->bindParam(1, $comic_id);
        $pages_stmt->execute();
        $pages = $pages_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obter capa
        $cover_stmt = $this->conn->prepare("SELECT cover FROM comics WHERE id = ?");
        $cover_stmt->bindParam(1, $comic_id);
        $cover_stmt->execute();
        $cover = $cover_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Deletar registros relacionados
        $tables = [
            'comic_categories' => 'comic_id',
            'comic_pages' => 'comic_id',
            'favorites' => 'comic_id',
            'reviews' => 'comic_id',
            'reading_progress' => 'comic_id',
            'comic_comments' => 'comic_id'
        ];
        
        foreach($tables as $table => $column) {
            $delete_stmt = $this->conn->prepare("DELETE FROM $table WHERE $column = ?");
            $delete_stmt->bindParam(1, $comic_id);
            $delete_stmt->execute();
        }
        
        // Deletar quadrinho
        $delete_comic_stmt = $this->conn->prepare("DELETE FROM comics WHERE id = ?");
        $delete_comic_stmt->bindParam(1, $comic_id);
        $delete_comic_stmt->execute();
        
        // Deletar arquivos físicos
        if($cover && $cover['cover'] && file_exists($cover['cover'])) {
            unlink($cover['cover']);
        }
        
        foreach($pages as $page) {
            if($page['image_url'] && file_exists($page['image_url'])) {
                unlink($page['image_url']);
            }
        }
        
        $this->conn->commit();
        return ['success' => true, 'message' => 'Quadrinho excluído com sucesso!'];
        
    } catch (Exception $e) {
        $this->conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}


} // FIM DA CLASSE Auth

$auth = new Auth();
?>
