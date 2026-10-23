<?php
// Conexión PDO reutilizada por las páginas PHP; configura credenciales locales aquí.
$pdo = new PDO('mysql:host=localhost;port=3306;dbname=alumnos', 'root', '');
// See the "errors" folder for details...
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

