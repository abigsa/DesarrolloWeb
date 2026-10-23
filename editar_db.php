<?php
/** Edición unificada. Se reemplazan únicamente los cursos y estudios de este alumno.
 *  Una transacción garantiza que no queden cambios a medias.
 */
require_once __DIR__.'/db.php';
require_once __DIR__.'/partials/layout.php';
require_once __DIR__.'/academico_helpers.php';
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }
$id=filter_input(INPUT_GET,'id_alumno',FILTER_VALIDATE_INT);
if (!$id || $id<1) { header('Location: index.php'); exit; }
$stmt=$pdo->prepare('SELECT id_alumno,nombres,apellidos,correo,aficiones FROM alumno WHERE id_alumno=?');
$stmt->execute([$id]); $datos=$stmt->fetch(PDO::FETCH_ASSOC);
if (!$datos) { $_SESSION['error']='Estudiante no encontrado.'; header('Location: index.php'); exit; }
$_SESSION['csrf_editar'] ??= bin2hex(random_bytes(32));
// Cargar cursos actuales, en el formato que utiliza el formulario.
$stmt=$pdo->prepare('SELECT ac.anio,c.nombre FROM alumno_cursos ac JOIN cursos c ON c.id_curso=ac.id_curso WHERE ac.id_alumno=? ORDER BY ac.anio DESC');
$stmt->execute([$id]); $cursos=$stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt=$pdo->prepare('SELECT anio,institucion,grado FROM educacion WHERE id_alumno=? ORDER BY anio DESC');
$stmt->execute([$id]); $estudios=$stmt->fetchAll(PDO::FETCH_ASSOC);
$errores=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($_SESSION['csrf_editar'],(string)($_POST['csrf_token'] ?? ''))) { http_response_code(403); exit('Formulario vencido.'); }
    $datos=leerEstudiante($_POST,$errores);
    $cursos=limpiarFilas(filasPost($_POST['cursos'] ?? [],['anio','nombre']),'cursos',$errores);
    $estudios=limpiarFilas(filasPost($_POST['educacion'] ?? [],['anio','institucion','grado']),'educacion',$errores);
    if (!$errores) {
        try {
            $pdo->beginTransaction();
            $lock=$pdo->prepare('SELECT id_alumno FROM alumno WHERE id_alumno=? FOR UPDATE'); $lock->execute([$id]);
            if (!$lock->fetchColumn()) throw new RuntimeException('Estudiante eliminado.');
            $stmt=$pdo->prepare('UPDATE alumno SET nombres=?,apellidos=?,correo=?,aficiones=? WHERE id_alumno=?');
            $stmt->execute([$datos['nombres'],$datos['apellidos'],$datos['correo'],$datos['aficiones'],$id]);
            // Se borran las asociaciones anteriores, no el catálogo global de cursos.
            $pdo->prepare('DELETE FROM alumno_cursos WHERE id_alumno=?')->execute([$id]);
            guardarCursos($pdo,$id,$cursos);
            // Educación pertenece exclusivamente a este estudiante.
            $pdo->prepare('DELETE FROM educacion WHERE id_alumno=?')->execute([$id]);
            guardarEducacion($pdo,$id,$estudios);
            $pdo->commit();
            $_SESSION['success']='Datos personales, cursos y educación actualizados.';
            header('Location: ver.php?id_alumno='.$id,true,303); exit;
        } catch(Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log($ex->getMessage());
            $errores[]='No fue posible guardar los cambios. No se modificaron datos.';
        }
    }
}
abrirPagina('Editar estudiante');
?>
<div class="page-heading"><div><p class="eyebrow">Gestión académica</p><h1>Editar estudiante</h1><p class="subtitle">Modifica los datos, cursos y estudios desde un solo formulario.</p></div><a class="btn btn-secondary" href="ver.php?id_alumno=<?= (int)$id ?>">← Volver</a></div>
<?php foreach ($errores as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<form method="post" class="card integrated-form">
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_editar']) ?>">
<section class="academic-block academic-blue">
 <div class="academic-heading"><span class="academic-number">01</span><div><h2>Datos del estudiante</h2><p>Información personal obligatoria.</p></div></div>
 <div class="field-grid"><div class="field"><label for="nombres">Nombres</label><input id="nombres" name="nombres" type="text" required maxlength="50" value="<?= e($datos['nombres']) ?>"></div><div class="field"><label for="apellidos">Apellidos</label><input id="apellidos" name="apellidos" type="text" required maxlength="50" value="<?= e($datos['apellidos']) ?>"></div></div>
 <div class="field"><label for="correo">Correo electrónico</label><input type="email" id="correo" name="correo" required maxlength="30" value="<?= e($datos['correo']) ?>"></div>
 <div class="field"><label for="aficiones">Aficiones</label><textarea id="aficiones" name="aficiones" maxlength="100" required><?= e($datos['aficiones']) ?></textarea></div>
</section>
<?php seccionesAcademicas($cursos,$estudios); ?>
<p class="small-note">Nota: al guardar se actualizan las listas completas de cursos y estudios de este estudiante. Otros estudiantes no se ven afectados.</p>
<div class="form-actions"><button type="submit">Guardar todos los cambios</button><a class="btn btn-secondary" href="ver.php?id_alumno=<?= (int)$id ?>">Cancelar</a></div>
</form>
<?php cerrarPagina(); ?>
