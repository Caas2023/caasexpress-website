<?php
require_once __DIR__ . '/../src/Config/Database.php';

use Src\Config\Database;

// Configuração
$wordpressUrl = $argv[1] ?? 'https://seusite.com'; // Passe a URL como argumento ou edite aqui
$apiUrl = rtrim($wordpressUrl, '/') . '/wp-json/wp/v2';

echo "Iniciando importação de: $wordpressUrl\n";

try {
    $pdo = Database::getInstance();
    
    // 1. Importar Categorias
    echo "Importando categorias...\n";
    $categories = fetchAll($apiUrl . '/categories?per_page=100');
    $stmtCat = $pdo->prepare("INSERT OR IGNORE INTO term_taxonomy (term_id, name, slug, description, count, taxonomy) VALUES (?, ?, ?, ?, ?, 'category')");
    
    foreach ($categories as $cat) {
        $stmtCat->execute([
            $cat['id'],
            $cat['name'],
            $cat['slug'],
            $cat['description'],
            $cat['count']
        ]);
        echo " - Categoria: {$cat['name']}\n";
    }

    // 2. Importar Posts
    echo "Importando posts...\n";
    $posts = fetchAll($apiUrl . '/posts?per_page=20&_embed'); // _embed traz a imagem destacada e autor
    
    $stmtPost = $pdo->prepare("INSERT OR REPLACE INTO posts (id, title, slug, content, excerpt, status, type, author_id, featured_media, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtMeta = $pdo->prepare("INSERT INTO postmeta (post_id, meta_key, meta_value) VALUES (?, ?, ?)");
    $stmtRel = $pdo->prepare("INSERT OR IGNORE INTO term_relationships (object_id, term_taxonomy_id) VALUES (?, ?)");
    $stmtMedia = $pdo->prepare("INSERT OR IGNORE INTO media (id, title, file_path, mime_type, alt_text) VALUES (?, ?, ?, ?, ?)");

    foreach ($posts as $post) {
        $featuredMediaId = $post['featured_media'];
        
        // Processar Imagem Destacada
        if ($featuredMediaId > 0 && isset($post['_embedded']['wp:featuredmedia'][0])) {
            $media = $post['_embedded']['wp:featuredmedia'][0];
            $stmtMedia->execute([
                $media['id'],
                $media['title']['rendered'],
                $media['source_url'], // Salvamos a URL remota por enquanto
                $media['mime_type'],
                $media['alt_text']
            ]);
        }

        // Inserir Post
        $stmtPost->execute([
            $post['id'],
            $post['title']['rendered'],
            $post['slug'],
            $post['content']['rendered'],
            $post['excerpt']['rendered'],
            $post['status'],
            $post['type'],
            1, // Hardcoded para admin por enquanto, ou mapear autores
            $featuredMediaId,
            formatDate($post['date']),
            formatDate($post['modified'])
        ]);

        // Categorias
        if (!empty($post['categories'])) {
            foreach ($post['categories'] as $catId) {
                $stmtRel->execute([$post['id'], $catId]);
            }
        }

        echo " - Post importado: {$post['title']['rendered']}\n";
    }

    echo "Importação concluída com sucesso!\n";

} catch (Exception $e) {
    echo "Erro fatal: " . $e->getMessage() . "\n";
}

// Helpers
function fetchAll($url) {
    $data = [];
    $page = 1;
    do {
        $currentUrl = $url . "&page=$page";
        
        // Usar cURL ao invés de file_get_contents para melhor suporte SSL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $currentUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desabilita verificação SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if (!$response || $httpCode !== 200) {
            echo "   [Debug] Falha ao buscar: $currentUrl (HTTP $httpCode)\n";
            break;
        }

        $pageData = json_decode($response, true);
        if (empty($pageData) || !is_array($pageData)) break;

        $data = array_merge($data, $pageData);
        $page++;
        
        // Limite de 50 páginas (até 1000 posts)
        if ($page > 50) break; 

    } while (true);
    
    return $data;
}

function formatDate($dateString) {
    return date('Y-m-d H:i:s', strtotime($dateString));
}
