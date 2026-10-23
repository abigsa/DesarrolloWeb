<?php
require_once "db.php";
session_start();
if ( !isset($_SESSION['id_usuario']) ) {
	die("Not logged in");
}
if ( isset($_POST['delete']) && isset($_POST['id_alumno']) ) {
    $sql = "DELETE FROM alumno WHERE id_alumno = :zip";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(':zip' => $_POST['id_alumno']));
    $_SESSION['success'] = 'Registro Eliminado!!';
    header( 'Location: index.php' ) ;
    return;
}

// Guardian: Make sure that id_usuario is present
if ( ! isset($_GET['id_alumno']) ) {
$_SESSION['error'] = "no se especificó alumno a eliminar";
  header('Location: index.php');
  return;
}

$stmt = $pdo->prepare("SELECT id_alumno, nombres, apellidos, correo, aficiones FROM alumno where id_alumno = :idx");
$stmt->execute(array(":idx" => $_GET['id_alumno']));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ( $row === false ) {
    $_SESSION['error'] = 'Alumno no encontrado';
    header( 'Location: index.php' ) ;
    return;
}
$fn = ($row['nombres'] .", " . $row['apellidos'] );
$em = ($row['correo']);
?>
<html>
<head>
<title>Eliminar alumno</title>

<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="diseno.css">
</head><body class="pagina-delete">
<h1>Eliminar alumno</h1>

<h1>Desea eliminar al alumno: <?= ($row['nombres'] .", " . $row['apellidos']) ?></h1>

<form method="post">
<p>Alumno: <?php echo($fn);?></p>
<p>Correo:<?php echo($em);?></p>

<input type="hidden" name="id_alumno" value="<?= $row['id_alumno'] ?>">
<input type="submit" value="Delete" name="delete">
<a href="index.php">Cancel</a>
</form>
