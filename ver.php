<?php
/** Vista de detalle y formación complementaria del estudiante. */
require_once __DIR__ . '/db.php'; session_start();
require_once __DIR__ . '/partials/layout.php';
$id = filter_input(INPUT_GET, 'id_alumno', FILTER_VALIDATE_INT);
if (!$id) { $_SESSION['error'] = 'Selecciona un estudiante.'; header('Location: index.php'); exit; }
$stmt = $pdo->prepare('SELECT id_alumno, nombres, apellidos, correo, aficiones FROM alumno WHERE id_alumno = :id');
$stmt->execute([':id' => $id]); $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$alumno) { $_SESSION['error'] = 'Estudiante no encontrado.'; header('Location: index.php'); exit; }
$stmt = $pdo->prepare('SELECT ac.anio, c.nombre FROM alumno_cursos ac INNER JOIN cursos c ON c.id_curso = ac.id_curso WHERE ac.id_alumno = :id ORDER BY ac.anio DESC');
$stmt->execute([':id' => $id]); $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Historial de educación del alumno seleccionado.
$stmt = $pdo->prepare('SELECT anio, institucion, grado FROM educacion WHERE id_alumno=? ORDER BY anio DESC');
$stmt->execute([$id]); $educacion = $stmt->fetchAll(PDO::FETCH_ASSOC);
abrirPagina('Detalle de estudiante'); ?>
<div class="page-heading"><div><p class="eyebrow">Ficha de estudiante</p><h1><?= e($alumno['nombres'] . ' ' . $alumno['apellidos']) ?></h1><p class="subtitle">Información personal y formación complementaria.</p></div><a class="btn btn-secondary" href="index.php">← Volver</a></div>
<section class="card card-pad"><div class="details"><div><p class="detail-label">Nombres</p><p class="detail-value"><?= e($alumno['nombres']) ?></p></div><div><p class="detail-label">Apellidos</p><p class="detail-value"><?= e($alumno['apellidos']) ?></p></div><div><p class="detail-label">Correo electrónico</p><p class="detail-value"><?= e($alumno['correo']) ?></p></div><div><p class="detail-label">Aficiones</p><p class="detail-value"><?= e($alumno['aficiones']) ?></p></div></div>
<h2 class="section-title">Cursos y certificaciones</h2>
<?php if ($cursos): ?><ul class="course-list"><?php foreach($cursos as $curso): ?><li><span class="tag"><?= e($curso['anio']) ?></span><?= e($curso['nombre']) ?></li><?php endforeach; ?></ul><?php else: ?><p class="muted">No hay cursos registrados para este estudiante.</p><?php endif; ?>
<h2 class="section-title">Educación</h2>
<?php if ($educacion): ?><ul class="course-list"><?php foreach ($educacion as $estudio): ?><li><span class="tag"><?= e($estudio['anio']) ?></span><strong><?= e($estudio['institucion']) ?></strong> · <?= e($estudio['grado']) ?></li><?php endforeach; ?></ul><?php else: ?><p class="muted">No hay estudios académicos registrados.</p><?php endif; ?>
<div class="form-actions"><a class="btn btn-secondary" href="educacion.php?id_alumno=<?= (int)$id ?>"><?= isset($_SESSION['id_usuario']) ? 'Administrar educación' : 'Ver educación' ?></a></div>
<?php if (isset($_SESSION['id_usuario'])): ?><div class="form-actions"><a class="btn" href="edit.php?id_alumno=<?= (int)$id ?>">Editar estudiante</a></div><?php endif; ?>
</section><?php cerrarPagina(); ?>
