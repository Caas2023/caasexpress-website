<?php
// Autoloader Simples (padrão PSR-4 simplificado)
spl_autoload_register(function ($class) {
    // Espaço de nome: Src\Controllers\PostController -> src/Controllers/PostController.php
    $prefix = 'Src\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use Src\Controllers\PostController;
use Src\Controllers\MediaController;
use Src\Controllers\UserController;
use Src\Utils\Response;

// Verificar Método HTTP
$method = $_SERVER['REQUEST_METHOD'];
// Pegar URI (path)
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Remover prefixos
$path = str_replace('/api/index.php', '', $path);
// Normalizar o path para remover /index.php inicial se vier do router
if (strpos($path, '/index.php/wp-json') === 0) {
    $path = str_replace('/index.php', '', $path);
}
$path = rtrim($path, '/');

// Roteamento
// 1. Posts
if ($method === 'GET' && preg_match('#/wp-json/wp/v2/posts$#', $path)) {
    (new PostController())->index();
}
elseif ($method === 'GET' && preg_match('#/wp-json/wp/v2/posts/(\d+)#', $path, $matches)) {
    (new PostController())->show($matches[1]);
}
elseif ($method === 'POST' && preg_match('#/wp-json/wp/v2/posts$#', $path)) {
    (new PostController())->create();
}
elseif ($method === 'POST' && preg_match('#/wp-json/wp/v2/posts/(\d+)$#', $path, $matches)) {
    (new PostController())->update($matches[1]);
}
elseif ($method === 'DELETE' && preg_match('#/wp-json/wp/v2/posts/(\d+)$#', $path, $matches)) {
    (new PostController())->delete($matches[1]);
}

// 2. Auth & Users
elseif ($method === 'GET' && preg_match('#/wp-json/wp/v2/users/me$#', $path)) {
    (new UserController())->me();
}
elseif ($method === 'GET' && preg_match('#/wp-json/wp/v2/users$#', $path)) {
    (new UserController())->index();
}

// 3. Web Stories (usando PostController com type=web-story por enquanto ou mock)
elseif ($method === 'GET' && preg_match('#/wp-json/wp/v2/web-story$#', $path)) {
    // Por enquanto, reuse index de posts filtrando type web-story se implementado, ou mock vazio
    $_GET['type'] = 'web-story';
    (new PostController())->index();
}

// 4. Media
elseif ($method === 'POST' && preg_match('#/wp-json/wp/v2/media$#', $path)) {
    (new MediaController())->create();
}
elseif ($method === 'GET' && preg_match('#/wp-json/wp/v2/media$#', $path)) {
    (new MediaController())->index(); // Falta implementar index no MediaController
}

// 5. Categorias
elseif ($method === 'GET' && preg_match('#/wp-json/wp/v2/categories$#', $path)) {
     // Mock ou implementar CategoriaController
     Response::json([['id'=>1, 'name'=>'Geral', 'slug'=>'geral', 'count'=>0]]);
}

// 6. Stats - Contagem real do banco de dados
elseif ($method === 'GET' && preg_match('#/wp-json/wp/v2/stats$#', $path)) {
    $pdo = \Src\Config\Database::getInstance();
    $posts = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'publish' AND type = 'post'")->fetchColumn();
    $pages = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'publish' AND type = 'page'")->fetchColumn();
    $comments = 0; // Implementar se tiver tabela de comentários
    Response::json([
        'posts' => (int)$posts,
        'pages' => (int)$pages,
        'comments' => $comments
    ]);
}

// 7. Stats por Status - Contagem de posts por status
elseif ($method === 'GET' && preg_match('#/wp-json/wp/v2/stats/status$#', $path)) {
    $pdo = \Src\Config\Database::getInstance();
    $all = $pdo->query("SELECT COUNT(*) FROM posts WHERE type = 'post'")->fetchColumn();
    $publish = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'publish' AND type = 'post'")->fetchColumn();
    $draft = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'draft' AND type = 'post'")->fetchColumn();
    Response::json([
        'all' => (int)$all,
        'publish' => (int)$publish,
        'draft' => (int)$draft
    ]);
}

elseif ($method === 'OPTIONS') {
    // CORS Preflight
    Response::json(['status' => 'ok']);
}
else {
    Response::error("Endpoint not found: $method $path", 404);
}
