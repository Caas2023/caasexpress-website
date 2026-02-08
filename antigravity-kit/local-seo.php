<?php
/**
 * Configuração de SEO Local (Schema.org)
 * Permite definir dados da empresa para Rich Snippets
 */

require_once __DIR__ . '/../src/Config/Database.php';
require_once __DIR__ . '/../src/Utils/Auth.php';
use Src\Utils\Auth;
use Src\Config\Database;

Auth::check();
header('Content-Type: text/html; charset=utf-8');

$pdo = Database::getInstance();
$message = '';
$messageType = '';

// Helper para salvar configs
function saveConfig($pdo, $key, $value) {
    if (empty($value)) return;
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO ai_config (key, value) VALUES (?, ?)");
    $stmt->execute([$key, $value]);
}

function getConfig($pdo, $key) {
    $stmt = $pdo->prepare("SELECT value FROM ai_config WHERE key = ?");
    $stmt->execute([$key]);
    $res = $stmt->fetch();
    return $res ? $res['value'] : '';
}

// Processar Formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'business_name', 'business_type', 'business_address', 
        'business_city', 'business_zip', 'business_phone', 
        'business_logo', 'business_geo_lat', 'business_geo_lng',
        'business_price_range'
    ];
    
    foreach ($fields as $field) {
        saveConfig($pdo, $field, $_POST[$field] ?? '');
    }
    
    $message = "Dados de SEO Local atualizados com sucesso!";
    $messageType = 'success';
}

// Carregar Dados Atuais
$data = [];
$fields = [
    'business_name', 'business_type', 'business_address', 
    'business_city', 'business_zip', 'business_phone', 
    'business_logo', 'business_geo_lat', 'business_geo_lng',
    'business_price_range'
];
foreach ($fields as $field) {
    $data[$field] = getConfig($pdo, $field);
}

// Schema Preview
$schema = [
    "@context" => "https://schema.org",
    "@type" => $data['business_type'] ?: 'LocalBusiness',
    "name" => $data['business_name'] ?: 'Minha Empresa',
    "image" => $data['business_logo'],
    "address" => [
        "@type" => "PostalAddress",
        "streetAddress" => $data['business_address'],
        "addressLocality" => $data['business_city'],
        "postalCode" => $data['business_zip'],
        "addressCountry" => "BR"
    ],
    "telephone" => $data['business_phone'],
    "priceRange" => $data['business_price_range'] ?: "$$"
];

if (!empty($data['business_geo_lat'])) {
    $schema["geo"] = [
        "@type" => "GeoCoordinates",
        "latitude" => $data['business_geo_lat'],
        "longitude" => $data['business_geo_lng']
    ];
}

$jsonSchema = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Local (Schema) | Caas Express</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #0a0a0a; color: #e5e5e5; padding: 2rem; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { color: #fff; margin-bottom: 0.5rem; }
        .subtitle { color: #888; margin-bottom: 2rem; }
        .card { background: #1a1a1a; padding: 2rem; border-radius: 8px; border: 1px solid #333; margin-bottom: 2rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .full-width { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 0.5rem; color: #aaa; font-size: 0.9rem; }
        input, select { 
            width: 100%; padding: 0.75rem; 
            background: #0a0a0a; border: 1px solid #333; 
            color: white; border-radius: 4px; box-sizing: border-box;
        }
        input:focus { outline: none; border-color: #E63946; }
        .btn { 
            background: #E63946; color: white; border: none; 
            padding: 1rem 2rem; font-size: 1rem; font-weight: bold; 
            border-radius: 6px; cursor: pointer; width: 100%;
        }
        .btn:hover { background: #c62e3a; }
        .preview-box { background: #0f172a; padding: 1.5rem; border-radius: 6px; border: 1px solid #1e293b; overflow-x: auto; }
        pre { color: #93c5fd; margin: 0; }
        .message { padding: 1rem; border-radius: 6px; margin-bottom: 1rem; background: #14532d; color: #86efac; border: 1px solid #22c55e; }
        .back-link { color: #666; text-decoration: none; display: inline-block; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <a href="../admin.php" class="back-link">← Voltar ao Admin</a>
        
        <h1>📍 Configuração de SEO Local</h1>
        <p class="subtitle">Defina os dados da sua empresa para aparecer no Google Maps e Rich Snippets.</p>
        
        <?php if($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="card">
                <h3>Dados da Empresa</h3>
                <div class="form-grid">
                    <div class="full-width">
                        <label>Nome do Negócio</label>
                        <input type="text" name="business_name" value="<?= htmlspecialchars($data['business_name']) ?>" placeholder="Ex: Motoboy Express 24h">
                    </div>
                    
                    <div>
                        <label>Tipo de Negócio (Schema Type)</label>
                        <select name="business_type">
                            <option value="LocalBusiness" <?= $data['business_type'] == 'LocalBusiness' ? 'selected' : '' ?>>Negócio Local (Geral)</option>
                            <option value="ProfessionalService" <?= $data['business_type'] == 'ProfessionalService' ? 'selected' : '' ?>>Serviço Profissional</option>
                            <option value="Store" <?= $data['business_type'] == 'Store' ? 'selected' : '' ?>>Loja (Varejo)</option>
                            <option value="Restaurant" <?= $data['business_type'] == 'Restaurant' ? 'selected' : '' ?>>Restaurante</option>
                            <option value="Organization" <?= $data['business_type'] == 'Organization' ? 'selected' : '' ?>>Organização</option>
                        </select>
                    </div>

                    <div>
                        <label>Telefone (Com DDD)</label>
                        <input type="text" name="business_phone" value="<?= htmlspecialchars($data['business_phone']) ?>" placeholder="+55 11 99999-9999">
                    </div>

                    <div class="full-width">
                        <label>Endereço Completo</label>
                        <input type="text" name="business_address" value="<?= htmlspecialchars($data['business_address']) ?>" placeholder="Rua Exemplo, 123">
                    </div>

                    <div>
                        <label>Cidade</label>
                        <input type="text" name="business_city" value="<?= htmlspecialchars($data['business_city']) ?>" placeholder="São Paulo">
                    </div>

                    <div>
                        <label>CEP (Zip Code)</label>
                        <input type="text" name="business_zip" value="<?= htmlspecialchars($data['business_zip']) ?>" placeholder="00000-000">
                    </div>
                    
                    <div>
                        <label>Latitude (Google Maps)</label>
                        <input type="text" name="business_geo_lat" value="<?= htmlspecialchars($data['business_geo_lat']) ?>" placeholder="-23.550520">
                    </div>
                    
                    <div>
                        <label>Longitude (Google Maps)</label>
                        <input type="text" name="business_geo_lng" value="<?= htmlspecialchars($data['business_geo_lng']) ?>" placeholder="-46.633308">
                    </div>
                    
                    <div class="full-width">
                        <label>Logo URL (Link da Imagem)</label>
                        <input type="text" name="business_logo" value="<?= htmlspecialchars($data['business_logo']) ?>" placeholder="https://seusite.com/logo.png">
                    </div>
                </div>
                
                <br>
                <button type="submit" class="btn">💾 Salvar Configurações</button>
            </div>
        </form>
        
        <div class="card">
            <h3>👁️ Preview do Código (JSON-LD)</h3>
            <p style="color:#666; font-size:0.9rem; margin-bottom:1rem;">Este código será inserido automaticamente em todas as páginas do seu site.</p>
            
            <div class="preview-box">
                <pre><?= htmlspecialchars($jsonSchema) ?></pre>
            </div>
        </div>
    </div>
</body>
</html>
