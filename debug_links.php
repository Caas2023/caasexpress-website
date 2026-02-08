<?php
require_once __DIR__ . '/src/Config/Database.php';
use Src\Config\Database;

$pdo = Database::getInstance();

echo "=== DIAGNÓSTICO DE LINKS INTERNOS ===\n\n";

// 1. Procurar posts que têm links para outros POSTS (ignorando páginas comuns)
$sql = "SELECT id, title, content FROM posts 
        WHERE content LIKE '%caasexpresss.com/%' 
        AND content NOT LIKE '%/contato%' 
        AND content NOT LIKE '%/sobre-nos%' 
        AND content NOT LIKE '%/blog%'
        LIMIT 5";

$stmt = $pdo->query($sql);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($posts)) {
    echo "❌ NENHUM link entre posts encontrado ainda.\n";
    echo "O robô pode não ter processado interlinking suficiente ou os links estão com formato diferente.\n";
} else {
    foreach ($posts as $p) {
        echo "Post #{$p['id']} - {$p['title']}\n";
        
        // Extrair links
        preg_match_all('/href=["\']([^"\']+)["\']/', $p['content'], $matches);
        $links = $matches[1] ?? [];
        
        $validLinks = 0;
        foreach ($links as $link) {
            if (strpos($link, 'caasexpresss.com') !== false && 
                strpos($link, 'contato') === false && 
                strpos($link, 'sobre-nos') === false &&
                strpos($link, 'blog') === false) {
                
                echo "   ✅ Found Valid Link: $link\n";
                $validLinks++;
                
                // Testar se detectaria este link como Inbound para o alvo
                $slug = str_replace(['https://caasexpresss.com/', 'http://caasexpresss.com/'], '', $link);
                $slug = trim($slug, '/'); // Remove barras
                if ($slug) {
                    echo "      -> Alvo (Slug): $slug\n";
                    
                    // Verificar se o Controller contaria isso
                    // Query usada no Controller: LIKE "%caasexpresss.com/$slug%"
                    // Se o link for .../slug e a query busca .../slug, OK.
                    // Se o link for .../slug/ e a query busca .../slug, OK (LIKE pega parcial se não for restrito)
                    // Mas espere, a query é: content LIKE "%caasexpresss.com/$slug%"
                    // Se o link for "https://caasexpresss.com/slug", e buscarmos "%caasexpresss.com/slug%", bate.
                    // Se o slug do post alvo for "slug", bate.
                }
            }
        }
        
        if ($validLinks == 0) {
            echo "   (apenas links ignorados encontrados)\n";
        }
        echo "\n";
    }
}

// 2. Verificar quantos posts têm '_auto_linked'
$linkedCount = $pdo->query("SELECT COUNT(*) FROM postmeta WHERE meta_key = '_auto_linked'")->fetchColumn();
echo "Total de posts processados pelo Auto-Interlink: $linkedCount\n";
