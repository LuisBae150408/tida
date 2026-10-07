-- =================================================================
-- MIGRACIÓN — publicación de Instagram, video en la promoción y
-- fondo de imágenes del hero.
-- =================================================================
-- Ejecuta este archivo UNA SOLA VEZ, solo si tu base de datos ya
-- estaba creada de antes (con el schema.sql anterior). Si estás
-- instalando el sitio desde cero, no lo necesitas: el schema.sql
-- actual ya incluye todo esto.

SET NAMES utf8mb4;

ALTER TABLE configuracion ADD COLUMN IF NOT EXISTS instagram_post_url VARCHAR(255) NULL;
ALTER TABLE configuracion ADD COLUMN IF NOT EXISTS instagram_post_activo TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE promocion ADD COLUMN IF NOT EXISTS tipo ENUM('imagen','video') NOT NULL DEFAULT 'imagen' AFTER activa;
ALTER TABLE promocion ADD COLUMN IF NOT EXISTS video VARCHAR(255) NULL AFTER imagen;

CREATE TABLE IF NOT EXISTS hero_fondos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  imagen VARCHAR(255) NOT NULL,
  orden INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3 fondos abstractos de muestra (dentro de la paleta de la marca)
-- para que la portada ya se vea bien mientras llegan fotos reales.
-- Se pueden borrar o reemplazar desde Configuración cuando gusten.
-- Copia también la carpeta uploads/hero/ del zip a tu servidor para
-- que estas rutas existan de verdad. Solo se insertan si la tabla
-- está vacía (para poder correr este archivo más de una vez sin
-- duplicar filas).
INSERT INTO hero_fondos (imagen, orden)
SELECT * FROM (
  SELECT 'uploads/hero/hero-1.webp' AS imagen, 1 AS orden
  UNION ALL SELECT 'uploads/hero/hero-2.webp', 2
  UNION ALL SELECT 'uploads/hero/hero-3.webp', 3
) AS semilla
WHERE NOT EXISTS (SELECT 1 FROM hero_fondos);
