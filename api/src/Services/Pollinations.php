<?php
namespace Src\Services;

require_once __DIR__ . '/../Config/Database.php';
use Src\Config\Database;

class PollinationsConfig {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance();
        $this->initTable();
    }
    
    private function initTable() {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS ai_config (
            key TEXT PRIMARY KEY,
            value TEXT
        )");
        
        // Default seeding
        $this->setIfEmpty('text_model', 'openai');
        $this->setIfEmpty('image_model', 'flux');
        
        // Backup Keys Seeds
        $this->setIfEmpty('pollinations_api_key_2', ''); // Backup Account
        $this->setIfEmpty('openai_api_key', '');         // Direct OpenAI
        $this->setIfEmpty('gemini_api_key', '');         // Direct Gemini
    }
    
    private function setIfEmpty($key, $value) {
        $stmt = $this->pdo->prepare("SELECT key FROM ai_config WHERE key = ?");
        $stmt->execute([$key]);
        if (!$stmt->fetch()) {
            $this->set($key, $value);
        }
    }
    
    public function get($key, $default = null) {
        $stmt = $this->pdo->prepare("SELECT value FROM ai_config WHERE key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    }
    
    public function set($key, $value) {
        $stmt = $this->pdo->prepare("INSERT OR REPLACE INTO ai_config (key, value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
    
    public function getAll() {
        return $this->pdo->query("SELECT * FROM ai_config")->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}

class PollinationsService {
    private $baseUrl = 'https://gen.pollinations.ai';
    private $config;
    private $apiKey;
    
    public function __construct() {
        $this->config = new PollinationsConfig();
        $this->apiKey = $this->config->get('pollinations_api_key');
    }
    
    private function request($endpoint, $method = 'GET', $data = null) {
        $providers = [
            'primary' => ['key' => $this->config->get('pollinations_api_key'), 'type' => 'pollinations'],
            'backup' => ['key' => $this->config->get('pollinations_api_key_2'), 'type' => 'pollinations'],
            'openai' => ['key' => $this->config->get('openai_api_key'), 'type' => 'openai'],
            'gemini' => ['key' => $this->config->get('gemini_api_key'), 'type' => 'gemini']
        ];
        
        $errors = [];
        
        foreach ($providers as $name => $provider) {
            if (empty($provider['key']) && $provider['type'] !== 'pollinations') continue; // Pollinations funciona sem chave (modelo free), outros não
            
            try {
                if ($provider['type'] === 'pollinations') {
                    return $this->requestPollinations($endpoint, $method, $data, $provider['key']);
                } elseif ($provider['type'] === 'openai') {
                    // Adaptar endpoint e payload para OpenAI NATIVO
                    if ($endpoint === '/v1/chat/completions') {
                        return $this->requestOpenAI($data, $provider['key']);
                    }
                } elseif ($provider['type'] === 'gemini') {
                    if ($endpoint === '/v1/chat/completions') {
                        return $this->requestGemini($data, $provider['key']);
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "$name: " . $e->getMessage();
                continue; // Tenta o próximo
            }
        }
        
        throw new \Exception("Todas as tentativas de API falharam. Logs: " . implode(' | ', $errors));
    }
    
    private function requestPollinations($endpoint, $method, $data, $key) {
        $url = $this->baseUrl . $endpoint;
        $headers = ['Content-Type: application/json'];
        if ($key) $headers[] = "Authorization: Bearer $key";
        
        return $this->executeCurl($url, $method, $data, $headers);
    }
    
    private function requestOpenAI($data, $key) {
        $url = 'https://api.openai.com/v1/chat/completions';
        $headers = ['Content-Type: application/json', "Authorization: Bearer $key"];
        
        // Payload é compatível, mas modelo pode precisar de ajuste se for específico do Pollinations
        // Se modelo for 'pollinations-x', substituir por 'gpt-4o' para OpenAI Direct
        if (strpos($data['model'], 'gpt') === false) $data['model'] = 'gpt-4o';
        
        return $this->executeCurl($url, 'POST', $data, $headers);
    }
    
    private function requestGemini($data, $key) {
        // Gemini API REST (generativelanguage.googleapis.com)
        // Requer tradução de formato Messages -> Contents
        $model = 'gemini-2.0-flash'; // Hardcoded fallback for now
        $url = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=$key";
        $headers = ['Content-Type: application/json'];
        
        // Tradução simples de mensagens
        $contents = [];
        foreach ($data['messages'] as $msg) {
            $role = ($msg['role'] === 'user') ? 'user' : 'model';
            if ($msg['role'] === 'system') continue; // Gemini usa systemInstruction, simplificar ignorando ou anexando
            $contents[] = ['role' => $role, 'parts' => [['text' => $msg['content']]]];
        }
        
        $geminiData = ['contents' => $contents];
        $response = $this->executeCurl($url, 'POST', $geminiData, $headers);
        
        // Adaptar resposta para formato OpenAI (para compatibilidade com resto do codigo)
        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
        return [
            'choices' => [
                ['message' => ['content' => $text]]
            ]
        ];
    }

    private function executeCurl($url, $method, $data, $headers) {
        $ch = \curl_init($url);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        \curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Fix para Windows (SSL Certificate Error)
        \curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        \curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        if ($method === 'POST') {
            \curl_setopt($ch, CURLOPT_POST, true);
            if ($data) \curl_setopt($ch, CURLOPT_POSTFIELDS, \json_encode($data));
        }
        
        $response = \curl_exec($ch);
        $httpCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (\curl_errno($ch)) {
            throw new \Exception(\curl_error($ch));
        }
        if ($httpCode >= 400) {
            throw new \Exception("HTTP $httpCode: " . substr($response, 0, 200));
        }
        
        return \json_decode($response, true);
    }
    
    public function getTextModels() {
        try {
            return $this->request('/v1/models');
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public function getImageModels() {
        try {
            return $this->request('/image/models');
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public function generateText($prompt, $system = 'You are a helpful assistant.') {
        $model = $this->config->get('text_model', 'openai');
        
        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7
        ];
        
        $response = $this->request('/v1/chat/completions', 'POST', $data);
        
        // Retornar apenas o texto (para compatibilidade com chamadas simples)
        return $response['choices'][0]['message']['content'] ?? '';
    }
    
    public function generateImage($prompt, $width = 1024, $height = 1024) {
        $model = $this->config->get('image_model', 'flux');
        $safeUrlPrompt = urlencode($prompt);
        
        // Image generation logic uses GET endpoint according to docs
        // But for consistency we can construct the URL
        // Docs: GET /image/{prompt}?model=flux&width=1024...
        
        $url = "{$this->baseUrl}/image/{$safeUrlPrompt}?model={$model}&width={$width}&height={$height}&nologo=true";
        if ($this->apiKey) {
            $url .= "&key={$this->apiKey}";
        }
        
        return $url; // Return URL directly as it redirects to image
    }
}
