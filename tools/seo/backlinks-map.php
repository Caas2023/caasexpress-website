<?php
/**
 * Mapeamento de Backlinks Internos
 * Analisa todos os posts e cria um mapa de links internos entre eles
 */

require_once __DIR__ . '/../src/Config/Database.php';
require_once __DIR__ . '/../src/Utils/Response.php';
require_once __DIR__ . '/../src/Utils/Auth.php';
use Src\Utils\Auth;
use Src\Config\Database;

Auth::check();

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = Database::getInstance();
    
    // URL Base do site (produção)
    if (!defined('BASE_URL')) define('BASE_URL', 'https://caasexpresss.com');
    
    // Helper: Buscar Slug por ID
    if (!function_exists('getSlugById')) {
        function getSlugById($pdo, $id) {
            static $cache = [];
            if (isset($cache[$id])) return $cache[$id];
            
            $stmt = $pdo->prepare("SELECT slug FROM posts WHERE id = ?");
            $stmt->execute([$id]);
            $slug = $stmt->fetchColumn();
            $cache[$id] = $slug ?: $id;
            return $cache[$id];
        }
    }
    
    // Buscar todos os posts publicados
    $stmt = $pdo->query("SELECT id, title, slug, content FROM posts WHERE status = 'publish' ORDER BY created_at DESC");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalPosts = count($posts);
    $linksMap = [];
    $orphanPages = [];
    $mostLinked = [];
    $leastLinked = [];
    
    // Criar mapa de slugs para IDs
    $slugToId = [];
    $idToTitle = [];
    foreach ($posts as $post) {
        $slugToId[$post['slug']] = $post['id'];
        $idToTitle[$post['id']] = $post['title'];
        $mostLinked[$post['id']] = 0; // Inicializar contador de backlinks recebidos
    }
    
    // Analisar cada post
    foreach ($posts as $post) {
        $content = $post['content'];
        $linksMap[$post['id']] = [
            'title' => $post['title'],
            'slug' => $post['slug'],
            'outgoing_links' => [],
            'incoming_links' => []
        ];
        
        // Encontrar links internos no conteúdo
        // Padrões: href="/post?id=X", href="post.php?id=X", href="/slug", links relativos
        preg_match_all('/href=["\']([^"\']*)["\']/', $content, $matches);
        
        foreach ($matches[1] as $link) {
            // Links para posts por ID
            if (preg_match('/post\.php\?id=(\d+)|post\?id=(\d+)|\/post\/(\d+)/', $link, $m)) {
                $targetId = $m[1] ?: $m[2] ?: $m[3];
                if (isset($idToTitle[$targetId]) && $targetId != $post['id']) {
                    $linksMap[$post['id']]['outgoing_links'][] = [
                        'id' => $targetId,
                        'title' => $idToTitle[$targetId]
                    ];
                    $mostLinked[$targetId]++;
                }
            }
            
            // Links para posts por slug
            foreach ($slugToId as $slug => $id) {
                if (strpos($link, $slug) !== false && $id != $post['id']) {
                    $linksMap[$post['id']]['outgoing_links'][] = [
                        'id' => $id,
                        'title' => $idToTitle[$id]
                    ];
                    $mostLinked[$id]++;
                }
            }
            
            // Links caasexpresss.com (externos para o próprio site)
            if (strpos($link, 'caasexpresss.com') !== false) {
                $linksMap[$post['id']]['outgoing_links'][] = [
                    'id' => 'external-self',
                    'title' => 'Link externo para próprio site: ' . $link,
                    'url' => $link
                ];
            }
        }
    }
    
    // Calcular links recebidos (incoming)
    foreach ($linksMap as $postId => &$data) {
        foreach ($linksMap as $otherId => $otherData) {
            if ($otherId == $postId) continue;
            foreach ($otherData['outgoing_links'] as $outLink) {
                if (isset($outLink['id']) && $outLink['id'] == $postId) {
                    $data['incoming_links'][] = [
                        'id' => $otherId,
                        'title' => $idToTitle[$otherId]
                    ];
                }
            }
        }
    }
    
    // Identificar páginas órfãs (sem backlinks)
    foreach ($linksMap as $postId => $data) {
        if (empty($data['incoming_links'])) {
            $orphanPages[] = [
                'id' => $postId,
                'title' => $data['title'],
                'slug' => $data['slug']
            ];
        }
    }
    
    // Ordenar mais linkados
    arsort($mostLinked);
    $topLinked = array_slice($mostLinked, 0, 10, true);
    
} catch (Exception $e) {
    die('Erro: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapeamento de Backlinks Internos | Caas Express</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', -apple-system, sans-serif; 
            background: #0a0a0a; 
            color: #e5e5e5;
            line-height: 1.6;
            padding: 2rem;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { 
            font-size: 2rem; 
            margin-bottom: 1rem; 
            color: #fff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        h1::before { content: '🔗'; }
        h2 { 
            font-size: 1.25rem; 
            margin: 2rem 0 1rem; 
            color: #E63946;
            border-bottom: 1px solid #333;
            padding-bottom: 0.5rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
        }
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #E63946;
        }
        .stat-card .label { color: #888; font-size: 0.875rem; }
        .warning { background: #2d1f1f; border-color: #E63946; }
        .success { background: #1f2d1f; border-color: #4ade80; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 2rem;
            background: #1a1a1a;
            border-radius: 8px;
            overflow: hidden;
        }
        th, td { 
            padding: 0.75rem 1rem; 
            text-align: left; 
            border-bottom: 1px solid #333;
        }
        th { 
            background: #222; 
            color: #E63946; 
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
        }
        tr:hover { background: #222; }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-danger { background: #7f1d1d; color: #fca5a5; }
        .badge-success { background: #14532d; color: #86efac; }
        .badge-info { background: #1e3a5f; color: #93c5fd; }
        a { color: #60a5fa; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .links-list { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 0.5rem; 
        }
        .link-tag {
            background: #2d2d44;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
        }
        .orphan-alert { 
            background: linear-gradient(135deg, #7f1d1d 0%, #450a0a 100%);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .back-link { 
            display: inline-block;
            margin-bottom: 1rem;
            color: #888;
        }
        .export-btn {
            background: #E63946;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        .export-btn:hover { background: #c62e3a; }
    </style>
</head>
<body>
    <div class="container">
        <a href="../admin.php" class="back-link">← Voltar ao Admin</a>
        
        <h1>Mapeamento de Backlinks Internos</h1>
        <p style="color: #888; margin-bottom: 2rem;">Análise completa de links internos entre os posts do blog</p>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?= $totalPosts ?></div>
                <div class="label">Total de Posts</div>
            </div>
            <div class="stat-card <?= count($orphanPages) > 0 ? 'warning' : 'success' ?>">
                <div class="number"><?= count($orphanPages) ?></div>
                <div class="label">Páginas Órfãs (sem backlinks)</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= array_sum($mostLinked) ?></div>
                <div class="label">Total de Links Internos</div>
            </div>
            <div class="stat-card success">
                <div class="number"><?= $totalPosts - count($orphanPages) ?></div>
                <div class="label">Posts com Backlinks</div>
            </div>
        </div>
        
        <?php if (!empty($orphanPages)): ?>
        <h2>⚠️ Páginas Órfãs (Precisam de Backlinks)</h2>
        <div class="orphan-alert">
            <p><strong>Atenção:</strong> Estas páginas não têm nenhum link interno apontando para elas. Isso prejudica o SEO e a descoberta pelo Google.</p>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Slug</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orphanPages as $orphan): ?>
                <tr>
                    <td><?= $orphan['id'] ?></td>
                    <td><strong><?= htmlspecialchars($orphan['title']) ?></strong></td>
                    <td><code><?= htmlspecialchars($orphan['slug']) ?></code></td>
                    <td><a href="<?= BASE_URL . '/' . getSlugById($pdo, $orphan['id']) ?>" target="_blank">Ver Post</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <h2>🏆 Posts Mais Linkados</h2>
        <table>
            <thead>
                <tr>
                    <th>Posição</th>
                    <th>Post</th>
                    <th>Backlinks Recebidos</th>
                </tr>
            </thead>
            <tbody>
                <?php $pos = 1; foreach ($topLinked as $id => $count): ?>
                <tr>
                    <td><span class="badge badge-info">#<?= $pos++ ?></span></td>
                    <td><a href="<?= BASE_URL . '/' . getSlugById($pdo, $id) ?>"><?= htmlspecialchars($idToTitle[$id] ?? 'Post #'.$id) ?></a></td>
                    <td><span class="badge badge-success"><?= $count ?> links</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>📊 Mapa Completo de Links</h2>
        <table>
            <thead>
                <tr>
                    <th>Post</th>
                    <th>Links de Saída</th>
                    <th>Backlinks Recebidos</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($linksMap as $postId => $data): ?>
                <tr>
                    <td>
                        <a href="<?= BASE_URL . '/' . getSlugById($pdo, $postId) ?>"><?= htmlspecialchars($data['title']) ?></a>
                    </td>
                    <td>
                        <?php if (empty($data['outgoing_links'])): ?>
                            <span class="badge badge-danger">Nenhum</span>
                        <?php else: ?>
                            <div class="links-list">
                                <?php foreach (array_slice($data['outgoing_links'], 0, 5) as $link): ?>
                                    <span class="link-tag"><?= htmlspecialchars(substr($link['title'] ?? '', 0, 30)) ?>...</span>
                                <?php endforeach; ?>
                                <?php if (count($data['outgoing_links']) > 5): ?>
                                    <span class="badge badge-info">+<?= count($data['outgoing_links']) - 5 ?> mais</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (empty($data['incoming_links'])): ?>
                            <span class="badge badge-danger">Órfão</span>
                        <?php else: ?>
                            <span class="badge badge-success"><?= count($data['incoming_links']) ?> backlinks</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        $outCount = count($data['outgoing_links']);
                        $inCount = count($data['incoming_links']);
                        if ($inCount == 0) echo '<span class="badge badge-danger">Precisa de backlinks</span>';
                        elseif ($outCount == 0) echo '<span class="badge badge-info">Sem links de saída</span>';
                        else echo '<span class="badge badge-success">OK</span>';
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>💡 Recomendações SEO</h2>
        <ul style="list-style: none; padding: 0;">
            <li style="padding: 0.5rem 0; border-bottom: 1px solid #333;">
                ✅ <strong>Adicione backlinks às páginas órfãs</strong> - Inclua links para elas em posts relacionados
            </li>
            <li style="padding: 0.5rem 0; border-bottom: 1px solid #333;">
                ✅ <strong>Use anchor text descritivo</strong> - Em vez de "clique aqui", use palavras-chave relevantes
            </li>
            <li style="padding: 0.5rem 0; border-bottom: 1px solid #333;">
                ✅ <strong>Crie posts de pilar</strong> - Conteúdos principais que linkam para vários posts relacionados
            </li>
            <li style="padding: 0.5rem 0;">
                ✅ <strong>Atualize posts antigos</strong> - Adicione links para novos conteúdos relevantes
            </li>
        </ul>
        
        <p style="margin-top: 2rem; color: #666; font-size: 0.875rem;">
            Última análise: <?= date('d/m/Y H:i:s') ?>
        </p>
    </div>
</body>
</html>
