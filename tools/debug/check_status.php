<?php
require_once __DIR__ . '/src/Config/Database.php';
use Src\Config\Database;

$pdo = Database::getInstance();

$total = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'publish' AND type = 'post'")->fetchColumn();
$comSeo = $pdo->query("SELECT COUNT(DISTINCT post_id) FROM postmeta WHERE meta_key = '_yoast_wpseo_metadesc' AND meta_value != ''")->fetchColumn();
$comLinks = $pdo->query("SELECT COUNT(DISTINCT post_id) FROM postmeta WHERE meta_key = '_auto_linked'")->fetchColumn();

echo "=== STATUS DO PROCESSAMENTO ===\n\n";
echo "Total de posts: $total\n";
echo "Com SEO: $comSeo (" . round($comSeo/$total*100, 1) . "%)\n";
echo "Com Links: $comLinks (" . round($comLinks/$total*100, 1) . "%)\n";
echo "Pendentes SEO: " . ($total - $comSeo) . "\n";
