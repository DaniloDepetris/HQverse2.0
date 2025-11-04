<?php
require_once 'includes_auth.php';

if(!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header("Location: comics.php");
    exit();
}

$success = '';
$error = '';

// Processar formulário de adicionar quadrinho
if($_POST && isset($_POST['add_comic'])) {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $cover_url = $_POST['cover_url'] ?? '';
    $price = $_POST['price'] ?? 0;
    $page_count = $_POST['page_count'] ?? 0;
    $publisher_id = $_POST['publisher_id'] ?? 1;
    $categories = $_POST['categories'] ?? [];

    if(!empty($title) && !empty($description)) {
        $result = addComic($title, $description, $cover_url, $price, $page_count, $publisher_id, $categories);
        if($result) {
            $success = "Quadrinho adicionado com sucesso!";
        } else {
            $error = "Erro ao adicionar quadrinho!";
        }
    } else {
        $error = "Preencha todos os campos obrigatórios!";
    }
}

// Função para adicionar quadrinho
function addComic($title, $description, $cover_url, $price, $page_count, $publisher_id, $categories) {
    require_once 'config_database.php';
    $database = new Database();
    $conn = $database->getConnection();

    try {
        // Inserir o quadrinho
        $query = "INSERT INTO comics (title, author_id, publisher_id, cover, description, price, page_count, is_published, status) 
                  VALUES (:title, :author_id, :publisher_id, :cover, :description, :price, :page_count, TRUE, 'published')";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":author_id", $_SESSION['user_id']);
        $stmt->bindParam(":publisher_id", $publisher_id);
        $stmt->bindParam(":cover", $cover_url);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":price", $price);
        $stmt->bindParam(":page_count", $page_count);
        
        if($stmt->execute()) {
            $comic_id = $conn->lastInsertId();
            
            // Adicionar categorias
            if(!empty($categories)) {
                foreach($categories as $category_id) {
                    $cat_query = "INSERT INTO comic_categories (comic_id, category_id) VALUES (:comic_id, :category_id)";
                    $cat_stmt = $conn->prepare($cat_query);
                    $cat_stmt->bindParam(":comic_id", $comic_id);
                    $cat_stmt->bindParam(":category_id", $category_id);
                    $cat_stmt->execute();
                }
            }
            
            return true;
        }
    } catch(PDOException $e) {
        error_log("Erro ao adicionar quadrinho: " . $e->getMessage());
    }
    
    return false;
}

// Buscar dados para os selects
function getPublishers() {
    require_once 'config_database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        $stmt = $conn->query("SELECT id, name FROM publishers ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getCategories() {
    require_once 'config_database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        $stmt = $conn->query("SELECT id, name FROM categories ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function getComics() {
    require_once 'config_database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        $query = "SELECT c.*, u.username as author_name, 
                         GROUP_CONCAT(cat.name SEPARATOR ', ') as categories
                  FROM comics c 
                  LEFT JOIN users u ON c.author_id = u.id 
                  LEFT JOIN comic_categories cc ON c.id = cc.comic_id 
                  LEFT JOIN categories cat ON cc.category_id = cat.id 
                  GROUP BY c.id 
                  ORDER BY c.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

$publishers = getPublishers();
$categories = getCategories();
$comics = getComics();
$users = $auth->getAllUsers();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - HQ Verso</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            min-height: 100vh;
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e94560;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #e94560;
            text-decoration: none;
        }
        
        .admin-nav {
            display: flex;
            gap: 20px;
        }
        
        .nav-btn {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .nav-btn:hover, .nav-btn.active {
            background: #e94560;
        }
        
        .admin-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .form-section, .list-section {
            background: rgba(255, 255, 255, 0.05);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #e94560;
            border-bottom: 2px solid #e94560;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 16px;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .form-group select[multiple] {
            height: 120px;
        }
        
        .checkbox-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #e94560;
            color: white;
        }
        
        .btn-primary:hover {
            background: #d8345f;
            transform: translateY(-2px);
        }
        
        .comics-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .comic-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            border-left: 4px solid #e94560;
        }
        
        .comic-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #e94560;
        }
        
        .comic-meta {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 5px;
        }
        
        .comic-categories {
            font-size: 0.8rem;
            opacity: 0.6;
        }
        
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-top: 4px solid #e94560;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #e94560;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid #4caf50;
            color: #4caf50;
        }
        
        .alert-error {
            background: rgba(233, 69, 96, 0.2);
            border: 1px solid #e94560;
            color: #e94560;
        }
        
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: #e94560;
        }
        
        @media (max-width: 768px) {
            .admin-content {
                grid-template-columns: 1fr;
            }
            
            .checkbox-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <a href="comics.php" class="logo">HQ VERSO - ADMIN</a>
            <div class="admin-nav">
                <a href="comics.php" class="nav-btn">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
                <a href="logout.php" class="nav-btn">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($comics); ?></div>
                <div class="stat-label">Quadrinhos Cadastrados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($users); ?></div>
                <div class="stat-label">Usuários Registrados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($categories); ?></div>
                <div class="stat-label">Categorias</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($publishers); ?></div>
                <div class="stat-label">Editoras</div>
            </div>
        </div>
        
        <div class="admin-content">
            <div class="form-section">
                <h2 class="section-title">Adicionar Novo Quadrinho</h2>
                <form method="POST">
                    <input type="hidden" name="add_comic" value="1">
                    
                    <div class="form-group">
                        <label for="title">Título do Quadrinho *</label>
                        <input type="text" id="title" name="title" required placeholder="Ex: Batman: O Cavaleiro das Trevas">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Descrição *</label>
                        <textarea id="description" name="description" required placeholder="Descrição do quadrinho..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="cover_url">URL da Capa</label>
                        <input type="url" id="cover_url" name="cover_url" placeholder="https://exemplo.com/capa.jpg">
                    </div>
                    
                    <div class="form-group">
                        <label for="publisher_id">Editora</label>
                        <select id="publisher_id" name="publisher_id">
                            <?php foreach($publishers as $publisher): ?>
                                <option value="<?php echo $publisher['id']; ?>"><?php echo htmlspecialchars($publisher['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Preço (R$)</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="29.90">
                    </div>
                    
                    <div class="form-group">
                        <label for="page_count">Número de Páginas</label>
                        <input type="number" id="page_count" name="page_count" min="1" value="100">
                    </div>
                    
                    <div class="form-group">
                        <label>Categorias</label>
                        <div class="checkbox-group">
                            <?php foreach($categories as $category): ?>
                                <div class="checkbox-item">
                                    <input type="checkbox" id="cat_<?php echo $category['id']; ?>" 
                                           name="categories[]" value="<?php echo $category['id']; ?>">
                                    <label for="cat_<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Adicionar Quadrinho
                    </button>
                </form>
            </div>
            
            <div class="list-section">
                <h2 class="section-title">Quadrinhos Cadastrados</h2>
                <div class="comics-list">
                    <?php if(empty($comics)): ?>
                        <p style="text-align: center; opacity: 0.7;">Nenhum quadrinho cadastrado ainda.</p>
                    <?php else: ?>
                        <?php foreach($comics as $comic): ?>
                            <div class="comic-item">
                                <div class="comic-title"><?php echo htmlspecialchars($comic['title']); ?></div>
                                <div class="comic-meta">
                                    por <?php echo htmlspecialchars($comic['author_name']); ?> | 
                                    R$ <?php echo number_format($comic['price'], 2, ',', '.'); ?> | 
                                    <?php echo $comic['page_count']; ?> páginas
                                </div>
                                <div class="comic-categories">
                                    Categorias: <?php echo $comic['categories'] ? htmlspecialchars($comic['categories']) : 'Nenhuma'; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <a href="comics.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Voltar para a Loja
        </a>
    </div>

    <script>
        // Preview da capa
        document.getElementById('cover_url').addEventListener('input', function(e) {
            const url = e.target.value;
            if(url) {
                // Poderia adicionar preview da imagem aqui
                console.log('URL da capa:', url);
            }
        });
        
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            
            if(!title || !description) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios!');
                return false;
            }
        });
        
        console.log('Painel Admin carregado com sucesso!');
    </script>
</body>
</html>