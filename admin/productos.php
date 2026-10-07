<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/uploads.php';

$pdo = get_pdo();
$categoriasOpts = $pdo->query('SELECT id, nombre FROM categorias ORDER BY orden ASC, nombre ASC')->fetchAll();

if (!$categoriasOpts) {
    set_flash('error', 'Primero crea al menos una categoría antes de agregar productos.');
    header('Location: categorias.php');
    exit;
}

function producto_desde_post(): array
{
    return [
        'categoria_id' => $_POST['categoria_id'] ?? '',
        'nombre' => trim($_POST['nombre'] ?? ''),
        'descripcion' => trim($_POST['descripcion'] ?? ''),
        'precio' => trim($_POST['precio'] ?? '') === '' ? null : (float) $_POST['precio'],
        'nota' => trim($_POST['nota'] ?? ''),
        'destacado' => isset($_POST['destacado']) ? 1 : 0,
        'orden' => (int) ($_POST['orden'] ?? 0),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $d = producto_desde_post();
        $catValida = in_array($d['categoria_id'], array_column($categoriasOpts, 'id'), true);

        if ($d['nombre'] === '' || !$catValida) {
            set_flash('error', 'Faltan datos obligatorios (nombre y categoría).');
        } else {
            $baseId = slugify($d['nombre']);
            $id = $baseId;
            $i = 2;
            $check = $pdo->prepare('SELECT COUNT(*) FROM productos WHERE id = ?');
            while (true) {
                $check->execute([$id]);
                if ((int) $check->fetchColumn() === 0) break;
                $id = $baseId . '-' . $i++;
            }

            $imagen = null;
            try {
                if (!empty($_FILES['imagen']['name'])) {
                    $imagen = subir_imagen_webp($_FILES['imagen'], 'uploads/productos', $id);
                }
                $pdo->prepare('INSERT INTO productos (id, categoria_id, nombre, descripcion, precio, nota, imagen, destacado, orden) VALUES (?,?,?,?,?,?,?,?,?)')
                    ->execute([$id, $d['categoria_id'], $d['nombre'], $d['descripcion'], $d['precio'], $d['nota'] ?: null, $imagen, $d['destacado'], $d['orden']]);
                set_flash('ok', 'Producto "' . $d['nombre'] . '" creado.');
            } catch (RuntimeException $e) {
                set_flash('error', $e->getMessage());
            }
        }
    }

    if ($accion === 'guardar') {
        $id = $_POST['id'] ?? '';
        $d = producto_desde_post();
        $catValida = in_array($d['categoria_id'], array_column($categoriasOpts, 'id'), true);

        if ($id === '' || $d['nombre'] === '' || !$catValida) {
            set_flash('error', 'Faltan datos obligatorios.');
        } else {
            try {
                $actual = $pdo->prepare('SELECT imagen FROM productos WHERE id = ?');
                $actual->execute([$id]);
                $imagenActual = $actual->fetchColumn();
                $imagenNueva = $imagenActual;

                if (!empty($_FILES['imagen']['name'])) {
                    $imagenNueva = subir_imagen_webp($_FILES['imagen'], 'uploads/productos', $id);
                    borrar_imagen_si_existe($imagenActual ?: null);
                } elseif (!empty($_POST['quitar_imagen'])) {
                    borrar_imagen_si_existe($imagenActual ?: null);
                    $imagenNueva = null;
                }

                $pdo->prepare('UPDATE productos SET categoria_id=?, nombre=?, descripcion=?, precio=?, nota=?, imagen=?, destacado=?, orden=? WHERE id=?')
                    ->execute([$d['categoria_id'], $d['nombre'], $d['descripcion'], $d['precio'], $d['nota'] ?: null, $imagenNueva, $d['destacado'], $d['orden'], $id]);
                set_flash('ok', 'Producto actualizado.');
            } catch (RuntimeException $e) {
                set_flash('error', $e->getMessage());
            }
        }
    }

    if ($accion === 'eliminar') {
        $id = $_POST['id'] ?? '';
        $actual = $pdo->prepare('SELECT imagen FROM productos WHERE id = ?');
        $actual->execute([$id]);
        $imagenActual = $actual->fetchColumn();
        $pdo->prepare('DELETE FROM productos WHERE id = ?')->execute([$id]);
        borrar_imagen_si_existe($imagenActual ?: null);
        set_flash('ok', 'Producto eliminado.');
    }

    header('Location: productos.php');
    exit;
}

$productosList = $pdo->query(
    'SELECT p.*, c.nombre AS categoria_nombre FROM productos p
     JOIN categorias c ON c.id = p.categoria_id
     ORDER BY c.orden ASC, p.orden ASC, p.nombre ASC'
)->fetchAll();

$porCategoria = [];
foreach ($productosList as $p) {
    $porCategoria[$p['categoria_id']][] = $p;
}

$adminPageTitle = 'Productos';
$adminPageSubtitle = count($productosList) . ' productos en ' . count($categoriasOpts) . ' categorías.';
require __DIR__ . '/includes/layout-top.php';
?>

<?php foreach ($categoriasOpts as $cat): $items = $porCategoria[$cat['id']] ?? []; ?>
<div class="admin-card">
  <div class="admin-card-head">
    <h2><?= htmlspecialchars($cat['nombre']) ?> (<?= count($items) ?>)</h2>
  </div>
  <div class="admin-list">
    <?php foreach ($items as $p): $editId = 'edit-prod-' . $p['id']; $confirmMsg = '¿Eliminar "' . $p['nombre'] . '"? También se borrará su foto y cualquier descuento asociado.'; ?>
    <div class="admin-row">
      <div class="admin-row-media">
        <?php if (!empty($p['imagen'])): ?>
          <img src="../<?= htmlspecialchars($p['imagen']) ?>" alt="">
        <?php else: ?>
          <?= categoria_icono_svg($p['categoria_id']) ?>
        <?php endif; ?>
      </div>
      <div class="admin-row-body">
        <div class="admin-row-title"><?= htmlspecialchars($p['nombre']) ?></div>
        <div class="admin-row-sub"><?= $p['precio'] !== null ? fmt_precio((float) $p['precio']) : htmlspecialchars($p['nota'] ?: 'sin precio') ?> · orden <?= (int) $p['orden'] ?></div>
      </div>
      <span class="admin-row-tag <?= $p['destacado'] ? '' : 'is-off' ?>"><?= $p['destacado'] ? 'Destacado' : 'Normal' ?></span>
      <div class="admin-row-actions">
        <button type="button" class="btn-edit-toggle" data-edit-toggle="<?= htmlspecialchars($editId) ?>">Editar</button>
      </div>
    </div>

    <form class="edit-bar" id="<?= htmlspecialchars($editId) ?>" method="post" action="productos.php" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">

      <div class="field">
        <label>Categoría</label>
        <select name="categoria_id">
          <?php foreach ($categoriasOpts as $c2): ?>
            <option value="<?= htmlspecialchars($c2['id']) ?>" <?= $c2['id'] === $p['categoria_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c2['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Nombre</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($p['nombre']) ?>" required>
      </div>
      <div class="field field-full">
        <label>Descripción</label>
        <textarea name="descripcion"><?= htmlspecialchars($p['descripcion'] ?? '') ?></textarea>
      </div>
      <div class="field">
        <label>Precio (USD)</label>
        <input type="number" step="0.01" min="0" name="precio" value="<?= $p['precio'] !== null ? htmlspecialchars((string) $p['precio']) : '' ?>" placeholder="vacío = sin precio fijo">
      </div>
      <div class="field">
        <label>Nota (si no tiene precio fijo)</label>
        <input type="text" name="nota" value="<?= htmlspecialchars($p['nota'] ?? '') ?>" placeholder="Ej. Precio por unidad">
      </div>
      <div class="field">
        <label>Orden dentro de la categoría</label>
        <input type="number" name="orden" value="<?= (int) $p['orden'] ?>">
      </div>
      <div class="field">
        <label>&nbsp;</label>
        <label class="toggle-switch">
          <input type="checkbox" name="destacado" <?= $p['destacado'] ? 'checked' : '' ?>>
          <span class="track"></span>
          <span class="label-text">Destacado (aparece en la vitrina de la portada)</span>
        </label>
      </div>
      <div class="field field-full">
        <label>Foto del producto</label>
        <div class="field-file">
          <img class="preview" src="<?= !empty($p['imagen']) ? '../' . htmlspecialchars($p['imagen']) : '../assets/icono-navy.png' ?>" alt="">
          <input type="file" name="imagen" accept="image/png,image/jpeg,image/webp">
        </div>
        <?php if (!empty($p['imagen'])): ?>
          <label class="toggle-switch" style="margin-top:6px;">
            <input type="checkbox" name="quitar_imagen">
            <span class="track"></span>
            <span class="label-text">Quitar la foto actual (vuelve al ícono de categoría)</span>
          </label>
        <?php endif; ?>
        <span class="field-hint">Se comprime automáticamente a WebP al subirla.</span>
      </div>

      <div class="edit-bar-actions">
        <button type="submit" name="accion" value="guardar" class="btn btn-save">Guardar</button>
        <button type="submit" name="accion" value="eliminar" class="btn btn-danger" data-confirm="<?= htmlspecialchars($confirmMsg) ?>">Eliminar</button>
        <button type="button" class="btn btn-discard">Descartar</button>
      </div>
    </form>
    <?php endforeach; ?>

    <?php if (!$items): ?>
      <p class="field-hint">Todavía no hay productos en esta categoría.</p>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<div class="admin-card">
  <div class="admin-card-head"><h2>Nuevo producto</h2></div>
  <form method="post" action="productos.php" enctype="multipart/form-data" class="edit-bar is-open" style="display:grid;">
    <?= csrf_field() ?>
    <div class="field">
      <label>Categoría</label>
      <select name="categoria_id">
        <?php foreach ($categoriasOpts as $c2): ?>
          <option value="<?= htmlspecialchars($c2['id']) ?>"><?= htmlspecialchars($c2['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="nuevo-prod-nombre">Nombre</label>
      <input type="text" id="nuevo-prod-nombre" name="nombre" required placeholder="Ej. Latte de Avellana">
    </div>
    <div class="field field-full">
      <label>Descripción</label>
      <textarea name="descripcion" placeholder="Ej. Espresso, leche texturizada y sirope de avellana."></textarea>
    </div>
    <div class="field">
      <label>Precio (USD)</label>
      <input type="number" step="0.01" min="0" name="precio" placeholder="vacío = sin precio fijo">
    </div>
    <div class="field">
      <label>Nota (si no tiene precio fijo)</label>
      <input type="text" name="nota" placeholder="Ej. Precio por unidad">
    </div>
    <div class="field">
      <label>Orden dentro de la categoría</label>
      <input type="number" name="orden" value="0">
    </div>
    <div class="field">
      <label>&nbsp;</label>
      <label class="toggle-switch">
        <input type="checkbox" name="destacado">
        <span class="track"></span>
        <span class="label-text">Destacado (aparece en la vitrina de la portada)</span>
      </label>
    </div>
    <div class="field field-full">
      <label>Foto del producto (opcional)</label>
      <div class="field-file">
        <img class="preview" src="../assets/icono-navy.png" alt="">
        <input type="file" name="imagen" accept="image/png,image/jpeg,image/webp">
      </div>
      <span class="field-hint">Se comprime automáticamente a WebP al subirla. Si no subes nada, se muestra el ícono de la categoría.</span>
    </div>
    <div class="edit-bar-actions">
      <button type="submit" name="accion" value="crear" class="btn btn-save">Crear producto</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
