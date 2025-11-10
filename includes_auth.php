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

    // NOVA FUNÇÃO: Buscar usuários com informações de follow
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
} // FIM DA CLASSE Auth

$auth = new Auth();
?>
