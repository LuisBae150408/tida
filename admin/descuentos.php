<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();
require_once __DIR__ . '/../includes/data.php';

$pdo = get_pdo();
$categoriasOpts = $pdo->query('SELECT id, nombre FROM categorias ORDER BY orden ASC, nombre ASC')->fetchAll();
$productosOpts = $pdo->query('SELECT id, nombre FROM productos ORDER BY nombre ASC')->fetchAll();
$catPorId = array_column($categoriasOpts, 'nombre', 'id');
$prodPorId = array_column($productosOpts, 'nombre', 'id');

$diasSemana = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 0 => 'Dom'];

function descuento_desde_post(): array
{
    $dias = $_POST['dias_semana'] ?? [];
    $dias = array_values(array_filter(array_map('intval', is_array($dias) ? $dias : []), fn($d) => $d >= 0 && $d <= 6));
    return [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'tipo' => ($_POST['tipo'] ?? '') === 'monto' ? 'monto' : 'porcentaje',
        'valor' => (float) ($_POST['valor'] ?? 0),
        'aplica_a' => in_array($_POST['aplica_a'] ?? '', ['producto', 'categoria', 'todo'], true) ? $_POST['aplica_a'] : 'todo',
        'producto_id' => $_POST['producto_id'] ?: null,
        'categoria_id' => $_POST['categoria_id'] ?: null,
        'dias_semana' => count($dias) === 7 || !$dias ? null : implode(',', $dias), // 7 días marcados = "todos" = null
        'activo' => isset($_POST['activo']) ? 1 : 0,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear' || $accion === 'guardar') {
        $d = descuento_desde_post();
        $error = null;
        if ($d['nombre'] === '' || $d['valor'] <= 0) {
            $error = 'El nombre y un valor mayor a 0 son obligatorios.';
        } elseif ($d['aplica_a'] === 'producto' && !$d['producto_id']) {
            $error = 'Elige a qué producto aplica el descuento.';
        } elseif ($d['aplica_a'] === 'categoria' && !$d['categoria_id']) {
            $error = 'Elige a qué categoría aplica el descuento.';
        }

        if ($d['aplica_a'] !== 'producto') $d['producto_id'] = null;
        if ($d['aplica_a'] !== 'categoria') $d['categoria_id'] = null;

        if ($error) {
            set_flash('error', $error);
        } elseif ($accion === 'crear') {
            $pdo->prepare('INSERT INTO descuentos (nombre, tipo, valor, aplica_a, producto_id, categoria_id, dias_semana, activo) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$d['nombre'], $d['tipo'], $d['valor'], $d['aplica_a'], $d['producto_id'], $d['categoria_id'], $d['dias_semana'], $d['activo']]);
            set_flash('ok', 'Descuento "' . $d['nombre'] . '" creado.');
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE descuentos SET nombre=?, tipo=?, valor=?, aplica_a=?, producto_id=?, categoria_id=?, dias_semana=?, activo=? WHERE id=?')
                ->execute([$d['nombre'], $d['tipo'], $d['valor'], $d['aplica_a'], $d['producto_id'], $d['categoria_id'], $d['dias_semana'], $d['activo'], $id]);
            set_flash('ok', 'Descuento actualizado.');
        }
    }

    if ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM descuentos WHERE id = ?')->execute([$id]);
        set_flash('ok', 'Descuento eliminado.');
    }

    header('Location: descuentos.php');
    exit;
}

$descuentosList = $pdo->query('SELECT * FROM descuentos ORDER BY activo DESC, nombre ASC')->fetchAll();

$adminPageTitle = 'Descuentos';
$adminPageSubtitle = 'Aplican a todo el menú, a una categoría o a un producto puntual, con o sin días específicos.';
require __DIR__ . '/includes/layout-top.php';

function etiqueta_aplica_a(array $d, array $catPorId, array $prodPorId): string
{
    if ($d['aplica_a'] === 'producto') return 'Producto: ' . ($prodPorId[$d['producto_id']] ?? '—');
    if ($d['aplica_a'] === 'categoria') return 'Categoría: ' . ($catPorId[$d['categoria_id']] ?? '—');
    return 'Todo el menú';
}

function etiqueta_dias(?string $csv, array $diasSemana): string
{
    if (!$csv) return 'Todos los días';
    $dias = array_map('intval', explode(',', $csv));
    return implode(', ', array_map(fn($d) => $diasSemana[$d], $dias));
}
?>

<div class="admin-card">
  <div class="admin-card-head">
    <h2>Descuentos (<?= count($descuentosList) ?>)</h2>
  </div>
  <div class="admin-list">
    <?php foreach ($descuentosList as $d): $editId = 'edit-desc-' . $d['id']; $confirmMsg = '¿Eliminar el descuento "' . $d['nombre'] . '"?'; $diasMarcados = $d['dias_semana'] ? array_map('intval', explode(',', $d['dias_semana'])) : [0,1,2,3,4,5,6]; ?>
    <div class="admin-row">
      <div class="admin-row-media">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="2.2"/><circle cx="16" cy="16" r="2.2"/><path d="M6 18 18 6"/></svg>
      </div>
      <div class="admin-row-body">
        <div class="admin-row-title"><?= htmlspecialchars($d['nombre']) ?> — <?= $d['tipo'] === 'porcentaje' ? rtrim(rtrim(number_format((float) $d['valor'], 1), '0'), '.') . '%' : fmt_precio((float) $d['valor']) ?></div>
        <div class="admin-row-sub"><?= htmlspecialchars(etiqueta_aplica_a($d, $catPorId, $prodPorId)) ?> · <?= htmlspecialchars(etiqueta_dias($d['dias_semana'], $diasSemana)) ?></div>
      </div>
      <span class="admin-row-tag <?= $d['activo'] ? '' : 'is-off' ?>"><?= $d['activo'] ? 'Activo' : 'Pausado' ?></span>
      <div class="admin-row-actions">
        <button type="button" class="btn-edit-toggle" data-edit-toggle="<?= htmlspecialchars($editId) ?>">Editar</button>
      </div>
    </div>

    <form class="edit-bar" id="<?= htmlspecialchars($editId) ?>" method="post" action="descuentos.php">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">

      <div class="field">
        <label>Nombre</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($d['nombre']) ?>" required>
      </div>
      <div class="field">
        <label>Tipo</label>
        <select name="tipo">
          <option value="porcentaje" <?= $d['tipo'] === 'porcentaje' ? 'selected' : '' ?>>Porcentaje (%)</option>
          <option value="monto" <?= $d['tipo'] === 'monto' ? 'selected' : '' ?>>Monto fijo ($)</option>
        </select>
      </div>
      <div class="field">
        <label>Valor</label>
        <input type="number" step="0.01" min="0.01" name="valor" value="<?= htmlspecialchars((string) $d['valor']) ?>" required>
      </div>
      <div class="field">
        <label>Aplica a</label>
        <select name="aplica_a" data-aplica-a-select>
          <option value="todo" <?= $d['aplica_a'] === 'todo' ? 'selected' : '' ?>>Todo el menú</option>
          <option value="categoria" <?= $d['aplica_a'] === 'categoria' ? 'selected' : '' ?>>Una categoría</option>
          <option value="producto" <?= $d['aplica_a'] === 'producto' ? 'selected' : '' ?>>Un producto</option>
        </select>
      </div>
      <div class="field" data-shows-for="categoria">
        <label>Categoría</label>
        <select name="categoria_id">
          <?php foreach ($categoriasOpts as $c2): ?>
            <option value="<?= htmlspecialchars($c2['id']) ?>" <?= $d['categoria_id'] === $c2['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c2['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" data-shows-for="producto">
        <label>Producto</label>
        <select name="producto_id">
          <?php foreach ($productosOpts as $p2): ?>
            <option value="<?= htmlspecialchars($p2['id']) ?>" <?= $d['producto_id'] === $p2['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p2['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field field-full">
        <label>Días en que aplica</label>
        <div class="chip-check">
          <?php foreach ($diasSemana as $num => $label): $cid = $editId . '-d' . $num; ?>
            <input type="checkbox" id="<?= $cid ?>" name="dias_semana[]" value="<?= $num ?>" <?= in_array($num, $diasMarcados, true) ? 'checked' : '' ?>>
            <label for="<?= $cid ?>"><?= $label ?></label>
          <?php endforeach; ?>
        </div>
        <span class="field-hint">Sin ningún día marcado (o todos) = aplica todos los días.</span>
      </div>
      <div class="field">
        <label>&nbsp;</label>
        <label class="toggle-switch">
          <input type="checkbox" name="activo" <?= $d['activo'] ? 'checked' : '' ?>>
          <span class="track"></span>
          <span class="label-text">Activo</span>
        </label>
      </div>

      <div class="edit-bar-actions">
        <button type="submit" name="accion" value="guardar" class="btn btn-save">Guardar</button>
        <button type="submit" name="accion" value="eliminar" class="btn btn-danger" data-confirm="<?= htmlspecialchars($confirmMsg) ?>">Eliminar</button>
        <button type="button" class="btn btn-discard">Descartar</button>
      </div>
    </form>
    <?php endforeach; ?>

    <?php if (!$descuentosList): ?>
      <p class="field-hint">Todavía no hay descuentos creados.</p>
    <?php endif; ?>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-head"><h2>Nuevo descuento</h2></div>
  <form method="post" action="descuentos.php" class="edit-bar is-open" style="display:grid;" id="nuevo-descuento">
    <?= csrf_field() ?>
    <div class="field">
      <label for="nd-nombre">Nombre</label>
      <input type="text" id="nd-nombre" name="nombre" required placeholder="Ej. Happy Hour">
    </div>
    <div class="field">
      <label>Tipo</label>
      <select name="tipo">
        <option value="porcentaje">Porcentaje (%)</option>
        <option value="monto">Monto fijo ($)</option>
      </select>
    </div>
    <div class="field">
      <label>Valor</label>
      <input type="number" step="0.01" min="0.01" name="valor" required placeholder="Ej. 15">
    </div>
    <div class="field">
      <label>Aplica a</label>
      <select name="aplica_a" data-aplica-a-select>
        <option value="todo">Todo el menú</option>
        <option value="categoria">Una categoría</option>
        <option value="producto">Un producto</option>
      </select>
    </div>
    <div class="field" data-shows-for="categoria">
      <label>Categoría</label>
      <select name="categoria_id">
        <?php foreach ($categoriasOpts as $c2): ?>
          <option value="<?= htmlspecialchars($c2['id']) ?>"><?= htmlspecialchars($c2['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field" data-shows-for="producto">
      <label>Producto</label>
      <select name="producto_id">
        <?php foreach ($productosOpts as $p2): ?>
          <option value="<?= htmlspecialchars($p2['id']) ?>"><?= htmlspecialchars($p2['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field field-full">
      <label>Días en que aplica</label>
      <div class="chip-check">
        <?php foreach ($diasSemana as $num => $label): $cid = 'nd-d' . $num; ?>
          <input type="checkbox" id="<?= $cid ?>" name="dias_semana[]" value="<?= $num ?>">
          <label for="<?= $cid ?>"><?= $label ?></label>
        <?php endforeach; ?>
      </div>
      <span class="field-hint">Sin ningún día marcado = aplica todos los días.</span>
    </div>
    <div class="field">
      <label>&nbsp;</label>
      <label class="toggle-switch">
        <input type="checkbox" name="activo" checked>
        <span class="track"></span>
        <span class="label-text">Activo</span>
      </label>
    </div>
    <div class="edit-bar-actions">
      <button type="submit" name="accion" value="crear" class="btn btn-save">Crear descuento</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
