<?php
/**
 * PLANTILLA. Copia este archivo como "config.php" (sin .example) y completa
 * los 4 valores con los datos de tu hosting o de XAMPP local
 * (XAMPP: host 'localhost', usuario 'root', contraseña vacía).
 * config.php está en .gitignore: nunca se sube a GitHub.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'nombre_de_la_base');
define('DB_USER', 'usuario_de_la_base');
define('DB_PASS', 'contraseña_de_la_base');

// Zona horaria usada para calcular "qué día es hoy" en los descuentos
date_default_timezone_set('America/Caracas');
