<?php
require_once __DIR__ . '/config.php';

/**
 * Conexión PDO única y reutilizable. Cualquier archivo que necesite
 * hablar con la base de datos incluye este archivo y usa la
 * variable $pdo.
 */
function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die('No se pudo conectar a la base de datos. Revisa includes/config.php. (' . htmlspecialchars($e->getMessage()) . ')');
        }
    }
    return $pdo;
}

$pdo = get_pdo();
