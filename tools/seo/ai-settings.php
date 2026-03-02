<?php
/**
 * Configurações de IA (Pollinations.ai)
 */
require_once __DIR__ . '/../src/Services/Pollinations.php';
use Src\Services\PollinationsConfig;
use Src\Services\PollinationsService;
require_once __DIR__ . '/../src/Utils/Response.php';
require_once __DIR__ . '/../src/Utils/Auth.php';
use Src\Utils\Auth;

// Proteção de segurança
Auth::check();

header('Content-Type: text/html; charset=utf-8');

$config = new PollinationsConfig();
$service = new PollinationsService();
$message = '';
$messageType = '';

// Buscar modelos disponíveis (cachear se possível, aqui vamos buscar direto)
// Fallback manual caso a API falhe ou para carregar mais rápido
$textModels = [
    // Top Tier / Popular
    ['id' => 'openai', 'name' => 'OpenAI GPT-5 Mini (Default)'],
    ['id' => 'openai-large', 'name' => 'OpenAI GPT-5.2 (Large)'],
    ['id' => 'openai-fast', 'name' => 'OpenAI GPT-5 Nano (Fast)'],
    
    // Perplexity & Search
    ['id' => 'perplexity-fast', 'name' => 'Perplexity Sonar'],
    ['id' => 'perplexity-reasoning', 'name' => 'Perplexity Sonar Reasoning'],
    ['id' => 'gemini-search', 'name' => 'Google Gemini 3 Flash (Search)'],
    ['id' => 'sciphi', 'name' => 'SciPhi (Search)'],

    // Google Gemini
    ['id' => 'gemini', 'name' => 'Google Gemini 3 Flash'],
    ['id' => 'gemini-fast', 'name' => 'Google Gemini 2.5 Flash Lite'],
    ['id' => 'gemini-large', 'name' => 'Google Gemini 3 Pro'],
    ['id' => 'gemini-legacy', 'name' => 'Google Gemini 2.5 Pro'],
    ['id' => 'gemini-thinking', 'name' => 'Gemini 2.0 Thinking'],

    // Anthropic Claude
    ['id' => 'claude', 'name' => 'Claude Sonnet 4.5'],
    ['id' => 'claude-fast', 'name' => 'Claude Haiku 4.5'],
    ['id' => 'claude-large', 'name' => 'Claude Opus 4.5'],

    // Others (DeepSeek, Grok, Qwen, Mistral)
    ['id' => 'deepseek', 'name' => 'DeepSeek V3.2'],
    ['id' => 'deepseek-r1', 'name' => 'DeepSeek R1 (Reasoning)'],
    ['id' => 'grok', 'name' => 'xAI Grok 4 Fast'],
    ['id' => 'qwen-coder', 'name' => 'Qwen3 Coder 30B'],
    ['id' => 'mistral', 'name' => 'Mistral Small 3.2 24B'],
    ['id' => 'nova-fast', 'name' => 'Amazon Nova Micro'],
    ['id' => 'minimax', 'name' => 'MiniMax M2.1'],
    ['id' => 'kimi', 'name' => 'Moonshot Kimi K2.5'],
    ['id' => 'glm', 'name' => 'Z.ai GLM-4.7'],
    ['id' => 'chickytutor', 'name' => 'ChickyTutor (Language)'],
    
    // Specialized
    ['id' => 'openai-audio', 'name' => 'OpenAI GPT-4o Mini Audio'],
    ['id' => 'midijourney', 'name' => 'Midijourney (Musical/Creative)'],
    ['id' => 'evil', 'name' => 'Evil Mode (Uncensored)'],
    ['id' => 'p1', 'name' => 'Pollinations 1 (Fast)'],
];

$imageModels = [
    ['id' => 'flux', 'name' => 'Flux Schnell (Default)'],
    ['id' => 'turbo', 'name' => 'SDXL Turbo'],
    ['id' => 'midijourney', 'name' => 'Midijourney'],
];

// Tentar buscar da API se tiver chave (ou mesmo sem)
try {
    $apiTextModels = $service->getTextModels();
    // Se a API retornar estrutura diferente, adaptar. Por enquanto usamos a lista hardcoded + a da API se funcionar
    // A API retorna objetos complexos, vamos simplificar se vier
    if (!empty($apiTextModels) && is_array($apiTextModels)) {
        // Implementar parser se necessário. Por hora confia na lista manual que é segura.
    }
} catch (Exception $e) {
    // Ignorar erro de conexão na listagem
}

// Salvar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the main settings form was submitted (by checking for the main API key field)
    if (isset($_POST['pollinations_api_key'])) {
        // Salvar chaves
        $config->set('pollinations_api_key', trim($_POST['pollinations_api_key']));
        $config->set('pollinations_api_key_2', trim($_POST['pollinations_api_key_2']));
        $config->set('openai_api_key', trim($_POST['openai_api_key']));
        $config->set('gemini_api_key', trim($_POST['gemini_api_key']));

        // Salvar modelos
        $config->set('text_model', $_POST['text_model']);
        $config->set('image_model', $_POST['image_model']);
        
        $message = "Configurações salvas com sucesso!";
        $messageType = 'success';
        
        // Atualizar service com nova key
        $service = new PollinationsService();
    }
    
    // Teste de Geração
    if (isset($_POST['test_prompt'])) {
        try {
            $prompt = $_POST['test_prompt'];
            $response = $service->generateText($prompt);
            $generatedText = $response['choices'][0]['message']['content'] ?? 'Erro ao gerar texto.';
            $message = "Teste realizado com sucesso!";
            $messageType = 'success';
        } catch (Exception $e) {
            $message = "Erro no teste: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$currentKey = $config->get('pollinations_api_key', '');
$currentTextModel = $config->get('text_model', 'openai');
$currentImageModel = $config->get('image_model', 'flux');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração IA | Caas Express Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', -apple-system, sans-serif; 
            background: #0a0a0a; 
            color: #e5e5e5;
            line-height: 1.6;
            padding: 2rem;
        }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { 
            font-size: 2rem; 
            margin-bottom: 0.5rem; 
            color: #fff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        h1::before { content: '🤖'; }
        .subtitle { color: #888; margin-bottom: 2rem; }
        
        .card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; color: #ccc; font-weight: 500; }
        input[type="text"], select, textarea {
            width: 100%;
            padding: 0.75rem;
            background: #0a0a0a;
            border: 1px solid #333;
            border-radius: 6px;
            color: #fff;
            font-size: 1rem;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #E63946;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            border: none;
            font-size: 1rem;
            transition: all 0.2s;
        }
        .btn-primary { background: #E63946; color: white; }
        .btn-primary:hover { background: #c62e3a; }
        .btn-secondary { background: #333; color: white; }
        .btn-secondary:hover { background: #444; }
        
        .message {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .message-success { background: #14532d; color: #86efac; border: 1px solid #22c55e; }
        .message-error { background: #7f1d1d; color: #fca5a5; border: 1px solid #ef4444; }
        
        .back-link { 
            display: inline-block;
            margin-bottom: 1rem;
            color: #888;
            text-decoration: none;
        }
        .back-link:hover { color: #fff; }
        
        .api-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            background: #333;
            color: #aaa;
            margin-left: 0.5rem;
        }
        .api-status.active { background: #14532d; color: #86efac; }
        
        .output-box {
            background: #0a0a0a;
            border: 1px solid #333;
            border-radius: 6px;
            padding: 1rem;
            margin-top: 1rem;
            font-family: monospace;
            white-space: pre-wrap;
            color: #10b981;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../admin.php" class="back-link">← Voltar ao Admin</a>
        
        <h1>Configuração IA <span class="api-status <?= $currentKey ? 'active' : '' ?>"><?= $currentKey ? 'Ativo' : 'Sem Chave' ?></span></h1>
        <p class="subtitle">Configure a integração com Pollinations.ai</p>
        
        <?php if ($message): ?>
        <div class="message message-<?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="card">
                <h2>🔑 Credenciais</h2>
                <div class="form-group">
                    <label>Pollinations API Key (Principal):</label>
                    <input type="text" name="pollinations_api_key" value="<?= htmlspecialchars($config->get('pollinations_api_key')) ?>" placeholder="Insira sua chave aqui">
                    <p class="help" style="color: #666; font-size: 0.9em; margin-top: 0.25rem;">Necessária para modelos premium.</p>
                </div>
                
                <h3 style="margin-top: 2rem; border-top: 1px solid #333; padding-top: 1rem;">🛡️ Sistema de Contingência (Fallback)</h3>
                <p class="help" style="color: #666; font-size: 0.9em; margin-bottom: 1rem;">Se a chave principal falhar, o sistema tentará usar estas chaves na ordem abaixo:</p>
                
                <div class="form-group">
                    <label>2. Pollinations API Key (Backup/Conta Secundária):</label>
                    <input type="text" name="pollinations_api_key_2" value="<?= htmlspecialchars($config->get('pollinations_api_key_2')) ?>" placeholder="Chave alternativa">
                </div>
                
                <div class="form-group">
                    <label>3. OpenAI API Key (Uso direto - Não implementado via Pollinations Proxy):</label>
                    <input type="text" name="openai_api_key" value="<?= htmlspecialchars($config->get('openai_api_key')) ?>" placeholder="sk-...">
                </div>
                
                 <div class="form-group">
                    <label>4. Gemini API Key (Uso direto):</label>
                    <input type="text" name="gemini_api_key" value="<?= htmlspecialchars($config->get('gemini_api_key')) ?>" placeholder="AIza...">
                </div>
                <small style="color: #666; display: block; margin-top: 0.5rem;">
                    Não tem uma chave? <a href="https://pollinations.ai" target="_blank" style="color: #60a5fa;">Obter chave gratuita</a>
                </small>
                
                <h2>🧠 Seleção de Modelos</h2>
                <div class="form-group">
                    <label>Modelo de Texto (Padrão)</label>
                    <select name="text_model">
                        <?php foreach ($textModels as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= $currentTextModel === $m['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Modelo de Imagem (Padrão)</label>
                    <select name="image_model">
                        <?php foreach ($imageModels as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= $currentImageModel === $m['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Salvar Configurações</button>
            </div>
        </form>
        
        <div class="card">
            <h2>🧪 Testar Conexão</h2>
            <form method="POST">
                <input type="hidden" name="test_mode" value="1">
                <div class="form-group">
                    <label>Prompt de Teste</label>
                    <input type="text" name="test_prompt" placeholder="Escreva algo para testar..." value="Escreva uma frase motivacional curta sobre programação.">
                </div>
                <button type="submit" class="btn btn-secondary">Gerar Teste</button>
            </form>
            
            <?php if (isset($generatedText)): ?>
            <div class="output-box">
                <?= htmlspecialchars($generatedText) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
