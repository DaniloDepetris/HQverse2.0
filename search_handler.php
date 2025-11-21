<?php
require_once 'includes_auth.php';

header('Content-Type: application/json');

if(!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit();
}

if(isset($_GET['q']) && !empty($_GET['q'])) {
    $searchTerm = $_GET['q'];
    $results = performSearch($searchTerm);
    echo json_encode(['success' => true, 'data' => $results]);
} else {
    echo json_encode(['success' => false, 'message' => 'Termo de busca vazio']);
}

function performSearch($searchTerm) {
    global $auth;
    
    $results = [
        'comics' => searchComics($searchTerm),
        'users' => searchUsers($searchTerm)
    ];
    
    return $results;
}

function searchComics($searchTerm) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "
            SELECT 
                c.id,
                c.title,
                c.cover,
                c.description,
                c.price,
                c.status,
                c.views,
                u.username as author_name,
                p.name as publisher_name,
                COUNT(DISTINCT r.id) as review_count,
                COALESCE(AVG(r.rating), 0) as avg_rating
            FROM comics c
            LEFT JOIN users u ON c.author_id = u.id
            LEFT JOIN publishers p ON c.publisher_id = p.id
            LEFT JOIN reviews r ON c.id = r.comic_id
            WHERE (c.title LIKE :search 
               OR c.description LIKE :search
               OR u.username LIKE :search
               OR p.name LIKE :search)
               AND c.status = 'published'
            GROUP BY c.id
            ORDER BY 
                CASE 
                    WHEN c.title LIKE :search_exact THEN 1
                    WHEN u.username LIKE :search_exact THEN 2
                    ELSE 3
                END,
                c.views DESC
            LIMIT 20
        ";
        
        $stmt = $conn->prepare($query);
        $search = "%$searchTerm%";
        $search_exact = "$searchTerm%";
        $stmt->bindParam(":search", $search);
        $stmt->bindParam(":search_exact", $search_exact);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Erro na busca de quadrinhos: " . $e->getMessage());
        return [];
    }
}

function searchUsers($searchTerm) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "
            SELECT 
                u.id,
                u.username,
                u.email,
                u.role,
                u.avatar,
                u.created_at,
                COUNT(DISTINCT c.id) as comics_count,
                COUNT(DISTINCT f.id) as favorites_count,
                COUNT(DISTINCT r.id) as reviews_count
            FROM users u
            LEFT JOIN comics c ON u.id = c.author_id
            LEFT JOIN favorites f ON u.id = f.user_id
            LEFT JOIN reviews r ON u.id = r.user_id
            WHERE u.username LIKE :search 
               OR u.email LIKE :search
            GROUP BY u.id
            ORDER BY 
                CASE 
                    WHEN u.username LIKE :search_exact THEN 1
                    WHEN u.email LIKE :search_exact THEN 2
                    ELSE 3
                END,
                u.created_at DESC
            LIMIT 20
        ";
        
        $stmt = $conn->prepare($query);
        $search = "%$searchTerm%";
        $search_exact = "$searchTerm%";
        $stmt->bindParam(":search", $search);
        $stmt->bindParam(":search_exact", $search_exact);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Erro na busca de usuários: " . $e->getMessage());
        return [];
    }
}
?>
