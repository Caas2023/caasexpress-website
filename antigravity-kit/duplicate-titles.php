<?php
/**
 * Detector e Corretor de Títulos Duplicados
 * Identifica posts com títulos repetidos e permite reescrevê-los
 */

require_once __DIR__ . '/../src/Config/Database.php';
require_once __DIR__ . '/../src/Utils/Response.php';
require_once __DIR__ . '/../src/Utils/Auth.php';
use Src\Utils\Auth;
use Src\Config\Database;

Auth::check();

header('Content-Type: text/html; charset=utf-8');

$pdo = Database::getInstance();
$message = '';
$messageType = '';

// Processar atualização de título
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_title') {
        $postId = (int)$_POST['post_id'];
        $newTitle = trim($_POST['new_title']);
        $newSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $newTitle));
        $newSlug = trim($newSlug, '-');
        
        if ($postId && $newTitle) {
            $stmt = $pdo->prepare("UPDATE posts SET title = ?, slug = ?, updated_at = datetime('now', 'localtime') WHERE id = ?");
            $stmt->execute([$newTitle, $newSlug, $postId]);
            $message = "Título do post #$postId atualizado com sucesso!";
            $messageType = 'success';
        }
    }
    
    if ($_POST['action'] === 'auto_fix_all') {
        // Corrigir todos automaticamente
        $fixed = autoFixDuplicates($pdo);
        $message = "$fixed títulos foram atualizados automaticamente!";
        $messageType = 'success';
    }
}

// Função para corrigir duplicatas automaticamente
function autoFixDuplicates($pdo) {
    $duplicates = findDuplicates($pdo);
    $fixed = 0;
    
    foreach ($duplicates as $title => $posts) {
        // Pular o primeiro (manter original)
        for ($i = 1; $i < count($posts); $i++) {
            $post = $posts[$i];
            $newTitle = generateUniqueTitle($post['title'], $post['id'], $i);
            $newSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $newTitle));
            $newSlug = trim($newSlug, '-');
            
            $stmt = $pdo->prepare("UPDATE posts SET title = ?, slug = ?, updated_at = datetime('now', 'localtime') WHERE id = ?");
            $stmt->execute([$newTitle, $newSlug, $post['id']]);
            $fixed++;
        }
    }
    
    return $fixed;
}

// Gerar título único baseado no conteúdo
function generateUniqueTitle($originalTitle, $postId, $index) {
    // Variações para tornar único
    $suffixes = [
        ' - Guia Completo',
        ' - Dicas e Estratégias', 
        ' - Tudo que Você Precisa Saber',
        ' - Passo a Passo',
        ' - Como Funciona',
        ' - Benefícios e Vantagens',
        ' - O Guia Definitivo',
        ' - Saiba Mais'
    ];
    
    $suffixIndex = ($index - 1) % count($suffixes);
    return trim($originalTitle) . $suffixes[$suffixIndex];
}

// Encontrar títulos duplicados
function findDuplicates($pdo) {
    $stmt = $pdo->query("
        SELECT title, COUNT(*) as count 
        FROM posts 
        WHERE type = 'post' 
        GROUP BY title 
        HAVING count > 1 
        ORDER BY count DESC
    ");
    $duplicateTitles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $duplicates = [];
    foreach ($duplicateTitles as $dup) {
        $postsStmt = $pdo->prepare("SELECT id, title, slug, created_at FROM posts WHERE title = ? ORDER BY created_at ASC");
        $postsStmt->execute([$dup['title']]);
        $duplicates[$dup['title']] = $postsStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $duplicates;
}

$duplicates = findDuplicates($pdo);
$totalDuplicates = array_sum(array_map(fn($posts) => count($posts) - 1, $duplicates));

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Títulos Duplicados | Caas Express Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', -apple-system, sans-serif; 
            background: #0a0a0a; 
            color: #e5e5e5;
            line-height: 1.6;
            padding: 2rem;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { 
            font-size: 2rem; 
            margin-bottom: 0.5rem; 
            color: #fff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        h1::before { content: '📝'; }
        .subtitle { color: #888; margin-bottom: 2rem; }
        h2 { 
            font-size: 1.25rem; 
            margin: 2rem 0 1rem; 
            color: #E63946;
        }
        .stats-bar {
            display: flex;
            gap: 2rem;
            background: #1a1a1a;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid #333;
        }
        .stat { text-align: center; }
        .stat-number { font-size: 2rem; font-weight: 700; color: #E63946; }
        .stat-label { font-size: 0.75rem; color: #888; text-transform: uppercase; }
        .card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .duplicate-group {
            margin-bottom: 2rem;
            border: 1px solid #333;
            border-radius: 8px;
            overflow: hidden;
        }
        .duplicate-header {
            background: #2d1f1f;
            padding: 1rem;
            border-bottom: 1px solid #333;
        }
        .duplicate-header h3 {
            color: #fca5a5;
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }
        .duplicate-header small { color: #888; }
        .duplicate-item {
            padding: 1rem;
            border-bottom: 1px solid #222;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .duplicate-item:last-child { border-bottom: none; }
        .duplicate-item.original { background: #1a2e1a; }
        .duplicate-item.duplicate { background: #1a1a1a; }
        .item-info { flex: 1; }
        .item-info .id { color: #888; font-size: 0.75rem; }
        .item-info .title { color: #fff; font-weight: 500; }
        .item-info .date { color: #666; font-size: 0.75rem; }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-original { background: #14532d; color: #86efac; }
        .badge-duplicate { background: #7f1d1d; color: #fca5a5; }
        .form-inline {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        input[type="text"] {
            padding: 0.5rem;
            background: #0a0a0a;
            border: 1px solid #333;
            border-radius: 4px;
            color: #fff;
            width: 300px;
        }
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            border: none;
            font-size: 0.875rem;
        }
        .btn-primary { background: #E63946; color: white; }
        .btn-primary:hover { background: #c62e3a; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-lg { padding: 0.75rem 1.5rem; font-size: 1rem; }
        .message {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .message-success { background: #14532d; color: #86efac; border: 1px solid #22c55e; }
        .message-error { background: #7f1d1d; color: #fca5a5; border: 1px solid #ef4444; }
        a { color: #60a5fa; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .back-link { 
            display: inline-block;
            margin-bottom: 1rem;
            color: #888;
        }
        .success-box {
            background: #14532d;
            border: 1px solid #22c55e;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
        }
        .success-box h2 { color: #86efac; margin-bottom: 0.5rem; }
        .warning-box {
            background: #78350f;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .suggestion {
            background: #1e3a5f;
            padding: 0.5rem;
            border-radius: 4px;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #93c5fd;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../admin.php" class="back-link">← Voltar ao Admin</a>
        
        <h1>Títulos Duplicados</h1>
        <p class="subtitle">Identifique e corrija posts com títulos repetidos</p>
        
        <?php if ($message): ?>
        <div class="message message-<?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="stats-bar">
            <div class="stat">
                <div class="stat-number"><?= count($duplicates) ?></div>
                <div class="stat-label">Títulos Duplicados</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?= $totalDuplicates ?></div>
                <div class="stat-label">Posts Afetados</div>
            </div>
        </div>
        
        <?php if (empty($duplicates)): ?>
        <div class="success-box">
            <h2>✅ Nenhum título duplicado!</h2>
            <p>Todos os seus posts têm títulos únicos.</p>
        </div>
        <?php else: ?>
        
        <div class="warning-box">
            <strong>⚠️ Títulos duplicados prejudicam SEO!</strong>
            <p>O Google pode considerar conteúdo duplicado. Corrija os títulos abaixo.</p>
        </div>
        
        <form method="POST" style="margin-bottom: 2rem;">
            <input type="hidden" name="action" value="auto_fix_all">
            <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('Corrigir automaticamente <?= $totalDuplicates ?> títulos duplicados?')">
                🚀 Corrigir Todos Automaticamente
            </button>
            <small style="color: #888; margin-left: 1rem;">Adiciona sufixos como "Guia Completo", "Passo a Passo" etc.</small>
        </form>
        
        <?php foreach ($duplicates as $title => $posts): ?>
        <div class="duplicate-group">
            <div class="duplicate-header">
                <h3>"<?= htmlspecialchars($title) ?>"</h3>
                <small><?= count($posts) ?> posts com este título</small>
            </div>
            
            <?php foreach ($posts as $index => $post): ?>
            <div class="duplicate-item <?= $index === 0 ? 'original' : 'duplicate' ?>">
                <div class="item-info">
                    <div class="id">ID: <?= $post['id'] ?></div>
                    <div class="title"><?= htmlspecialchars($post['title']) ?></div>
                    <div class="date">Criado: <?= date('d/m/Y', strtotime($post['created_at'])) ?></div>
                    <?php if ($index > 0): ?>
                    <div class="suggestion">
                        💡 Sugestão: <?= htmlspecialchars(generateUniqueTitle($post['title'], $post['id'], $index)) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if ($index === 0): ?>
                    <span class="badge badge-original">✓ Manter Original</span>
                    <?php else: ?>
                    <form method="POST" class="form-inline">
                        <input type="hidden" name="action" value="update_title">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <input type="text" name="new_title" placeholder="Novo título..." value="<?= htmlspecialchars(generateUniqueTitle($post['title'], $post['id'], $index)) ?>">
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
        
        <p style="margin-top: 2rem; color: #666; font-size: 0.875rem;">
            Última análise: <?= date('d/m/Y H:i:s') ?>
        </p>
    </div>
</body>
</html>
