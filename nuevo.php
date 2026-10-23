<?php
require_once "db.php";
session_start();
if ( !isset($_SESSION['id_usuario']) ) {
	die("Ingresar al sistema"); //Diferencia entre die y redireccionar a alguna pagina como login.php/index.php
}
	
if ( isset($_POST['nombres']) 
	&& isset($_POST['apellidos'])
	&& isset($_POST['correo'])
	&& isset($_POST['aficiones'])) {

    // validación de datos del lado del server/cliente?
    if ( strlen($_POST['nombres']) < 1 || strlen($_POST['apellidos']) < 1) {
        $_SESSION['error'] = 'Datos incompletos';
        header("Location: nuevo.php");
        return;
    }

    if (!filter_var(trim($_POST['correo']), FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Correo electrónico no válido';
        header("Location: nuevo.php");
        return;
    }

    foreach (['nombres'=>50, 'apellidos'=>50, 'correo'=>30, 'aficiones'=>100] as $campo=>$maximo) {
        if (trim($_POST[$campo]) === '' || mb_strlen(trim($_POST[$campo]), 'UTF-8') > $maximo) {
            $_SESSION['error'] = ucfirst($campo).' es obligatorio y admite hasta '.$maximo.' caracteres.';
            header('Location: nuevo.php');
            exit;
        }
    }
    $repetidos = [];
	/* Validar los mismos campos que envía el formulario. */
	  for($i=1; $i<=9; $i++) {
		if ( ! isset($_POST['cur_anio'.$i]) ) continue;
		if ( ! isset($_POST['cur_nombre'.$i]) ) continue;

		$anio = trim($_POST['cur_anio'.$i]);
		$curso = trim($_POST['cur_nombre'.$i]);

		if ( strlen($anio) == 0 || strlen($curso) == 0 || mb_strlen($curso, 'UTF-8') > 50 ) {
			$_SESSION['error'] = 'Todos los campos son requeridos';
			header("Location: nuevo.php");
			return;
		}

        $clave = mb_strtolower($curso, 'UTF-8').'|'.$anio;
        if (isset($repetidos[$clave])) {
            $_SESSION['error'] = 'No repitas el mismo curso y año.';
            header('Location: nuevo.php');
            exit;
        }
        $repetidos[$clave] = true;
		if (filter_var($anio, FILTER_VALIDATE_INT) === false || (int)$anio < 1900 || (int)$anio > 2100) {
			$_SESSION['error'] = 'El año debe ser un entero entre 1900 y 2100';
			header("Location: nuevo.php");
		  return ;
		}
	  }
	
    try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO alumno(id_usuario, nombres, apellidos, correo, aficiones)
		VALUES ( :ide, :nom, :ape, :cor, :afi);');
  
    $stmt->execute(array(
	  ':ide' => $_SESSION['id_usuario'],
	  ':nom' => htmlentities(trim($_POST['nombres']), ENT_QUOTES, 'UTF-8'),
	  ':ape' => htmlentities(trim($_POST['apellidos']), ENT_QUOTES, 'UTF-8'),
	  ':cor' => htmlentities(trim($_POST['correo']), ENT_QUOTES, 'UTF-8'),
	  ':afi' => htmlentities(trim($_POST['aficiones']), ENT_QUOTES, 'UTF-8'))
	);
	
	$id_alumno = $pdo->lastInsertId();
	
	//insertar cursos y curso si es nuevo
	for($i=1; $i<=9; $i++) {
		if ( ! isset($_POST['cur_anio'.$i]) ) continue;
		if ( ! isset($_POST['cur_nombre'.$i]) ) continue;

		$anio = trim($_POST['cur_anio'.$i]);
		$curso = trim($_POST['cur_nombre'.$i]);

		$id_curso = false;
		$stmt = $pdo->prepare('select id_curso from cursos WHERE nombre = :nom');
		$stmt->execute(array(':nom' => $curso));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if ($row!==false) $id_curso= $row['id_curso'];
		if ($id_curso===false){
			$stmt = $pdo->prepare('INSERT INTO cursos(nombre) VALUES (:nom)');
			$stmt->execute(array(':nom' => $curso));
			$id_curso = $pdo->lastInsertId();
		}
		$stmt = $pdo->prepare('INSERT INTO alumno_cursos(id_alumno, anio, id_curso) VALUES ( :ide, :anio, :idc)');
		$stmt->execute(array(
		  ':ide' => $id_alumno,
		  ':anio' => $anio,
		  ':idc' => $id_curso)
			);
    }
  	
    $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log($e->getMessage());
        $_SESSION['error'] = 'No se guardó el registro. Revisa los cursos y vuelve a intentarlo.';
        header('Location: nuevo.php');
        exit;
    }
    $_SESSION['success'] = 'Registro agregado!!!';
    header( 'Location: index.php' ) ;
    return;
}

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php require_once('head.php'); ?>
<title>Nuevo alumno</title>
<link rel="stylesheet" href="diseno.css">
<link rel="stylesheet" href="nuevo.css">
</head>
<body class="pagina-nuevo">
<h1>Nuevo alumno</h1>
<p class="intro-nuevo">Datos del estudiante y formación complementaria.</p>
<?php if (isset($_SESSION['error'])): ?>
<p style="color:red" role="alert"><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?></p>
<?php unset($_SESSION['error']); endif; ?>
<form method="post" id="form-alumno">
<div class="campo"><label for="fn">Nombres</label><input type="text" name="nombres" id="fn" maxlength="50" required></div>
<div class="campo"><label for="ln">Apellidos</label><input type="text" name="apellidos" id="ln" maxlength="50" required></div>
<div class="campo"><label for="em">Correo</label><input type="email" name="correo" id="em" maxlength="30" required></div>
<div class="campo"><label for="afi">Aficiones</label><textarea name="aficiones" id="afi" rows="3" maxlength="100" required></textarea></div>
<section class="seccion-cursos" aria-labelledby="titulo-cursos">
<div class="cabecera-cursos"><h2 id="titulo-cursos">Cursos y certificaciones IT</h2><button type="button" id="addCurso" aria-label="Agregar curso">+</button></div>
<p class="ayuda-cursos">Escribe el nombre para buscar un curso existente o registrar uno nuevo. Máximo 9 cursos.</p>
<div id="curso_fields"></div>
<p id="estado-cursos" role="status" aria-live="polite"></p>
</section>
<div class="acciones-nuevo"><input type="submit" value="Grabar"><a href="index.php">Cancelar</a></div>
</form>
<script>
(function () {
    var estado = document.getElementById('estado-cursos');
    if (!window.jQuery || !jQuery.ui || !jQuery.ui.autocomplete) {
        estado.textContent = 'No se cargó jQuery UI. Revisa tu conexión a Internet y recarga la página para agregar cursos.';
        document.getElementById('addCurso').disabled = true;
        return;
    }
    jQuery(function ($) {
        function agregarCurso() {
            var i;
            for (i = 1; i <= 9; i++) {
                if (!document.getElementById('curso' + i)) break;
            }
            if (i > 9) {
                estado.textContent = 'Ya tienes 9 cursos. Quita uno para agregar otro.';
                return;
            }
            var fila = $('<div>', {id: 'curso' + i, class: 'fila-curso'});
            fila.html('<div class="campo"><label for="anio'+i+'">Año</label><div class="anio-controles"><input type="number" id="anio'+i+'" name="cur_anio'+i+'" min="1900" max="2100" step="1" required><button type="button" class="quitar-curso" aria-label="Quitar este curso">−</button></div></div><div class="campo"><label for="nombre'+i+'">Nombre</label><input type="text" id="nombre'+i+'" name="cur_nombre'+i+'" class="cursos" maxlength="50" autocomplete="off" placeholder="Ej. PHP" required></div>');
            $('#curso_fields').append(fila);
            fila.find('.cursos').autocomplete({
                minLength: 1,
                delay: 200,
                source: function (request, response) {
                    $.ajax({url: 'cursos.php', data: {term: request.term}, dataType: 'json'})
                    .done(function (datos) {
                        response(datos);
                        estado.textContent = datos.length ? 'Selecciona una sugerencia con el mouse o con las flechas y Enter.' : 'No hay coincidencias. El nombre escrito se registrará como un curso nuevo.';
                    })
                    .fail(function () {
                        response([]);
                        estado.textContent = 'No se pudieron consultar los cursos. Revisa tu sesión y la conexión a MySQL.';
                    });
                },
                select: function () { estado.textContent = 'Curso seleccionado.'; }
            });
            estado.textContent = '';
            fila.find('input').first().trigger('focus');
        }
        $('#addCurso').on('click', agregarCurso);
        $('#curso_fields').on('click', '.quitar-curso', function () {
            $(this).closest('.fila-curso').find('.cursos').autocomplete('destroy');
            $(this).closest('.fila-curso').remove();
            estado.textContent = '';
        });
    });
})();
</script>
</body>
</html>
