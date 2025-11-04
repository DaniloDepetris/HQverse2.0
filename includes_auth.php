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

    public function register($username, $email, $password) {
        try {
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
                return true;
            }
            return "Erro ao cadastrar usuário!";

        } catch(PDOException $exception) {
            return "Erro: " . $exception->getMessage();
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
}

$auth = new Auth();
?>