<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/data.php'; // solo para $config->nombre_sitio

if (admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($usuario !== '' && $password !== '' && admin_login($usuario, $password)) {
        header('Location: index.php');
        exit;
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Ingresar — Admin <?= htmlspecialchars($config['nombre_sitio']) ?></title>
<link rel="icon" type="image/png" href="../assets/icono-navy.png" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Work+Sans:wght@400;500;600&display=swap">
<link rel="stylesheet" href="assets/admin.css" />
</head>
<body class="admin-body">
  <div class="login-shell">
    <div class="login-card">
      <img src="../assets/icono.png" alt="" />
      <h1>Panel de administración</h1>
      <p class="sub"><?= htmlspecialchars($config['nombre_sitio']) ?></p>

      <?php if ($error): ?>
        <div class="admin-flash error" style="margin-bottom:14px;"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" action="login.php">
        <?= csrf_field() ?>
        <div class="field">
          <label for="usuario">Usuario</label>
          <input type="text" id="usuario" name="usuario" autocomplete="username" required autofocus>
        </div>
        <div class="field">
          <label for="password">Contraseña</label>
          <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn btn-save">Ingresar</button>
      </form>
    </div>
  </div>
</body>
</html>
