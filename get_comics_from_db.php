<?php
require_once 'includes_auth.php';
header('Content-Type: application/json');

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Não logado']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Buscar quadrinhos do banco com suas categorias
    $query = "
        SELECT 
            c.id,
            c.title,
            c.cover,
            c.description,
            c.page_count as meta,
            GROUP_CONCAT(DISTINCT cat.id) as category_ids,
            GROUP_CONCAT(DISTINCT cat.name) as category_names,
            u.username as author
        FROM comics c
        LEFT JOIN users u ON c.author_id = u.id
        LEFT JOIN comic_categories cc ON c.id = cc.comic_id
        LEFT JOIN categories cat ON cc.category_id = cat.id
        WHERE c.status = 'published'
        GROUP BY c.id
        ORDER BY c.created_at DESC
        LIMIT 50
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $comics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Processar os dados para o formato esperado pelo frontend
    $processedComics = array_map(function($comic) {
        // Processar categorias
        $categories = [];
        if ($comic['category_names']) {
            $categoryNames = explode(',', $comic['category_names']);
            // Mapear nomes para os slugs usados no frontend
            $categoryMap = [
                'Super-heróis' => 'super-herois',
                'Mangá' => 'manga',
                'Graphic Novels' => 'graphic-novels',
                'Clássicos' => 'classicos',
                'Indie' => 'indie'
            ];
            
            foreach ($categoryNames as $name) {
                $slug = $categoryMap[$name] ?? strtolower($name);
                if ($slug) {
                    $categories[] = $slug;
                }
            }
        }
        
        // Garantir que sempre tenha pelo menos a categoria 'all'
        if (empty($categories)) {
            $categories = ['all'];
        } else {
            $categories[] = 'all';
        }
        
        return [
            'id' => (int)$comic['id'],
            'title' => $comic['title'],
            'cover' => $comic['cover'] ?: 'https://via.placeholder.com/200x300/1a1a2e/e94560?text=' . urlencode(substr($comic['title'], 0, 15)),
            'meta' => $comic['meta'] ? 'Páginas: ' . $comic['meta'] : 'Quadrinho',
            'description' => $comic['description'] ?: 'Descrição não disponível.',
            'categories' => array_unique($categories),
            'progress' => 0, // Será preenchido pelo progresso
            'author' => $comic['author'] ?: 'Autor desconhecido'
        ];
    }, $comics);
    
    echo json_encode([
        'success' => true,
        'comics' => $processedComics
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar quadrinhos: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar quadrinhos: ' . $e->getMessage()
    ]);
}
?>
