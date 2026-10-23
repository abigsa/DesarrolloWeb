<?php
require_once "db.php";
session_start();


if ( isset($_POST['correo']) 
	&& isset($_POST['pass'])) {


    //validar
    if ( strlen($_POST['correo']) < 1 || strlen($_POST['pass']) < 1) {
        $_SESSION['error'] = 'datos incompletos';
        header("Location: login.php");
        return;
    }

    if ( strpos($_POST['correo'],'@') === false ) {
        $_SESSION['error'] = 'Correo no válido';
        header("Location: login.php");
        return;
    }
	//$salt="XyZzy12*_";
	
	//$check = hash('md5', $salt.$_POST['pass']);
	$check = $_POST['pass'];
    $stmt = $pdo->prepare('select id_usuario, nombre from usuario WHERE correo = :em AND password = :pw');
	
	$stmt->execute(array( ':em' => $_POST['correo'], ':pw' => $check));
	
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	if ( $row !== false ) {
         $_SESSION['nombre'] = $row['nombre'];
         $_SESSION['id_usuario'] = $row['id_usuario'];
		 $_SESSION['success']="Bienvenido ". $row['nombre'];;
         // Redirect the browser to index.php
         header("Location: index.php");
         return;
	}else{
		$_SESSION['error'] = 'usuario no encontrado, revisar usuario y/o contraseña';
	}
} 

// Flash pattern
if ( isset($_SESSION['error']) ) {
    echo '<p style="color:red">'.$_SESSION['error']."</p>\n";
    unset($_SESSION['error']);
}
?>
<html>
<head>
<title>Ingreso al sistema</title>

<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="diseno.css">
</head><body class="pagina-login">
<h1>Ingrese sus credenciales</h1>
<div class="container">
<form method="post">
<p>correo:
<input type="text" name="correo" id="f_correo"></p>

<p>Password:
<input type="password" name="pass" id="f_pass"></p>

<p><input type="submit" onclick="return doValidate();" value="Log In"/>
<a href="login.php">Cancelar</a></p>
</form>

<script>
function doValidate() {
         console.log('Validating...');
         try {
             pw = document.getElementById('f_pass').value;
			 em = document.getElementById('f_correo').value;
             console.log("Validating pw="+pw);
			 console.log("Validating em="+em);
             if (pw == null || pw == "") {
                 alert("Debe especificar ambos campos");
                 return false;
             }
             return true;
         } catch(e) {
             return false;
         }
         return false;
     }
</script>
</div>
</body>

