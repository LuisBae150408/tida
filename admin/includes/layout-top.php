<?php
/**
 * Incluir DESPUÉS de require_admin_login() y de definir, si aplica,
 * $adminPageTitle / $adminPageSubtitle. Requiere que $config ya
 * exista (viene de includes/data.php).
 */
require_once __DIR__ . '/../../includes/auth.php';
require_admin_login(); // por si este archivo se pidiera directo
$current = basename($_SERVER['PHP_SELF']);
$navLinks = [
    'index.php'         => ['Panel', '<path d="M3 10.5 12 4l9 6.5" /><path d="M5 9.5V20h14V9.5" />'],
    'categorias.php'     => ['Categorías', '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>'],
    'productos.php'      => ['Productos', '<path d="M4 8h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V8Z"/><path d="M17 9h1.5a2.5 2.5 0 0 1 0 5H17"/>'],
    'descuentos.php'     => ['Descuentos', '<circle cx="8" cy="8" r="2.2"/><circle cx="16" cy="16" r="2.2"/><path d="M6 18 18 6"/>'],
    'configuracion.php'  => ['Configuración', '<circle cx="12" cy="12" r="3"/><path d="M19.4 13a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.04 1.56V19a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 8.96 17.34a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.7 1.7 0 0 0 .34-1.87 1.7 1.7 0 0 0-1.56-1.04H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.66 6.96a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.7 1.7 0 0 0 1.87.34H9a1.7 1.7 0 0 0 1.04-1.56V1a2 2 0 1 1 4 0v.09a1.7 1.7 0 0 0 1.04 1.56 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.7 1.7 0 0 0-.34 1.87V9a1.7 1.7 0 0 0 1.56 1.04H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.51 1.05Z"/>'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= htmlspecialchars($adminPageTitle ?? 'Panel') ?> — Admin <?= htmlspecialchars($config['nombre_sitio'] ?? 'Tida') ?></title>
<link rel="icon" type="image/png" href="../assets/icono-navy.png" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Work+Sans:wght@400;500;600&display=swap">
<link rel="stylesheet" href="assets/admin.css" />
</head>
<body class="admin-body">
<div class="admin-shell">

  <aside class="admin-sidebar">
    <div class="admin-brand">
      <img src="../assets/icono.png" alt="" />
      <span>Admin</span>
    </div>
    <nav class="admin-nav">
      <?php foreach ($navLinks as $href => [$label, $iconPaths]): ?>
      <a href="<?= $href ?>" class="<?= $current === $href ? 'is-active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><?= $iconPaths ?></svg>
        <span><?= $label ?></span>
      </a>
      <?php endforeach; ?>
    </nav>
    <div class="admin-sidebar-foot">
      <p class="admin-user">Sesión: <?= htmlspecialchars($_SESSION['admin_usuario'] ?? '') ?></p>
      <form method="post" action="logout.php">
        <?= csrf_field() ?>
        <button class="admin-logout" type="submit">
          <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
          Cerrar sesión
        </button>
      </form>
    </div>
  </aside>

  <main class="admin-main">
    <div class="admin-topbar">
      <div>
        <h1 class="admin-title"><?= htmlspecialchars($adminPageTitle ?? '') ?></h1>
        <?php if (!empty($adminPageSubtitle)): ?><p class="admin-subtitle"><?= htmlspecialchars($adminPageSubtitle) ?></p><?php endif; ?>
      </div>
    </div>

    <?php if (!empty($_SESSION['flash'])): ?>
      <div class="admin-flash <?= htmlspecialchars($_SESSION['flash']['type']) ?>"><?= htmlspecialchars($_SESSION['flash']['msg']) ?></div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
