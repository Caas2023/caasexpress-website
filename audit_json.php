<?php
require_once __DIR__ . '/src/Config/Database.php';
use Src\Config\Database;
$pdo = Database::getInstance();

header('Content-Type: application/json');

$total = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'publish' AND type = 'post'")->fetchColumn();
$seo = $pdo->query("SELECT COUNT(DISTINCT post_id) FROM postmeta WHERE meta_key = '_yoast_wpseo_metadesc' AND meta_value != ''")->fetchColumn();
$links = $pdo->query("SELECT COUNT(DISTINCT post_id) FROM postmeta WHERE meta_key = '_auto_linked'")->fetchColumn();

echo json_encode([
    'total_posts' => $total,
    'seo_done' => $seo,
    'links_done' => $links,
    'seo_percent' => round(($seo/$total)*100, 1),
    'links_percent' => round(($links/$total)*100, 1)
]);
