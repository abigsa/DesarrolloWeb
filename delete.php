<?php
/** Elimina solamente después de confirmación y validación de sesión/token. */
require_once __DIR__ . '/db.php'; session_start();
require_once __DIR__ . '/partials/layout.php';
if (!isset($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }
$id = filter_input(INPUT_POST, 'id_alumno', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'id_alumno', FILTER_VALIDATE_INT);
if (!$id) { $_SESSION['error'] = 'Estudiante no especificado.'; header('Location: index.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !isset($_SESSION['csrf_eliminar']) || !hash_equals($_SESSION['csrf_eliminar'], $token)) { http_response_code(403); exit('Solicitud no válida.'); }
    $stmt = $pdo->prepare('DELETE FROM alumno WHERE id_alumno = :id');
    try {
        $stmt->execute([':id' => $id]);
        unset($_SESSION['csrf_eliminar']);
        $_SESSION['success'] = 'Estudiante eliminado correctamente.';
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $_SESSION['error'] = 'No fue posible eliminar el estudiante. Comprueba sus registros relacionados.';
    }
    header('Location: index.php', true, 303); exit;
}
$stmt = $pdo->prepare('SELECT id_alumno, nombres, apellidos, correo FROM alumno WHERE id_alumno = :id');
$stmt->execute([':id' => $id]); $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$alumno) { $_SESSION['error'] = 'Estudiante no encontrado.'; header('Location: index.php'); exit; }
$_SESSION['csrf_eliminar'] = bin2hex(random_bytes(32));
abrirPagina('Eliminar estudiante'); ?>
<div class="content-heading"><p class="eyebrow">Administración</p><h1>Eliminar estudiante</h1><p class="subtitle">Confirma que seleccionaste el registro correcto.</p></div><div style="height:24px"></div>
<form class="card form-card narrow" method="post"><div class="warning-box"><strong>Esta acción no se puede deshacer.</strong><p>Se eliminará el registro de <?= e($alumno['nombres'] . ' ' . $alumno['apellidos']) ?>.</p><p class="small-note"><?= e($alumno['correo']) ?></p></div>
<input type="hidden" name="id_alumno" value="<?= (int)$id ?>"><input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_eliminar']) ?>">
<div class="form-actions"><button class="btn-danger" type="submit">Sí, eliminar</button><a class="btn btn-secondary" href="index.php">Cancelar</a></div></form><?php cerrarPagina(); ?>
