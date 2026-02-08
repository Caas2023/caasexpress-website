<?php
require_once __DIR__ . '/src/Config/Database.php';
use Src\Config\Database;
$pdo = Database::getInstance();

echo "=== DIAGNÓSTICO PROFUNDO DE LINKS ===\n\n";

// 1. Encontrar links
$posts = $pdo->query("SELECT id, title, content FROM posts WHERE content LIKE '%href=\"https://caasexpresss.com/%' LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

$regexController = '/href=["\']https?:\/\/caasexpresss\.com\/([a-z0-9\-]+)\/?["\']/';

foreach($posts as $p){
    echo "Post #{$p['id']}: {$p['title']}\n";
    preg_match_all('/href=["\']([^"\']+)["\']/', $p['content'], $matches);
    
    $foundInternal = false;
    foreach($matches[1] as $link){
        if(strpos($link, 'caasexpresss.com')===false) continue;
        
        $foundInternal = true;
        echo "   Link encontrado: $link\n";
        
        // Testar Regex do Controller
        // Simulando o contexto href="..."
        if(preg_match($regexController, 'href="'.$link.'"', $m)){
           echo "      ✅ REGEX MATCH! Slug extraído: '{$m[1]}'\n";
           
           // Testar se acharia inbound (usando o slug extraído)
           $slug = $m[1];
           $inboundCount = $pdo->query("SELECT COUNT(*) FROM posts WHERE content LIKE '%caasexpresss.com/$slug%' AND id != {$p['id']}")->fetchColumn();
           echo "      Inbound Simulado (posts linkando para '$slug'): $inboundCount\n";

        } else {
           echo "      ❌ REGEX FAIL!\n";
           echo "      Regex esperado: [a-z0-9\-]+\n";
           
           // Check for invisible chars or unallowed chars
           $slugPart = str_replace(['https://caasexpresss.com/', 'http://caasexpresss.com/'], '', $link);
           echo "      Parte do slug: '$slugPart'\n";
           echo "      Dump: " . bin2hex($slugPart) . "\n";
        }
    }
    
    if(!$foundInternal) echo "   (Nenhum link interno neste post)\n";
    echo "\n";
}
