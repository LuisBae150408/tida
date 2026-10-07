<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();
require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/uploads.php';

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar_config') {
        $nombre_sitio = trim($_POST['nombre_sitio'] ?? '');
        $subtitulo = trim($_POST['subtitulo'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $horario_semana = trim($_POST['horario_semana'] ?? '');
        $horario_domingo = trim($_POST['horario_domingo'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $instagram = ltrim(trim($_POST['instagram'] ?? ''), '@');

        if ($nombre_sitio === '') {
            set_flash('error', 'El nombre del sitio no puede quedar vacío.');
        } else {
            $pdo->prepare('UPDATE configuracion SET nombre_sitio=?, subtitulo=?, direccion=?, horario_semana=?, horario_domingo=?, whatsapp=?, instagram=? WHERE id=1')
                ->execute([$nombre_sitio, $subtitulo, $direccion, $horario_semana, $horario_domingo, $whatsapp ?: null, $instagram ?: null]);
            set_flash('ok', 'Configuración del sitio actualizada.');
        }
        header('Location: configuracion.php');
        exit;
    }

    if ($accion === 'guardar_promocion') {
        $activa = isset($_POST['activa']) ? 1 : 0;
        $tipo = ($_POST['tipo'] ?? 'imagen') === 'video' ? 'video' : 'imagen';
        $titulo = trim($_POST['titulo'] ?? '');
        $texto = trim($_POST['texto'] ?? '');

        $actualRow = $pdo->query('SELECT imagen, video FROM promocion WHERE id = 1')->fetch();
        $imagen = $actualRow['imagen'] ?: null;
        $video = $actualRow['video'] ?: null;

        try {
            if ($tipo === 'imagen') {
                if (!empty($_FILES['imagen']['name'])) {
                    $imagen = subir_imagen_webp($_FILES['imagen'], 'uploads/promocion', 'promo', 1200);
                    borrar_imagen_si_existe($actualRow['imagen'] ?: null);
                } elseif (!empty($_POST['quitar_imagen'])) {
                    borrar_imagen_si_existe($actualRow['imagen'] ?: null);
                    $imagen = null;
                }
            } else {
                if (!empty($_FILES['video']['name'])) {
                    $video = subir_video($_FILES['video'], 'uploads/promocion', 'promo-video');
                    borrar_video_si_existe($actualRow['video'] ?: null);
                } elseif (!empty($_POST['quitar_video'])) {
                    borrar_video_si_existe($actualRow['video'] ?: null);
                    $video = null;
                }
            }

            $tieneMedia = $tipo === 'video' ? !empty($video) : !empty($imagen);
            if ($activa && !$tieneMedia) {
                set_flash('error', $tipo === 'video' ? 'Sube un video para poder activar la promoción en la portada.' : 'Sube una foto para poder activar la promoción en la portada.');
            } else {
                $pdo->prepare('UPDATE promocion SET activa=?, tipo=?, titulo=?, texto=?, imagen=?, video=? WHERE id=1')
                    ->execute([$activa, $tipo, $titulo ?: null, $texto ?: null, $imagen, $video]);
                set_flash('ok', 'Promoción de la portada actualizada.');
            }
        } catch (RuntimeException $e) {
            set_flash('error', $e->getMessage());
        }
        header('Location: configuracion.php');
        exit;
    }

    if ($accion === 'guardar_instagram') {
        $activo = isset($_POST['instagram_post_activo']) ? 1 : 0;
        $url = trim($_POST['instagram_post_url'] ?? '');

        if ($activo && $url === '') {
            set_flash('error', 'Pega el link de la publicación para poder mostrarla en la portada.');
        } elseif ($url !== '' && !preg_match('#^https://(www\.)?instagram\.com/(p|reel)/[A-Za-z0-9_-]+#', $url)) {
            set_flash('error', 'Ese link no parece ser de una publicación de Instagram. Debe verse como https://www.instagram.com/p/XXXXXXXXX/');
        } else {
            $pdo->prepare('UPDATE configuracion SET instagram_post_url=?, instagram_post_activo=? WHERE id=1')
                ->execute([$url ?: null, $activo]);
            set_flash('ok', 'Publicación de Instagram actualizada.');
        }
        header('Location: configuracion.php');
        exit;
    }

    if ($accion === 'hero_fondo_agregar') {
        try {
            if (empty($_FILES['imagen']['name'])) {
                set_flash('error', 'Elige una imagen para agregar al fondo de la portada.');
            } else {
                $maxOrden = (int) $pdo->query('SELECT COALESCE(MAX(orden),0) FROM hero_fondos')->fetchColumn();
                $ruta = subir_imagen_webp($_FILES['imagen'], 'uploads/hero', 'hero', 1920);
                $pdo->prepare('INSERT INTO hero_fondos (imagen, orden) VALUES (?, ?)')->execute([$ruta, $maxOrden + 1]);
                set_flash('ok', 'Imagen de fondo agregada.');
            }
        } catch (RuntimeException $e) {
            set_flash('error', $e->getMessage());
        }
        header('Location: configuracion.php');
        exit;
    }

    if ($accion === 'hero_fondo_guardar_orden') {
        $stmt = $pdo->prepare('UPDATE hero_fondos SET orden=? WHERE id=?');
        foreach (($_POST['orden'] ?? []) as $id => $valor) {
            $stmt->execute([(int) $valor, (int) $id]);
        }
        set_flash('ok', 'Orden del fondo actualizado.');
        header('Location: configuracion.php');
        exit;
    }

    if ($accion === 'hero_fondo_eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmtSel = $pdo->prepare('SELECT imagen FROM hero_fondos WHERE id = ?');
        $stmtSel->execute([$id]);
        $imgPath = $stmtSel->fetchColumn();
        $pdo->prepare('DELETE FROM hero_fondos WHERE id = ?')->execute([$id]);
        borrar_imagen_si_existe($imgPath ?: null);
        set_flash('ok', 'Imagen de fondo eliminada.');
        header('Location: configuracion.php');
        exit;
    }

    if ($accion === 'cambiar_password') {
        $actual = $_POST['password_actual'] ?? '';
        $nueva = $_POST['password_nueva'] ?? '';
        $confirmar = $_POST['password_confirmar'] ?? '';

        $stmt = $pdo->prepare('SELECT password_hash FROM admin_usuarios WHERE id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        $hashActual = $stmt->fetchColumn();

        if (!$hashActual || !password_verify($actual, $hashActual)) {
            set_flash('error', 'La contraseña actual no es correcta.');
        } elseif (strlen($nueva) < 6) {
            set_flash('error', 'La nueva contraseña debe tener al menos 6 caracteres.');
        } elseif ($nueva !== $confirmar) {
            set_flash('error', 'La confirmación no coincide con la nueva contraseña.');
        } else {
            $pdo->prepare('UPDATE admin_usuarios SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($nueva, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
            set_flash('ok', 'Contraseña actualizada.');
        }
        header('Location: configuracion.php');
        exit;
    }
}

$promo = $pdo->query('SELECT * FROM promocion WHERE id = 1')->fetch();
$heroFondos = $pdo->query('SELECT * FROM hero_fondos ORDER BY orden ASC, id ASC')->fetchAll();

$adminPageTitle = 'Configuración';
$adminPageSubtitle = 'Datos generales del sitio, promoción de portada y acceso al panel.';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="admin-card">
  <div class="admin-card-head">
    <h2>Datos del sitio</h2>
    <p>Se usan en el título de las páginas, el pie de página y la sección de ubicación.</p>
  </div>
  <form method="post" action="configuracion.php" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
    <?= csrf_field() ?>
    <div class="field field-full">
      <label for="cfg-nombre">Nombre del sitio</label>
      <input type="text" id="cfg-nombre" name="nombre_sitio" value="<?= htmlspecialchars($config['nombre_sitio']) ?>" required>
    </div>
    <div class="field field-full">
      <label for="cfg-subtitulo">Subtítulo (debajo del logo, en la portada)</label>
      <input type="text" id="cfg-subtitulo" name="subtitulo" value="<?= htmlspecialchars($config['subtitulo']) ?>">
    </div>
    <div class="field field-full">
      <label for="cfg-direccion">Dirección</label>
      <input type="text" id="cfg-direccion" name="direccion" value="<?= htmlspecialchars($config['direccion']) ?>">
    </div>
    <div class="field">
      <label for="cfg-horario-semana">Horario Lun–Sáb</label>
      <input type="text" id="cfg-horario-semana" name="horario_semana" value="<?= htmlspecialchars($config['horario_semana']) ?>">
    </div>
    <div class="field">
      <label for="cfg-horario-domingo">Horario Domingos</label>
      <input type="text" id="cfg-horario-domingo" name="horario_domingo" value="<?= htmlspecialchars($config['horario_domingo']) ?>">
    </div>
    <div class="field">
      <label for="cfg-whatsapp">WhatsApp (con código de país)</label>
      <input type="tel" id="cfg-whatsapp" name="whatsapp" value="<?= htmlspecialchars($config['whatsapp'] ?? '') ?>" placeholder="Ej. 584121234567">
    </div>
    <div class="field">
      <label for="cfg-instagram">Instagram (sin @)</label>
      <input type="text" id="cfg-instagram" name="instagram" value="<?= htmlspecialchars($config['instagram'] ?? '') ?>" placeholder="tidapasteleria">
    </div>
    <div class="field-full" style="text-align:right;">
      <button type="submit" name="accion" value="guardar_config" class="btn btn-save">Guardar cambios</button>
    </div>
  </form>
</div>

<div class="admin-card">
  <div class="admin-card-head">
    <h2>Publicación de Instagram en la portada</h2>
    <p>Pega el link de una publicación (la que ustedes elijan) y se incrusta tal cual en la portada, con foto/video y texto reales de Instagram.</p>
  </div>
  <form method="post" action="configuracion.php" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
    <?= csrf_field() ?>
    <div class="field">
      <label>&nbsp;</label>
      <label class="toggle-switch">
        <input type="checkbox" name="instagram_post_activo" <?= !empty($config['instagram_post_activo']) ? 'checked' : '' ?>>
        <span class="track"></span>
        <span class="label-text">Mostrar en la portada</span>
      </label>
    </div>
    <div class="field field-full">
      <label for="cfg-ig-url">Link de la publicación</label>
      <input type="url" id="cfg-ig-url" name="instagram_post_url" value="<?= htmlspecialchars($config['instagram_post_url'] ?? '') ?>" placeholder="https://www.instagram.com/p/XXXXXXXXX/">
      <span class="field-hint">Entra a la publicación en Instagram → los 3 puntos (⋯) → "Copiar enlace", y pégalo aquí.</span>
    </div>
    <div class="field-full" style="text-align:right;">
      <button type="submit" name="accion" value="guardar_instagram" class="btn btn-save">Guardar publicación</button>
    </div>
  </form>
</div>

<div class="admin-card">
  <div class="admin-card-head">
    <h2>Promoción destacada de la portada</h2>
    <p>Aparece como una tarjeta justo debajo del hero, solo si está activa y tiene imagen o video.</p>
  </div>
  <form method="post" action="configuracion.php" enctype="multipart/form-data" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
    <?= csrf_field() ?>
    <div class="field">
      <label>&nbsp;</label>
      <label class="toggle-switch">
        <input type="checkbox" name="activa" <?= !empty($promo['activa']) ? 'checked' : '' ?>>
        <span class="track"></span>
        <span class="label-text">Mostrar en la portada</span>
      </label>
    </div>
    <div class="field">
      <label>Tipo de publicación</label>
      <select name="tipo" data-aplica-a-select>
        <option value="imagen" <?= ($promo['tipo'] ?? 'imagen') === 'imagen' ? 'selected' : '' ?>>Imagen</option>
        <option value="video" <?= ($promo['tipo'] ?? 'imagen') === 'video' ? 'selected' : '' ?>>Video</option>
      </select>
    </div>
    <div class="field field-full">
      <label>Título</label>
      <input type="text" name="titulo" value="<?= htmlspecialchars($promo['titulo'] ?? '') ?>" placeholder="Ej. Nueva carta de temporada">
    </div>
    <div class="field field-full">
      <label>Texto</label>
      <input type="text" name="texto" value="<?= htmlspecialchars($promo['texto'] ?? '') ?>" placeholder="Ej. Prueba nuestras bebidas de otoño esta semana.">
    </div>
    <div class="field field-full" data-shows-for="imagen">
      <label>Foto</label>
      <div class="field-file">
        <img class="preview" src="<?= !empty($promo['imagen']) ? '../' . htmlspecialchars($promo['imagen']) : '../assets/icono-navy.png' ?>" alt="">
        <input type="file" name="imagen" accept="image/png,image/jpeg,image/webp">
      </div>
      <?php if (!empty($promo['imagen'])): ?>
        <label class="toggle-switch" style="margin-top:6px;">
          <input type="checkbox" name="quitar_imagen">
          <span class="track"></span>
          <span class="label-text">Quitar la foto actual</span>
        </label>
      <?php endif; ?>
      <span class="field-hint">Se comprime automáticamente a WebP al subirla.</span>
    </div>
    <div class="field field-full" data-shows-for="video">
      <label>Video</label>
      <?php if (!empty($promo['video'])): ?>
        <video class="preview" src="../<?= htmlspecialchars($promo['video']) ?>" style="width:180px;height:auto;border-radius:var(--radius-md);display:block;margin-bottom:8px;" muted controls></video>
      <?php endif; ?>
      <input type="file" name="video" accept="video/mp4,video/webm,video/quicktime">
      <?php if (!empty($promo['video'])): ?>
        <label class="toggle-switch" style="margin-top:6px;">
          <input type="checkbox" name="quitar_video">
          <span class="track"></span>
          <span class="label-text">Quitar el video actual</span>
        </label>
      <?php endif; ?>
      <span class="field-hint">MP4, WebM o MOV, máximo 40MB. Se sube tal cual (no se comprime). Si tu hosting rechaza el archivo por tamaño, pídeles que suban <code>upload_max_filesize</code> y <code>post_max_size</code> en PHP.</span>
    </div>
    <div class="field-full" style="text-align:right;">
      <button type="submit" name="accion" value="guardar_promocion" class="btn btn-save">Guardar promoción</button>
    </div>
  </form>
</div>

<div class="admin-card">
  <div class="admin-card-head">
    <h2>Fondo de la portada</h2>
    <p>Imágenes que rotan detrás del logo en el hero, con un velo oscuro encima para que el texto se siga leyendo bien. Sin ninguna imagen, se usa el navy sólido de siempre.</p>
  </div>
  <div class="admin-list">
    <?php foreach ($heroFondos as $hf): ?>
    <div class="admin-row">
      <div class="admin-row-media"><img src="../<?= htmlspecialchars($hf['imagen']) ?>" alt=""></div>
      <div class="admin-row-body">
        <div class="admin-row-title">Fondo #<?= (int) $hf['id'] ?></div>
      </div>
      <form method="post" action="configuracion.php" style="display:flex;align-items:center;gap:8px;">
        <?= csrf_field() ?>
        <div class="field" style="margin:0;">
          <input type="number" name="orden[<?= (int) $hf['id'] ?>]" value="<?= (int) $hf['orden'] ?>" style="width:64px;">
        </div>
        <button type="submit" name="accion" value="hero_fondo_guardar_orden" class="btn btn-discard" style="padding:8px 12px;">Guardar orden</button>
      </form>
      <form method="post" action="configuracion.php">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $hf['id'] ?>">
        <button type="submit" name="accion" value="hero_fondo_eliminar" class="btn btn-danger" data-confirm="¿Eliminar esta imagen de fondo?">Eliminar</button>
      </form>
    </div>
    <?php endforeach; ?>
    <?php if (!$heroFondos): ?>
      <p class="field-hint">No hay imágenes de fondo todavía — se está usando el navy sólido de siempre.</p>
    <?php endif; ?>
  </div>
  <form method="post" action="configuracion.php" enctype="multipart/form-data" style="display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;margin-top:14px;padding-top:14px;border-top:1px dashed rgba(49,60,76,0.16);">
    <?= csrf_field() ?>
    <div class="field" style="flex:1;min-width:220px;">
      <label>Agregar imagen de fondo</label>
      <input type="file" name="imagen" accept="image/png,image/jpeg,image/webp">
      <span class="field-hint">Se comprime a WebP automáticamente. Usa fotos horizontales, bien iluminadas.</span>
    </div>
    <button type="submit" name="accion" value="hero_fondo_agregar" class="btn btn-save">Agregar</button>
  </form>
</div>

<div class="admin-card">
  <div class="admin-card-head">
    <h2>Seguridad</h2>
    <p>Cambia la contraseña de acceso al panel (usuario: <?= htmlspecialchars($_SESSION['admin_usuario']) ?>).</p>
  </div>
  <form method="post" action="configuracion.php" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;max-width:640px;">
    <?= csrf_field() ?>
    <div class="field">
      <label>Contraseña actual</label>
      <input type="password" name="password_actual" autocomplete="current-password" required>
    </div>
    <div class="field">
      <label>Nueva contraseña</label>
      <input type="password" name="password_nueva" autocomplete="new-password" minlength="6" required>
    </div>
    <div class="field">
      <label>Confirmar nueva contraseña</label>
      <input type="password" name="password_confirmar" autocomplete="new-password" minlength="6" required>
    </div>
    <div class="field-full" style="text-align:right;">
      <button type="submit" name="accion" value="cambiar_password" class="btn btn-save">Actualizar contraseña</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
