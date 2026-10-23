<?php
/** Componentes comunes: encabezado, navegación y cierre de página. */
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function abrirPagina(string $titulo, string $seccion = ''): void {
    $sesionActiva = isset($_SESSION['id_usuario']);
    ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · Campus Estudiantil</title>
    <link rel="stylesheet" href="diseno.css">
</head>
<body>
<header class="site-header">
  <div class="shell header-inner">
    <a href="index.php" class="brand"><span class="brand-mark">CE</span><span>Campus <b>Estudiantil</b><small>Gestión de estudiantes</small></span></a>
    <nav class="nav" aria-label="Navegación principal">
      <a class="<?= $seccion === 'lista' ? 'active' : '' ?>" href="index.php">Estudiantes</a>
      <?php if ($sesionActiva): ?><a class="<?= $seccion === 'nuevo' ? 'active' : '' ?>" href="nuevo.php">Nuevo registro</a><a href="logout.php">Cerrar sesión</a>
      <?php else: ?><a class="<?= $seccion === 'login' ? 'active' : '' ?>" href="login.php">Iniciar sesión</a><?php endif; ?>
    </nav>
  </div>
</header>
<main class="shell page">
<?php
}
function cerrarPagina(): void { ?>
</main><footer class="footer shell">Campus Estudiantil <span>·</span> Proyecto de Desarrollo Web</footer>
</body></html>
<?php }
function mostrarAvisos(): void {
    foreach (['error' => 'danger', 'success' => 'success'] as $clave => $tipo) {
        if (isset($_SESSION[$clave])) {
            echo '<div class="alert alert-' . $tipo . '" role="alert">' . e($_SESSION[$clave]) . '</div>';
            unset($_SESSION[$clave]);
        }
    }
}
