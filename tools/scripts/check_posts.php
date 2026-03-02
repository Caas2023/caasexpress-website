<?php
// Script para verificar contagem de posts
require_once __DIR__ . '/src/Config/Database.php';
use Src\Config\Database;

$pdo = Database::getInstance();

echo "=== Verificação de Posts ===\n\n";

$total = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
echo "Total de posts na tabela: $total\n";

$published = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'publish'")->fetchColumn();
echo "Posts publicados: $published\n";

$draft = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'")->fetchColumn();
echo "Posts rascunho: $draft\n";

$types = $pdo->query("SELECT type, COUNT(*) as c FROM posts GROUP BY type")->fetchAll();
echo "\nPor tipo:\n";
foreach ($types as $t) {
    echo "  - {$t['type']}: {$t['c']}\n";
}

echo "\n=== Primeiros 5 posts ===\n";
$posts = $pdo->query("SELECT id, title, status, type FROM posts ORDER BY id DESC LIMIT 5")->fetchAll();
foreach ($posts as $p) {
    echo "ID: {$p['id']} | Status: {$p['status']} | Type: {$p['type']} | Title: " . substr($p['title'], 0, 50) . "...\n";
}
