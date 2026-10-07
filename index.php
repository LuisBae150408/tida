<?php
require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/helpers.php';

// Productos marcados como "destacado" -> pool de la vitrina del hero.
// landing.js elige 8 al azar de este arreglo en cada carga.
$vitrinaPool = array_values(array_map(
    fn($p) => [
        'name' => $p['nombre'],
        'categoryId' => $p['categoria_id'],
        'icon' => categoria_icono_svg($p['categoria_id']),
    ],
    array_filter($productos, fn($p) => !empty($p['destacado']))
));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
<meta name="description" content="<?= htmlspecialchars($config['subtitulo']) ?> — <?= htmlspecialchars($config['nombre_sitio']) ?>, <?= htmlspecialchars($config['direccion']) ?>." />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Work+Sans:wght@400;500;600&display=swap">
<title><?= htmlspecialchars($config['nombre_sitio']) ?></title>
<link rel="icon" type="image/png" href="assets/icono-navy.png" />
<link rel="stylesheet" href="landing.css" />
</head>
<body>

  <header class="site-header">
    <div class="brand">
      <img class="brand-icon" src="assets/icono.png" alt="Tida" />
    </div>
    <a class="header-cta" href="menu/index.php">Ver menú</a>
  </header>

  <!-- ============ HERO + VITRINA (100vh en móvil, dos columnas en escritorio) ============ -->
  <section class="hero theme-navy">
    <?php if ($hero_fondos): ?>
    <div class="hero-bg" aria-hidden="true">
      <?php foreach ($hero_fondos as $i => $hf): ?>
        <img class="hero-bg-img<?= $i === 0 ? ' is-active' : '' ?>" src="<?= htmlspecialchars($hf['imagen']) ?>" alt="" />
      <?php endforeach; ?>
      <div class="hero-bg-tint"></div>
    </div>
    <?php endif; ?>

    <div class="hero-flourish flourish-pos-c" aria-hidden="true"><svg viewBox="0 0 600 300" xmlns="http://www.w3.org/2000/svg"><path d="M20,190 C20,110 95,55 165,80 C220,100 205,165 140,165 C205,168 250,95 330,78 C410,62 470,105 466,158 C462,202 410,222 372,198"/></svg></div>
    <div class="hero-flourish flourish-pos-a" aria-hidden="true"><svg viewBox="0 0 600 300" xmlns="http://www.w3.org/2000/svg"><path d="M480,50 C525,95 512,168 438,180 C360,192 305,132 342,82 C298,118 242,196 165,205 C95,213 40,178 34,126"/></svg></div>
    <div class="hero-flourish flourish-pos-d" aria-hidden="true"><svg viewBox="0 0 600 300" xmlns="http://www.w3.org/2000/svg"><path d="M60,60 C140,40 200,90 170,140 C145,182 80,180 70,140 C130,190 210,220 300,200 C400,178 420,120 500,100 C540,90 570,100 580,130"/></svg></div>

    <div class="hero-main">
      <div class="hero-head">
        <p class="hero-kicker">Bienvenido a</p>
        <img src="assets/logo-full-white.png" alt="<?= htmlspecialchars($config['nombre_sitio']) ?>" class="hero-logo-full" />
        <p class="hero-sub"><?= htmlspecialchars($config['subtitulo']) ?></p>

        <div class="hero-features">
          <span class="hero-feature"><svg viewBox="0 0 24 24" fill="none" stroke="#d1c49b" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V8Z"/><path d="M17 9h1.5a2.5 2.5 0 0 1 0 5H17"/><path d="M8 3.5c-.6.7-.6 1.3 0 2s.6 1.3 0 2M12 3.5c-.6.7-.6 1.3 0 2s.6 1.3 0 2"/></svg>Café de especialidad</span>
          <span class="hero-feature"><svg viewBox="0 0 24 24" fill="none" stroke="#d1c49b" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 20c8 0 14-6 14-14 0 0-9-1-13 4S5 20 5 20Z"/><path d="M5 20c2-4 4-7 8-10"/></svg>Ingredientes locales</span>
          <span class="hero-feature"><svg viewBox="0 0 24 24" fill="none" stroke="#d1c49b" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12.5V19a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-6.5"/><path d="M4 12.5a8 6.5 0 0 1 16 0"/><path d="M9 13.5c1 1 2 1 3 0s2-1 3 0" stroke-width="1.3"/></svg>Repostería artesanal</span>
        </div>

        <div class="hero-actions">
          <a class="btn-primary" href="menu/index.php">Ver menú completo</a>
          <a class="btn-ghost" href="#ubicacion">Cómo llegar</a>
        </div>
      </div>

      <div class="showcase" id="showcase" aria-label="Vitrina de productos — arrastra o usa las flechas para girar">
        <button class="showcase-arrow arrow-left" id="arrowLeft" aria-label="Girar a la izquierda">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-7 7 7 7"/></svg>
        </button>

        <div class="showcase-stage">
          <div class="showcase-ring" id="showcaseRing"></div>
          <div class="showcase-shadow" aria-hidden="true"></div>
        </div>

        <button class="showcase-arrow arrow-right" id="arrowRight" aria-label="Girar a la derecha">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5l7 7-7 7"/></svg>
        </button>
      </div>
    </div>

    <button class="hero-scroll-hint" id="scrollHint" aria-label="Ver más">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
    </button>
  </section>

  <?php if ($promocion): ?>
  <!-- ============ PROMOCIÓN DESTACADA (editable desde /admin) ============ -->
  <section class="section promo-section">
    <div class="promo-card">
      <?php if ($promocion['tipo'] === 'video'): ?>
        <video class="promo-photo" src="<?= htmlspecialchars($promocion['video']) ?>" autoplay muted loop playsinline></video>
      <?php else: ?>
        <img class="promo-photo" src="<?= htmlspecialchars($promocion['imagen']) ?>" alt="" />
      <?php endif; ?>
      <div class="promo-info">
        <?php if (!empty($promocion['titulo'])): ?>
          <h2 class="promo-title"><?= htmlspecialchars($promocion['titulo']) ?></h2>
        <?php endif; ?>
        <?php if (!empty($promocion['texto'])): ?>
          <p class="promo-text"><?= htmlspecialchars($promocion['texto']) ?></p>
        <?php endif; ?>
        <a class="btn-primary" href="menu/index.php">Ver menú</a>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if (!empty($config['instagram_post_activo']) && !empty($config['instagram_post_url'])): ?>
  <!-- ============ PUBLICACIÓN DE INSTAGRAM ELEGIDA (editable desde /admin) ============ -->
  <section class="section instagram-section">
    <header class="section-head">
      <p class="section-kicker">Desde Instagram</p>
      <h2 class="section-title"><span class="script-part">Síguenos</span><span class="bold-part">de cerca</span></h2>
    </header>
    <div class="instagram-embed-wrap">
      <blockquote
        class="instagram-media"
        data-instgrm-permalink="<?= htmlspecialchars($config['instagram_post_url']) ?>"
        data-instgrm-version="14"
      >
        <a href="<?= htmlspecialchars($config['instagram_post_url']) ?>" target="_blank" rel="noopener">Ver esta publicación en Instagram</a>
      </blockquote>
    </div>
    <?php if (!empty($config['instagram'])): ?>
      <a class="btn-ghost instagram-follow" href="https://instagram.com/<?= urlencode($config['instagram']) ?>" target="_blank" rel="noopener">Ver más en @<?= htmlspecialchars($config['instagram']) ?></a>
    <?php endif; ?>
  </section>
  <script async src="//www.instagram.com/embed.js"></script>
  <?php endif; ?>

  <!-- ============ CATEGORÍAS ============ -->
  <section class="section categories-section section-bleed theme-sage">
    <div class="deco-fade" style="background:linear-gradient(to bottom, var(--navy), transparent);"></div>
    <div class="deco-flourish deco-head side-left" aria-hidden="true"><svg viewBox="0 0 600 300" xmlns="http://www.w3.org/2000/svg"><path d="M20,126 C26,178 80,213 150,205 C227,196 187,118 232,82 C264,132 308,192 386,180 C460,168 505,95 480,50"/></svg></div>
    <div class="deco-flourish deco-body side-left" aria-hidden="true"><svg viewBox="0 0 600 300" xmlns="http://www.w3.org/2000/svg"><path d="M15,110 C110,45 250,42 345,95 C400,126 398,158 452,150 C500,143 528,105 566,82 M120,220 C160,196 200,196 226,220"/></svg></div>
    <img class="deco-watermark wm-right" src="assets/icono-navy.png" alt="" aria-hidden="true" />
    <div class="section-inner">
      <header class="section-head">
        <p class="section-kicker">El menú completo</p>
        <h2 class="section-title"><span class="script-part">Explora</span><span class="bold-part">Categorías</span></h2>
      </header>
      <div class="categories-grid">
        <?php foreach ($categorias as $cat): ?>
        <a class="category-card" href="menu/index.php#<?= htmlspecialchars($cat['id']) ?>">
          <span class="category-card-icon"><?= categoria_icono_svg($cat['id']) ?></span>
          <span class="category-card-name"><?= htmlspecialchars($cat['nombre']) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ============ DESTACADOS / RECOMENDADOS DEL BARISTA ============ -->
  <section class="section highlights-section section-bleed theme-coral">
    <div class="deco-fade" style="background:linear-gradient(to bottom, var(--sage), transparent);"></div>
    <div class="deco-flourish deco-head side-right" aria-hidden="true"><svg viewBox="0 0 600 300" xmlns="http://www.w3.org/2000/svg"><path d="M60,60 C140,40 200,90 170,140 C145,182 80,180 70,140 C130,190 210,220 300,200 C400,178 420,120 500,100 C540,90 570,100 580,130"/></svg></div>
    <div class="deco-flourish deco-body side-right" aria-hidden="true"><svg viewBox="0 0 600 300" xmlns="http://www.w3.org/2000/svg"><path d="M20,190 C20,110 95,55 165,80 C220,100 205,165 140,165 C205,168 250,95 330,78 C410,62 470,105 466,158 C462,202 410,222 372,198"/></svg></div>
    <img class="deco-watermark wm-left" src="assets/icono-navy.png" alt="" aria-hidden="true" />
    <div class="section-inner">
      <header class="section-head">
        <p class="section-kicker">Sello de la casa</p>
        <h2 class="section-title"><span class="script-part">Recomendados</span><span class="bold-part">Del Barista</span></h2>
      </header>
      <div class="highlights-strip">
        <?php foreach ($highlights as $h): ?>
        <span class="highlight-pill">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 20c8 0 14-6 14-14 0 0-9-1-13 4S5 20 5 20Z"/><path d="M5 20c2-4 4-7 8-10"/></svg>
          <?= htmlspecialchars($h) ?>
        </span>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ============ UBICACIÓN Y HORARIOS ============ -->
  <section class="section location-section" id="ubicacion">
    <div class="location-card">
      <div class="location-info">
        <p class="section-kicker">Visítanos</p>
        <h2 class="section-title"><span class="script-part">Ubicación</span><span class="bold-part">Y Horarios</span></h2>
        <ul class="location-list">
          <li><strong>Dirección:</strong> <?= htmlspecialchars($config['direccion']) ?></li>
          <li><strong>Horario:</strong> <?= htmlspecialchars($config['horario_semana']) ?></li>
          <li><strong>Domingos:</strong> <?= htmlspecialchars($config['horario_domingo']) ?></li>
        </ul>
        <div class="location-actions">
          <a class="btn-primary" href="menu/index.php">Ver menú y pedir</a>
          <a class="btn-ghost" href="#" id="locationMapLink">Cómo llegar</a>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ FOOTER ============ -->
  <footer class="site-footer">
    <img class="footer-icon" src="assets/icono-navy.png" alt="" />
    <p class="footer-name"><?= htmlspecialchars($config['nombre_sitio']) ?></p>
    <?php if (!empty($config['instagram'])): ?>
    <div class="footer-social">
      <a href="https://instagram.com/<?= urlencode($config['instagram']) ?>" target="_blank" rel="noopener">@<?= htmlspecialchars($config['instagram']) ?></a>
    </div>
    <?php endif; ?>
    <p class="footer-copy">© <span id="footerYear"></span> Tida. Todos los derechos reservados.</p>
  </footer>

  <script>
    // Pool de productos para la vitrina — generado por PHP a partir de
    // includes/data.php (productos con destacado = true). landing.js
    // elige 8 al azar de este arreglo en cada carga de la página.
    window.PRODUCT_POOL = <?= json_encode($vitrinaPool, JSON_UNESCAPED_UNICODE) ?>;
  </script>
  <script src="landing.js"></script>
</body>
</html>
