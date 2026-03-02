<?php
require_once __DIR__ . '/src/Config/Database.php';
use Src\Config\Database;

$pdo = Database::getInstance();

echo "=== TESTE DE CONTAGEM DE LINKS ===\n\n";

// Pegar um post qualquer
$post = $pdo->query("SELECT id, slug, content FROM posts WHERE status = 'publish' AND type = 'post' LIMIT 1")->fetch();

echo "Post ID: {$post['id']}\n";
echo "Slug: {$post['slug']}\n\n";

// Teste 1: Buscar posts que linkam para este
$slug = $post['slug'];
$inboundStmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE content LIKE ? AND id != ?");
$inboundStmt->execute(["%caasexpresss.com/$slug%", $post['id']]);
$inbound = $inboundStmt->fetchColumn();
echo "Links de entrada (caasexpresss.com/$slug): $inbound\n";

// Teste 2: Ver se o conteúdo tem links
$content = $post['content'];
preg_match_all('/href=["\'](https?:\/\/caasexpresss\.com\/[^"\']+)["\']/', $content, $allLinks);
echo "\nLinks neste post:\n";
if (!empty($allLinks[1])) {
    foreach (array_unique($allLinks[1]) as $link) {
        echo "   - $link\n";
    }
} else {
    echo "   (nenhum link interno encontrado)\n";
}

// Teste 3: Ver outro post que tem links
echo "\n\n=== POST COM MAIS LINKS ===\n";
$post2 = $pdo->query("SELECT id, slug, content FROM posts WHERE content LIKE '%caasexpresss.com/%' AND status = 'publish' LIMIT 1")->fetch();
if ($post2) {
    echo "Post ID: {$post2['id']}, Slug: {$post2['slug']}\n";
    preg_match_all('/href=["\'](https?:\/\/caasexpresss\.com\/[a-z0-9\-]+)\/?["\']/', $post2['content'], $matches);
    echo "Links encontrados: " . count($matches[1]) . "\n";
    foreach (array_slice(array_unique($matches[1]), 0, 5) as $l) {
        echo "   - $l\n";
    }
}
