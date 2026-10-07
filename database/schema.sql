-- =================================================================
-- TIDA CAFÉ Y PASTELERÍA — esquema de base de datos
-- =================================================================
-- Importa este archivo completo en phpMyAdmin (o por consola) sobre
-- una base de datos vacía. Crea las tablas y las deja precargadas
-- con el contenido que ya estaba escrito "a mano" en
-- includes/data.php, para que el sitio se vea exactamente igual
-- que antes en el primer arranque, pero ya editable desde /admin.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------
-- Configuración del sitio (fila única)
-- -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS configuracion (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
  nombre_sitio VARCHAR(150) NOT NULL DEFAULT 'Tida — Café y Pastelería',
  subtitulo VARCHAR(180) NOT NULL DEFAULT 'Café de especialidad & pastelería artesanal',
  direccion VARCHAR(255) NOT NULL DEFAULT '— pendiente por definir —',
  horario_semana VARCHAR(150) NOT NULL DEFAULT 'Lun a Sáb, 8:00 am – 7:00 pm',
  horario_domingo VARCHAR(150) NOT NULL DEFAULT '9:00 am – 3:00 pm',
  whatsapp VARCHAR(30) NULL,
  instagram VARCHAR(100) NULL DEFAULT 'tidapasteleria',
  instagram_post_url VARCHAR(255) NULL,
  instagram_post_activo TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT chk_configuracion_singleton CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO configuracion (id, nombre_sitio, subtitulo, direccion, horario_semana, horario_domingo, whatsapp, instagram)
VALUES (1, 'Tida — Café y Pastelería', 'Café de especialidad & pastelería artesanal', '— pendiente por definir —', 'Lun a Sáb, 8:00 am – 7:00 pm', '9:00 am – 3:00 pm', NULL, 'tidapasteleria')
ON DUPLICATE KEY UPDATE id = id;

-- -----------------------------------------------------------------
-- Publicación / promoción destacada de la landing (fila única,
-- se muestra solo si activa = 1 y tiene imagen o video según "tipo")
-- -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS promocion (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
  activa TINYINT(1) NOT NULL DEFAULT 0,
  tipo ENUM('imagen','video') NOT NULL DEFAULT 'imagen',
  titulo VARCHAR(150) NULL,
  texto VARCHAR(255) NULL,
  imagen VARCHAR(255) NULL,
  video VARCHAR(255) NULL,
  CONSTRAINT chk_promocion_singleton CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO promocion (id, activa, tipo, titulo, texto, imagen, video)
VALUES (1, 0, 'imagen', NULL, NULL, NULL, NULL)
ON DUPLICATE KEY UPDATE id = id;

-- -----------------------------------------------------------------
-- Fondo del hero (portada): varias imágenes que rotan detrás del
-- logo, con un velo navy semitransparente encima para que el texto
-- siga siendo legible. Se gestionan desde Configuración.
-- -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hero_fondos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  imagen VARCHAR(255) NOT NULL,
  orden INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3 fondos abstractos de muestra (dentro de la paleta de la marca)
-- para que la portada ya se vea bien mientras llegan fotos reales.
-- Se pueden borrar o reemplazar desde Configuración cuando gusten.
INSERT INTO hero_fondos (imagen, orden) VALUES
('uploads/hero/hero-1.webp', 1),
('uploads/hero/hero-2.webp', 2),
('uploads/hero/hero-3.webp', 3);

-- -----------------------------------------------------------------
-- Categorías del menú
-- -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
  id VARCHAR(60) NOT NULL PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  kicker VARCHAR(120) NOT NULL DEFAULT '',
  tagline VARCHAR(160) NOT NULL DEFAULT '',
  tema ENUM('navy','sage','taupe','coral','gold') NOT NULL DEFAULT 'navy',
  orden INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO categorias (id, nombre, kicker, tagline, tema, orden) VALUES
('cafes-clasicos',    'Cafés Clásicos',       'Espressos',                'Ristretto · Lungo · Macchiato',          'taupe', 1),
('cafes-especiales',  'Cafés Especiales',     'Firma de la casa',         'Nuestras combinaciones favoritas',       'navy',  2),
('frappe-frios',      'Frappuccino & Fríos',  'Para el calor',            'Frappuccino · Ice Coffees · Milkshakes', 'taupe', 3),
('filtrado',          'Servicio de Filtrado', 'Café de especialidad',     'Preparado en tu mesa',                   'sage',  4),
('refrescantes',      'Refrescantes',         'Limonadas & Jamaicas',     'Frías, con frutas de temporada',         'coral', 5),
('saludables',        'Bebidas Saludables',   'Funcionales',              'Energía real, ingredientes reales',      'sage',  6),
('desayunos',         'Desayunos y Brunch',   'Sandwiches',               'Para empezar bien el día',               'gold',  7),
('tostadas',          'Tostadas',             'Sobre pan artesanal',      'Mediterráneo · Capresa · Fit · Hummus',  'sage',  8),
('focaccia-tortilla', 'Focaccia Italiana',    'Pan artesanal de la casa', 'Focaccia · Tortilla Española',           'navy',  9),
('snacks',            'Snacks & Té',          'Para compartir',           'Snacks · Té e infusiones',               'gold', 10)
ON DUPLICATE KEY UPDATE id = id;

-- -----------------------------------------------------------------
-- Productos
-- -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS productos (
  id VARCHAR(80) NOT NULL PRIMARY KEY,
  categoria_id VARCHAR(60) NOT NULL,
  nombre VARCHAR(150) NOT NULL,
  descripcion TEXT NULL,
  precio DECIMAL(8,2) NULL,
  nota VARCHAR(120) NULL,
  imagen VARCHAR(255) NULL,
  destacado TINYINT(1) NOT NULL DEFAULT 0,
  orden INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO productos (id, categoria_id, nombre, descripcion, precio, destacado, orden) VALUES
('ristretto',      'cafes-clasicos', 'Ristretto',    'Espresso corto y concentrado.',                          1.5, 1, 1),
('lungo',          'cafes-clasicos', 'Lungo',        'Espresso largo, cuerpo suave.',                          1.5, 0, 2),
('macchiato',      'cafes-clasicos', 'Macchiato',    'Espresso manchado con un toque de leche.',               1.5, 0, 3),
('americano',      'cafes-clasicos', 'Americano',    'Café negro o guayoyo.',                                  2.5, 0, 4),
('capuccino',      'cafes-clasicos', 'Capuccino',    'Espresso doble y leche texturizada.',                    3,   1, 5),
('latte',          'cafes-clasicos', 'Latte',        'Espresso y leche texturizada.',                          3,   0, 6),
('mocaccino',      'cafes-clasicos', 'Mocaccino',    'Combinación perfecta café + chocolate.',                 4,   0, 7),
('flat-white',     'cafes-clasicos', 'Flat White',   'Doble espresso con leche texturizada, sabor intenso y acabado suave.', 3.5, 1, 8),

('latte-saborizado', 'cafes-especiales', 'Latte Saborizado',       'Latte + sirope de vainilla con caramelo.', 3.5, 0, 1),
('latte-caramelo',   'cafes-especiales', 'Latte Caramelo',         'Latte + caramelo.',                        3.5, 0, 2),
('latte-pistacho',   'cafes-especiales', 'Latte Pistacho',         'Latte + crema de pistacho.',               4.5, 1, 3),
('affogato',         'cafes-especiales', 'Affogato',               'Espresso sobre helado de vainilla.',       5,   1, 4),
('chocolate-taza',   'cafes-especiales', 'Chocolate a la Taza',    'Elaborado con cacao y leche de tu elección.', 3.5, 1, 5),

('frap-clasico',       'frappe-frios', 'Frappuccino Clásico',   'Granizado de café y leche.',                 5, 0, 1),
('frap-chocolate',     'frappe-frios', 'Frappuccino Chocolate', 'Base de frappuccino + chocolate y sirope.', 6, 0, 2),
('frap-caramelo',      'frappe-frios', 'Frappuccino Caramelo',  'Base de frappuccino + sirope de caramelo.', 6, 0, 3),
('frap-oreo',          'frappe-frios', 'Frappuccino Oreo',      'Base de frappuccino + oreo y chocolate.',   7, 1, 4),
('ice-latte',          'frappe-frios', 'Ice Latte',             'Espresso, leche y hielo.',                  3.5, 0, 5),
('ice-caramel-latte',  'frappe-frios', 'Ice Caramel Latte',     'Latte frío + sirope de caramelo.',          4, 0, 6),
('latte-3-leches',     'frappe-frios', 'Latte 3 Leches',        'Latte frío inspirado en la torta 3 leches.', 5, 1, 7),
('milkshake-oreo',     'frappe-frios', 'Oreo Milkshake',        'Batido con helado y oreos.',                8, 1, 8),
('pinacoco-mix',       'frappe-frios', 'Piñacoco Mix',          'Licuado de piña con leche y coco.',         5, 0, 9),
('ice-toddy',          'frappe-frios', 'Ice Toddy',             'Toddy granizado.',                          5, 0, 10),

('filtrado-especialidad', 'filtrado', 'Servicio de Filtrado', 'Cafés de especialidad preparados en métodos de filtrado, directamente en tu mesa. Descubre distintos orígenes cada semana.', 5, 1, 1),

('limonada-clasica',     'refrescantes', 'Limonada Clásica',      'Refrescante, perfecta para el calor.',    2,   0, 1),
('limonada-rosa',        'refrescantes', 'Limonada Rosa',         'Limonada clásica + fresas.',              3.5, 1, 2),
('limonada-verde',       'refrescantes', 'Limonada Verde',        'Limonada clásica + hierbabuena.',         2.5, 0, 3),
('jamaica-clasica',      'refrescantes', 'Jamaica Clásica',       'Infusión de jamaica fría o caliente.',    2,   0, 4),
('jamaica-ice-tropical', 'refrescantes', 'Jamaica Ice Tropical',  'Jamaica + parchita.',                     3,   1, 5),
('jamaica-pina',         'refrescantes', 'Jamaica Piña',          'Jamaica infusionada con piña y especias.', 2.5, 0, 6),

('moka-banana-protein',      'saludables', 'Moka Banana Protein',      'Proteína + espresso + leche a elección + banana + mantequilla de maní.', 7.5, 1, 1),
('mega-c-booster',           'saludables', 'Mega C Booster',           'Naranja + fresa + guayaba + jengibre y chía.',                            5,   0, 2),
('strawberry-fields-matcha', 'saludables', 'Strawberry Fields Matcha', 'Matcha + leche a elección + fresas.',                                    5,   1, 3),
('espresso-marino',          'saludables', 'Espresso Marino',          'Spirulina + espresso + leche a elección.',                               5,   0, 4),

('sandwich-manhattan',   'desayunos', 'Sandwich Manhattan',   '54.4gr de proteína: pechuga de pollo guisada, queso mozzarella, huevo, tomate y espinaca fresca, salsa de yogurt griego y mostaza.', 9, 1, 1),
('jamon-queso',          'desayunos', 'Jamón y Queso',        'Sandwich clásico: jamón de pierna, queso mozzarela, lechuga y tomate.', 6, 0, 2),
('doble-queso-especial', 'desayunos', 'Doble Queso Especial', 'Puro sabor fundido: queso mozzarela, queso gouda, cebolla caramelizada y rúcula.', 8, 0, 3),
('proteico-fit',         'desayunos', 'Proteico Fit',         '25gr de proteína y macronutrientes: 3 huevos al gusto, aguacate, jamón, pan sin gluten, mantequilla y mermelada.', 9, 1, 4),

('tostada-mediterraneo', 'tostadas', 'Mediterráneo', 'Ricotta + tomates cherry en reducción, albahaca, orégano, aceite de oliva y reducción de vinagre balsámico.', 9, 0, 1),
('tostada-capresa',      'tostadas', 'Capresa',      'Tomate fresco + mozzarella, albahaca, aceite de oliva y reducción de vinagre balsámico.', 9, 1, 2),
('tostada-fit',          'tostadas', 'Fit',          'Crema de aguacate, mozzarella y jamón de pechuga de pollo sobre tortilla con cebollín.', 9, 0, 3),
('tostada-hummus',       'tostadas', 'Hummus',       'Hummus tahini y pimentón asado, aceite de oliva, aceitunas y pimentón dulce.', 9, 1, 4),

('focaccia-mortadela', 'focaccia-tortilla', 'Focaccia de Mortadela',          'Pan focaccia artesanal, tomates en reducción, queso mozzarella, rúcula y lechuga, con mortadela.', 11, 1, 1),
('focaccia-mixta',     'focaccia-tortilla', 'Focaccia Pastrami, Salami o Mixta', 'Mismo pan focaccia artesanal con proteína a elección: pastrami, salami o mixta.', 14, 0, 2),
('tortilla-espanola',  'focaccia-tortilla', 'Tortilla Española',              'Tortilla de papas y cebolla caramelizada, acompañada de pan artesanal.', 10, 1, 3),

('patatas-bravas', 'snacks', 'Patatas Bravas',         'Crujientes, con nuestra salsa brava.', 8,   1, 1),
('tequenos',       'snacks', 'Tequeños',               'Clásicos, dorados y crujientes.',       8,   1, 2),
('arepas',         'snacks', 'Arepas',                 'Precio por unidad.',                    4.5, 0, 3),
('te-caliente',    'snacks', 'Té / Infusión Caliente', 'Selección de infusiones calientes.',    1,   0, 4),
('te-frio',        'snacks', 'Té / Infusión Fría',     'Selección de infusiones frías.',        2,   0, 5)
ON DUPLICATE KEY UPDATE id = id;

-- -----------------------------------------------------------------
-- Descuentos
-- -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS descuentos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  tipo ENUM('porcentaje','monto') NOT NULL DEFAULT 'porcentaje',
  valor DECIMAL(8,2) NOT NULL,
  aplica_a ENUM('producto','categoria','todo') NOT NULL DEFAULT 'todo',
  producto_id VARCHAR(80) NULL,
  categoria_id VARCHAR(60) NULL,
  dias_semana VARCHAR(20) NULL COMMENT 'CSV de días 0-6 (0=domingo). NULL = todos los días',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_descuentos_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_descuentos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------
-- Usuarios del panel de administración
-- -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_usuarios (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuario por defecto: admin / tida2026
-- (cambia la contraseña apenas entres por primera vez, desde
--  Configuración › Seguridad en el panel)
INSERT INTO admin_usuarios (usuario, password_hash) VALUES
('admin', '$2b$12$IpeIDNjVEhIcVcbIwfyIsO1rkkI8XJl96MCUN64PqbF3ZxrB4r2j6')
ON DUPLICATE KEY UPDATE usuario = usuario;

SET FOREIGN_KEY_CHECKS = 1;
