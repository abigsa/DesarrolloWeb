<?php
/**
 * MÓDULO EDUCACIÓN - CRUD académico.
 * GET ?id_alumno=... lista y ofrece formulario de alta.
 * GET ?id_alumno=...&editar=... carga un estudio para editar.
 * POST accion=guardar crea o actualiza mediante consultas preparadas.
 * POST accion=eliminar borra un registro con protección CSRF.
 * Las acciones que alteran datos requieren una sesión iniciada.
 */
require_once __DIR__ . '/db.php';
session_start();
require_once __DIR__ . '/partials/layout.php';

$idAlumno = filter_input(INPUT_GET, 'id_alumno', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'id_alumno', FILTER_VALIDATE_INT);
if (!$idAlumno) {
    $_SESSION['error'] = 'Selecciona un estudiante válido.';
    header('Location: index.php'); exit;
}

// Busca primero al estudiante; así no se pueden asociar estudios a IDs inexistentes.
$stmt = $pdo->prepare('SELECT id_alumno, nombres, apellidos FROM alumno WHERE id_alumno = ?');
$stmt->execute([$idAlumno]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$alumno) {
    $_SESSION['error'] = 'El estudiante no existe.';
    header('Location: index.php'); exit;
}

$autorizado = isset($_SESSION['id_usuario']);
$_SESSION['csrf_educacion'] ??= bin2hex(random_bytes(32));
$errores = [];
$datos = ['id_educacion' => 0, 'anio' => '', 'institucion' => '', 'grado' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$autorizado) { http_response_code(403); exit('Debes iniciar sesión.'); }
    // La sesión guarda el token esperado; el formulario envía una copia.
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_educacion'], $token)) {
        http_response_code(403); exit('Token de seguridad incorrecto.');
    }
    $accion = $_POST['accion'] ?? '';
    $idEducacion = filter_input(INPUT_POST, 'id_educacion', FILTER_VALIDATE_INT) ?: 0;
    if ($accion === 'eliminar' && $idEducacion > 0) {
        // Incluye id_alumno en WHERE para evitar borrar estudios de otro alumno.
        $stmt = $pdo->prepare('DELETE FROM educacion WHERE id_educacion = ? AND id_alumno = ?');
        $stmt->execute([$idEducacion, $idAlumno]);
        $_SESSION['success'] = $stmt->rowCount() ? 'Estudio eliminado correctamente.' : 'No se encontró el estudio.';
        header('Location: educacion.php?id_alumno=' . $idAlumno, true, 303); exit;
    }
    if ($accion === 'guardar') {
        $anio = filter_var($_POST['anio'] ?? null, FILTER_VALIDATE_INT);
        $institucion = trim((string)($_POST['institucion'] ?? ''));
        $grado = trim((string)($_POST['grado'] ?? ''));
        $datos = ['id_educacion' => $idEducacion, 'anio' => $_POST['anio'] ?? '', 'institucion' => $institucion, 'grado' => $grado];
        $maxAnio = (int)date('Y') + 5;
        if ($anio === false || $anio < 1900 || $anio > $maxAnio) $errores[] = 'Ingresa un año entre 1900 y ' . $maxAnio . '.';
        if ($institucion === '' || mb_strlen($institucion) > 120) $errores[] = 'La institución es obligatoria (máximo 120 caracteres).';
        if ($grado === '' || mb_strlen($grado) > 120) $errores[] = 'El grado es obligatorio (máximo 120 caracteres).';
        if (!$errores) {
            if ($idEducacion > 0) {
                $stmt = $pdo->prepare('UPDATE educacion SET anio=?, institucion=?, grado=? WHERE id_educacion=? AND id_alumno=?');
                $stmt->execute([$anio, $institucion, $grado, $idEducacion, $idAlumno]);
                // Si otro alumno tiene ese ID, la condición lo protege.
                $check = $pdo->prepare('SELECT id_educacion FROM educacion WHERE id_educacion=? AND id_alumno=?');
                $check->execute([$idEducacion, $idAlumno]);
                $_SESSION['success'] = $check->fetchColumn() ? 'Estudio actualizado.' : 'No se encontró el estudio solicitado.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO educacion (id_alumno, anio, institucion, grado) VALUES (?, ?, ?, ?)');
                $stmt->execute([$idAlumno, $anio, $institucion, $grado]);
                $_SESSION['success'] = 'Estudio registrado correctamente.';
            }
            header('Location: educacion.php?id_alumno=' . $idAlumno, true, 303); exit;
        }
    } else if ($accion !== 'eliminar') $errores[] = 'Acción desconocida.';
}

// Cargar el registro específico para rellenar los campos en modo edición.
$editarId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
if ($autorizado && $editarId && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $pdo->prepare('SELECT * FROM educacion WHERE id_educacion=? AND id_alumno=?');
    $stmt->execute([$editarId, $idAlumno]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($registro) $datos = $registro;
    else $errores[] = 'El estudio solicitado no existe para este estudiante.';
}

$stmt = $pdo->prepare('SELECT id_educacion, anio, institucion, grado FROM educacion WHERE id_alumno=? ORDER BY anio DESC, id_educacion DESC');
$stmt->execute([$idAlumno]);
$estudios = $stmt->fetchAll(PDO::FETCH_ASSOC);
abrirPagina('Educación del estudiante');
?>
<div class="page-heading"><div><p class="eyebrow">Historial académico</p><h1>Educación de <?= e($alumno['nombres'] . ' ' . $alumno['apellidos']) ?></h1><p class="subtitle">Registra y consulta los estudios del estudiante.</p></div><a class="btn btn-secondary" href="ver.php?id_alumno=<?= $idAlumno ?>">← Volver a la ficha</a></div>
<?php mostrarAvisos(); ?>
<?php foreach ($errores as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<section class="school-section" aria-labelledby="estudios-title">
 <div class="school-section-head yellow"><span class="num">03</span><h2 id="estudios-title">Estudios registrados</h2><span class="helper"><?= count($estudios) ?> registros</span></div>
 <div class="table-wrap"><table class="table"><thead><tr><th>Año</th><th>Institución</th><th>Grado</th><?php if ($autorizado): ?><th>Acciones</th><?php endif; ?></tr></thead><tbody>
 <?php foreach ($estudios as $estudio): ?><tr><td><?= e($estudio['anio']) ?></td><td><?= e($estudio['institucion']) ?></td><td><?= e($estudio['grado']) ?></td>
 <?php if ($autorizado): ?><td><div class="table-actions"><a href="educacion.php?id_alumno=<?= $idAlumno ?>&amp;editar=<?= (int)$estudio['id_educacion'] ?>#formulario-educacion">Editar</a>
 <form method="post" onsubmit="return confirm('¿Eliminar este estudio?');" class="inline-form"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_educacion']) ?>"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id_alumno" value="<?= $idAlumno ?>"><input type="hidden" name="id_educacion" value="<?= (int)$estudio['id_educacion'] ?>"><button type="submit" class="small-danger">Eliminar</button></form></div></td><?php endif; ?></tr><?php endforeach; ?>
 <?php if (!$estudios): ?><tr><td colspan="<?= $autorizado ? 4 : 3 ?>" class="empty-state">Este estudiante todavía no tiene estudios registrados.</td></tr><?php endif; ?>
 </tbody></table></div>
</section>
<?php if ($autorizado): ?>
<section class="card form-card education-form" id="formulario-educacion"><h2 class="section-title"><?= (int)$datos['id_educacion'] ? 'Editar estudio' : 'Agregar estudio' ?></h2>
<form method="post" action="educacion.php?id_alumno=<?= $idAlumno ?>#formulario-educacion">
<input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_educacion']) ?>"><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id_alumno" value="<?= $idAlumno ?>"><input type="hidden" name="id_educacion" value="<?= (int)$datos['id_educacion'] ?>">
<div class="field-grid"><div class="field"><label for="anio">Año *</label><input type="number" id="anio" name="anio" min="1900" max="<?= (int)date('Y')+5 ?>" required value="<?= e($datos['anio']) ?>" placeholder="Ej. 2026"></div>
<div class="field"><label for="institucion">Institución *</label><input type="text" id="institucion" name="institucion" maxlength="120" required value="<?= e($datos['institucion']) ?>" placeholder="Ej. Universidad Mariano Gálvez"></div></div>
<div class="field"><label for="grado">Grado o título *</label><input type="text" id="grado" name="grado" maxlength="120" required value="<?= e($datos['grado']) ?>" placeholder="Ej. Bachillerato en Computación"></div>
<div class="form-actions"><button type="submit"><?= (int)$datos['id_educacion'] ? 'Guardar cambios' : 'Registrar estudio' ?></button><?php if ((int)$datos['id_educacion']): ?><a class="btn btn-secondary" href="educacion.php?id_alumno=<?= $idAlumno ?>#formulario-educacion">Cancelar edición</a><?php endif; ?></div>
</form></section>
<?php else: ?><p class="small-note">Inicia sesión para registrar o modificar estudios académicos.</p><?php endif; ?>
<?php cerrarPagina(); ?>
