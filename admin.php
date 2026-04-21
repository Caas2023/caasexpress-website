<?php
require_once __DIR__ . '/src/Config/Database.php';
require_once __DIR__ . '/src/Components/Logo.php';
use Src\Config\Database;
$pdo = Database::getInstance();

// Carregar usuários e categorias para os seletores
$usersStmt = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM posts WHERE author_id = u.id) as post_count FROM users u ORDER BY u.id");
$allUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

$catsStmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$allCats = $catsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo | Caas Express</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Quill Editor (CDN) -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

    <!-- Styles -->
    <link rel="stylesheet" href="admin.css">
</head>

<body>

    <!-- Login Screen -->
    <div id="login-screen" class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="logo-icon" style="background:transparent;">
                    <?php echo \Src\Components\Logo::render('symbol', 60, 60); ?>
                </div>
                <h2>Caas Express Admin</h2>
                <p>Entre para gerenciar o conteúdo</p>
            </div>
            <form id="login-form">
                <div class="form-group">
                    <label>Usuário</label>
                    <input type="text" id="username" placeholder="admin" required>
                </div>
                <div class="form-group">
                    <label>Senha</label>
                    <input type="password" id="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-primary btn-block">Entrar</button>
            </form>
            <div id="login-error" class="error-message" style="display: none;">Credenciais inválidas</div>
        </div>
    </div>

    <!-- Dashboard Screen -->
    <div id="dashboard-screen" class="dashboard-container" style="display: none;">

        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header" style="padding: 1rem;">
                <?php echo \Src\Components\Logo::render('symbol', 40, 40); ?>
                <span style="font-family: 'Outfit', sans-serif; font-weight: 800; color: white;">Caas Admin</span>
            </div>

            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" data-page="dashboard">
                    <span class="icon">🏠</span> Painel
                </a>
                <a href="#" class="nav-item" data-page="posts">
                    <span class="icon">📌</span> Posts
                </a>
                <a href="#" class="nav-item" data-page="media">
                    <span class="icon">📷</span> Mídia
                </a>
                <a href="#" class="nav-item" data-page="pages">
                    <span class="icon">📄</span> Páginas
                </a>
                <a href="#" class="nav-item" data-page="comments">
                    <span class="icon">💬</span> Comentários
                </a>
                <div class="nav-divider"></div>
                <a href="#" class="nav-item" data-page="appearance">
                    <span class="icon">🎨</span> Aparência
                </a>
                <a href="#" class="nav-item" data-page="plugins">
                    <span class="icon">🔌</span> Plugins
                </a>
                <a href="#" class="nav-item" data-page="users">
                    <span class="icon">👥</span> Usuários
                </a>
                <a href="#" class="nav-item" data-page="tools">
                    <span class="icon">🛠️</span> Ferramentas
                </a>
                <a href="antigravity-kit/backlinks-map.php" class="nav-item" target="_blank">
                    <span class="icon">🔗</span> Mapa de Backlinks
                </a>
                <a href="antigravity-kit/auto-seo.php" class="nav-item" target="_blank">
                    <span class="icon">🤖</span> Auto-SEO
                </a>
                <a href="antigravity-kit/auto-interlink.php" class="nav-item" target="_blank">
                    <span class="icon">🔀</span> Auto-Interlink
                </a>
                <a href="antigravity-kit/duplicate-titles.php" class="nav-item" target="_blank">
                    <span class="icon">📝</span> Títulos Duplicados
                </a>
                <a href="antigravity-kit/ai-settings.php" class="nav-item" target="_blank">
                    <span class="icon">⚙️</span> Configuração IA
                </a>
                <a href="antigravity-kit/local-seo.php" class="nav-item" target="_blank">
                    <span class="icon">📍</span> SEO Local (Schema)
                </a>
                <a href="#" class="nav-item" data-page="settings">
                    <span class="icon">⚙️</span> Configurações
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="index.php" target="_blank" class="nav-item">
                    <span class="icon">👁️</span> Ver Site
                </a>
                <a href="#" id="logout-btn" class="nav-item">
                    <span class="icon">🚪</span> Sair
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">

            <!-- VIEW: DASHBOARD -->
            <div id="view-dashboard" class="view-section">
                <div class="page-header">
                    <h1>Painel</h1>
                </div>
                <div class="dashboard-widgets">
                    <div class="widget-card">
                        <h3>Agora</h3>
                        <ul class="stats-list">
                            <li><strong id="dashboard-posts-count">-</strong> Posts</li>
                            <li><strong id="dashboard-pages-count">-</strong> Páginas</li>
                            <li><strong id="dashboard-comments-count">-</strong> Comentários</li>
                        </ul>
                        <p class="sub-text">WordPress 6.4.2 rodando tema CaasExpress.</p>
                    </div>
                    <div class="widget-card">
                        <h3>Atividade</h3>
                        <p>Recentemente publicado</p>
                        <ul class="activity-list" id="dashboard-activity-list">
                            <li>Carregando...</li>
                        </ul>
                    </div>
                    <div class="widget-card">
                        <h3>Rascunho Rápido</h3>
                        <div class="form-group"><input type="text" placeholder="Título" id="quick-draft-title"></div>
                        <div class="form-group"><textarea placeholder="O que você está pensando?"
                                id="quick-draft-content"></textarea></div>
                        <button class="btn-secondary" onclick="AdminApp.quickDraft()">Salvar Rascunho</button>
                    </div>
                </div>
            </div>

            <!-- VIEW: POSTS LIST -->
            <div id="view-posts" class="view-section" style="display: none;">
                <div class="page-header">
                    <h1>Posts</h1>
                    <button class="btn-primary" id="btn-new-post">Adicionar Novo</button>
                </div>
                <div class="sub-nav" id="posts-status-filters">
                    <a href="#" class="active" onclick="AdminApp.filterPosts('all')">Todos (<span id="count-all">0</span>)</a> | 
                    <a href="#" onclick="AdminApp.filterPosts('publish')">Publicados (<span id="count-publish">0</span>)</a> | 
                    <a href="#" onclick="AdminApp.filterPosts('draft')">Rascunhos (<span id="count-draft">0</span>)</a>
                </div>
                <div class="card">
                    <div class="table-actions">
                        <select>
                            <option>Ações em massa</option>
                        </select>
                        <button class="btn-secondary">Aplicar</button>
                        <select>
                            <option>Todas as datas</option>
                        </select>
                        <select>
                            <option>Todas as categorias</option>
                        </select>
                        <button class="btn-secondary">Filtrar</button>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox"></th>
                                <th>Título</th>
                                <th>Autor</th>
                                <th>Categoria</th>
                                <th>Tags</th>
                                <th style="text-align:center;">🔽 Entrada</th>
                                <th style="text-align:center;">🔼 Saída</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody id="posts-table-body"></tbody>
                    </table>
                    <div id="posts-pagination"></div>
                </div>
            </div>

            <!-- VIEW: POST EDITOR (Shared for Posts and Pages) -->
            <div id="view-editor" class="view-section" style="display: none;">
                <div class="page-header">
                    <h1 id="editor-title-label">Editar Post</h1>
                    <div class="header-actions">
                        <button class="btn-secondary" id="btn-cancel-edit">Voltar</button>
                    </div>
                </div>

                <div class="editor-grid">
                    <!-- Main Editor Area -->
                    <div class="editor-main">
                        <div class="form-group">
                            <input type="text" id="post-title" class="title-input" placeholder="Adicione o título aqui"
                                required>
                        </div>

                        <!-- Rich Text Editor -->
                        <div class="card" style="padding: 0; overflow:hidden;">
                            <div id="quill-editor" style="height: 400px;"></div>
                        </div>

                        <!-- SEO Metabox -->
                        <div class="card seo-card" style="margin-top: 2rem;">
                            <div class="seo-header">
                                <h3>SEO - RankMath</h3>
                                <span class="badge-seo">10/100</span>
                            </div>
                            <div class="form-group">
                                <label>Palavra-chave Foco</label>
                                <input type="text" id="seo-keyword">
                            </div>
                            <div class="seo-preview">
                                <div class="preview-title" id="preview-seo-title">Título SEO...</div>
                                <div class="preview-url">caasexpresss.com/blog/...</div>
                                <div class="preview-desc" id="preview-seo-desc">Descrição...</div>
                            </div>
                            <div class="form-group"><label>Título SEO</label><input type="text" id="seo-title"></div>
                            <div class="form-group"><label>Meta Descrição</label><textarea id="seo-desc" rows="2"></textarea></div>
                            <hr style="border-top: 1px solid #eee; margin: 1rem 0;">
                            <div class="form-group">
                                <label>🔗 Palavras-chave para Auto-Link (Backlinks)</label>
                                <input type="text" id="auto-link-keywords" placeholder="ex: motoboy, entrega rápida (separados por vírgula)">
                                <small style="display:block; color:#666; font-size: 0.8rem; margin-top: 0.25rem;">
                                    Se preenchido, o Robô criará links em outros posts apontando para este post quando encontrar estas palavras.
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="editor-sidebar">
                        <div class="card">
                            <h3>Publicar</h3>
                            <div class="form-group">
                                <button class="btn-primary btn-block" id="btn-save-post">Atualizar / Publicar</button>
                            </div>
                            <div class="form-group row-flex">
                                <label>Status:</label> <select id="post-status">
                                    <option value="publish">Publicado</option>
                                    <option value="draft">Rascunho</option>
                                </select>
                            </div>
                            <div class="form-group row-flex">
                                <label>Visibilidade:</label> <span>Público</span> <a href="#">Editar</a>
                            </div>
                            <div class="form-group row-flex">
                                <label>Publicar:</label> <span>Imediatamente</span> <a href="#">Editar</a>
                            </div>
                            <div class="card-footer-actions">
                                <a href="#" style="color:#b32d2e;">Mover para Lixeira</a>
                            </div>
                        </div>

                        <div class="card" style="margin-top: 1rem;">
                            <h3>Autor</h3>
                            <select id="post-author" style="width:100%; padding:0.5rem;">
                                <?php
                                foreach ($allUsers as $author):
                                ?>
                                <option value="<?= $author['id'] ?>"><?= htmlspecialchars($author['display_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="card" style="margin-top: 1rem;" id="editor-categories-box">
                            <h3>Categorias</h3>
                            <div class="categories-list-check" id="categories-checklist">
                                <?php foreach ($allCats as $cat): ?>
                                <label style="display:block; margin-bottom:0.5rem;">
                                    <input type="checkbox" name="post-category" value="<?= $cat['id'] ?>">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <a href="#" class="btn-text" id="btn-add-cat-quick">+ Adicionar Nova Categoria</a>
                        </div>

                        <div class="card" style="margin-top: 1rem;">
                            <h3>Imagem Destacada</h3>
                            <div id="featured-image-preview" class="image-preview"
                                onclick="AdminApp.openMediaLibrary('featured')">
                                <span class="placeholder-text">Definir imagem destacada</span>
                            </div>
                            <input type="hidden" id="post-image-url">
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW: MEDIA -->
            <div id="view-media" class="view-section" style="display: none;">
                <div class="page-header">
                    <h1>Biblioteca de Mídia</h1>
                    <button class="btn-primary" onclick="AdminApp.openMediaLibrary()">Adicionar Nova</button>
                </div>
                <div class="media-toolbar">
                    <select>
                        <option>Todos os itens de mídia</option>
                    </select>
                    <select>
                        <option>Todas as datas</option>
                    </select>
                    <button class="btn-secondary">Filtrar</button>
                </div>
                <div class="card">
                    <div class="media-grid" id="view-media-grid">
                        <p style="padding:2rem;">Carregando mídia...</p>
                    </div>
                </div>
            </div>

            <!-- VIEW: PAGES -->
            <div id="view-pages" class="view-section" style="display: none;">
                <div class="page-header">
                    <h1>Páginas</h1>
                    <button class="btn-primary" onclick="AdminApp.createPage()">Adicionar Nova</button>
                </div>
                <div class="card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Autor</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody id="pages-table-body">
                            <tr>
                                <td colspan="3">Nenhuma página encontrada.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW: COMMENTS -->
            <div id="view-comments" class="view-section" style="display: none;">
                <div class="page-header">
                    <h1>Comentários</h1>
                </div>
                <div class="card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Autor</th>
                                <th>Comentário</th>
                                <th>Em resposta a</th>
                                <th>Enviado em</th>
                            </tr>
                        </thead>
                        <tbody id="comments-table-body">
                            <tr>
                                <td colspan="4" align="center">Nenhum comentário aguardando moderação.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW: APPEARANCE -->
            <div id="view-appearance" class="view-section" style="display: none;">
                <div class="page-header">
                    <h1>Temas</h1>
                    <button class="btn-primary">Adicionar Novo</button>
                </div>
                <div class="themes-grid">
                    <div class="theme-card active">
                        <div class="theme-screenshot" style="background:#ddd;"></div>
                        <div class="theme-info">
                            <h3>Caas Express V2</h3>
                            <span class="badge-success">Ativo</span>
                        </div>
                    </div>
                    <div class="theme-card">
                        <div class="theme-screenshot" style="background:#333;"></div>
                        <div class="theme-info">
                            <h3>Twenty Twenty-Four</h3>
                            <button class="btn-secondary">Ativar</button>
                        </div>
                    </div>
                    <div class="theme-card">
                        <div class="theme-screenshot" style="background:#555;"></div>
                        <div class="theme-info">
                            <h3>Twenty Twenty-Three</h3>
                            <button class="btn-secondary">Ativar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW: PLUGINS -->
            <div id="view-plugins" class="view-section" style="display: none;">
                <div class="page-header">
                    <h1>Plugins</h1>
                    <button class="btn-primary">Adicionar Novo</button>
                </div>
                <div class="card">
                    <table class="data-table plugins-table">
                        <thead>
                            <tr>
                                <th>Plugin</th>
                                <th>Descrição</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="plugin-active">
                                <td><strong>Akismet Anti-Spam</strong></td>
                                <td>Usado por milhões, Akismet é possivelmente a melhor maneira de proteger seu blog de
                                    spam.</td>
                                <td><a href="#">Configurações</a> | <a href="#" style="color:#b32d2e">Desativar</a></td>
                            </tr>
                            <tr class="plugin-active">
                                <td><strong>Yoast SEO</strong></td>
                                <td>A primeira verdadeira solução completa de SEO para WordPress.</td>
                                <td><a href="#">Configurações</a> | <a href="#" style="color:#b32d2e">Desativar</a></td>
                            </tr>
                            <tr>
                                <td><strong>Hello Dolly</strong></td>
                                <td>Isso não é apenas um plugin, simboliza a esperança e o entusiasmo de toda uma
                                    geração.</td>
                                <td><a href="#">Ativar</a> | <a href="#" style="color:#b32d2e">Excluir</a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW: USERS -->
            <div id="view-users" class="view-section" style="display: none;">
                <div class="page-header">
                    <h1>Usuários</h1>
                    <button class="btn-primary">Adicionar Novo</button>
                </div>
                <div class="card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nome de usuário</th>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Função</th>
                                <th>Posts</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body">
                            <?php
                            $usersStmt = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM posts WHERE author_id = u.id) as post_count FROM users u ORDER BY u.id");
                            $allUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($allUsers as $u):
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                                <td><?= htmlspecialchars($u['display_name']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= ucfirst($u['role']) ?></td>
                                <td><?= $u['post_count'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW: TOOLS -->
            <div id="view-tools" class="view-section" style="display: none;">
                <div class="page-header">
                    <h1>Ferramentas</h1>
                </div>
                <div class="card">
                    <h3>Importar / Exportar</h3>
                    <p>Mova conteúdo entre sites WordPress.</p>
                    <div class="tools-grid">
                        <div class="tool-item">
                            <h4>Exportar Dados</h4>
                            <p>Baixe todo o seu conteúdo em formato JSON/XML.</p>
                            <button class="btn-secondary" onclick="alert('Exportação iniciada...')">Exportar
                                Tudo</button>
                        </div>
                        <div class="tool-item">
                            <h4>Saúde do Site</h4>
                            <p>Seu site está <strong>Saudável</strong>.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW: SETTINGS -->
            <div id="view-settings" class="view-section" style="display: none;">
                <div class="page-header">
                    <h1>Configurações Gerais</h1>
                </div>
                <div class="card">
                    <div class="form-group row">
                        <label>Título do Site</label>
                        <input type="text" value="Caas Express" style="width: 50%;">
                    </div>
                    <div class="form-group row">
                        <label>Descrição (Tagline)</label>
                        <input type="text" value="Sua Escolha Confiável para Serviços de Motoboy" style="width: 50%;">
                    </div>
                    <div class="form-group row">
                        <label>Endereço do WordPress (URL)</label>
                        <input type="text" value="https://caasexpresss.com" disabled
                            style="background:#eee; width: 50%;">
                    </div>
                    <div class="form-group row">
                        <label>E-mail de Administração</label>
                        <input type="email" value="admin@caasexpresss.com" style="width: 50%;">
                    </div>
                    <div class="form-group row">
                        <label>Membros</label>
                        <label><input type="checkbox"> Qualquer pessoa pode se registrar</label>
                    </div>
                    <div class="form-group row">
                        <label>Idioma do Site</label>
                        <select>
                            <option>Português do Brasil</option>
                            <option>English</option>
                        </select>
                    </div>
                    <hr>
                    <button class="btn-primary" onclick="alert('Configurações salvas!')">Salvar Alterações</button>
                </div>
            </div>

        </main>
    </div>

    <!-- MEDIA MODAL -->
    <div id="media-modal" class="modal" style="display: none;">
        <div class="modal-content large-modal">
            <div class="modal-header">
                <h2>Biblioteca de Mídia</h2>
                <span class="close-modal" onclick="AdminApp.closeMediaLibrary()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="media-tabs">
                    <button class="tab-btn active" data-tab="upload">Enviar Arquivos</button>
                    <button class="tab-btn" data-tab="library">Biblioteca</button>
                </div>

                <div id="tab-upload" class="tab-content active">
                    <div class="upload-area" id="drop-zone">
                        <p>Arraste arquivos para cá ou</p>
                        <button class="btn-secondary" onclick="document.getElementById('file-input').click()">Selecionar
                            Arquivos</button>
                        <input type="file" id="file-input" hidden accept="image/*">
                    </div>
                    <div id="upload-progress" style="display:none; margin-top: 1rem;">
                        Enviando...
                    </div>
                </div>

                <div id="tab-library" class="tab-content">
                    <div class="media-grid" id="media-grid">
                        <!-- Images injected here -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-primary" disabled id="btn-select-media">Selecionar</button>
            </div>
        </div>
    </div>

    <script src="config.js"></script>
    <script src="api.js"></script>
    <script src="admin.js"></script>
</body>

</html>