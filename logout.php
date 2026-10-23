<?php
// Cierra la sesión de usuario y regresa a la lista pública.
session_start();
unset($_SESSION['nombre']);
unset($_SESSION['id_usuario']);
header('Location: index.php');
?>
