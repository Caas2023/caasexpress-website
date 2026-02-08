<?php
/**
 * Auto-SEO Tool (Powered by Pollinations.ai)
 * Gera automaticamente Meta Description e SEO Title para posts.
 */

// Dependências
require_once __DIR__ . '/../src/Config/Database.php';
require_once __DIR__ . '/../src/Services/Pollinations.php';
require_once __DIR__ . '/../src/Utils/Response.php';
require_once __DIR__ . '/../src/Utils/Auth.php';

use Src\Config\Database;
use Src\Services\PollinationsService;
use Src\Utils\Auth;

// Segurança
Auth::check();

header('Content-Type: text/html; charset=utf-8');

$pdo = Database::getInstance();
$aiInfo = new \Src\Services\PollinationsConfig();
$aiService = new PollinationsService();

$message = '';
$messageType = '';

// API JSON Handlers
if (isset($_GET['action']) && $_GET['action'] === 'list_pending_json') {
    header('Content-Type: application/json');
    try {
        // Buscar TODOS os IDs pendentes (sem limite)
        $ids = $pdo->query("SELECT p.id FROM posts p WHERE p.status = 'publish' AND p.type = 'post' AND NOT EXISTS (SELECT 1 FROM postmeta pm WHERE pm.post_id = p.id AND pm.meta_key = '_yoast_wpseo_metadesc' AND pm.meta_value != '')")->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['ids' => $ids]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Processar Ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Configurar API Key se necessário (reusa config global)
    // ...

    if ($_POST['action'] === 'generate_seo') {
        $postId = (int)$_POST['post_id'];
        $content = $_POST['content_preview'] ?? '';
        
        if ($postId && $content) {
            try {
                // 1. Gerar Meta Description
                // UTF-8 Clean & Safe Truncate
                $cleanContent = preg_replace('/[\x00-\x1F\x7F]/u', '', strip_tags($content)); 
                $cleanContent = mb_substr($cleanContent, 0, 1000, 'UTF-8');
                
                $promptDesc = "Summarize the following text into a compelling SEO Meta Description (max 155 characters). Language: Portuguese. Text: " . $cleanContent;
                $metaDesc = $aiService->generateText($promptDesc, 'openai'); // Usa default ou o que tiver
                
                // 2. Gerar SEO Title
                $cleanContentTitle = mb_substr($cleanContent, 0, 500, 'UTF-8');
                $promptTitle = "Create a catchy, click-worthy SEO Title (max 60 chars) for this text. Language: Portuguese. Text: " . $cleanContentTitle;
                $seoTitle = $aiService->generateText($promptTitle, 'openai');
                
                // Limpar aspas e quebras
                $metaDesc = trim(str_replace(['"', "\n", "\r"], '', $metaDesc));
                $seoTitle = trim(str_replace(['"', "\n", "\r"], '', $seoTitle));

                // Salvar em postmeta
                // Verifica se já existe, se não insert, se sim update
                saveMeta($pdo, $postId, '_yoast_wpseo_metadesc', $metaDesc);
                saveMeta($pdo, $postId, '_yoast_wpseo_title', $seoTitle);
                
                $message = "SEO gerado com sucesso para o Post #$postId!";
                $messageType = 'success';
                
            } catch (Exception $e) {
                $message = "Erro na IA: " . $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    if ($_POST['action'] === 'generate_seo_ajax') {
        header('Content-Type: application/json');
        $postId = (int)$_POST['post_id'];
        
        try {
            // Buscar conteúdo do post
            $stmt = $pdo->prepare("SELECT content FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            $post = $stmt->fetch();
            
            if (!$post) throw new Exception("Post não encontrado");
            
            $content = strip_tags($post['content']);
            if (strlen($content) < 50) throw new Exception("Conteúdo muito curto");
            
            // 1. Gerar Meta Description
            // UTF-8 Safety
            $cleanContent = preg_replace('/[\x00-\x1F\x7F]/u', '', strip_tags($post['content']));
            $cleanContent = mb_substr($cleanContent, 0, 1000, 'UTF-8');
            
            $promptDesc = "Summarize the following text into a compelling SEO Meta Description (max 155 characters). Language: Portuguese. Text: " . $cleanContent;
            $metaDesc = $aiService->generateText($promptDesc, 'openai');
            
            // 2. Gerar SEO Title
            $cleanContentTitle = mb_substr($cleanContent, 0, 500, 'UTF-8');
            $promptTitle = "Create a catchy, click-worthy SEO Title (max 60 chars) for this text. Language: Portuguese. Text: " . $cleanContentTitle;
            $seoTitle = $aiService->generateText($promptTitle, 'openai');
            
            $metaDesc = trim(str_replace(['"', "\n", "\r"], '', $metaDesc));
            $seoTitle = trim(str_replace(['"', "\n", "\r"], '', $seoTitle));
            
            saveMeta($pdo, $postId, '_yoast_wpseo_metadesc', $metaDesc);
            saveMeta($pdo, $postId, '_yoast_wpseo_title', $seoTitle);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'save_config') {
        header('Content-Type: application/json');
        try {
            saveConfig($pdo, 'auto_seo_batch_size', $_POST['batch_size']);
            saveConfig($pdo, 'auto_seo_batch_delay', $_POST['batch_delay']);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    if ($_POST['action'] === 'generate_batch') {
        // Lógica de lote (pode demorar, ideal seria AJAX, mas vamos fazer simples loop 3 por vez para não estourar timeout)
        $limit = 3;
        $count = 0;
        
        $posts = getPostsMissingSeo($pdo, $limit);
        
        foreach ($posts as $post) {
            $content = strip_tags($post['content']);
            if (empty($content)) continue;
            
            try {
                // UTF-8 Clean & Safe Truncate
                $cleanContent = preg_replace('/[\x00-\x1F\x7F]/u', '', $content);
                $cleanContent = mb_substr($cleanContent, 0, 1000, 'UTF-8');

                $promptDesc = "Summarize this into a SEO Meta Description (max 150 chars). Portuguese. Text: " . $cleanContent;
                $metaDesc = $aiService->generateText($promptDesc, 'openai');
                
                $cleanContentTitle = mb_substr($cleanContent, 0, 500, 'UTF-8');
                $promptTitle = "Create a SEO Title (max 60 chars). Portuguese. Text: " . $cleanContentTitle;
                $seoTitle = $aiService->generateText($promptTitle, 'openai');
                
                $metaDesc = trim(str_replace(['"', "\n"], '', $metaDesc));
                $seoTitle = trim(str_replace(['"', "\n"], '', $seoTitle));
                
                saveMeta($pdo, $post['id'], '_yoast_wpseo_metadesc', $metaDesc);
                saveMeta($pdo, $post['id'], '_yoast_wpseo_title', $seoTitle);
                $count++;
            } catch (Exception $e) {
                continue; // Pula se der erro em um
            }
        }
        
        $message = "IA gerou SEO para $count posts automaticamente!";
        $messageType = 'success';
    }
}

// Helpers
function saveMeta($pdo, $postId, $key, $value) {
    // Check exists
    $stmt = $pdo->prepare("SELECT id FROM postmeta WHERE post_id = ? AND meta_key = ?");
    $stmt->execute([$postId, $key]);
    if ($stmt->fetch()) {
        $upd = $pdo->prepare("UPDATE postmeta SET meta_value = ? WHERE post_id = ? AND meta_key = ?");
        $upd->execute([$value, $postId, $key]);
    } else {
        $ins = $pdo->prepare("INSERT INTO postmeta (post_id, meta_key, meta_value) VALUES (?, ?, ?)");
        $ins->execute([$postId, $key, $value]);
    }
}

function getPostsMissingSeo($pdo, $limit = 50) {
    // Busca posts que NÃO têm meta description
    $sql = "SELECT p.id, p.title, p.content 
            FROM posts p 
            WHERE p.status = 'publish' 
            AND p.type = 'post'
            AND NOT EXISTS (
                SELECT 1 FROM postmeta pm 
                WHERE pm.post_id = p.id 
                AND pm.meta_key = '_yoast_wpseo_metadesc'
                AND pm.meta_value != ''
            )
            LIMIT $limit";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

// Carregar dados
$pendingPosts = getPostsMissingSeo($pdo, 20); // Mostrar 20 pendentes
$totalPending = count(getPostsMissingSeo($pdo, 9999));

// Helper: Ler Config do Banco
function getConfig($pdo, $key, $default = null) {
    try {
        $stmt = $pdo->prepare("SELECT config_value FROM ai_config WHERE config_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function saveConfig($pdo, $key, $value) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM ai_config WHERE config_key = ?");
        $stmt->execute([$key]);
        if ($stmt->fetch()) {
            $upd = $pdo->prepare("UPDATE ai_config SET config_value = ? WHERE config_key = ?");
            $upd->execute([$value, $key]);
        } else {
            $ins = $pdo->prepare("INSERT INTO ai_config (config_key, config_value) VALUES (?, ?)");
            $ins->execute([$key, $value]);
        }
    } catch (Exception $e) { /* ignore */ }
}

// Carregar configurações salvas
$savedBatchSize = getConfig($pdo, 'auto_seo_batch_size', 5);
$savedBatchDelay = getConfig($pdo, 'auto_seo_batch_delay', 30); // Em segundos

// Determinar unidade e valor para exibição
$savedDelayUnit = 1; // Default: Segundos
$savedDelayValue = $savedBatchDelay;

if ($savedBatchDelay >= 86400 && $savedBatchDelay % 86400 == 0) {
    $savedDelayUnit = 86400;
    $savedDelayValue = $savedBatchDelay / 86400;
} elseif ($savedBatchDelay >= 3600 && $savedBatchDelay % 3600 == 0) {
    $savedDelayUnit = 3600;
    $savedDelayValue = $savedBatchDelay / 3600;
} elseif ($savedBatchDelay >= 60 && $savedBatchDelay % 60 == 0) {
    $savedDelayUnit = 60;
    $savedDelayValue = $savedBatchDelay / 60;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto-SEO IA | Caas Express</title>
    <style>
        body { font-family: sans-serif; background: #0a0a0a; color: #e5e5e5; padding: 2rem; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #fff; border-bottom: 1px solid #333; padding-bottom: 1rem; }
        .card { background: #1a1a1a; padding: 1.5rem; border-radius: 8px; border: 1px solid #333; margin-bottom: 1.5rem; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; border: none; font-weight: bold; color:white; }
        .btn-primary { background: #E63946; }
        .btn-success { background: #10b981; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 1rem; border-bottom: 1px solid #333; text-align: left; }
        th { background: #222; }
        .message { padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
        .success { background: #14532d; color: #86efac; }
        .error { background: #7f1d1d; color: #fca5a5; }
        
        /* Progress Bar */
        #progress-container { display: none; margin-top: 1.5rem; }
        .progress-bar-bg { width: 100%; background: #333; height: 20px; border-radius: 10px; overflow: hidden; }
        #progress-bar { height: 100%; background: #10b981; width: 0%; transition: width 0.5s; }
        #status-text { margin-top: 0.5rem; color: #999; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="container">
        <a href="../admin.php" style="color: #666; text-decoration: none;">← Voltar ao Admin</a>
        <h1>✨ Auto-SEO IA (Batch Mode)</h1>
        
        <?php if($message): ?>
            <div class="message <?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Status Global</h2>
            <p>Posts sem otimização SEO: <strong><?= $totalPending ?></strong></p>
            
            <?php if ($totalPending > 0): ?>
            
            <div id="settings-panel" style="background:#222; padding:1rem; border-radius:6px; margin-bottom:1rem; border:1px solid #333;">
                <h3 style="margin-top:0; border-bottom:1px solid #444; padding-bottom:0.5rem;">⚙️ Configuração do Robô</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                    <div>
                        <label style="display:block; margin-bottom:0.5rem; color:#aaa;">Posts por Lote (Batch Size)</label>
                        <input type="number" id="batch-size" value="<?= $savedBatchSize ?>" min="1" max="50" style="width:100%; padding:0.5rem; background:#333; border:1px solid #555; color:white; border-radius:4px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:0.5rem; color:#aaa;">Pausa entre Lotes</label>
                        <div style="display:flex; gap:0.5rem;">
                            <input type="number" id="batch-delay" value="<?= $savedDelayValue ?>" min="1" style="flex:1; padding:0.5rem; background:#333; border:1px solid #555; color:white; border-radius:4px;">
                            <select id="batch-unit" style="padding:0.5rem; background:#333; border:1px solid #555; color:white; border-radius:4px;">
                                <option value="1" <?= $savedDelayUnit == 1 ? 'selected' : '' ?>>Segundos</option>
                                <option value="60" <?= $savedDelayUnit == 60 ? 'selected' : '' ?>>Minutos</option>
                                <option value="3600" <?= $savedDelayUnit == 3600 ? 'selected' : '' ?>>Horas</option>
                                <option value="86400" <?= $savedDelayUnit == 86400 ? 'selected' : '' ?>>Dias</option>
                            </select>
                        </div>
                        <small style="color:#666;">Ex: 24 Horas = 1 lote por dia.</small>
                    </div>
                </div>
            </div>

            <div id="controls">
                <button id="btn-start-batch" class="btn btn-primary" style="width:100%; padding:1rem; font-size:1.1rem;">
                    🚀 Iniciar Otimização (Seguindo Configuração)
                </button>
            </div>
            
            <div id="progress-container">
                <div class="progress-bar-bg">
                    <div id="progress-bar"></div>
                </div>
                <div id="status-text">Aguardando início...</div>
                <pre id="log-output" style="background:#222; padding:1rem; height:150px; overflow:auto; font-size:12px; margin-top:1rem; border-radius:4px; font-family:monospace;"></pre>
            </div>
            
            <?php else: ?>
                <div style="text-align:center; padding:2rem;">
                    <span style="font-size:3rem;">🎉</span>
                    <h3 style="color:#10b981;">Parabéns! Todo o site está otimizado.</h3>
                    <p style="color:#888;">Nenhum post pendente encontrado.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Lista de pendentes para debug -->
        <?php if (!empty($pendingPosts)): ?>
        <div class="card">
            <h3>Próximos Pendentes</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingPosts as $post): ?>
                    <tr id="row-<?= $post['id'] ?>">
                        <td>#<?= $post['id'] ?></td>
                        <td><?= htmlspecialchars($post['title']) ?></td>
                        <td>Pendente</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script>
    document.getElementById('btn-start-batch')?.addEventListener('click', async () => {
        const btn = document.getElementById('btn-start-batch');
        const container = document.getElementById('progress-container');
        const bar = document.getElementById('progress-bar');
        const status = document.getElementById('status-text');
        const log = document.getElementById('log-output');
        
        btn.disabled = true;
        btn.innerText = '⏳ Processando...';
        container.style.display = 'block';
        
        // 1. Obter lista de IDs pendentes (AJAX)
        // Para simplificar, vamos processar os IDs que já estão na tela, e recarregar a página depois
        // Ou melhor: fazer fetch de todos os IDs pendentes via API
        
        logInsert('Iniciando... Buscando lista de posts pendentes...');
        
        try {
            // Criar endpoint ad-hoc para buscar todos os IDs se necessário
            // Mas vamos usar os que PHP já renderizou ou fazer um endpoint JSON simples aqui mesmo
            // Hack rápido: vamos chamar auto-seo.php?action=list_pending_json
            
            const response = await fetch('auto-seo.php?action=list_pending_json');
            const data = await response.json();
            const allIds = data.ids;
            
            if (!allIds || allIds.length === 0) {
                logInsert('Nenhum post pendente encontrado.');
                btn.disabled = false;
                return;
            }
            
            logInsert(`Encontrados ${allIds.length} posts para otimizar.`);
            
            logInsert(`Encontrados ${allIds.length} posts para otimizar.`);
            
            // Configurar Batch (Lendo do inputs)
            const BATCH_SIZE = parseInt(document.getElementById('batch-size').value) || 5;
            const DELAY_VAL = parseInt(document.getElementById('batch-delay').value) || 30;
            const DELAY_MULT = parseInt(document.getElementById('batch-unit').value) || 1;
            
            const DELAY_SEC = DELAY_VAL * DELAY_MULT;
            const DELAY_MS = DELAY_SEC * 1000;
            
            // Texto legível para o log
            const unitText = document.getElementById('batch-unit').options[document.getElementById('batch-unit').selectedIndex].text;
            
            // 0. Salvar Configuração no Banco (Para o Robô Background usar)
            try {
                const configData = new FormData();
                configData.append('action', 'save_config');
                configData.append('batch_size', BATCH_SIZE);
                configData.append('batch_delay', DELAY_SEC); // Salvar em SEGUNDOS
                await fetch('auto-seo.php', { method: 'POST', body: configData });
                logInsert('💾 Configuração salva no banco de dados.');
            } catch(e) { console.error(e); }
            
            logInsert(`⚙️ Configuração: Lotes de ${BATCH_SIZE} posts a cada ${DELAY_VAL} ${unitText}.`);
            
            let processed = 0;
            
            for (let i = 0; i < allIds.length; i += BATCH_SIZE) {
                const chunk = allIds.slice(i, i + BATCH_SIZE);
                
                status.innerText = `Processando lote ${Math.ceil((i+1)/BATCH_SIZE)} de ${Math.ceil(allIds.length/BATCH_SIZE)}...`;
                progress(processed, allIds.length);
                
                // Processar Chunk em paralelo
                logInsert(`⚡ Processando posts: ${chunk.join(', ')}`);
                
                const promises = chunk.map(id => processSinglePost(id));
                await Promise.all(promises);
                
                processed += chunk.length;
                progress(processed, allIds.length);
                
                // Pausa (se não for o último)
                if (i + BATCH_SIZE < allIds.length) {
                    logInsert(`⏸️ Aguardando ${DELAY_VAL} ${unitText} para o próximo lote...`);
                    // Aviso se for longo
                    if (DELAY_SEC > 60) {
                        logInsert(`⚠️ DICA: Mantenha esta aba aberta. Se fechar, o processo para.`);
                    }
                    await new Promise(r => setTimeout(r, DELAY_MS));
                }
            }
            
            logInsert('✅ Concluído! Recarregando página...');
            setTimeout(() => window.location.reload(), 2000);
            
        } catch (e) {
            logInsert('❌ Erro fatal: ' + e.message);
            btn.disabled = false;
        }
    });
    
    async function processSinglePost(id) {
        const formData = new FormData();
        formData.append('action', 'generate_seo_ajax');
        formData.append('post_id', id);
        
        try {
            const res = await fetch('auto-seo.php', { method: 'POST', body: formData });
            const json = await res.json();
            
            if (json.success) {
                logInsert(`✓ Post #${id}: Sucesso`);
            } else {
                logInsert(`⚠️ Post #${id}: Falha - ${json.error}`);
            }
        } catch (e) {
            logInsert(`❌ Post #${id}: Erro de rede`);
        }
    }
    
    function progress(current, total) {
        const pct = Math.round((current / total) * 100);
        document.getElementById('progress-bar').style.width = pct + '%';
    }
    
    function logInsert(msg) {
        const log = document.getElementById('log-output');
        const div = document.createElement('div');
        div.innerText = `[${new Date().toLocaleTimeString()}] ${msg}`;
        log.prepend(div);
    }
    </script>
</body>
</html>
