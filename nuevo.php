<?php
/** Alta unificada: datos personales, cursos y educación en una transacción. */
require_once __DIR__.'/db.php';
require_once __DIR__.'/partials/layout.php';
require_once __DIR__.'/academico_helpers.php';
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }
$_SESSION['csrf_nuevo'] ??= bin2hex(random_bytes(32));
$errores=[];
$datos=['nombres'=>'','apellidos'=>'','correo'=>'','aficiones'=>'']; $cursos=[]; $estudios=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($_SESSION['csrf_nuevo'], (string)($_POST['csrf_token'] ?? ''))) { http_response_code(403); exit('Formulario vencido.'); }
    $datos=leerEstudiante($_POST,$errores);
    $cursos=limpiarFilas(filasPost($_POST['cursos'] ?? [],['anio','nombre']),'cursos',$errores);
    $estudios=limpiarFilas(filasPost($_POST['educacion'] ?? [],['anio','institucion','grado']),'educacion',$errores);
    if (!$errores) {
        try {
            $pdo->beginTransaction();
            $stmt=$pdo->prepare('INSERT INTO alumno(id_usuario,nombres,apellidos,correo,aficiones) VALUES (?,?,?,?,?)');
            $stmt->execute([$_SESSION['id_usuario'],$datos['nombres'],$datos['apellidos'],$datos['correo'],$datos['aficiones']]);
            $idAlumno=$pdo->lastInsertId();
            guardarCursos($pdo,$idAlumno,$cursos);
            guardarEducacion($pdo,$idAlumno,$estudios);
            $pdo->commit();
            $_SESSION['success']='Estudiante, cursos y educación guardados correctamente.';
            header('Location: ver.php?id_alumno='.$idAlumno,true,303); exit;
        } catch (Throwable $ex) {
            if($pdo->inTransaction()) $pdo->rollBack();
            error_log($ex->getMessage());
            $errores[]='No fue posible guardar. Revisa la conexión y que exista la tabla educacion.';
        }
    }
}
abrirPagina('Nuevo estudiante','nuevo');
?>
<div class="page-heading"><div><p class="eyebrow">Gestión académica</p><h1>Nuevo estudiante</h1><p class="subtitle">Completa los datos del estudiante y, si corresponde, sus cursos y estudios.</p></div><a class="btn btn-secondary" href="index.php">← Volver</a></div>
<?php foreach($errores as $error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endforeach; ?>
<form method="post" class="card integrated-form">
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_nuevo']) ?>">
<section class="academic-block academic-blue">
 <div class="academic-heading"><span class="academic-number">01</span><div><h2>Datos del estudiante</h2><p>Información personal obligatoria.</p></div></div>
 <div class="field-grid"><div class="field"><label for="nombres">Nombres</label><input id="nombres" name="nombres" type="text" required maxlength="50" value="<?= e($datos['nombres']) ?>"></div><div class="field"><label for="apellidos">Apellidos</label><input id="apellidos" name="apellidos" type="text" required maxlength="50" value="<?= e($datos['apellidos']) ?>"></div></div>
 <div class="field"><label for="correo">Correo electrónico</label><input id="correo" type="email" name="correo" required maxlength="30" value="<?= e($datos['correo']) ?>"></div>
 <div class="field"><label for="aficiones">Aficiones</label><textarea id="aficiones" name="aficiones" maxlength="100" required><?= e($datos['aficiones']) ?></textarea></div>
</section>
<?php seccionesAcademicas($cursos,$estudios); ?>
<div class="form-actions"><button type="submit">Guardar estudiante</button><a class="btn btn-secondary" href="index.php">Cancelar</a></div>
</form>
<?php cerrarPagina(); ?>
