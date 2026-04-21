<!DOCTYPE html>
<html lang="pt-BR">
<?php require_once __DIR__ . '/src/Components/Logo.php'; ?>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description"
    content="Serviço de motoboy em Guarulhos e São Paulo. Entregas rápidas, seguras e agendadas. Caas Express: agilidade e confiança para você ou sua empresa.">
  <meta name="keywords" content="motoboy guarulhos, entregas rápidas, motoboy são paulo, entrega expressa, motofrete">
  <meta name="author" content="Caas Express">

  <!-- Open Graph -->
  <meta property="og:title" content="Caas Express - Motoboy Guarulhos | Entregas Rápidas">
  <meta property="og:description"
    content="Serviço de motoboy em Guarulhos com entregas rápidas, seguras e agendadas. Agilidade e confiança para você ou sua empresa.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://caasexpresss.com">
  <meta property="og:image" content="./assets/og-image.jpg">

  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,<?= base64_encode(\Src\Components\Logo::render('symbol', 100, 100)) ?>">

  <!-- DNS Prefetch & Preconnect for Performance -->
  <link rel="dns-prefetch" href="//fonts.googleapis.com">
  <link rel="dns-prefetch" href="//fonts.gstatic.com">
  <link rel="dns-prefetch" href="//images.unsplash.com">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  
  <!-- Preload Critical Fonts -->
  <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap"
    rel="stylesheet" media="print" onload="this.media='all'">
  <noscript>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
  </noscript>

  <!-- Critical CSS Inline (Above the fold) -->
  <style>
    /* Critical CSS for LCP */
    body{font-family:'Inter',-apple-system,sans-serif;margin:0;overflow-x:hidden}
    .header{position:fixed;top:0;left:0;right:0;z-index:300;padding:1rem 0}
    .hero{min-height:100vh;display:flex;align-items:center;background:linear-gradient(135deg,#0D1B2A 0%,#1E3A5F 50%,#B71C1C 100%)}
  </style>

  <!-- Main Styles (non-blocking) -->
  <link rel="preload" href="index.css" as="style">
  <link rel="stylesheet" href="index.css">

  <!-- Schema.org - LocalBusiness + Organization SEO -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "Caas Express",
    "description": "Serviço de motoboy em Guarulhos e São Paulo. Entregas rápidas, seguras e agendadas.",
    "url": "https://caasexpresss.com",
    "telephone": "+55-11-95724-8425",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Guarulhos",
      "addressRegion": "SP",
      "addressCountry": "BR"
    },
    "geo": {
      "@type": "GeoCoordinates",
      "latitude": "-23.4538",
      "longitude": "-46.5333"
    },
    "areaServed": ["Guarulhos", "São Paulo", "Grande São Paulo"],
    "serviceType": ["Motoboy", "Entrega Expressa", "Motofrete"],
    "priceRange": "$$",
    "openingHoursSpecification": {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
      "opens": "07:00",
      "closes": "22:00"
    },
    "sameAs": [
      "https://www.instagram.com/caasexpress",
      "https://www.facebook.com/caasexpress"
    ]
  }
  </script>

  <title>Caas Express - Motoboy Guarulhos | Entregas Rápidas e Seguras</title>
</head>

<body>
  <!-- Header -->
  <header class="header" id="header">
    <div class="container">
      <a href="#" class="logo">
        <?php echo \Src\Components\Logo::render('full', 200, 48); ?>
      </a>

      <nav class="nav">
        <ul class="nav-menu" id="nav-menu">
          <li><a href="#home" class="nav-link active">Início</a></li>
          <li><a href="#servicos" class="nav-link">Serviços</a></li>
          <li><a href="#sobre" class="nav-link">Sobre Nós</a></li>
          <li><a href="#contato" class="nav-link">Contato</a></li>
          <li><a href="blog.php" class="nav-link">Blog</a></li>
        </ul>

        <div class="nav-cta" id="nav-cta">
          <a href="https://wa.me/5511957248425" class="btn btn-whatsapp" target="_blank">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path
                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
            </svg>
            WhatsApp
          </a>
        </div>

        <button class="menu-toggle" id="menu-toggle" aria-label="Menu">
          <span></span>
          <span></span>
          <span></span>
        </button>
      </nav>
    </div>
  </header>

  <!-- Hero Section -->
  <section class="hero" id="home">
    <div class="container">
      <div class="hero-content">
        <div class="hero-text">
          <div class="hero-badge">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path
                d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
            </svg>
            #1 em Guarulhos
          </div>

          <h1>Entregas <span>Rápidas</span> em Guarulhos e SP</h1>

          <p class="hero-description">
            Motoboys profissionais, entregas seguras e pontualidade garantida.
            Sua encomenda chega em minutos com a Caas Express.
          </p>

          <div class="hero-buttons">
            <a href="https://wa.me/5511957248425?text=Olá!%20Gostaria%20de%20solicitar%20uma%20entrega."
              class="btn btn-primary btn-lg" target="_blank">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
              </svg>
              Solicitar Entrega
            </a>
            <a href="#servicos" class="btn btn-secondary btn-lg">
              Conhecer Serviços
            </a>
          </div>

          <div class="hero-features">
            <div class="hero-feature">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
              </svg>
              Entrega em até 30min
            </div>
            <div class="hero-feature">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
              </svg>
              Nota Fiscal
            </div>
            <div class="hero-feature">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
              </svg>
              Rastreamento
            </div>
          </div>
        </div>

        <div class="hero-image">
          <img src="./assets/hero-motoboy.png" 
               alt="Motoboy Caas Express realizando entrega rápida em Guarulhos"
               id="hero-img"
               width="550" height="400"
               fetchpriority="high"
               decoding="async">

          <div class="floating-element delivery">
            <span>10k+</span>
            <small>Entregas realizadas</small>
          </div>

          <div class="floating-element speed">
            <span>30min</span>
            <small>Tempo médio</small>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Services Section -->
  <section class="services" id="servicos">
    <div class="container">
      <div class="section-header animate-on-scroll">
        <div class="section-subtitle">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
            <path
              d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z" />
          </svg>
          Nossos Serviços
        </div>
        <h2 class="section-title">Soluções de Entrega para Cada Necessidade</h2>
        <p class="section-description">
          Oferecemos opções flexíveis para garantir que sua entrega chegue no tempo certo,
          com segurança e profissionalismo.
        </p>
      </div>

      <div class="services-grid">
        <!-- Service Card 1 -->
        <div class="service-card animate-on-scroll">
          <div class="service-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
          </div>
          <h3>Entrega Expressa</h3>
          <p>Atendimento de <strong>segunda a sexta, das 08:00 às 18:00</strong>. Rapidez e eficiência para suas
            demandas diárias em Guarulhos e região.</p>
          <ul class="service-features">
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
              </svg>
              Atendimento em até 15 minutos
            </li>
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
              </svg>
              Documentos e pequenos volumes
            </li>
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
              </svg>
              Confirmação por WhatsApp
            </li>
          </ul>
        </div>

        <!-- Service Card 2 -->
        <div class="service-card animate-on-scroll">
          <div class="service-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </div>
          <h3>Entrega Agendada</h3>
          <p>Organize suas entregas com antecedência. Agende o dia e horário que melhor atende sua empresa para garantir
            disponibilidade.</p>
          <ul class="service-features">
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
              </svg>
              Agendamento flexível
            </li>
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
              </svg>
              Ideal para empresas
            </li>
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
              </svg>
              Rotas otimizadas
            </li>
          </ul>
        </div>

        <!-- Service Card 3 -->
        <div class="service-card animate-on-scroll">
          <div class="service-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
            </svg>
          </div>
          <h3>Entrega Urgente</h3>
          <p>Precisa de entrega fora do horário comercial? Realizamos atendimento mediante <strong>agendamento
              antecipado</strong>.</p>
          <ul class="service-features">
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
              </svg>
              Prioridade absoluta
            </li>
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
              </svg>
              Medicamentos e documentos
            </li>
            <li>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
              </svg>
              Fora de horário sob agendamento
            </li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- Stats Section -->
  <section class="stats">
    <div class="container">
      <div class="stats-grid">
        <div class="stat-item animate-on-scroll">
          <div class="stat-number" data-target="5">0<span>+</span></div>
          <div class="stat-label">Anos de Experiência</div>
        </div>
        <div class="stat-item animate-on-scroll">
          <div class="stat-number" data-target="10000">0<span>+</span></div>
          <div class="stat-label">Entregas Realizadas</div>
        </div>
        <div class="stat-item animate-on-scroll">
          <div class="stat-number" data-target="500">0<span>+</span></div>
          <div class="stat-label">Clientes Satisfeitos</div>
        </div>
        <div class="stat-item animate-on-scroll">
          <div class="stat-number" data-target="98">0<span>%</span></div>
          <div class="stat-label">Taxa de Satisfação</div>
        </div>
      </div>
    </div>
  </section>

  <!-- About Section -->
  <section class="about" id="sobre">
    <div class="container">
      <div class="about-content">
        <div class="about-image animate-on-scroll">
          <img src="./assets/about-team.png" 
               alt="Equipe Caas Express - Motoboys profissionais em Guarulhos"
               id="about-img"
               width="500" height="400"
               loading="lazy"
               decoding="async">
          <div class="about-badge">
            <span>5+</span>
            <small>Anos de Confiança</small>
          </div>
        </div>

        <div class="about-text animate-on-scroll">
          <div class="section-subtitle">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path
                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
            </svg>
            Sobre Nós
          </div>
          <h2>Entregamos Mais do que Encomendas. <br>Entregamos <span style="color: var(--primary);">Confiança</span>.
          </h2>
          <p>
            A Caas Express é uma empresa especializada em serviços de motoboy e entregas rápidas em Guarulhos e região
            metropolitana de São Paulo. Com anos de experiência no mercado, construímos uma reputação sólida baseada em
            agilidade, segurança e compromisso.
          </p>
          <p>
            Nossa equipe de motoboys profissionais está sempre pronta para atender suas demandas, seja para entregas de
            documentos, medicamentos, peças automotivas ou qualquer outro item que precise chegar com rapidez ao seu
            destino.
          </p>

          <div class="about-features">
            <div class="about-feature">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
              </svg>
              Motoboys Treinados
            </div>

            <div class="about-feature">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
              </svg>
              Emissão de NF
            </div>
            <div class="about-feature">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
              </svg>
              Atendimento 24h
            </div>
          </div>

          <a href="https://wa.me/5511957248425" class="btn btn-primary" target="_blank">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path
                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
            </svg>
            Fale Conosco
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact Section -->
  <section class="contact" id="contato">
    <div class="container">
      <div class="contact-content">
        <div class="contact-info animate-on-scroll">
          <div class="section-subtitle">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path
                d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
            </svg>
            Contato
          </div>
          <h2>Entre em <span style="color: var(--primary);">Contato</span></h2>
          <p>Solicite um orçamento ou tire suas dúvidas. Nossa equipe está pronta para atendê-lo com agilidade e
            profissionalismo.</p>

          <div class="contact-cards">
            <div class="contact-card">
              <div class="contact-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                  <path
                    d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                </svg>
              </div>
              <div>
                <h4>Endereço</h4>
                <p>R. Sete de Setembro – Centro<br>Guarulhos – SP, 07011-020</p>
              </div>
            </div>

            <div class="contact-card">
              <div class="contact-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                  <path
                    d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" />
                </svg>
              </div>
              <div>
                <h4>Telefone / WhatsApp</h4>
                <p><a href="tel:+5511957248425">(11) 95724-8425</a></p>
              </div>
            </div>

            <div class="contact-card">
              <div class="contact-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                  <path
                    d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                </svg>
              </div>
              <div>
                <h4>E-mail</h4>
                <p><a href="mailto:contato@caasexpresss.com">contato@caasexpresss.com</a></p>
              </div>
            </div>

            <div class="contact-card">
              <div class="contact-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                  <path
                    d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z" />
                </svg>
              </div>
              <div>
                <h4>Horário de Funcionamento</h4>
                <p>Segunda a Sexta: 08h às 18h<br>Outros horários: Agendamento prévio</p>
              </div>
            </div>
          </div>
        </div>

        <div class="contact-form-wrapper animate-on-scroll">
          <h3 style="margin-bottom: var(--space-lg); color: var(--dark);">Solicite um Orçamento</h3>
          <form class="contact-form" id="contact-form">
            <div class="form-row">
              <div class="form-group">
                <label for="name">Nome Completo *</label>
                <input type="text" id="name" name="name" placeholder="Seu nome" required>
              </div>
              <div class="form-group">
                <label for="company">Nome da Empresa</label>
                <input type="text" id="company" name="company" placeholder="Nome da empresa">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="pickup">Endereço de Coleta *</label>
                <input type="text" id="pickup" name="pickup" placeholder="Endereço de retirada da mercadoria" required>
              </div>
              <div class="form-group">
                <label for="delivery">Endereço de Entrega *</label>
                <input type="text" id="delivery" name="delivery" placeholder="Endereço de destino da mercadoria" required>
              </div>
            </div>

            <div class="form-group">
              <label for="cargo">O que vai ser transportado? *</label>
              <input type="text" id="cargo" name="cargo" placeholder="Ex: Documentos, Caixas de 5kg, Peças automotivas..." required>
            </div>

            <div class="form-group">
              <label for="notes">Observação</label>
              <textarea id="notes" name="notes"
                placeholder="Detalhes adicionais, restrições de horário, etc..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-lg">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
              </svg>
              Enviar Solicitação
            </button>
          </form>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-brand">
          <a href="#" class="logo">
            <div class="logo-icon">🏍️</div>
            Caas <span>Express</span>
          </a>
          <p>
            Sua escolha confiável para serviços de motoboy em Guarulhos e São Paulo.
            Entregas rápidas, seguras e com emissão de nota fiscal.
          </p>
          <div class="footer-social">
            <a href="https://www.facebook.com/caasexpress" target="_blank" aria-label="Facebook">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" />
              </svg>
            </a>
            <a href="https://instagram.com/caas.express" target="_blank" aria-label="Instagram">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z" />
              </svg>
            </a>
            <a href="https://www.youtube.com/channel/UC1ZHrYhWbjBCK8bQQnJCUFA" target="_blank" aria-label="YouTube">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
              </svg>
            </a>
            <a href="https://www.linkedin.com/company/caas-express" target="_blank" aria-label="LinkedIn">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
              </svg>
            </a>
          </div>
        </div>

        <div class="footer-column">
          <h4>Links Rápidos</h4>
          <ul class="footer-links">
            <li><a href="#home">Início</a></li>
            <li><a href="#servicos">Serviços</a></li>
            <li><a href="#sobre">Sobre Nós</a></li>
            <li><a href="#contato">Contato</a></li>
          </ul>
        </div>

        <div class="footer-column">
          <h4>Serviços</h4>
          <ul class="footer-links">
            <li><a href="#servicos">Entrega Expressa</a></li>
            <li><a href="#servicos">Entrega Agendada</a></li>
            <li><a href="#servicos">Entrega Urgente</a></li>
            <li><a href="#servicos">Coleta de Documentos</a></li>
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

  <!-- WhatsApp Floating Button -->
  <div class="whatsapp-float">
    <a href="https://wa.me/5511957248425?text=Olá!%20Gostaria%20de%20solicitar%20uma%20entrega." target="_blank"
      aria-label="Fale conosco pelo WhatsApp">
      <svg viewBox="0 0 24 24" fill="currentColor">
        <path
          d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
      </svg>
    </a>
  </div>

  <!-- JavaScript -->
  <script src="main.js"></script>

  <!-- FAQPage Schema for GEO (AI citations) -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
      {
        "@type": "Question",
        "name": "Qual o tempo de entrega do motoboy em Guarulhos?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "A Caas Express realiza entregas expressas em Guarulhos com tempo médio de 30 minutos a 1 hora, dependendo da distância. Também oferecemos entregas urgentes com prioridade máxima."
        }
      },
      {
        "@type": "Question",
        "name": "A Caas Express atende quais regiões de São Paulo?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "Atendemos toda a Grande São Paulo, incluindo Guarulhos, São Paulo Capital, ABC Paulista (Santo André, São Bernardo, São Caetano), Osasco, e região metropolitana."
        }
      },
      {
        "@type": "Question",
        "name": "Quais tipos de entregas a Caas Express faz?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "Realizamos entregas expressas, agendadas, urgentes, coleta de documentos, pequenos volumes, amostras para laboratórios, e atendimento corporativo para empresas."
        }
      },
      {
        "@type": "Question",
        "name": "Como solicitar um motoboy da Caas Express?",
        "acceptedAnswer": {
          "@type": "Answer",
          "text": "Você pode solicitar nossos serviços pelo WhatsApp (11) 95724-8425, pelo site, ou ligando diretamente. Atendemos de segunda a sábado, das 7h às 22h."
        }
      }
    ]
  }
  </script>

  <!-- Scroll Reveal Animation Script -->
  <script>
    // Scroll Reveal for elements
    const revealElements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
    
    const revealOnScroll = () => {
      const windowHeight = window.innerHeight;
      revealElements.forEach(el => {
        const elementTop = el.getBoundingClientRect().top;
        if (elementTop < windowHeight - 100) {
          el.classList.add('active');
        }
      });
    };

    window.addEventListener('scroll', revealOnScroll);
    window.addEventListener('load', revealOnScroll);
  </script>
</body>

</html>