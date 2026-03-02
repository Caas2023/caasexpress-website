<?php
require_once __DIR__ . '/src/Config/Database.php';
use Src\Config\Database;
$pdo = Database::getInstance();

echo "=== BUSCA POR LINKS REAIS ENTRE POSTS ===\n\n";

// Buscar qualquer post que tenha link para algo que NAO seja as páginas fixas comuns
$exclude = ['contato', 'sobre-nos', 'blog', 'servicos', 'home', 'politica-de-privacidade', 'termos-de-uso'];
$sql = "SELECT id, title, content FROM posts WHERE content LIKE '%href=\"https://caasexpresss.com/%' ";

// Filtro grosseiro via SQL para reduzir resultados
foreach($exclude as $ex){
    $sql .= " AND content NOT LIKE '%/$ex%' ";
}
$sql .= " AND content NOT LIKE '%href=\"https://caasexpresss.com/\"%' LIMIT 5";

$posts = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

if(empty($posts)){
    echo "❌ NENHUM link entre posts encontrado nesta busca filtrada!\n";
    echo "   Isso sugere que o robô ainda não criou links significativos entre artigos.\n";
} else {
    foreach($posts as $p){
        echo "Post #{$p['id']}: {$p['title']}\n";
        preg_match_all('/href=["\']https?:\/\/caasexpresss\.com\/([a-z0-9\-]+)\/?["\']/', $p['content'], $matches);
        $links = $matches[1] ?? [];
        $found = 0;
        foreach($links as $l){
            if(!in_array($l, $exclude) && $l !== ''){
                echo "   ✅ Link válido para post: '$l'\n";
                $found++;
            }
        }
    }
}

$cnt = $pdo->query("SELECT COUNT(*) FROM postmeta WHERE meta_key = '_auto_linked'")->fetchColumn();
echo "\nTotal de posts marcados como processados (_auto_linked): $cnt\n";
