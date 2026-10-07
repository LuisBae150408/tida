<?php
/**
 * Sube una imagen (jpg/png/webp) desde un formulario del admin, la
 * redimensiona si es muy ancha y la guarda comprimida en formato
 * .webp. Devuelve la ruta relativa a la raíz del sitio (para guardar
 * en la base de datos) o lanza RuntimeException con un mensaje listo
 * para mostrarle al usuario.
 */
function subir_imagen_webp(array $file, string $destDirRelative, string $baseName, int $maxWidth = 900): string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Hubo un error al subir el archivo. Intenta de nuevo.');
    }
    if ($file['size'] > 6 * 1024 * 1024) {
        throw new RuntimeException('La imagen pesa demasiado (máximo 6MB).');
    }
    if (!function_exists('imagewebp')) {
        throw new RuntimeException('El servidor no tiene soporte de WebP en la extensión GD. Contacta a tu hosting.');
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        throw new RuntimeException('El archivo no es una imagen válida.');
    }

    switch ($info['mime']) {
        case 'image/jpeg': $src = imagecreatefromjpeg($file['tmp_name']); break;
        case 'image/png':  $src = imagecreatefrompng($file['tmp_name']); break;
        case 'image/webp': $src = imagecreatefromwebp($file['tmp_name']); break;
        default: throw new RuntimeException('Formato no soportado. Usa una imagen JPG, PNG o WEBP.');
    }
    if (!$src) {
        throw new RuntimeException('No se pudo procesar la imagen.');
    }

    $width = imagesx($src);
    $height = imagesy($src);
    if ($width > $maxWidth) {
        $newHeight = (int) round($height * ($maxWidth / $width));
        $resized = imagecreatetruecolor($maxWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $src, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
        imagedestroy($src);
        $src = $resized;
    }

    $destDirAbs = rtrim(__DIR__ . '/../' . $destDirRelative, '/');
    if (!is_dir($destDirAbs) && !mkdir($destDirAbs, 0755, true) && !is_dir($destDirAbs)) {
        imagedestroy($src);
        throw new RuntimeException('No se pudo crear la carpeta de destino en el servidor.');
    }

    $filename = $baseName . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.webp';
    $destAbs = $destDirAbs . '/' . $filename;
    $ok = imagewebp($src, $destAbs, 82);
    imagedestroy($src);

    if (!$ok) {
        throw new RuntimeException('No se pudo guardar la imagen convertida.');
    }

    return rtrim($destDirRelative, '/') . '/' . $filename;
}

/** Borra un archivo de imagen guardado previamente (ruta relativa a la raíz del sitio). */
function borrar_imagen_si_existe(?string $rutaRelativa): void
{
    if (!$rutaRelativa) return;
    $abs = __DIR__ . '/../' . $rutaRelativa;
    if (is_file($abs)) {
        @unlink($abs);
    }
}

/**
 * Sube un video (mp4/webm/mov) tal cual, sin recomprimir — recomprimir
 * video necesitaría ffmpeg, que no está garantizado en el hosting.
 * Valida el tipo real del archivo (no solo la extensión) y un tamaño
 * máximo razonable. Devuelve la ruta relativa a la raíz del sitio, o
 * lanza RuntimeException con un mensaje listo para el usuario.
 */
function subir_video(array $file, string $destDirRelative, string $baseName, int $maxBytes = 40 * 1024 * 1024): string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('El video pesa más de lo que este servidor permite subir. Pídele a tu hosting que aumente upload_max_filesize y post_max_size en PHP.');
        }
        throw new RuntimeException('Hubo un error al subir el video. Intenta de nuevo.');
    }
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('El video pesa demasiado (máximo ' . round($maxBytes / 1024 / 1024) . 'MB). Comprímelo antes de subirlo.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $extPorMime = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov'];
    if (!isset($extPorMime[$mime])) {
        throw new RuntimeException('Formato de video no soportado. Usa MP4, WebM o MOV.');
    }

    $destDirAbs = rtrim(__DIR__ . '/../' . $destDirRelative, '/');
    if (!is_dir($destDirAbs) && !mkdir($destDirAbs, 0755, true) && !is_dir($destDirAbs)) {
        throw new RuntimeException('No se pudo crear la carpeta de destino en el servidor.');
    }

    $filename = $baseName . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $extPorMime[$mime];
    $destAbs = $destDirAbs . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destAbs)) {
        throw new RuntimeException('No se pudo guardar el video en el servidor.');
    }

    return rtrim($destDirRelative, '/') . '/' . $filename;
}

/** Borra un archivo de video guardado previamente (ruta relativa a la raíz del sitio). */
function borrar_video_si_existe(?string $rutaRelativa): void
{
    if (!$rutaRelativa) return;
    $abs = __DIR__ . '/../' . $rutaRelativa;
    if (is_file($abs)) {
        @unlink($abs);
    }
}
