<?php
// Includes diretos (Vercel lambda não suporta bem autoloader PSR-4)
require_once __DIR__ . '/../src/Config/Database.php';
require_once __DIR__ . '/../src/Utils/Response.php';
require_once __DIR__ . '/../src/Utils/Auth.php';

// CORREÇÃO: Adicionado a pasta /php/ nos caminhos abaixo para bater com seu Git!
require_once __DIR__ . '/../src/controllers/php/PostController.php';
require_once __DIR__ . '/../src/controllers/php/MediaController.php';
require_once __DIR__ . '/../src/controllers/php/UserController.php';

use Src\Controllers\PostController;
use Src\Controllers\MediaController;
use Src\Controllers\UserController;
use Src\Utils\Response;


// Verificar Método HTTP
$method = $_SERVER['REQUEST_METHOD'];
// Pegar URI (path) sem query string
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = $uri;

// Remover prefixos comuns e normalizar
$path = str_replace('/api/index.php', '', $path);
$path = str_replace('/api', '', $path);

// Se o roteador do Vercel/Apache passar index.php no meio, limpa
if (strpos($path, '/index.php') === 0) {
    $path = substr($path, 10);
}

$path = '/' . ltrim($path, '/');
$path = rtrim($path, '/');
if ($path === '') $path = '/';

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

// 3. Web Stories
elseif ($method === 'GET' && preg_match('#/wp-json/wp/v2/web-story$#', $path)) {
    $_GET['type'] = 'web-story';
    (new PostController())->index();
}

// 4. Media
elseif ($method === 'POST' && preg_match('#/wp-json/wp/v2/media$#', $path)) {
    (new MediaController())->create();
}
elseif ($method === 'GET' && preg_match('#/wp-json/wp/v2/media$#', $path)) {
    (new MediaController())->index(); 
}

// 5. Categorias
elseif ($method === 'GET' && preg_match('#/wp-json/wp/v2/categories$#', $path)) {
     Response::json([['id'=>1, 'name'=>'Geral', 'slug'=>'geral', 'count'=>0]]);
}

// 6. Stats - Contagem real do banco de dados
elseif ($method === 'GET' && preg_match('#/wp-json/wp/v2/stats$#', $path)) {
    $pdo = \Src\Config\Database::getInstance();
    $posts = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'publish' AND type = 'post'")->fetchColumn();
    $pages = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'publish' AND type = 'page'")->fetchColumn();
    $comments = 0; 
    Response::json([
        'posts' => (int)$posts,
        'pages' => (int)$pages,
        'comments' => $comments
    ]);
}

// 7. Stats por Status
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
