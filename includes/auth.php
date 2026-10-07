<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

/** Llamar al comienzo de cualquier página de /admin que requiera sesión. */
function require_admin_login(): void
{
    if (!admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function admin_login(string $usuario, string $password): bool
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id, password_hash FROM admin_usuarios WHERE usuario = ?');
    $stmt->execute([$usuario]);
    $row = $stmt->fetch();
    if ($row && password_verify($password, $row['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $row['id'];
        $_SESSION['admin_usuario'] = $usuario;
        return true;
    }
    return false;
}

function admin_logout(): void
{
    $_SESSION = [];
    session_destroy();
}

/* ---------------------------------------------------------------
   CSRF simple: un token por sesión, se exige en todos los formularios
   que crean/editan/borran algo en el admin.
   --------------------------------------------------------------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/* Mensaje que se muestra una sola vez arriba de la página siguiente. */
function set_flash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function csrf_check(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Token de seguridad inválido. Vuelve atrás y recarga la página.');
    }
}
