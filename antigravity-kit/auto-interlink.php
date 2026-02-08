<?php
/**
 * Auto-Interlinking Tool
 * Automatiza a criação de links internos baseado em posts pilar
 * 
 * Funcionalidades:
 * 1. Define até 5 posts pilar com palavras-chave
 * 2. Analisa todos os artigos
 * 3. Encontra palavras âncora candidatas
 * 4. Insere links internos automaticamente
 */

require_once __DIR__ . '/../src/Config/Database.php';
require_once __DIR__ . '/../src/Utils/Response.php';
require_once __DIR__ . '/../src/Utils/Auth.php';
use Src\Utils\Auth;
use Src\Config\Database;
require_once __DIR__ . '/../src/Services/Pollinations.php';
use Src\Services\PollinationsService;

Auth::check();

header('Content-Type: text/html; charset=utf-8');

session_start();

$pdo = Database::getInstance();
$message = '';
$messageType = '';

// URL Base do site (produção)
if (!defined('BASE_URL')) define('BASE_URL', 'https://caasexpresss.com');

// Helper: Buscar Slug por ID
function getSlugById($pdo, $id) {
    static $cache = [];
    if (isset($cache[$id])) return $cache[$id];
    
    $stmt = $pdo->prepare("SELECT slug FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $slug = $stmt->fetchColumn();
    $cache[$id] = $slug ?: $id;
    return $cache[$id];
}

// Carregar configuração de pilares do banco (criar tabela se não existir)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS pillar_posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        post_id INTEGER NOT NULL,
        keywords TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {
    // Tabela já existe, ok
}

// Processar formulário de adicionar pilar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_pillar') {
        $postId = (int)$_POST['post_id'];
        $keywords = trim($_POST['keywords']);
        
        if ($postId && $keywords) {
            // Verificar se já existe
            $check = $pdo->prepare("SELECT id FROM pillar_posts WHERE post_id = ?");
            $check->execute([$postId]);
            if ($check->fetch()) {
                // Atualizar
                $stmt = $pdo->prepare("UPDATE pillar_posts SET keywords = ? WHERE post_id = ?");
                $stmt->execute([$keywords, $postId]);
                $message = "Pilar atualizado com sucesso!";
            } else {
                // Verificar limite de 5
                $count = $pdo->query("SELECT COUNT(*) FROM pillar_posts")->fetchColumn();
                if ($count >= 5) {
                    $message = "Limite de 5 posts pilar atingido!";
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO pillar_posts (post_id, keywords) VALUES (?, ?)");
                    $stmt->execute([$postId, $keywords]);
                    $message = "Pilar adicionado com sucesso!";
                }
            }
            $messageType = $messageType ?: 'success';
        }
    }
    
    if ($action === 'remove_pillar') {
        $pillarId = (int)$_POST['pillar_id'];
        $stmt = $pdo->prepare("DELETE FROM pillar_posts WHERE id = ?");
        $stmt->execute([$pillarId]);
        $message = "Pilar removido!";
        $messageType = 'success';
    }
    
    // AI Semantic Expansion
    if ($action === 'expand_keywords') {
        $pillarId = (int)$_POST['pillar_id'];
        $stmt = $pdo->prepare("SELECT keywords FROM pillar_posts WHERE id = ?");
        $stmt->execute([$pillarId]);
        $current = $stmt->fetchColumn();
        
        if ($current) {
            try {
                $ai = new PollinationsService();
                $prompt = "Generate 15 semantic synonyms or variations (short phrases) for these keywords: '$current'. Context: SEO Internal Linking. Language: Portuguese. Return ONLY comma-separated values, no explanation.";
                
                // Corrigido: segundo argumento é o System Prompt, não o modelo.
                // O modelo é pego da configuração.
                $variations = $ai->generateText($prompt, 'You are an SEO expert specialized in semantic nuances.');
                
                if (empty($variations)) {
                    throw new Exception("A IA retornou uma resposta vazia.");
                }
                
                // Limpar e mesclar
                $variations = trim(str_replace(['"', '.', "\n", "Here are", "synonyms"], '', $variations));
                $newKeywords = $current . ', ' . $variations;
                
                // Deduplicar
                $parts = array_map('trim', explode(',', $newKeywords));
                $parts = array_unique(array_filter($parts));
                $finalKeywords = implode(', ', $parts);
                
                $upd = $pdo->prepare("UPDATE pillar_posts SET keywords = ? WHERE id = ?");
                $upd->execute([$finalKeywords, $pillarId]);
                
                $message = "IA expandiu as keywords com sucesso! (+ " . count($parts) . " variações)";
                $messageType = 'success';
            } catch (Exception $e) {
                $message = "Erro na IA: " . $e->getMessage();
                $messageType = 'error';
                // Adicionar debug visual se possível
                $message .= " (Verifique suas chaves de API em Configurações)";
            }
        }
    }
    

// Lógica AI Auto-Link
    if ($action === 'apply_links') {
        // Aplicar links automaticamente
        $applied = applyAutoLinks($pdo);
        $message = "Links aplicados! $applied artigos foram atualizados.";
        $messageType = 'success';
    }

    // Lógica AI Auto-Link
    if ($action === 'ai_auto_link') {
        $applied = applyAiAutoLinks($pdo);
        $message = "🤖 AI aplicou $applied links inteligentes em todo o site!";
        $messageType = 'success';
    }
}

// ... funções existentes ...

// Função para preview de links (Modo Manual)
function previewAutoLinks($pdo) {
    $pillars = $pdo->query("SELECT pp.*, p.title, p.slug FROM pillar_posts pp JOIN posts p ON pp.post_id = p.id")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pillars)) return [];
    
    $pillarIds = array_map(fn($p) => $p['post_id'], $pillars);
    $placeholders = implode(',', array_fill(0, count($pillarIds), '?'));
    $stmt = $pdo->prepare("SELECT id, title, content FROM posts WHERE id NOT IN ($placeholders) AND status = 'publish'");
    $stmt->execute($pillarIds);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $suggestions = [];
    
    foreach ($posts as $post) {
        $content = strip_tags($post['content']);
        
        foreach ($pillars as $pillar) {
            $keywords = array_map('trim', explode(',', $pillar['keywords']));
            
            foreach ($keywords as $keyword) {
                if (empty($keyword)) continue;
                
                // Já tem link?
                $pillarSlug = getSlugById($pdo, $pillar['post_id']);
                if (strpos($post['content'], $pillarSlug) !== false) {
                    continue;
                }
                
                // Regex melhorada: Case insensitive, Word Boundary UTF-8 e ignora HTML tags
                // A regex antiga \b não funciona bem com caracteres acentuados em PHP/PCRE padrão sem 'u' modifier correto
                // Vamos usar \b mas com modificador utf-8 e talvez alternar para (?<=^|\s) se necessário
                
                $kw = preg_quote($keyword, '/');
                $pattern = '/(?<=^|[\s\.,;!?\(\)])(' . $kw . ')(?=[\s\.,;!?\(\)]|$)(?![^<]*>)/iu';
                
                // Buscar keyword
                if (preg_match($pattern, $content)) {
                    $suggestions[] = [
                        'post_id' => $post['id'],
                        'post_title' => $post['title'],
                        'keyword' => $keyword,
                        'pillar_id' => $pillar['post_id'],
                        'pillar_title' => $pillar['title']
                    ];
                    break; // Uma sugestão por pilar por post
                }
            }
        }
    }
    
    return $suggestions;
}

// Função AI: Analisa TODOS os posts e gera links baseados em títulos
function previewAiLinks($pdo) {
    // 1. Carregar todos os posts (Título e ID)
    $allPosts = $pdo->query("SELECT id, title, slug FROM posts WHERE status = 'publish' AND type = 'post'")->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Carregar conteúdo de todos os posts para análise
    $contents = $pdo->query("SELECT id, title, content FROM posts WHERE status = 'publish' AND type = 'post'")->fetchAll(PDO::FETCH_ASSOC);
    
    $suggestions = [];
    $stopWords = ['como', 'para', 'pelo', 'pela', 'onde', 'qual', 'quem', 'entao', 'pois', 'porque', 'sobre', 'apos', 'antes', 'guia', 'dicas', 'tudo', 'voce', 'precisa', 'saber', 'passo'];

    // 3. Mapear palavras-chave (baseadas em títulos)
    $keywordMap = [];
    foreach ($allPosts as $target) {
        // Keyword 1: Título exato (muito forte)
        $keywordMap[] = [
            'keyword' => $target['title'],
            'target_id' => $target['id'],
            'target_title' => $target['title'],
            'score' => 100
        ];
        
        // Keyword 2: Título limpo (sem stop words, se tiver > 2 palavras)
        $words = preg_split('/\s+/', strtolower($target['title']));
        $cleanWords = array_diff($words, $stopWords);
        if (count($cleanWords) >= 2) {
            $shortTitle = implode(' ', $cleanWords);
            if (strlen($shortTitle) > 10) { // Evitar keywords muito curtas
                $keywordMap[] = [
                    'keyword' => $shortTitle,
                    'target_id' => $target['id'],
                    'target_title' => $target['title'],
                    'score' => 80
                ];
            }
        }
    }

    // 4. Analisar conteúdos
    foreach ($contents as $source) {
        $content = strip_tags($source['content']);
        $sourceId = $source['id'];
        
        // Limite: max 3 links AI por post
        $linksFound = 0;
        
        foreach ($keywordMap as $map) {
            if ($linksFound >= 3) break;
            
            // Não linkar para si mesmo
            if ($map['target_id'] == $sourceId) continue;
            
            // Verificar se já tem link para esse target
            $targetSlug = getSlugById($pdo, $map['target_id']);
            if (strpos($source['content'], $targetSlug) !== false) continue;
            
            // Regex para garantir palavra inteira (Word Boundary)
            // A mesma regex usada em applyAiAutoLinks para garantir consistência
            $pattern = '/\b(' . preg_quote($map['keyword'], '/') . ')\b(?![^<]*>)/iu';
            
            if (preg_match($pattern, $content)) {
                $suggestions[] = [
                    'source_id' => $sourceId,
                    'source_title' => $source['title'],
                    'keyword' => $map['keyword'],
                    'target_id' => $map['target_id'],
                    'target_title' => $map['target_title'],
                    'score' => $map['score']
                ];
                $linksFound++;
            }
        }
    }
    
    // Ordenar por score
    usort($suggestions, fn($a, $b) => $b['score'] <=> $a['score']);
    
    return array_slice($suggestions, 0, 100); // Retornar top 100 sugestões para preview
}

function applyAiAutoLinks($pdo) {
    // Mesma lógica do preview, mas aplicando update
    // Simplificado para performance: reusa lógica
    $suggestions = previewAiLinks($pdo); 
    $count = 0;
    
    foreach ($suggestions as $s) {
        $stmt = $pdo->prepare("SELECT content FROM posts WHERE id = ?");
        $stmt->execute([$s['source_id']]);
        $post = $stmt->fetch();
        
        if ($post) {
            $content = $post['content'];
            $pattern = '/\b(' . preg_quote($s['keyword'], '/') . ')\b(?![^<]*>)/iu';
            
            if (preg_match($pattern, $content)) {
                $targetSlug = getSlugById($pdo, $s['target_id']);
                $link = '<a href="' . BASE_URL . '/' . $targetSlug . '" title="' . htmlspecialchars($s['target_title']) . '">' . '$1' . '</a>';
                $newContent = preg_replace($pattern, $link, $content, 1);
                
                $upd = $pdo->prepare("UPDATE posts SET content = ?, updated_at = datetime('now', 'localtime') WHERE id = ?");
                $upd->execute([$newContent, $s['source_id']]);
                $count++;
            }
        }
    }
    return $count;
}

// MODO MANUAL: Aplicar Links
function applyAutoLinks($pdo) {
    $suggestions = previewAutoLinks($pdo);
    $count = 0;
    
    foreach ($suggestions as $s) {
        $stmt = $pdo->prepare("SELECT content FROM posts WHERE id = ?");
        $stmt->execute([$s['post_id']]);
        $post = $stmt->fetch();
        
        if ($post) {
            $content = $post['content'];
            // Regex melhorada (mesma do preview)
            $kw = preg_quote($s['keyword'], '/');
            $pattern = '/(?<=^|[\s\.,;!?\(\)])(' . $kw . ')(?=[\s\.,;!?\(\)]|$)(?![^<]*>)/iu';
            
            // Só aplica se ainda não tiver link
            $pillarSlug = getSlugById($pdo, $s['pillar_id']);
            if (strpos($content, $pillarSlug) === false && preg_match($pattern, $content)) {
                $link = '<a href="' . BASE_URL . '/' . $pillarSlug . '" title="' . htmlspecialchars($s['pillar_title']) . '">' . '$1' . '</a>';
                // Substitui apenas a primeira ocorrência (limit=1)
                $newContent = preg_replace($pattern, $link, $content, 1);
                
                $upd = $pdo->prepare("UPDATE posts SET content = ?, updated_at = datetime('now', 'localtime') WHERE id = ?");
                $upd->execute([$newContent, $s['post_id']]);
                $count++;
            }
        }
    }
    return $count;
}

// Carregar dados normais
$allPosts = $pdo->query("SELECT id, title FROM posts WHERE status = 'publish' ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
$pillars = $pdo->query("SELECT pp.*, p.title FROM pillar_posts pp JOIN posts p ON pp.post_id = p.id")->fetchAll(PDO::FETCH_ASSOC);

// Inicializar variáveis para evitar "Undefined variable"
$suggestions = [];
$aiSuggestions = [];

$mode = $_GET['mode'] ?? 'manual';
if ($mode === 'ai') {
    $aiSuggestions = previewAiLinks($pdo);
    $activeCount = count($aiSuggestions);
} else {
    $suggestions = previewAutoLinks($pdo);
    $activeCount = count($suggestions);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto-Interlinking | Caas Express Admin</title>
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
        h1::before { content: '🔗'; }
        .subtitle { color: #888; margin-bottom: 2rem; }
        h2 { 
            font-size: 1.25rem; 
            margin: 2rem 0 1rem; 
            color: #E63946;
            border-bottom: 1px solid #333;
            padding-bottom: 0.5rem;
        }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 1.5rem;
        }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; color: #888; font-size: 0.875rem; }
        select, input, textarea {
            width: 100%;
            padding: 0.75rem;
            background: #0a0a0a;
            border: 1px solid #333;
            border-radius: 6px;
            color: #fff;
            font-size: 1rem;
        }
        select:focus, input:focus, textarea:focus {
            outline: none;
            border-color: #E63946;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            border: none;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .btn-primary { background: #E63946; color: white; }
        .btn-primary:hover { background: #c62e3a; transform: translateY(-2px); }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: #7f1d1d; color: #fca5a5; }
        .btn-danger:hover { background: #991b1b; }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.875rem; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
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
        .badge-primary { background: #1e3a5f; color: #93c5fd; }
        .badge-success { background: #14532d; color: #86efac; }
        .badge-warning { background: #78350f; color: #fcd34d; }
        a { color: #60a5fa; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .message {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .message-success { background: #14532d; color: #86efac; border: 1px solid #22c55e; }
        .message-error { background: #7f1d1d; color: #fca5a5; border: 1px solid #ef4444; }
        .info-box {
            background: #1e3a5f;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .info-box h4 { color: #93c5fd; margin-bottom: 0.5rem; }
        .info-box p { color: #cbd5e1; font-size: 0.875rem; }
        .back-link { 
            display: inline-block;
            margin-bottom: 1rem;
            color: #888;
        }
        .arrow { font-size: 1.5rem; color: #E63946; }
        .pillar-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #222;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 0.5rem;
        }
        .pillar-info h4 { color: #fff; margin-bottom: 0.25rem; }
        .pillar-keywords { color: #888; font-size: 0.875rem; }
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
    </style>
</head>
<body>
    <div class="container">
        <a href="../admin.php" class="back-link">← Voltar ao Admin</a>
        
        <h1>Auto-Interlinking</h1>
        <p class="subtitle">Automatize a criação de links internos baseado em posts pilar</p>
        
        <?php if ($message): ?>
        <div class="message message-<?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>
        
        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat">
                <div class="stat-number"><?= count($pillars) ?>/5</div>
                <div class="stat-label">Posts Pilar</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?= $activeCount ?></div>
                <div class="stat-label">Links Sugeridos</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?= count($allPosts) ?></div>
                <div class="stat-label">Total de Posts</div>
            </div>
        </div>
        
        <div class="tabs" style="margin-bottom: 2rem; border-bottom: 1px solid #333; display: flex; gap: 1rem;">
            <a href="?mode=manual" class="tab-link <?= $mode === 'manual' ? 'active' : '' ?>" style="padding: 1rem; color: #fff; text-decoration: none; border-bottom: 3px solid <?= $mode === 'manual' ? '#E63946' : 'transparent' ?>">🛠️ Manual (Posts Pilar)</a>
            <a href="?mode=ai" class="tab-link <?= $mode === 'ai' ? 'active' : '' ?>" style="padding: 1rem; color: #fff; text-decoration: none; border-bottom: 3px solid <?= $mode === 'ai' ? '#10b981' : 'transparent' ?>">🤖 IA Auto-Link (Automático)</a>
        </div>

        <?php if ($mode === 'ai'): ?>
            <!-- MODO AI -->
            <div class="ai-mode">
                <div class="info-box" style="background: #1e293b; border-color: #3b82f6;">
                    <h4>🤖 Como a IA funciona?</h4>
                    <p>O sistema analisou todos os seus articles e detectou automaticamente oportunidades de linkagem baseada nos títulos dos seus posts. Ele identifica menções naturais e sugere links.</p>
                </div>

                <?php if (!empty($aiSuggestions)): ?>
                    <form method="POST" style="margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; background: #1a1a1a; padding: 1rem; border-radius: 8px; border: 1px solid #333;">
                        <div>
                            <strong style="color: #10b981; font-size: 1.25rem;"><?= count($aiSuggestions) ?> sugestões encontradas</strong>
                            <p style="color: #888; font-size: 0.875rem;">Baseado em análise semântica de títulos</p>
                        </div>
                        <input type="hidden" name="action" value="ai_auto_link">
                        <button type="submit" class="btn btn-success" style="background: #10b981;" onclick="return confirm('Aplicar essas <?= count($aiSuggestions) ?> sugestões automaticamente?')">
                            ✨ Aplicar Sugestões da IA
                        </button>
                    </form>

                    <table>
                        <thead>
                            <tr>
                                <th>Artigo Origem</th>
                                <th style="text-align: center;">Score</th>
                                <th>Link Sugerido Para (Destino)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($aiSuggestions as $s): ?>
                            <tr>
                                <td>
                                    <a href="<?= BASE_URL . '/' . getSlugById($pdo, $s['source_id']) ?>" target="_blank" style="color: #cbd5e1;">
                                        <?= htmlspecialchars(substr($s['source_title'], 0, 40)) ?>...
                                    </a>
                                    <br>
                                    <small style="color: #64748b;">Âncora encontrada: <span class="badge" style="background: #334155; color: #fff;"><?= htmlspecialchars($s['keyword']) ?></span></small>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge" style="background: <?= $s['score'] >= 90 ? '#14532d' : '#854d0e' ?>; color: #fff;">
                                        <?= $s['score'] ?>%
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL . '/' . getSlugById($pdo, $s['target_id']) ?>" target="_blank" style="color: #10b981; font-weight: bold;">
                                        <?= htmlspecialchars(substr($s['target_title'], 0, 40)) ?>...
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="card" style="text-align: center; padding: 3rem;">
                        <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">🤔</span>
                        <h3 style="color: #fff; margin-bottom: 0.5rem;">Nenhuma sugestão encontrada no momento</h3>
                        <p style="color: #888;">Seus posts já parecem estar bem linkados ou não fomos capazes de encontrar conexões óbvias baseadas nos títulos.</p>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- MODO MANUAL -->
            <div class="grid-2">
                <h2>📌 Posts Pilar (<?= count($pillars) ?>/5)</h2>
                
                <div class="info-box">
                    <h4>💡 O que são Posts Pilar?</h4>
                    <p>São seus conteúdos principais. Defina palavras-chave e o sistema vai automaticamente criar links para esses posts quando encontrar essas palavras em outros artigos.</p>
                </div>
                
                <?php if (count($pillars) < 5): ?>
                <div class="card">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_pillar">
                        
                        <div class="form-group">
                            <label>Selecione o Post Pilar</label>
                            <select name="post_id" required>
                                <option value="">-- Escolha um post --</option>
                                <?php foreach ($allPosts as $post): ?>
                                    <?php 
                                    $isPillar = in_array($post['id'], array_column($pillars, 'post_id'));
                                    if (!$isPillar):
                                    ?>
                                    <option value="<?= $post['id'] ?>"><?= htmlspecialchars($post['title']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Palavras-chave (separadas por vírgula)</label>
                            <textarea name="keywords" rows="3" placeholder="motoboy, entrega rápida, motofrete, courier" required></textarea>
                            <small style="color: #666;">Ex: motoboy, entrega expressa, motofrete</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">➕ Adicionar Pilar</button>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- Lista de Pilares -->
                <?php if (!empty($pillars)): ?>
                <div style="margin-top: 1.5rem;">
                    <?php foreach ($pillars as $pillar): ?>
                    <div class="pillar-card">
                        <div class="pillar-info">
                            <h4><?= htmlspecialchars($pillar['title']) ?></h4>
                            <div class="pillar-keywords">
                                <?php 
                                $keywords = array_map('trim', explode(',', $pillar['keywords']));
                                foreach ($keywords as $kw): 
                                ?>
                                <span class="badge badge-primary"><?= htmlspecialchars($kw) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="remove_pillar">
                            <input type="hidden" name="pillar_id" value="<?= $pillar['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Remover este pilar?')">✕</button>
                        </form>
                        
                        <form method="POST" style="display: inline; margin-left: 0.5rem;">
                            <input type="hidden" name="action" value="expand_keywords">
                            <input type="hidden" name="pillar_id" value="<?= $pillar['id'] ?>">
                            <button type="submit" class="btn btn-success btn-sm" title="Usar IA para encontrar sinônimos">⚡ Expandir Keywords</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Coluna 2: Preview e Aplicar -->
            <div>
                <h2>🔍 Preview de Links</h2>
                
                <?php if (!empty($suggestions)): ?>
                <div class="info-box" style="background: #14532d; border-color: #22c55e;">
                    <h4>✅ <?= count($suggestions) ?> links podem ser criados automaticamente!</h4>
                    <p>Clique no botão abaixo para aplicar todos os links de uma vez.</p>
                </div>
                
                <form method="POST" style="margin-bottom: 1.5rem;">
                    <input type="hidden" name="action" value="apply_links">
                    <button type="submit" class="btn btn-success" onclick="return confirm('Aplicar todos os <?= count($suggestions) ?> links automaticamente?')">
                        🚀 Aplicar Todos os Links
                    </button>
                </form>
                
                <table>
                    <thead>
                        <tr>
                            <th>Artigo</th>
                            <th></th>
                            <th>Link Para</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($suggestions, 0, 20) as $s): ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL . '/' . getSlugById($pdo, $s['post_id']) ?>" target="_blank">
                                    <?= htmlspecialchars(substr($s['post_title'], 0, 40)) ?>...
                                </a>
                                <br>
                                <small style="color: #888;">Âncora: <span class="badge badge-warning"><?= htmlspecialchars($s['keyword']) ?></span></small>
                            </td>
                            <td class="arrow">→</td>
                            <td>
                                <a href="<?= BASE_URL . '/' . getSlugById($pdo, $s['pillar_id']) ?>" target="_blank">
                                    <?= htmlspecialchars(substr($s['pillar_title'], 0, 40)) ?>...
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (count($suggestions) > 20): ?>
                <p style="color: #888; margin-top: 1rem;">E mais <?= count($suggestions) - 20 ?> sugestões...</p>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="card">
                    <p style="color: #888; text-align: center; padding: 2rem;">
                        <?php if (empty($pillars)): ?>
                        👈 Primeiro, adicione posts pilar com palavras-chave
                        <?php else: ?>
                        ✅ Todos os artigos já possuem links para os pilares!
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <h2>📊 Como Funciona</h2>
        <div class="card">
            <ol style="padding-left: 1.5rem; color: #888;">
                <li style="margin-bottom: 0.5rem;"><strong style="color: #fff;">Defina 5 Posts Pilar</strong> - Seus conteúdos principais/estratégicos</li>
                <li style="margin-bottom: 0.5rem;"><strong style="color: #fff;">Adicione Palavras-chave</strong> - Palavras que devem virar links</li>
                <li style="margin-bottom: 0.5rem;"><strong style="color: #fff;">Preview</strong> - Veja quais links serão criados</li>
                <li style="margin-bottom: 0.5rem;"><strong style="color: #fff;">Aplique</strong> - Um clique para adicionar todos os links</li>
            </ol>
            <p style="margin-top: 1rem; color: #888;">
                <strong style="color: #E63946;">⚠️ Importante:</strong> O sistema adiciona apenas 1 link por pilar por artigo para evitar over-optimization.
            </p>
        </div>
        <?php endif; ?>
        
        <p style="margin-top: 2rem; color: #666; font-size: 0.875rem;">
            Última análise: <?= date('d/m/Y H:i:s') ?>
        </p>
    </div>
</body>
</html>
