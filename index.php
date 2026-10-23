<?php
require_once "db.php";
session_start();
?>
<html>
<head>
<title>Lista de estudiantes</title>

<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="diseno.css">
</head><body class="pagina-index">
<h1>Lista de estudiantes</h1>

<?php
if ( isset($_SESSION['id_usuario']) ) {
	echo( '<p><a href="logout.php">Salir</a></p>');
}else{
	echo( '<p><a href="login.php">Ingresar a sistema</a></p>');
}

if ( isset($_SESSION['error']) ) {
    echo '<p style="color:red">'.$_SESSION['error']."</p>\n";
    unset($_SESSION['error']);
}
if ( isset($_SESSION['success']) ) {
    echo '<p style="color:green">'.$_SESSION['success']."</p>\n";
    unset($_SESSION['success']);
}
echo('<table border="1">'."\n");
echo('<tr>
<th>Nombres</th>
<th>Apellidos</th>
<th>Correo</th>
<th>Aficiones</th>
<th>Acción</th>
<tr>');

$stmt = $pdo->query("SELECT id_alumno, nombres, apellidos, correo, aficiones FROM alumno");
while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
    echo "<tr><td>";
    echo(($row['nombres']));
	echo "</td><td>";
    echo(($row['apellidos']));
    echo("</td><td>");
    echo(($row['correo']));
    echo("</td><td>");
    echo(($row['aficiones']));
    echo("</td><td>");

	echo('<a href="ver.php?id_alumno='.$row['id_alumno'].'">Ver</a> / ');
	if ( isset($_SESSION['id_usuario']) ) {
		echo('<a href="edit.php?id_alumno='.$row['id_alumno'].'">Edit</a> / ');
		echo('<a href="delete.php?id_alumno='.$row['id_alumno'].'">Delete</a>');
	}
	echo("</td></tr>\n");
}
?>
</table>
<?php
	if ( isset($_SESSION['id_usuario']) ) {
		echo( '<a href="nuevo.php">Agregar alumno</a>');
	}
?>

