<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();
require_once __DIR__ . '/../includes/data.php';

$pdo = get_pdo();
$temas = ['navy' => 'Navy', 'sage' => 'Sage', 'taupe' => 'Taupe', 'coral' => 'Coral (tan)', 'gold' => 'Gold'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $kicker = trim($_POST['kicker'] ?? '');
        $tagline = trim($_POST['tagline'] ?? '');
        $tema = array_key_exists($_POST['tema'] ?? '', $temas) ? $_POST['tema'] : 'navy';
        $orden = (int) ($_POST['orden'] ?? 0);

        if ($nombre === '') {
            set_flash('error', 'El nombre de la categoría es obligatorio.');
        } else {
            $baseId = slugify($nombre);
            $id = $baseId;
            $i = 2;
            $check = $pdo->prepare('SELECT COUNT(*) FROM categorias WHERE id = ?');
            while (true) {
                $check->execute([$id]);
                if ((int) $check->fetchColumn() === 0) break;
                $id = $baseId . '-' . $i++;
            }
            $pdo->prepare('INSERT INTO categorias (id, nombre, kicker, tagline, tema, orden) VALUES (?,?,?,?,?,?)')
                ->execute([$id, $nombre, $kicker, $tagline, $tema, $orden]);
            set_flash('ok', 'Categoría "' . $nombre . '" creada.');
        }
    }

    if ($accion === 'guardar') {
        $id = $_POST['id'] ?? '';
        $nombre = trim($_POST['nombre'] ?? '');
        $kicker = trim($_POST['kicker'] ?? '');
        $tagline = trim($_POST['tagline'] ?? '');
        $tema = array_key_exists($_POST['tema'] ?? '', $temas) ? $_POST['tema'] : 'navy';
        $orden = (int) ($_POST['orden'] ?? 0);

        if ($id === '' || $nombre === '') {
            set_flash('error', 'Faltan datos obligatorios.');
        } else {
            $pdo->prepare('UPDATE categorias SET nombre=?, kicker=?, tagline=?, tema=?, orden=? WHERE id=?')
                ->execute([$nombre, $kicker, $tagline, $tema, $orden, $id]);
            set_flash('ok', 'Categoría actualizada.');
        }
    }

    if ($accion === 'eliminar') {
        $id = $_POST['id'] ?? '';
        $count = $pdo->prepare('SELECT COUNT(*) FROM productos WHERE categoria_id = ?');
        $count->execute([$id]);
        if ((int) $count->fetchColumn() > 0) {
            set_flash('error', 'No puedes eliminar esta categoría porque todavía tiene productos. Muévelos a otra categoría o elimínalos primero.');
        } else {
            $pdo->prepare('DELETE FROM categorias WHERE id = ?')->execute([$id]);
            set_flash('ok', 'Categoría eliminada.');
        }
    }

    header('Location: categorias.php');
    exit;
}

$categoriasList = $pdo->query(
    'SELECT c.*, (SELECT COUNT(*) FROM productos p WHERE p.categoria_id = c.id) AS total_productos
     FROM categorias c ORDER BY orden ASC, nombre ASC'
)->fetchAll();

$adminPageTitle = 'Categorías';
$adminPageSubtitle = 'Secciones del menú — cada una define un color de tema y su orden de aparición.';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-card">
  <div class="admin-card-head">
    <h2>Categorías (<?= count($categoriasList) ?>)</h2>
    <p>Toca "Editar" para modificar una categoría existente.</p>
  </div>
  <div class="admin-list">
    <?php foreach ($categoriasList as $cat): $editId = 'edit-cat-' . $cat['id']; $confirmMsg = '¿Eliminar la categoría "' . $cat['nombre'] . '"? Esta acción no se puede deshacer.'; ?>
    <div class="admin-row">
      <div class="admin-row-media"><?= categoria_icono_svg($cat['id']) ?></div>
      <div class="admin-row-body">
        <div class="admin-row-title"><?= htmlspecialchars($cat['nombre']) ?></div>
        <div class="admin-row-sub"><?= htmlspecialchars($cat['kicker']) ?> · <?= (int) $cat['total_productos'] ?> producto<?= $cat['total_productos'] == 1 ? '' : 's' ?> · orden <?= (int) $cat['orden'] ?></div>
      </div>
      <span class="admin-row-tag"><?= htmlspecialchars($temas[$cat['tema']] ?? $cat['tema']) ?></span>
      <div class="admin-row-actions">
        <button type="button" class="btn-edit-toggle" data-edit-toggle="<?= htmlspecialchars($editId) ?>">Editar</button>
      </div>
    </div>

    <form class="edit-bar" id="<?= htmlspecialchars($editId) ?>" method="post" action="categorias.php">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= htmlspecialchars($cat['id']) ?>">
      <div class="field field-full">
        <label>Identificador (fijo)</label>
        <input type="text" value="<?= htmlspecialchars($cat['id']) ?>" disabled>
        <span class="field-hint">Se generó al crear la categoría y no se puede cambiar (lo usan las URLs del menú).</span>
      </div>
      <div class="field">
        <label for="nombre-<?= htmlspecialchars($cat['id']) ?>">Nombre</label>
        <input type="text" id="nombre-<?= htmlspecialchars($cat['id']) ?>" name="nombre" value="<?= htmlspecialchars($cat['nombre']) ?>" required>
      </div>
      <div class="field">
        <label>Kicker</label>
        <input type="text" name="kicker" value="<?= htmlspecialchars($cat['kicker']) ?>">
      </div>
      <div class="field field-full">
        <label>Tagline</label>
        <input type="text" name="tagline" value="<?= htmlspecialchars($cat['tagline']) ?>">
      </div>
      <div class="field">
        <label>Tema de color</label>
        <select name="tema">
          <?php foreach ($temas as $key => $label): ?>
            <option value="<?= $key ?>" <?= $cat['tema'] === $key ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Orden</label>
        <input type="number" name="orden" value="<?= (int) $cat['orden'] ?>">
      </div>

      <div class="edit-bar-actions">
        <button type="submit" name="accion" value="guardar" class="btn btn-save">Guardar</button>
        <button type="submit" name="accion" value="eliminar" class="btn btn-danger" data-confirm="<?= htmlspecialchars($confirmMsg) ?>">Eliminar</button>
        <button type="button" class="btn btn-discard">Descartar</button>
      </div>
    </form>
    <?php endforeach; ?>

    <?php if (!$categoriasList): ?>
      <p class="field-hint">Todavía no hay categorías. Crea la primera abajo.</p>
    <?php endif; ?>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h2>Nueva categoría</h2></div>
  <form method="post" action="categorias.php" class="edit-bar is-open" style="display:grid;">
    <?= csrf_field() ?>
    <div class="field">
      <label for="nueva-nombre">Nombre</label>
      <input type="text" id="nueva-nombre" name="nombre" required placeholder="Ej. Postres de la casa">
    </div>
    <div class="field">
      <label>Kicker</label>
      <input type="text" name="kicker" placeholder="Ej. Dulces">
    </div>
    <div class="field field-full">
      <label>Tagline</label>
      <input type="text" name="tagline" placeholder="Ej. Recién horneados cada mañana">
    </div>
    <div class="field">
      <label>Tema de color</label>
      <select name="tema">
        <?php foreach ($temas as $key => $label): ?>
          <option value="<?= $key ?>"><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Orden</label>
      <input type="number" name="orden" value="<?= count($categoriasList) + 1 ?>">
    </div>
    <div class="edit-bar-actions">
      <button type="submit" name="accion" value="crear" class="btn btn-save">Crear categoría</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
