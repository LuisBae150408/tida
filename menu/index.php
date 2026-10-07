<?php
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/helpers.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="Menú de <?= htmlspecialchars($config['nombre_sitio']) ?>: cafés clásicos y de especialidad, frappés, bebidas saludables, brunch y pastelería." />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Work+Sans:wght@400;500;600&display=swap">
<title>Menú — <?= htmlspecialchars($config['nombre_sitio']) ?></title>
<link rel="icon" type="image/png" href="../assets/icono-navy.png" />
<link rel="stylesheet" href="styles.css" />
</head>
<body>

  <header class="site-header">
    <div class="brand">
      <img class="brand-icon" src="../assets/icono.png" alt="Tida" />
    </div>
    <div class="header-actions">
      <button class="cart-toggle" id="cart-toggle" aria-label="Abrir carrito" aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="19" cy="21" r="1"></circle>
          <path d="M2.5 3h2l2.6 12.6a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L21.5 7H6"></path>
        </svg>
        <span class="cart-badge is-hidden" id="cart-badge">0</span>
      </button>
      <button class="hamburger" id="nav-toggle" aria-label="Ver categorías" aria-expanded="false">
        <svg viewBox="0 0 22 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
          <path d="M0 1h22M0 8h22M0 15h22"></path>
        </svg>
      </button>
    </div>
  </header>

  <nav class="category-nav" id="category-nav" aria-label="Categorías del menú">
    <?php foreach ($categorias as $i => $cat): ?>
      <button class="nav-pill<?= $i === 0 ? ' is-active' : '' ?>" data-target="<?= htmlspecialchars($cat['id']) ?>"><?= htmlspecialchars($cat['nombre']) ?></button>
    <?php endforeach; ?>
  </nav>

  <section class="hero">
    <div class="hero-flourish flourish-pos-c" aria-hidden="true"><svg viewBox="0 0 600 300" xmlns="http://www.w3.org/2000/svg"><path d="M20,190 C20,110 95,55 165,80 C220,100 205,165 140,165 C205,168 250,95 330,78 C410,62 470,105 466,158 C462,202 410,222 372,198"/></svg></div>
    <div class="hero-flourish flourish-pos-a" aria-hidden="true"><svg viewBox="0 0 600 300" xmlns="http://www.w3.org/2000/svg"><path d="M480,50 C525,95 512,168 438,180 C360,192 305,132 342,82 C298,118 242,196 165,205 C95,213 40,178 34,126"/></svg></div>
    <div class="hero-flourish flourish-pos-d" aria-hidden="true"><svg viewBox="0 0 600 300" xmlns="http://www.w3.org/2000/svg"><path d="M60,60 C140,40 200,90 170,140 C145,182 80,180 70,140 C130,190 210,220 300,200 C400,178 420,120 500,100 C540,90 570,100 580,130"/></svg></div>
    <div class="hero-inner">
      <p class="hero-script">Menú</p>
      <p class="hero-desc">Cafés clásicos y de especialidad, frappés, bebidas saludables, brunch y pastelería. Arma tu pedido y revisa el total en tiempo real.</p>
    </div>
  </section>

  <main class="menu-sections" id="menu-sections">
    <?php foreach ($categorias as $idx => $cat):
        $prevTema = $idx > 0 ? $categorias[$idx - 1]['tema'] : 'navy'; // el hero siempre es navy
        $prevColor = tema_color($prevTema);
        $curColor = tema_color($cat['tema']);
        $items = productos_de_categoria($productos, $cat['id']);
    ?>
    <section class="category theme-<?= htmlspecialchars($cat['tema']) ?>" id="<?= htmlspecialchars($cat['id']) ?>">
      <?php if ($prevColor !== $curColor): ?>
        <div class="category-fade" style="background:linear-gradient(to bottom, <?= $prevColor ?>, transparent);"></div>
      <?php endif; ?>
      <?= category_watermark_html($idx) ?>
      <?= category_flourishes_html($idx) ?>
      <header class="category-head">
        <p class="category-kicker"><?= htmlspecialchars($cat['kicker']) ?></p>
        <h2 class="category-title"><?= categoria_titulo_html($cat['nombre']) ?></h2>
        <p class="category-tagline"><?= htmlspecialchars($cat['tagline']) ?></p>
        <div class="category-rule"></div>
      </header>
      <div class="row-list">
        <?php foreach ($items as $item): $sinPrecio = $item['precio'] === null; ?>
        <div class="row-group">
        <article class="product-row" data-id="<?= htmlspecialchars($item['id']) ?>">
          <div class="row-media" aria-hidden="true"><?= producto_media_html($item, $cat['id'], '../') ?></div>
          <div class="row-body">
            <div class="row-heading">
              <h3><?= htmlspecialchars($item['nombre']) ?></h3>
              <span class="row-price"><?= precio_html($item) ?></span>
            </div>
            <p class="row-desc"><?= htmlspecialchars($item['descripcion']) ?></p>
          </div>
          <button
            class="row-add"
            data-id="<?= htmlspecialchars($item['id']) ?>"
            data-name="<?= htmlspecialchars($item['nombre']) ?>"
            data-price="<?= $sinPrecio ? '' : $item['precio'] ?>"
            data-category="<?= htmlspecialchars($cat['nombre']) ?>"
            aria-label="Agregar <?= htmlspecialchars($item['nombre']) ?>"
            <?= $sinPrecio ? 'disabled' : '' ?>
          >
            <span class="add-btn-icon">+</span>
          </button>
        </article>
        <?php if (!$sinPrecio): ?>
        <div class="row-comment-box" id="comment-<?= htmlspecialchars($item['id']) ?>" data-id="<?= htmlspecialchars($item['id']) ?>">
          <input type="text" class="row-comment-input" placeholder="Comentario para este producto (opcional). Ej. sin queso" maxlength="140">
          <div class="row-comment-actions">
            <button type="button" class="row-comment-cancel">Cancelar</button>
            <button type="button" class="row-comment-confirm">Agregar al carrito</button>
          </div>
        </div>
        <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endforeach; ?>
  </main>

  <footer class="site-footer">
    <img class="footer-icon" src="../assets/icono-navy.png" alt="" />
    <p>Precios expresados en <strong>USD</strong>. Sujeto a disponibilidad del día.</p>
  </footer>

  <!-- Overlay compartido por los dos paneles (carrito y categorías) -->
  <div class="drawer-scrim" id="drawer-scrim"></div>

  <!-- Categorías (panel del botón hamburguesa) -->
  <aside class="nav-drawer" id="nav-drawer" aria-label="Categorías del menú">
    <div class="cart-header">
      <h2>Categorías</h2>
      <button class="cart-close" id="nav-drawer-close" aria-label="Cerrar categorías">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
          <path d="M2 2l12 12M14 2L2 14"></path>
        </svg>
      </button>
    </div>
    <nav class="nav-drawer-list">
      <?php foreach ($categorias as $cat): ?>
        <button class="nav-drawer-item" data-target="<?= htmlspecialchars($cat['id']) ?>">
          <span class="nav-drawer-item-icon" aria-hidden="true"><?= categoria_icono_svg($cat['id']) ?></span>
          <span class="nav-drawer-item-text">
            <span class="nav-drawer-item-name"><?= htmlspecialchars($cat['nombre']) ?></span>
            <?php if ($cat['kicker']): ?><span class="nav-drawer-item-kicker"><?= htmlspecialchars($cat['kicker']) ?></span><?php endif; ?>
          </span>
        </button>
      <?php endforeach; ?>
    </nav>
  </aside>

  <!-- Carrito -->
  <aside class="cart-drawer" id="cart-drawer" aria-label="Carrito de compras">
    <div class="cart-header">
      <h2>Tu pedido</h2>
      <button class="cart-close" id="cart-close" aria-label="Cerrar carrito">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
          <path d="M2 2l12 12M14 2L2 14"></path>
        </svg>
      </button>
    </div>
    <div class="cart-body">
      <div class="cart-empty" id="cart-empty">
        <img src="../assets/icono-navy.png" alt="" class="cart-empty-icon" />
        <p>Tu carrito está vacío.<br>Agrega algo del menú para comenzar.</p>
      </div>
      <ul class="cart-items" id="cart-items"></ul>
    </div>
    <div class="cart-footer is-hidden" id="cart-footer">
      <div class="cart-total-row">
        <span class="cart-total-label">Total</span>
        <span class="cart-total-value" id="cart-total">$0</span>
      </div>
      <button class="cart-checkout" id="cart-checkout">Finalizar pedido</button>
    </div>
  </aside>

  <script src="script.js"></script>
</body>
</html>
