<?php
/**
 * Funciones de presentación compartidas entre index.php y
 * menu/index.php. No tocan datos (eso vive en data.php) — solo
 * transforman esos datos en HTML/SVG.
 */

/* ---------------------------------------------------------------
   Iconos de categoría — ilustraciones simples de línea (no fotos)
   para que las tarjetas/productos nunca se vean vacíos, en el
   mismo estilo minimal de la marca. Un icono por categoría.
   --------------------------------------------------------------- */
function categoria_icono_svg(string $catId): string
{
    $iconos = [
        'cafes-clasicos'     => '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V8Z"/><path d="M17 9h1.5a2.5 2.5 0 0 1 0 5H17"/><path d="M8 3.5c-.6.7-.6 1.3 0 2s.6 1.3 0 2M12 3.5c-.6.7-.6 1.3 0 2s.6 1.3 0 2"/></svg>',
        'cafes-especiales'   => '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V8Z"/><path d="M17 9h1.5a2.5 2.5 0 0 1 0 5H17"/><path d="M8 13c1 .8 2 .8 3 0s2-.8 3 0" stroke-width="1.3"/></svg>',
        'frappe-frios'       => '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12l-1.4 16.2A2 2 0 0 1 14.6 21H9.4a2 2 0 0 1-2-1.8L6 3Z"/><path d="M5.3 7h13.4"/><path d="M13 3v-.5M13 3 11 8"/></svg>',
        'filtrado'           => '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4h12l-2.2 5.5a2 2 0 0 1-.4.6L12 13.5 8.6 10a2 2 0 0 1-.4-.6L6 4Z"/><path d="M12 13.5V17"/><path d="M8 20.5h8"/></svg>',
        'refrescantes'       => '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h10l-1 15.5A2 2 0 0 1 14 20.5h-4a2 2 0 0 1-2-1.99L7 3Z"/><path d="M6.6 7.5h10.8"/><path d="M9 3 8 1.3M15 3l1-1.7"/></svg>',
        'saludables'         => '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l-.9 13.5A2.5 2.5 0 0 1 12.6 18.5h-1.2A2.5 2.5 0 0 1 8.9 16.5L8 3Z"/><path d="M7.5 8h9"/><path d="M12 18.5V21"/><path d="M17 5.2c1.4.6 2 1.8 1.6 3.4" stroke-width="1.3"/></svg>',
        'desayunos'          => '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3.5 11.5 12 6l8.5 5.5"/><path d="M4.5 11.5h15L18 18.5H6l-1.5-7Z"/><path d="M9 14.5h6"/></svg>',
        'tostadas'           => '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12.5V19a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-6.5"/><path d="M4 12.5a8 6.5 0 0 1 16 0"/><path d="M9 13.5c1 1 2 1 3 0s2-1 3 0" stroke-width="1.3"/></svg>',
        'focaccia-tortilla'  => '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="12" rx="9" ry="6"/><circle cx="8.5" cy="10.3" r="0.55" fill="currentColor" stroke="none"/><circle cx="15" cy="9.6" r="0.55" fill="currentColor" stroke="none"/><circle cx="9.3" cy="14" r="0.55" fill="currentColor" stroke="none"/><circle cx="15.4" cy="13.6" r="0.55" fill="currentColor" stroke="none"/><circle cx="12.1" cy="12.2" r="0.55" fill="currentColor" stroke="none"/></svg>',
        'snacks'             => '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M8.2 10h7.6l-2 9.2a1.6 1.6 0 0 1-3.6 0L8.2 10Z"/><path d="M9.6 10 9.1 5M12 10V4M14.4 10l.5-5" stroke-width="1.5"/></svg>',
    ];
    return $iconos[$catId] ?? '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/></svg>';
}

/* ---------------------------------------------------------------
   Formato de precio: "$3" o "$3.50" (sin ceros de más).
   --------------------------------------------------------------- */
function fmt_precio(?float $n): string
{
    if ($n === null) return '';
    $s = number_format($n, 2, '.', '');
    return '$' . preg_replace('/\.00$/', '', $s);
}

/* ---------------------------------------------------------------
   Convierte un nombre en un id de tipo slug ("Cafés Especiales" ->
   "cafes-especiales"), usado por el admin al crear categorías y
   productos nuevos.
   --------------------------------------------------------------- */
function slugify(string $texto): string
{
    $texto = trim($texto);
    if (function_exists('transliterator_transliterate')) {
        $texto = transliterator_transliterate('Any-Latin; Latin-ASCII;', $texto);
    } else {
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n', 'Ü' => 'u',
        ]);
    }
    $texto = strtolower($texto);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    return trim($texto, '-') ?: 'item';
}

/* ---------------------------------------------------------------
   Título de categoría dividido en primera palabra (script, arriba)
   + resto (bold mayúsculas, debajo) — mismo tratamiento tipográfico
   en toda la marca. Si es una sola palabra, va solo en script.
   --------------------------------------------------------------- */
function categoria_titulo_html(string $nombre): string
{
    $spacePos = strpos($nombre, ' ');
    if ($spacePos === false) {
        return '<span class="category-title-script">' . htmlspecialchars($nombre) . '</span>';
    }
    $first = substr($nombre, 0, $spacePos);
    $rest = substr($nombre, $spacePos + 1);
    return '<span class="category-title-script">' . htmlspecialchars($first) . '</span>'
         . '<span class="category-title-bold">' . htmlspecialchars($rest) . '</span>';
}

/* ---------------------------------------------------------------
   Color real detrás de cada tema — para difuminar el borde entre
   una categoría y la siguiente (que el cambio de fondo no se
   sienta cortado).
   --------------------------------------------------------------- */
function tema_color(string $tema): string
{
    $colores = [
        'navy'  => '#313c4c',
        'sage'  => '#5a7f71',
        'taupe' => '#b49c87',
        'coral' => '#d1c49b',
        'gold'  => '#97806c',
    ];
    return $colores[$tema] ?? '#313c4c';
}

/* ---------------------------------------------------------------
   Trazos decorativos de fondo — líneas sueltas estilo caligráfico.
   Los 4 diseños nacen desde su borde izquierdo; para el lado
   derecho se reflejan con scaleX(-1) vía la clase side-right.
   --------------------------------------------------------------- */
function flourish_paths(): array
{
    return [
        'M20,190 C20,110 95,55 165,80 C220,100 205,165 140,165 C205,168 250,95 330,78 C410,62 470,105 466,158 C462,202 410,222 372,198',
        'M20,126 C26,178 80,213 150,205 C227,196 187,118 232,82 C264,132 308,192 386,180 C460,168 505,95 480,50',
        'M15,110 C110,45 250,42 345,95 C400,126 398,158 452,150 C500,143 528,105 566,82 M120,220 C160,196 200,196 226,220',
        'M60,60 C140,40 200,90 170,140 C145,182 80,180 70,140 C130,190 210,220 300,200 C400,178 420,120 500,100 C540,90 570,100 580,130',
    ];
}

function flourish_svg(int $variant): string
{
    $paths = flourish_paths();
    $d = $paths[$variant % count($paths)];
    return '<svg viewBox="0 0 600 300" xmlns="http://www.w3.org/2000/svg"><path d="' . $d . '"/></svg>';
}

// Cada categoría usa un solo lado para todos sus trazos (izquierda o
// derecha, alternando por índice), así el watermark siempre puede ir
// en el lado contrario sin pisar ningún trazo.
function flourish_side(int $idx): string
{
    return $idx % 2 === 0 ? 'left' : 'right';
}

function category_flourishes_html(int $idx): string
{
    $side = flourish_side($idx);
    return
        '<div class="category-flourish flourish-head-1 side-' . $side . '" aria-hidden="true">' . flourish_svg($idx) . '</div>' .
        '<div class="category-flourish flourish-head-2 side-' . $side . '" aria-hidden="true">' . flourish_svg($idx + 2) . '</div>' .
        '<div class="category-flourish flourish-body-1 side-' . $side . '" aria-hidden="true">' . flourish_svg($idx + 1) . '</div>' .
        '<div class="category-flourish flourish-body-2 side-' . $side . '" aria-hidden="true">' . flourish_svg($idx + 3) . '</div>';
}

function category_watermark_html(int $idx): string
{
    $side = flourish_side($idx) === 'left' ? 'right' : 'left'; // siempre el lado contrario a los trazos
    return '<img class="category-watermark wm-' . $side . '" src="../assets/icono-navy.png" alt="" aria-hidden="true" />';
}

/* ---------------------------------------------------------------
   Productos de una categoría, en el mismo orden en que aparecen
   dentro de $productos (útil para no reescribir foreach+filter
   en cada página).
   --------------------------------------------------------------- */
function productos_de_categoria(array $productos, string $categoriaId): array
{
    return array_values(array_filter($productos, fn($p) => $p['categoria_id'] === $categoriaId));
}

/* ---------------------------------------------------------------
   Medio visual de un producto en las filas del menú: si el admin
   subió una foto se usa esa (comprimida a webp al subirla), si no
   se cae al ícono de línea de su categoría — así ninguna fila se ve
   vacía aunque no todos los productos tengan foto todavía.
   $prefix es "" en index.php y "../" en menu/index.php.
   --------------------------------------------------------------- */
function producto_media_html(array $producto, string $catId, string $prefix = ''): string
{
    if (!empty($producto['imagen'])) {
        return '<img src="' . htmlspecialchars($prefix . $producto['imagen']) . '" alt="" loading="lazy" />';
    }
    return categoria_icono_svg($catId);
}

/* ---------------------------------------------------------------
   Insignia de descuento + precio tachado, cuando data.php detectó
   un descuento activo aplicable a este producto.
   --------------------------------------------------------------- */
function precio_html(array $producto): string
{
    if ($producto['precio'] === null) {
        return htmlspecialchars($producto['nota'] ?? '');
    }
    if (!empty($producto['precio_original'])) {
        return '<span class="price-badge">' . htmlspecialchars($producto['descuento_label']) . '</span>'
             . '<span class="price-old">' . fmt_precio($producto['precio_original']) . '</span>'
             . '<span class="price-new">' . fmt_precio($producto['precio']) . '</span>';
    }
    return fmt_precio($producto['precio']);
}
