<?php
require_once __DIR__ . '/src/Config/Database.php';
use Src\Config\Database;

$pdo = Database::getInstance();

echo "=== VERIFICANDO FORMATO DOS LINKS ===\n\n";

// Buscar posts com links
$stmt = $pdo->query("SELECT id, slug, substr(content, 1, 2000) as content FROM posts WHERE content LIKE '%href%' LIMIT 3");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($posts as $p) {
    echo "Post #{$p['id']} (slug: {$p['slug']})\n";
    
    // Encontrar todos os hrefs
    preg_match_all('/href=["\']([^"\']+)["\']/', $p['content'], $matches);
    
    if (!empty($matches[1])) {
        echo "Links encontrados:\n";
        foreach (array_unique($matches[1]) as $link) {
            echo "   - $link\n";
        }
    } else {
        echo "   (nenhum link encontrado)\n";
    }
    echo "\n";
}
