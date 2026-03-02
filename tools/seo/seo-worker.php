<?php
/**
 * SEO Worker (Background Process)
 * Este script roda em loop infinito no terminal para processar SEO sem precisar do navegador.
 */

require_once __DIR__ . '/../src/Config/Database.php';
require_once __DIR__ . '/../src/Services/Pollinations.php';
require_once __DIR__ . '/../src/Utils/Response.php';

use Src\Config\Database;
use Src\Services\PollinationsService;

// Configuração para CLI
if (php_sapi_name() !== 'cli') {
    die("Este script deve ser rodado via Terminal (CLI).");
}

echo "\n============================================\n";
echo "   🤖 ROBÔ AUTO-SEO (BACKGROUND WORKER)    \n";
echo "============================================\n";
echo "Iniciando motor de IA...\n";

$pdo = Database::getInstance();
$aiService = new PollinationsService();

// Carregar Configurações do Banco ou Defaults
function getConfig($pdo, $key) {
    try {
        $stmt = $pdo->prepare("SELECT value FROM ai_config WHERE key = ?");
        $stmt->execute([$key]);
        $res = $stmt->fetch();
        return $res ? $res['value'] : null;
    } catch (Exception $e) { return null; }
}

// Helpers
function saveMeta($pdo, $postId, $key, $value) {
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

// URL Base do site (produção)
define('BASE_URL', 'https://caasexpresss.com');

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

// Helpers de Linkagem
function getOrphanPosts($pdo, $limit=3) {
    // Posts que ninguem linka para eles (simplificado)
    // Para ser preciso, precisaria de uma tabela de links, mas vamos por "não processados recentemente"
    // Vamos usar uma meta_key '_auto_linked' para saber se já rodamos o robô neles
    $sql = "SELECT p.id, p.title, p.content 
            FROM posts p 
            WHERE p.status = 'publish' 
            AND p.type = 'post'
            AND NOT EXISTS (
                SELECT 1 FROM postmeta pm 
                WHERE pm.post_id = p.id 
                AND pm.meta_key = '_auto_linked'
            )
            ORDER BY p.created_at DESC
            LIMIT $limit";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

// Função para buscar posts sem SEO
function getPostsMissingSeo($pdo, $limit = 5) {
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

function performAiLinking($pdo, $targetPost, $aiService) {
    if (!$targetPost) return;
    
    $targetId = $targetPost['id'];
    $targetTitle = $targetPost['title'];
    $targetContent = $targetPost['content'];
    
    echo "   > 🔗 Iniciando Linkagem Inteligente para: '$targetTitle'...\n";
    $linksCreated = 0;
    
    // --- FASE 1: INBOUND (Trazer links DE FORA para ESTE post) ---
    // Estratégia: Achar posts antigos que falem do assunto deste post novo
    
    // 1. Gerar Keywords do Título (Semanticamente relevantes)
    $stopWords = ['como', 'para', 'onde', 'pelo', 'pela', 'quem', 'qual', 'entao', 'pois', 'porque', 'sobre', 'apos', 'antes', 'guia', 'dicas', 'tudo', 'voce', 'precisa', 'saber', 'passo'];
    $words = preg_split('/\s+/', strtolower($targetTitle));
    $cleanWords = array_diff($words, $stopWords);
    $keywords = [];
    
    // Keyword A: Título Exato
    $keywords[] = $targetTitle;
    
    // Keyword B: Título "Curto" (se fizer sentido)
    if (count($cleanWords) >= 2) {
        $shortTitle = implode(' ', $cleanWords);
        if (strlen($shortTitle) > 10) $keywords[] = $shortTitle;
    }
    
    foreach ($keywords as $kw) {
        if ($linksCreated >= 3) break; // Max 3 backlinks inbound
        
        // Buscar posts candidatos (que tenham a keyword mas NÃO tenham o link ainda)
        // Usando LIKE para eficiência
        $sql = "SELECT id, title, content FROM posts 
                WHERE status='publish' AND type='post' AND id != ? 
                AND content LIKE ? 
                AND content NOT LIKE ? 
                ORDER BY RANDOM() LIMIT 5";
        
        $stmt = $pdo->prepare($sql);
        $targetSlug = getSlugById($pdo, $targetId);
        $stmt->execute([$targetId, "%$kw%", "%$targetSlug%"]);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($candidates as $source) {
            if ($linksCreated >= 3) break;
            
            $content = $source['content'];
            
            // Regex Segura para UTF-8 e Word Boundary
            $kwSafe = preg_quote($kw, '/');
            $pattern = '/(?<=^|[\s\.,;!?\(\)])(' . $kwSafe . ')(?=[\s\.,;!?\(\)]|$)(?![^<]*>)/iu';
            
            if (preg_match($pattern, $content)) {
                $link = '<a href="' . BASE_URL . '/' . $targetSlug . '" title="' . htmlspecialchars($targetTitle) . '">$1</a>';
                $newContent = preg_replace($pattern, $link, $content, 1);
                
                if ($newContent && $newContent !== $content) {
                    $upd = $pdo->prepare("UPDATE posts SET content = ?, updated_at = datetime('now', 'localtime') WHERE id = ?");
                    $upd->execute([$newContent, $source['id']]);
                    $linksCreated++;
                    echo "      ⬇️ [INBOUND] Post #{$source['id']} ('{$source['title']}') agora linka para CÁ. (Kw: $kw)\n";
                    break; // Um link por post fonte
                }
            }
        }
    }
    
    // --- FASE 2: OUTBOUND (Linkar DESTE post para os PILARES/KEYWORDS) ---
    // Estratégia: Verificar se este post menciona algum Pilar ou Keyword Definida
    
    // 1. Carregar Pilares (Tabela Dedicada + PostMeta PostHog Style)
    try {
        // Fonte 1: Pilares Oficiais
        $pillars = $pdo->query("SELECT pp.post_id, pp.keywords, p.title as pillar_title FROM pillar_posts pp JOIN posts p ON pp.post_id = p.id")->fetchAll(PDO::FETCH_ASSOC);
        
        // Fonte 2: Keywords definidas no editor (PostMeta)
        $metaPillars = $pdo->query("SELECT pm.post_id, pm.meta_value as keywords, p.title as pillar_title 
                                    FROM postmeta pm 
                                    JOIN posts p ON pm.post_id = p.id 
                                    WHERE pm.meta_key = 'auto_link_keywords' AND pm.meta_value != ''")->fetchAll(PDO::FETCH_ASSOC);
        
        // Mesclar
        $allTargets = array_merge($pillars, $metaPillars);
        
        $currentContent = $targetContent; // Começa com o conteúdo original
        $outboundChanged = false;
        $outboundCount = 0;
        $processedTargets = []; // Evitar duplicatas se o mesmo post estiver nas duas listas
        
        // Verifica se eu já estou linkando para os pilares (para não duplicar)
        foreach ($allTargets as $target) {
            $pid = $target['post_id'];
            
            if ($targetId == $pid) continue; // Não linkar pra si mesmo
            if (isset($processedTargets[$pid])) continue; // Já processou este alvo
            $pidSlug = getSlugById($pdo, $pid);
            if (strpos($currentContent, $pidSlug) !== false) continue; // Já tem link
            
            $processedTargets[$pid] = true;
            
            // --- NOVA REGRA: Limite de 5 Backlinks para Não-Pilares ---
            // 1. Verificar se é Pilar
            $isPillar = false;
            foreach ($pillars as $p) {
                if ($p['post_id'] == $pid) {
                    $isPillar = true;
                    break;
                }
            }
            
            // 2. Se NÃO for pilar, contar quantos backlinks já tem
            if (!$isPillar) {
                $countSql = "SELECT COUNT(*) FROM posts WHERE content LIKE ?";
                $countStmt = $pdo->prepare($countSql);
                $countStmt->execute(["%" . getSlugById($pdo, $pid) . "%"]);
                $currentBacklinks = $countStmt->fetchColumn();
                
                if ($currentBacklinks >= 5) {
                    // echo "      ⚠️ [LIMIT] Post #$pid já tem $currentBacklinks backlinks (Max 5). Pulando.\n";
                    continue; 
                }
            }
            // -----------------------------------------------------------
            
            $pKeys = array_map('trim', explode(',', $target['keywords']));
            foreach ($pKeys as $pKw) {
                if (empty($pKw)) continue;
                
                $kwSafe = preg_quote($pKw, '/');
                $pattern = '/(?<=^|[\s\.,;!?\(\)])(' . $kwSafe . ')(?=[\s\.,;!?\(\)]|$)(?![^<]*>)/iu';
                
                if (preg_match($pattern, $currentContent)) {
                    $pidSlug = getSlugById($pdo, $pid);
                    $link = '<a href="' . BASE_URL . '/' . $pidSlug . '" title="' . htmlspecialchars($target['pillar_title']) . '">$1</a>';
                    $currentContent = preg_replace($pattern, $link, $currentContent, 1);
                    $outboundChanged = true;
                    $outboundCount++;
                    echo "      ⬆️ [OUTBOUND] Criado link PARA o Post #$pid ('{$target['pillar_title']}'). (Kw: $pKw)\n";
                    break; // Um link por post alvo
                }
            }
        }
        
        // Se mudou algo na Fase 2, atualiza ESTE post
        if ($outboundChanged) {
            $upd = $pdo->prepare("UPDATE posts SET content = ?, updated_at = datetime('now', 'localtime') WHERE id = ?");
            $upd->execute([$currentContent, $targetId]);
        }
        
    } catch (Exception $e) { echo "      ⚠️ Erro ao processar Pilares: " . $e->getMessage() . "\n"; }
    
    return $linksCreated + $outboundCount;
}

// Loop Infinito
while (true) {
    // 1. Configurações
    $batchSize = (int) (getConfig($pdo, 'auto_seo_batch_size') ?: 5);
    $intervalSeconds = (int) (getConfig($pdo, 'auto_seo_batch_delay') ?: 60);
    if ($intervalSeconds < 5) $intervalSeconds = 5;
    
    echo "\n[".date('H:i:s')."] ⚙️ Config: $batchSize posts/ciclo. Pausa: {$intervalSeconds}s.\n";
    
    // --- TAREFA A: AUTO-SEO (Meta Tags) ---
    $postsSeo = getPostsMissingSeo($pdo, $batchSize);
    if (count($postsSeo) > 0) {
        echo "[SEO] Processando " . count($postsSeo) . " posts...\n";
        foreach ($postsSeo as $post) {
            try {
                // ... (Lógica de SEO existente - inalterada, apenas resumida aqui para o diff) ...
                // UTF-8 Clean & Safe Truncate
                $cleanContent = preg_replace('/[\x00-\x1F\x7F]/u', '', strip_tags($post['content']));
                $cleanContent = mb_substr($cleanContent, 0, 1000, 'UTF-8');
                
                if (strlen($cleanContent) < 10) { saveMeta($pdo, $post['id'], '_yoast_wpseo_metadesc', 'skipped'); continue; }
                
                // Prompt em Português
                $prompt = "Analise este conteúdo e gere metadados SEO em Português do Brasil. Retorne SOMENTE JSON válido com esta estrutura:
                {
                    \"seo_title\": \"Título chamativo com menos de 60 caracteres\",
                    \"seo_desc\": \"Descrição persuasiva com menos de 160 caracteres\",
                    \"focus_keyword\": \"Frase-chave principal\",
                    \"tags\": \"5 tags separadas por vírgula\",
                    \"auto_link_keywords\": \"3 palavras-chave secundárias para linkagem interna (separadas por vírgula)\"
                }
                
                Conteúdo: " . $cleanContent;
                
                $responseText = $aiService->generateText($prompt, 'Você é um Especialista em SEO. Retorne apenas JSON válido.');
                
                // Limpar Markdown do JSON se houver
                $responseText = str_replace(['```json', '```'], '', $responseText);
                $aiData = json_decode($responseText, true);
                
                if (!$aiData) {
                   echo "      ⚠️ Falha ao decodificar JSON da IA. Tentando fallback texto...\n"; 
                }
            } catch (Exception $e) {
                echo "      ⚠️ Erro na IA: " . $e->getMessage() . "\n";
            }
            
            if ($aiData) {
                // Salvar tudo no PostMeta
                saveMeta($pdo, $post['id'], '_yoast_wpseo_title', $aiData['seo_title']);
                saveMeta($pdo, $post['id'], 'seo_title', $aiData['seo_title']); // Compatibilidade Admin
                
                saveMeta($pdo, $post['id'], '_yoast_wpseo_metadesc', $aiData['seo_desc']);
                saveMeta($pdo, $post['id'], 'seo_desc', $aiData['seo_desc']); // Compatibilidade Admin
                
                saveMeta($pdo, $post['id'], '_yoast_wpseo_focuskw', $aiData['focus_keyword']);
                saveMeta($pdo, $post['id'], 'seo_keyword', $aiData['focus_keyword']); // Compatibilidade Admin
                
                // Tags (Se fosse WP real usaria taxonomia, aqui salvamos em meta por enquanto ou implementamos tags dps)
                // Vamos salvar num meta simples 'ai_tags'
                saveMeta($pdo, $post['id'], 'ai_tags', $aiData['tags']);
                
                // CRUCIAL: Auto-Link Keywords (Isso ativa o "Imã de Links")
                saveMeta($pdo, $post['id'], 'auto_link_keywords', $aiData['auto_link_keywords']);
                
                echo "      ✅ [AUTOPILOT] Metadados Gerados!\n";
                echo "         Título: {$aiData['seo_title']}\n";
                echo "         Desc: {$aiData['seo_desc']}\n";
                echo "         Link Keywords: {$aiData['auto_link_keywords']}\n";
            }
            
            // Marcar como processado para não repetir
            saveMeta($pdo, $post['id'], '_ai_autopilot_done', date('Y-m-d H:i:s'));
        }
    } else {
        echo "   [AUTOPILOT] Nenhum post pendente de análise.\n";
    }

    // --- TAREFA B: AUTO-LINKER (Robô de Linkagem) ---
    // Pega posts que ainda não foram "tratados" pelo robô de links
    $postsLink = getOrphanPosts($pdo, 2); // Faz 2 por vez para não pesar
    if (count($postsLink) > 0) {
        echo "[LINK] Verificando backlinks para " . count($postsLink) . " novos posts...\n";
        foreach ($postsLink as $post) {
            echo "   > Buscando backlinks para: {$post['title']}...\n";
            
            // 2. Chamar a IA (Regex + Semântica) para linkar tudo
            $linksCreated = performAiLinking($pdo, $post, $aiService);
            
            // 3. Marcar como processado
            saveMeta($pdo, $post['id'], '_auto_linked', date('Y-m-d H:i:s'));
            
            if ($linksCreated > 0) echo "   [LINK] Sucesso! $linksCreated conexões criadas (In/Out) para #{$post['id']}.\n";
            else echo "   [LINK] Nenhuma conexão óbvia encontrada para #{$post['id']}.\n";
        }
    } else {
        echo "[LINK] Nenhum post novo precisa de backlinks.\n";
    }

    // Garbage Collector
    gc_collect_cycles();
    
    if (count($postsSeo) == 0 && count($postsLink) == 0) {
        echo "💤 Tudo em dia. Dormindo $intervalSeconds s...\n";
    } else {
        echo "⏸️  Pausa de $intervalSeconds s...\n";
    }
    
    sleep($intervalSeconds);
}
