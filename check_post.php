<?php
require_once __DIR__ . '/src/Config/Database.php';
use Src\Config\Database;

$pdo = Database::getInstance();

// Buscar o post
$stmt = $pdo->query("SELECT id, title FROM posts WHERE title LIKE '%Planilha de custos%' LIMIT 1");
$post = $stmt->fetch();

if ($post) {
    echo "Post ID: {$post['id']}\n";
    echo "Título: {$post['title']}\n\n";
    
    // Buscar metadados
    $meta = $pdo->query("SELECT meta_key, meta_value FROM postmeta WHERE post_id = {$post['id']}")->fetchAll();
    
    if (empty($meta)) {
        echo "❌ NENHUM METADADO SEO ENCONTRADO!\n";
        echo "   Este post ainda NÃO foi processado pelo robô.\n";
    } else {
        echo "✅ Metadados encontrados:\n";
        foreach ($meta as $m) {
            echo "   {$m['meta_key']}: " . substr($m['meta_value'], 0, 60) . "...\n";
        }
    }
} else {
    echo "Post não encontrado\n";
}
