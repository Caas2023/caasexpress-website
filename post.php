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
    <?php require_once __DIR__ . '/src/Components/Logo.php'; ?>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,<?= base64_encode(\Src\Components\Logo::render('symbol', 100, 100)) ?>">

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
    <header class="header scrolled" id="header" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);">
        <div class="container">
            <a href="index.php" class="logo">
                <?php echo \Src\Components\Logo::render('full', 180, 40); ?>
            </a>
            <nav class="nav">
                <ul class="nav-menu" id="nav-menu">
                    <li><a href="index.php" class="nav-link">Início</a></li>
                    <li><a href="index.php#servicos" class="nav-link">Serviços</a></li>
                    <li><a href="index.php#sobre" class="nav-link">Sobre Nós</a></li>
                    <li><a href="index.php#contato" class="nav-link">Contato</a></li>
                    <li><a href="blog.php" class="nav-link active">Blog</a></li>
                </ul>
                <div class="nav-cta" id="nav-cta">
                    <a href="https://wa.me/5511957248425" class="btn btn-whatsapp" target="_blank">
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
                    <h1 class="post-title" style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 3rem; margin-top: 1rem;"><?= htmlspecialchars($post['title']) ?></h1>
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
            <div class="footer-content">
                <div class="footer-brand">
                    <a href="index.php" class="logo">
                        <?php echo \Src\Components\Logo::render('full', 180, 40); ?>
                    </a>
                    <p>
                        Sua escolha confiável para serviços de motoboy em Guarulhos e São Paulo.
                        Entregas rápidas, seguras e com emissão de nota fiscal.
                    </p>
                    <div class="footer-social">
                        <a href="https://www.facebook.com/caasexpress" target="_blank" aria-label="Facebook">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" /></svg>
                        </a>
                        <a href="https://instagram.com/caas.express" target="_blank" aria-label="Instagram">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z" /></svg>
                        </a>
                        <a href="https://www.youtube.com/channel/UC1ZHrYhWbjBCK8bQQnJCUFA" target="_blank" aria-label="YouTube">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" /></svg>
                        </a>
                    </div>
                </div>

                <div class="footer-column">
                    <h4>Links Rápidos</h4>
                    <ul class="footer-links">
                        <li><a href="index.php">Início</a></li>
                        <li><a href="index.php#servicos">Serviços</a></li>
                        <li><a href="index.php#sobre">Sobre Nós</a></li>
                        <li><a href="index.php#contato">Contato</a></li>
                        <li><a href="blog.php">Blog</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h4>Serviços</h4>
                    <ul class="footer-links">
                        <li><a href="index.php#servicos">Entrega Expressa</a></li>
                        <li><a href="index.php#servicos">Entrega Agendada</a></li>
                        <li><a href="index.php#servicos">Entrega Urgente</a></li>
                        <li><a href="index.php#servicos">Coleta de Documentos</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h4>Áreas Atendidas</h4>
                    <ul class="footer-links">
                        <li><a href="#">Guarulhos</a></li>
                        <li><a href="#">São Paulo - Capital</a></li>
                        <li><a href="#">ABC Paulista</a></li>
                        <li><a href="#">Região Metropolitana</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2024 Caas Express. Todos os direitos reservados.</p>
                <p>Desenvolvido com ❤️ em Guarulhos</p>
            </div>
        </div>
    </footer>

    <div class="whatsapp-float">
        <a href="https://wa.me/5511957248425?text=Olá!%20Gostaria%20de%20solicitar%20uma%20entrega." target="_blank" aria-label="Fale conosco pelo WhatsApp">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" /></svg>
        </a>
    </div>
    
    <script src="config.js"></script>
    <script src="main.js"></script>
</body>
</html>