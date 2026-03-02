<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    $ext = pathinfo($uri, PATHINFO_EXTENSION);
    $staticExts = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2'];
    if (in_array(strtolower($ext), $staticExts)) {
        return false;
    }
}

if (strpos($uri, '/wp-json') === 0) {
    require __DIR__ . '/api/index.php';
    return;
}

$phpFile = __DIR__ . $uri;
if (file_exists($phpFile) && pathinfo($phpFile, PATHINFO_EXTENSION) === 'php') {
    require $phpFile;
    return;
}

if (pathinfo($uri, PATHINFO_EXTENSION) === '') {
    $phpFileExt = __DIR__ . $uri . '.php';
    if (file_exists($phpFileExt)) {
        require $phpFileExt;
        return;
    }
}

if ($uri === '/' || $uri === '/index') {
    require __DIR__ . '/index.php';
    return;
}

// 404 - Redirecionar para página inicial
header('Location: /index.php', true, 302);
exit;
