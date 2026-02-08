<?php
// PHP Server-Side Rendering logic
require_once __DIR__ . '/src/Config/Database.php';
use Src\Config\Database;

$postId = $_GET['id'] ?? null;
$post = null;
$notFound = false;

if ($postId) {
    try {
        $pdo = Database::getInstance();
        
        if (is_numeric($postId)) {
            $stmt = $pdo->prepare("SELECT p.*, m.file_path as featured_media_url 
                                   FROM posts p 
                                   LEFT JOIN media m ON p.featured_media = m.id 
                                   WHERE p.id = :id");
            $stmt->execute([':id' => $postId]);
        } else {
            $stmt = $pdo->prepare("SELECT p.*, m.file_path as featured_media_url 
                                   FROM posts p 
                                   LEFT JOIN media m ON p.featured_media = m.id 
                                   WHERE p.slug = :slug");
            $stmt->execute([':slug' => $postId]);
        }
        
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$post) {
            $notFound = true;
        }
    } catch (Exception $e) {
        $notFound = true;
    }
} else {
    // Redireciona para blog se não tiver ID
    header("Location: blog.php");
    exit;
}

// Defaults para SEO
$pageTitle = $post ? $post['title'] . ' | Blog Caas Express' : 'Artigo não encontrado';
$pageDesc = $post ? ($post['excerpt'] ? strip_tags($post['excerpt']) : substr(strip_tags($post['content']), 0, 160)) : 'Artigo não encontrado.';
$pageImage = $post && $post['featured_media_url'] ? $post['featured_media_url'] : 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1200&h=600&fit=crop';
$publishedTime = $post ? date('c', strtotime($post['created_at'])) : '';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
    <meta name="author" content="Caas Express">
    
    <!-- Open Graph / SEO -->
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDesc) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($pageImage) ?>">
    <meta property="article:published_time" content="<?= $publishedTime ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect fill='%23E63946' rx='15' width='100' height='100'/%3E%3Ctext x='50%25' y='55%25' dominant-baseline='middle' text-anchor='middle' fill='white' font-size='50' font-family='Arial' font-weight='bold'%3EC%3C/text%3E%3C/svg%3E">

    <!-- Fonts & Styles -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="blog.css">

    <title><?= htmlspecialchars($pageTitle) ?></title>

    <?php
    // SEO Local (Schema Markup)
    $stmtConfig = $pdo->prepare("SELECT key, value FROM ai_config WHERE key LIKE 'business_%'");
    $stmtConfig->execute();
    $biz = $stmtConfig->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if (!empty($biz['business_name'])) {
        $schema = [
            "@context" => "https://schema.org",
            "@type" => $biz['business_type'] ?? 'LocalBusiness',
            "name" => $biz['business_name'],
            "image" => $biz['business_logo'] ?? '',
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress" => $biz['business_address'] ?? '',
                "addressLocality" => $biz['business_city'] ?? '',
                "postalCode" => $biz['business_zip'] ?? '',
                "addressCountry" => "BR"
            ],
            "telephone" => $biz['business_phone'] ?? '',
            "priceRange" => $biz['business_price_range'] ?? '$$'
        ];
        if (!empty($biz['business_geo_lat'])) {
            $schema["geo"] = [
                "@type" => "GeoCoordinates",
                "latitude" => $biz['business_geo_lat'],
                "longitude" => $biz['business_geo_lng']
            ];
        }
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . '</script>';
    }
    ?>
</head>
<body>
    <!-- Header -->
    <header class="header" id="header" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);">
        <div class="container">
            <a href="index.php" class="logo">
                <div class="logo-icon">🏍️</div>
                Caas <span>Express</span>
            </a>
            <nav class="nav">
                <ul class="nav-menu" id="nav-menu">
                    <li><a href="index.php#home" class="nav-link">Início</a></li>
                    <li><a href="index.php#servicos" class="nav-link">Serviços</a></li>
                    <li><a href="index.php#sobre" class="nav-link">Sobre Nós</a></li>
                    <li><a href="index.php#contato" class="nav-link">Contato</a></li>
                    <li><a href="blog.php" class="nav-link active">Blog</a></li>
                </ul>
                <div class="nav-cta" id="nav-cta">
                    <a href="https://wa.me/5511957248425" class="btn btn-whatsapp" target="_blank">
                        <!-- SVG Icon -->
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" /></svg>
                        WhatsApp
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <?php if ($notFound): ?>
        <div class="container" style="text-align:center; padding: 100px 0;">
            <h1>Artigo não encontrado</h1>
            <p style="color: #666; margin: 1rem 0;">O artigo que você procura não existe ou foi removido.</p>
            <a href="blog.php" class="btn btn-primary">Voltar para o Blog</a>
        </div>
    <?php else: ?>
        <article class="post-single">
            <div class="post-header-image" style="background-image: url('<?= htmlspecialchars($pageImage) ?>')">
                <div class="post-header-overlay"></div>
                <div class="container">
                    <div class="post-meta-badges">
                        <span class="post-category-badge">Dicas</span>
                    </div>
                    <h1 class="post-title"><?= htmlspecialchars($post['title']) ?></h1>
                    <div class="post-info">
                        <span>📅 <?= date('d/m/Y', strtotime($post['created_at'])) ?></span>
                        <span>⏱️ 5 min de leitura</span>
                    </div>
                </div>
            </div>
            
            <div class="container post-body-container">
                <div class="post-content">
                    <?= $post['content'] // Raw HTML from DB ?>
                </div>
                
                <div class="post-sidebar">
                    <div class="sidebar-widget">
                        <h3>Newsletter</h3>
                        <p>Receba novidades e dicas exclusivas.</p>
                        <form style="margin-top: 1rem;">
                            <input type="email" placeholder="Seu e-mail" style="width: 100%; padding: 0.8rem; margin-bottom: 0.5rem; border: 1px solid #ddd; border-radius: 8px;">
                            <button class="btn btn-primary" style="width: 100%;">Inscrever-se</button>
                        </form>
                    </div>
                    <div class="sidebar-widget" style="margin-top: 2rem;">
                        <a href="blog.php" class="btn btn-outline" style="width: 100%;">← Voltar para o Blog</a>
                    </div>
                </div>
            </div>
        </article>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2024 Caas Express. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
    
    <script src="config.js"></script>
    <script src="main.js"></script>
</body>
</html>