<?php
require_once __DIR__ . '/src/Config/Database.php';
use Src\Config\Database;

$pdo = Database::getInstance();

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║           🔍 AUDITORIA TOTAL - CAASEXPRESS                       ║\n";
echo "║           Data: " . date('d/m/Y H:i:s') . "                              ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// 1. POSTS
echo "📝 POSTS\n";
echo str_repeat("─", 50) . "\n";
$totalPosts = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'publish' AND type = 'post'")->fetchColumn();
$draftPosts = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'draft' AND type = 'post'")->fetchColumn();
$pages = $pdo->query("SELECT COUNT(*) FROM posts WHERE type = 'page'")->fetchColumn();
echo "   Total publicados: $totalPosts\n";
echo "   Rascunhos: $draftPosts\n";
echo "   Páginas: $pages\n\n";

// 2. SEO
echo "🎯 SEO (Metadados)\n";
echo str_repeat("─", 50) . "\n";
$comSeoTitle = $pdo->query("SELECT COUNT(DISTINCT post_id) FROM postmeta WHERE meta_key = '_yoast_wpseo_title' AND meta_value != ''")->fetchColumn();
$comMetaDesc = $pdo->query("SELECT COUNT(DISTINCT post_id) FROM postmeta WHERE meta_key = '_yoast_wpseo_metadesc' AND meta_value != ''")->fetchColumn();
$comFocusKw = $pdo->query("SELECT COUNT(DISTINCT post_id) FROM postmeta WHERE meta_key = '_yoast_wpseo_focuskw' AND meta_value != ''")->fetchColumn();
$comTags = $pdo->query("SELECT COUNT(DISTINCT post_id) FROM postmeta WHERE meta_key = '_ai_tags' AND meta_value != ''")->fetchColumn();
$comAutoLink = $pdo->query("SELECT COUNT(DISTINCT post_id) FROM postmeta WHERE meta_key = '_auto_link_keywords' AND meta_value != ''")->fetchColumn();
echo "   Com Título SEO: $comSeoTitle (" . round($comSeoTitle/$totalPosts*100, 1) . "%)\n";
echo "   Com Meta Desc: $comMetaDesc (" . round($comMetaDesc/$totalPosts*100, 1) . "%)\n";
echo "   Com Focus Keyword: $comFocusKw (" . round($comFocusKw/$totalPosts*100, 1) . "%)\n";
echo "   Com Tags IA: $comTags (" . round($comTags/$totalPosts*100, 1) . "%)\n";
echo "   Com Auto-Link KW: $comAutoLink (" . round($comAutoLink/$totalPosts*100, 1) . "%)\n";
echo "   ⚠️  Pendentes SEO: " . ($totalPosts - $comMetaDesc) . "\n\n";

// 3. INTERLINKING
echo "🔗 INTERLINKING\n";
echo str_repeat("─", 50) . "\n";
$comLinks = $pdo->query("SELECT COUNT(DISTINCT post_id) FROM postmeta WHERE meta_key = '_auto_linked'")->fetchColumn();
echo "   Posts com auto-link: $comLinks (" . round($comLinks/$totalPosts*100, 1) . "%)\n";
echo "   Pendentes: " . ($totalPosts - $comLinks) . "\n\n";

// 4. USUÁRIOS
echo "👥 USUÁRIOS\n";
echo str_repeat("─", 50) . "\n";
$users = $pdo->query("SELECT display_name, role FROM users ORDER BY role")->fetchAll();
foreach ($users as $u) {
    echo "   • {$u['display_name']} ({$u['role']})\n";
}
echo "\n";

// 5. CATEGORIAS
echo "📂 CATEGORIAS\n";
echo str_repeat("─", 50) . "\n";
$cats = $pdo->query("SELECT name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
if (empty($cats)) {
    echo "   (nenhuma categoria)\n";
} else {
    foreach ($cats as $cat) {
        echo "   • $cat\n";
    }
}
echo "\n";

// RESUMO FINAL
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║                        📊 RESUMO                                 ║\n";
echo "╠══════════════════════════════════════════════════════════════════╣\n";
$seoPercent = round($comMetaDesc/$totalPosts*100);
$linkPercent = round($comLinks/$totalPosts*100);
$seoBar = str_repeat("█", intval($seoPercent/5)) . str_repeat("░", 20-intval($seoPercent/5));
$linkBar = str_repeat("█", intval($linkPercent/5)) . str_repeat("░", 20-intval($linkPercent/5));
echo "║   SEO Completo:     $seoPercent% [$seoBar]           ║\n";
echo "║   Links Internos:   $linkPercent% [$linkBar]           ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
