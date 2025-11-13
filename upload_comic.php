<?php
require_once 'includes_auth.php';

if(!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Verificar se o usuário é criador
if(!$auth->isCreator($_SESSION['user_id'])) {
    header("Location: perfil.php");
    exit();
}

$success = '';
$error = '';

// Processar upload do quadrinho
if($_POST && isset($_POST['upload_comic'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categories = $_POST['categories'] ?? [];
    $is_premium = isset($_POST['is_premium']) ? 1 : 0;
    $price = $is_premium ? floatval($_POST['price'] ?? 0) : 0;
    
    // Validar dados
    if(empty($title)) {
        $error = "O título é obrigatório!";
    } elseif(empty($description)) {
        $error = "A descrição é obrigatória!";
    } elseif(empty($categories)) {
        $error = "Selecione pelo menos uma categoria!";
    } elseif($is_premium && $price <= 0) {
        $error = "Para quadrinhos premium, o preço deve ser maior que zero!";
    } else {
        // Processar upload da capa
        $cover_path = null;
        if(isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
            $cover_result = $auth->uploadComicCover($_FILES['cover'], $_SESSION['user_id']);
            if($cover_result['success']) {
                $cover_path = $cover_result['path'];
            } else {
                $error = $cover_result['message'];
            }
        }
        
        if(!$error) {
            // Criar o quadrinho
            $result = $auth->createComic([
                'title' => $title,
                'author_id' => $_SESSION['user_id'],
                'description' => $description,
                'cover' => $cover_path,
                'is_premium' => $is_premium,
                'price' => $price,
                'categories' => $categories,
                'page_count' => 0 // Será atualizado quando páginas forem adicionadas
            ]);
            
            if($result['success']) {
                $comic_id = $result['comic_id'];
                $success = "Quadrinho criado com sucesso! ID: " . $comic_id;
                
                // Processar upload das páginas
                if(isset($_FILES['pages']) && !empty($_FILES['pages']['name'][0])) {
                    $pages_result = $auth->uploadComicPages($_FILES['pages'], $comic_id, $_SESSION['user_id']);
                    if(!$pages_result['success']) {
                        $error .= " " . $pages_result['message'];
                    } else {
                        // Atualizar contagem de páginas
                        $auth->updateComicPageCount($comic_id, $pages_result['page_count']);
                        $success .= " " . $pages_result['message'];
                    }
                }
                
                // Redirecionar para o quadrinho se foi bem sucedido
                if(empty($error)) {
                    header("Location: comic.php?id=" . $comic_id);
                    exit();
                }
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Obter categorias disponíveis
$categories = $auth->getAllCategories();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicar Quadrinho - HQ Verso</title>
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
        }
        
        body { 
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%); 
            color: var(--text-primary); 
            min-height: 100vh; 
            padding: 20px; 
            line-height: 1.5;
        }
        
        .container { 
            max-width: 800px; 
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

        .upload-card {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
        }

        .upload-title {
            color: var(--accent-color);
            font-size: 2rem;
            margin-bottom: 10px;
            text-align: center;
        }

        .upload-subtitle {
            text-align: center;
            margin-bottom: 30px;
            opacity: 0.8;
        }

        .form-group { 
            margin-bottom: 25px; 
        }
        
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600;
            color: var(--accent-color);
        }
        
        .form-group input, 
        .form-group textarea,
        .form-group select { 
            width: 100%; 
            padding: 12px 15px; 
            border: 1px solid var(--border-color); 
            border-radius: 8px; 
            background: rgba(255, 255, 255, 0.08); 
            color: var(--text-primary); 
            font-size: 1rem; 
            transition: all 0.3s ease;
        }

        [data-theme="light"] .form-group input,
        [data-theme="light"] .form-group textarea,
        [data-theme="light"] .form-group select {
            background: rgba(0, 0, 0, 0.05);
        }
        
        .form-group input:focus, 
        .form-group textarea:focus,
        .form-group select:focus { 
            outline: none; 
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(233, 69, 96, 0.2);
        }
        
        .form-group textarea { 
            height: 120px; 
            resize: vertical; 
        }

        .form-group select[multiple] {
            height: 150px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .price-group {
            display: none;
            margin-top: 15px;
        }

        .price-group.active {
            display: block;
        }

        .file-upload {
            border: 2px dashed var(--border-color);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }

        .file-upload:hover {
            border-color: var(--accent-color);
            background: rgba(233, 69, 96, 0.05);
        }

        .file-upload i {
            font-size: 3rem;
            color: var(--accent-color);
            margin-bottom: 15px;
        }

        .file-preview {
            margin-top: 20px;
            display: none;
        }

        .file-preview.active {
            display: block;
        }

        .preview-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .preview-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }

        .btn { 
            padding: 15px 30px; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer; 
            border: none; 
            font-size: 1rem; 
            text-decoration: none; 
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, var(--accent-color) 0%, var(--accent-hover) 100%);
            color: white; 
            box-shadow: 0 4px 15px rgba(233, 69, 96, 0.3);
        }
        
        .btn-primary:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(233, 69, 96, 0.4);
        }

        .alert { 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 10px; 
            text-align: center; 
            font-weight: 500;
            border-left: 4px solid;
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

        .upload-tips {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }

        .upload-tips h3 {
            color: var(--accent-color);
            margin-bottom: 15px;
        }

        .tip-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .tip-item i {
            color: var(--accent-color);
            width: 20px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .upload-card {
                padding: 20px;
            }
            
            .upload-title {
                font-size: 1.5rem;
            }
            
            header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
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
        
        <div class="upload-card">
            <h1 class="upload-title">
                <i class="fas fa-upload"></i> Publicar Quadrinho
            </h1>
            <p class="upload-subtitle">Compartilhe sua história com o mundo!</p>
            
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="upload_comic" value="1">
                
                <div class="form-group">
                    <label for="title">Título do Quadrinho *</label>
                    <input type="text" id="title" name="title" 
                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                           required
                           placeholder="Ex: As Aventuras do Super-Herói">
                </div>
                
                <div class="form-group">
                    <label for="description">Descrição *</label>
                    <textarea id="description" name="description" 
                              required
                              placeholder="Descreva a história do seu quadrinho..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="categories">Categorias *</label>
                    <select id="categories" name="categories[]" multiple required>
                        <?php foreach($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                <?php echo (isset($_POST['categories']) && in_array($category['id'], $_POST['categories'])) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: var(--text-secondary); font-size: 0.8rem;">
                        Segure Ctrl (ou Cmd no Mac) para selecionar múltiplas categorias
                    </small>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="is_premium" name="is_premium" value="1"
                           <?php echo (isset($_POST['is_premium']) && $_POST['is_premium']) ? 'checked' : ''; ?>>
                    <label for="is_premium" style="margin: 0;">Quadrinho Premium (Pago)</label>
                </div>
                
                <div class="form-group price-group <?php echo (isset($_POST['is_premium']) && $_POST['is_premium']) ? 'active' : ''; ?>" id="priceGroup">
                    <label for="price">Preço (R$) *</label>
                    <input type="number" id="price" name="price" 
                           value="<?php echo htmlspecialchars($_POST['price'] ?? '0.00'); ?>" 
                           min="0" step="0.01"
                           placeholder="0.00">
                </div>
                
                <div class="form-group">
                    <label for="cover">Capa do Quadrinho *</label>
                    <div class="file-upload" onclick="document.getElementById('cover').click()">
                        <i class="fas fa-image"></i>
                        <h3>Clique para selecionar a capa</h3>
                        <p>Formatos: JPG, PNG, GIF, WebP (Máx. 5MB)</p>
                        <input type="file" id="cover" name="cover" accept="image/*" style="display: none;" required>
                    </div>
                    <div class="file-preview" id="coverPreview"></div>
                </div>
                
                <div class="form-group">
                    <label for="pages">Páginas do Quadrinho *</label>
                    <div class="file-upload" onclick="document.getElementById('pages').click()">
                        <i class="fas fa-file-image"></i>
                        <h3>Clique para selecionar as páginas</h3>
                        <p>Selecione múltiplas imagens. Formatos: JPG, PNG, GIF, WebP (Máx. 5MB cada)</p>
                        <input type="file" id="pages" name="pages[]" accept="image/*" multiple style="display: none;" required>
                    </div>
                    <div class="file-preview" id="pagesPreview"></div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-rocket"></i> Publicar Quadrinho
                </button>
            </form>
            
            <div class="upload-tips">
                <h3><i class="fas fa-lightbulb"></i> Dicas para uma boa publicação:</h3>
                <div class="tip-item">
                    <i class="fas fa-check"></i>
                    <span>Use imagens de alta qualidade (recomendado: 700x1000 pixels)</span>
                </div>
                <div class="tip-item">
                    <i class="fas fa-check"></i>
                    <span>Mantenha o tamanho das páginas consistente</span>
                </div>
                <div class="tip-item">
                    <i class="fas fa-check"></i>
                    <span>Escreva uma descrição atraente para chamar leitores</span>
                </div>
                <div class="tip-item">
                    <i class="fas fa-check"></i>
                    <span>Escolha categorias relevantes para sua história</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Alternar visibilidade do campo de preço
        document.getElementById('is_premium').addEventListener('change', function() {
            const priceGroup = document.getElementById('priceGroup');
            const priceInput = document.getElementById('price');
            
            if(this.checked) {
                priceGroup.classList.add('active');
                priceInput.required = true;
            } else {
                priceGroup.classList.remove('active');
                priceInput.required = false;
                priceInput.value = '0.00';
            }
        });

        // Preview da capa
        document.getElementById('cover').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('coverPreview');
            
            if(file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <div class="preview-item">
                            <img src="${e.target.result}" alt="Preview da capa">
                            <div>
                                <strong>${file.name}</strong>
                                <div>${(file.size / 1024 / 1024).toFixed(2)} MB</div>
                            </div>
                        </div>
                    `;
                    preview.classList.add('active');
                };
                reader.readAsDataURL(file);
            } else {
                preview.classList.remove('active');
                preview.innerHTML = '';
            }
        });

        // Preview das páginas
        document.getElementById('pages').addEventListener('change', function(e) {
            const files = e.target.files;
            const preview = document.getElementById('pagesPreview');
            
            if(files.length > 0) {
                let html = `<h4>${files.length} página(s) selecionada(s):</h4>`;
                
                for(let i = 0; i < Math.min(files.length, 5); i++) {
                    const file = files[i];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'preview-item';
                        previewItem.innerHTML = `
                            <img src="${e.target.result}" alt="Preview da página">
                            <div>
                                <strong>${file.name}</strong>
                                <div>${(file.size / 1024 / 1024).toFixed(2)} MB</div>
                            </div>
                        `;
                        preview.appendChild(previewItem);
                    };
                    
                    reader.readAsDataURL(file);
                }
                
                if(files.length > 5) {
                    html += `<p>+ ${files.length - 5} outra(s) página(s)</p>`;
                }
                
                preview.classList.add('active');
            } else {
                preview.classList.remove('active');
                preview.innerHTML = '';
            }
        });

        // Validação do formulário
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            const categories = document.getElementById('categories');
            const cover = document.getElementById('cover').files[0];
            const pages = document.getElementById('pages').files;
            const isPremium = document.getElementById('is_premium').checked;
            const price = document.getElementById('price').value;
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Validações básicas
            if(!title) {
                e.preventDefault();
                alert('Por favor, insira um título para o quadrinho!');
                return false;
            }
            
            if(!description) {
                e.preventDefault();
                alert('Por favor, insira uma descrição para o quadrinho!');
                return false;
            }
            
            let selectedCategories = 0;
            for(let i = 0; i < categories.options.length; i++) {
                if(categories.options[i].selected) selectedCategories++;
            }
            
            if(selectedCategories === 0) {
                e.preventDefault();
                alert('Por favor, selecione pelo menos uma categoria!');
                return false;
            }
            
            if(isPremium && (!price || parseFloat(price) <= 0)) {
                e.preventDefault();
                alert('Para quadrinhos premium, o preço deve ser maior que zero!');
                return false;
            }
            
            if(!cover) {
                e.preventDefault();
                alert('Por favor, selecione uma imagem para a capa!');
                return false;
            }
            
            if(pages.length === 0) {
                e.preventDefault();
                alert('Por favor, selecione pelo menos uma página!');
                return false;
            }
            
            // Validar tamanho máximo dos arquivos (5MB)
            const maxSize = 5 * 1024 * 1024;
            
            if(cover.size > maxSize) {
                e.preventDefault();
                alert('A imagem da capa é muito grande! O tamanho máximo é 5MB.');
                return false;
            }
            
            for(let i = 0; i < pages.length; i++) {
                if(pages[i].size > maxSize) {
                    e.preventDefault();
                    alert(`A página "${pages[i].name}" é muito grande! O tamanho máximo é 5MB por arquivo.`);
                    return false;
                }
            }
            
            // Mostrar loading
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publicando...';
            submitBtn.disabled = true;
        });

        // Arrastar e soltar arquivos
        document.querySelectorAll('.file-upload').forEach(uploadArea => {
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--accent-color)';
                this.style.background = 'rgba(233, 69, 96, 0.1)';
            });
            
            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--border-color)';
                this.style.background = '';
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--border-color)';
                this.style.background = '';
                
                const files = e.dataTransfer.files;
                if(files.length > 0) {
                    // Encontrar o input de arquivo correspondente
                    const input = this.parentElement.querySelector('input[type="file"]');
                    if(input) {
                        input.files = files;
                        
                        // Disparar evento change para atualizar preview
                        const event = new Event('change');
                        input.dispatchEvent(event);
                    }
                }
            });
        });
    </script>
</body>
</html>
