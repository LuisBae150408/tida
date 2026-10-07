<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();
require_once __DIR__ . '/../includes/data.php';

$pdo = get_pdo();
$totalCategorias = (int) $pdo->query('SELECT COUNT(*) FROM categorias')->fetchColumn();
$totalProductos = (int) $pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn();
$totalDescuentosActivos = (int) $pdo->query("SELECT COUNT(*) FROM descuentos WHERE activo = 1")->fetchColumn();
$promo = get_promocion();

$adminPageTitle = 'Panel';
$adminPageSubtitle = 'Resumen rápido de ' . $config['nombre_sitio'];
require __DIR__ . '/includes/layout-top.php';
?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-num"><?= $totalCategorias ?></div>
    <div class="stat-label">Categorías</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $totalProductos ?></div>
    <div class="stat-label">Productos</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $totalDescuentosActivos ?></div>
    <div class="stat-label">Descuentos activos</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $promo ? 'Sí' : 'No' ?></div>
    <div class="stat-label">Promoción visible en la landing</div>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-head">
    <h2>Accesos rápidos</h2>
  </div>
  <div class="stat-grid">
    <a class="btn btn-primary-solid" style="justify-content:center" href="categorias.php">Editar categorías</a>
    <a class="btn btn-primary-solid" style="justify-content:center" href="productos.php">Editar productos</a>
    <a class="btn btn-primary-solid" style="justify-content:center" href="descuentos.php">Editar descuentos</a>
    <a class="btn btn-primary-solid" style="justify-content:center" href="configuracion.php">Configuración del sitio</a>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
