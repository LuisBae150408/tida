<?php
/**
 * =================================================================
 * DATOS DEL MENÚ — TIDA CAFÉ Y PASTELERÍA
 * =================================================================
 * Antes este archivo tenía $categorias/$productos escritos a mano.
 * Ahora esos mismos arreglos —con la misma forma exacta— se leen de
 * la base de datos (tablas categorias/productos), para que se puedan
 * editar desde /admin sin tocar index.php, menu/index.php ni el CSS/JS
 * que ya los consume.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php'; // fmt_precio() se usa al calcular descuentos

/* ---------------------------------------------------------------
   Configuración general del sitio (nombre, horario, contacto...)
   --------------------------------------------------------------- */
function get_configuracion(): array
{
    static $config = null;
    if ($config === null) {
        $pdo = get_pdo();
        $config = $pdo->query('SELECT * FROM configuracion WHERE id = 1')->fetch();
        if (!$config) {
            // Fallback de emergencia si la tabla llegó a quedar vacía.
            $config = [
                'nombre_sitio' => 'Tida — Café y Pastelería',
                'subtitulo' => 'Café de especialidad & pastelería artesanal',
                'direccion' => '— pendiente por definir —',
                'horario_semana' => 'Lun a Sáb, 8:00 am – 7:00 pm',
                'horario_domingo' => '9:00 am – 3:00 pm',
                'whatsapp' => null,
                'instagram' => 'tidapasteleria',
                'instagram_post_url' => null,
                'instagram_post_activo' => 0,
            ];
        }
    }
    return $config;
}

/* ---------------------------------------------------------------
   Publicación/promoción destacada de la landing (imagen o video).
   --------------------------------------------------------------- */
function get_promocion(): ?array
{
    static $promo = false;
    if ($promo === false) {
        $pdo = get_pdo();
        $row = $pdo->query('SELECT * FROM promocion WHERE id = 1')->fetch();
        $tieneMedia = $row && ($row['tipo'] === 'video' ? !empty($row['video']) : !empty($row['imagen']));
        $promo = ($row && $row['activa'] && $tieneMedia) ? $row : null;
    }
    return $promo;
}

/* ---------------------------------------------------------------
   Descuentos activos — indexados para poder aplicarlos rápido a
   cada producto al armar $productos más abajo.
   --------------------------------------------------------------- */
function get_descuentos_activos(): array
{
    $pdo = get_pdo();
    return $pdo->query('SELECT * FROM descuentos WHERE activo = 1')->fetchAll();
}

// Hoy (0=domingo … 6=sábado, igual que los checkboxes del admin).
function descuento_aplica_hoy(array $d): bool
{
    if (empty($d['dias_semana'])) return true; // sin días marcados = todos los días
    $dias = array_map('intval', explode(',', $d['dias_semana']));
    return in_array((int) date('w'), $dias, true);
}

function descuento_para_producto(array $p, array $descuentos): ?array
{
    $mejor = null;
    foreach ($descuentos as $d) {
        if (!descuento_aplica_hoy($d)) continue;
        $coincide =
            ($d['aplica_a'] === 'todo') ||
            ($d['aplica_a'] === 'categoria' && $d['categoria_id'] === $p['categoria_id']) ||
            ($d['aplica_a'] === 'producto' && $d['producto_id'] === $p['id']);
        if (!$coincide) continue;
        // Si hay más de un descuento aplicable, se usa el de mayor valor.
        $valorEquivalente = $d['tipo'] === 'porcentaje' ? $d['valor'] : ($d['valor'] / max($p['precio'] ?? 1, 0.01) * 100);
        if ($mejor === null || $valorEquivalente > $mejor['_valorEquivalente']) {
            $d['_valorEquivalente'] = $valorEquivalente;
            $mejor = $d;
        }
    }
    return $mejor;
}

/* ---------------------------------------------------------------
   Categorías, en el orden guardado desde el admin.
   --------------------------------------------------------------- */
function get_categorias(): array
{
    $pdo = get_pdo();
    return $pdo->query('SELECT id, nombre, kicker, tagline, tema FROM categorias ORDER BY orden ASC, nombre ASC')->fetchAll();
}

/* ---------------------------------------------------------------
   Productos, con precio ya ajustado por descuentos activos:
   - precio          -> precio final a mostrar (con descuento si aplica)
   - precio_original -> solo presente si hay descuento, para tachar
   - descuento_label -> ej. "-15%" / "-$1.00", para la insignia
   --------------------------------------------------------------- */
function get_productos(): array
{
    $pdo = get_pdo();
    $rows = $pdo->query('SELECT id, categoria_id, nombre, descripcion, precio, nota, imagen, destacado FROM productos ORDER BY orden ASC, nombre ASC')->fetchAll();
    $descuentos = get_descuentos_activos();

    return array_map(function ($p) use ($descuentos) {
        $p['precio'] = $p['precio'] !== null ? (float) $p['precio'] : null;
        $p['destacado'] = (bool) $p['destacado'];

        if ($p['precio'] !== null) {
            $d = descuento_para_producto($p, $descuentos);
            if ($d) {
                $original = $p['precio'];
                $nuevo = $d['tipo'] === 'porcentaje'
                    ? $original * (1 - ((float) $d['valor'] / 100))
                    : max(0, $original - (float) $d['valor']);
                $p['precio_original'] = $original;
                $p['precio'] = round($nuevo, 2);
                $p['descuento_label'] = $d['tipo'] === 'porcentaje'
                    ? '-' . rtrim(rtrim(number_format((float) $d['valor'], 1), '0'), '.') . '%'
                    : '-' . fmt_precio((float) $d['valor']);
            }
        }
        return $p;
    }, $rows);
}

/* ---------------------------------------------------------------
   Fondo del hero: imágenes que rotan detrás del logo en la portada.
   --------------------------------------------------------------- */
function get_hero_fondos(): array
{
    $pdo = get_pdo();
    return $pdo->query('SELECT id, imagen, orden FROM hero_fondos ORDER BY orden ASC, id ASC')->fetchAll();
}

// Compatibilidad con index.php / menu/index.php, que ya esperan
// estas variables listas para usar (misma forma que antes).
$categorias = get_categorias();
$productos = get_productos();
$config = get_configuracion();
$promocion = get_promocion();
$hero_fondos = get_hero_fondos();

$highlights = [
    'Café 100% de especialidad',
    'Opciones veganas disponibles',
    'Pastelería artesanal del día',
    'Opciones sin azúcar añadida',
    'Ingredientes de origen local',
];
