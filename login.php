<?php
/** Autenticación: conserva la consulta del proyecto original.
 * Aviso: la BD de ejemplo usa contraseñas en texto plano; migrar a password_hash.
 */
require_once __DIR__ . '/db.php';
session_start();
require_once __DIR__ . '/partials/layout.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim((string)($_POST['correo'] ?? ''));
    $clave = (string)($_POST['pass'] ?? '');
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || $clave === '') {
        $_SESSION['error'] = 'Ingresa un correo y contraseña válidos.';
    } else {
        $stmt = $pdo->prepare('SELECT id_usuario, nombre FROM usuario WHERE correo = :em AND password = :pw');
        $stmt->execute([':em' => $correo, ':pw' => $clave]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($usuario) {
            session_regenerate_id(true);
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['success'] = 'Bienvenido, ' . $usuario['nombre'] . '.';
            header('Location: index.php', true, 303);
            exit;
        }
        $_SESSION['error'] = 'Correo o contraseña incorrectos.';
    }
}
abrirPagina('Iniciar sesión', 'login'); ?>
<div class="content-heading"><p class="eyebrow">Acceso al sistema</p><h1>Bienvenido de nuevo</h1><p class="subtitle">Ingresa para administrar los registros de estudiantes.</p></div>
<div style="height:22px"></div><?php mostrarAvisos(); ?>
<form class="card form-card narrow" method="post">
<div class="field"><label for="correo">Correo electrónico</label><input id="correo" type="email" name="correo" autocomplete="username" required maxlength="254" placeholder="nombre@ejemplo.com"></div>
<div class="field"><label for="pass">Contraseña</label><input id="pass" type="password" name="pass" autocomplete="current-password" required></div>
<div class="form-actions"><button type="submit">Ingresar</button><a class="btn btn-secondary" href="index.php">Volver al listado</a></div>
</form>
<?php cerrarPagina(); ?>
