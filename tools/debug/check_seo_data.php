<?php
require_once __DIR__ . '/src/Config/Database.php';
use Src\Config\Database;

$pdo = Database::getInstance();

echo "=== ÚLTIMOS METADADOS SEO SALVOS ===\n\n";

$stmt = $pdo->query("
    SELECT p.id, p.title, 
           MAX(CASE WHEN pm.meta_key = '_yoast_wpseo_title' THEN pm.meta_value END) as seo_title,
           MAX(CASE WHEN pm.meta_key = '_yoast_wpseo_metadesc' THEN pm.meta_value END) as meta_desc,
           MAX(CASE WHEN pm.meta_key = '_yoast_wpseo_focuskw' THEN pm.meta_value END) as focus_kw
    FROM posts p
    LEFT JOIN postmeta pm ON p.id = pm.post_id AND pm.meta_key LIKE '_yoast%'
    WHERE p.type = 'post' AND p.status = 'publish'
    GROUP BY p.id
    HAVING seo_title IS NOT NULL OR meta_desc IS NOT NULL
    ORDER BY p.id DESC
    LIMIT 10
");

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($results)) {
    echo "❌ Nenhum metadado SEO encontrado no banco.\n";
} else {
    foreach ($results as $r) {
        echo "Post #{$r['id']}: {$r['title']}\n";
        echo "   SEO Title: " . ($r['seo_title'] ?: '(vazio)') . "\n";
        echo "   Meta Desc: " . substr($r['meta_desc'] ?: '(vazio)', 0, 60) . "...\n";
        echo "   Focus KW:  " . ($r['focus_kw'] ?: '(vazio)') . "\n\n";
    }
}

echo "\n=== TOTAL DE METADADOS ===\n";
$count = $pdo->query("SELECT COUNT(*) FROM postmeta WHERE meta_key LIKE '_yoast%'")->fetchColumn();
echo "Total de entradas _yoast*: $count\n";
