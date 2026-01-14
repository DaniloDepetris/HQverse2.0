<?php
require_once 'includes_auth.php';
header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não logado']);
    exit();
}

$comic_id = $_GET['comic_id'] ?? 0;

if (!$comic_id) {
    echo json_encode(['success' => false, 'message' => 'ID do quadrinho não especificado']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Buscar dados do quadrinho
    $query = "SELECT id, title, cover, description, page_count FROM comics WHERE id = :comic_id AND status = 'published'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':comic_id', $comic_id);
    $stmt->execute();
    
    $comic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$comic) {
        echo json_encode(['success' => false, 'message' => 'Quadrinho não encontrado']);
        exit();
    }
    
    // Buscar páginas do quadrinho
    $pagesQuery = "SELECT page_number, image_url, title FROM comic_pages WHERE comic_id = :comic_id ORDER BY page_number";
    $pagesStmt = $conn->prepare($pagesQuery);
    $pagesStmt->bindParam(':comic_id', $comic_id);
    $pagesStmt->execute();
    
    $pages = $pagesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Se não houver páginas específicas, criar páginas padrão
    if (empty($pages)) {
        $pages = [
            ['page_number' => 1, 'image_url' => $comic['cover'], 'title' => 'Capa'],
            ['page_number' => 2, 'image_url' => generatePlaceholder($comic['title'], 1), 'title' => 'Página 1'],
            ['page_number' => 3, 'image_url' => generatePlaceholder($comic['title'], 2), 'title' => 'Página 2'],
            ['page_number' => 4, 'image_url' => generatePlaceholder($comic['title'], 3), 'title' => 'Página 3'],
            ['page_number' => 5, 'image_url' => generatePlaceholder($comic['title'], 4), 'title' => 'Página 4']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'comic' => $comic,
        'pages' => $pages
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar páginas do quadrinho: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar quadrinho'
    ]);
}

// Função para gerar placeholders mais específicos
function generatePlaceholder($title, $pageNumber) {
    $shortTitle = urlencode(substr($title, 0, 15));
    return "https://via.placeholder.com/700x1000/1a1a2e/e94560?text={$shortTitle}+Página+{$pageNumber}";
}
?>
