<?php
require_once __DIR__ . '/db.php';
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}
$id = filter_input(INPUT_GET, 'id_alumno', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    $_SESSION['error'] = 'Seleccione un alumno desde la lista.';
    header('Location: index.php');
    exit;
}
$stmt = $pdo->prepare('SELECT id_alumno, nombres, apellidos, correo, aficiones FROM alumno WHERE id_alumno = :id');
$stmt->execute([':id' => $id]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$alumno) {
    $_SESSION['error'] = 'Alumno no encontrado.';
    header('Location: index.php');
    exit;
}
if (empty($_SESSION['token_editar'])) {
    $_SESSION['token_editar'] = bin2hex(random_bytes(32));
}
$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token_editar'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['token_editar'], $token)) {
        http_response_code(403);
        exit('Formulario vencido. Regresa a la lista y selecciona Edit nuevamente.');
    }
    foreach (['nombres' => 50, 'apellidos' => 50, 'correo' => 30, 'aficiones' => 100] as $campo => $limite) {
        $valor = $_POST[$campo] ?? '';
        $alumno[$campo] = is_string($valor) ? trim($valor) : '';
        if ($alumno[$campo] === '' || mb_strlen($alumno[$campo], 'UTF-8') > $limite) {
            $errores[] = ucfirst($campo) . ': ingresa entre 1 y ' . $limite . ' caracteres.';
        }
    }
    if (!filter_var($alumno['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresa un correo electrónico válido.';
    }
    if (!$errores) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT id_alumno FROM alumno WHERE id_alumno = :id FOR UPDATE');
            $stmt->execute([':id' => $id]);
            if (!$stmt->fetch()) {
                throw new RuntimeException('El alumno ya no existe.');
            }
            $stmt = $pdo->prepare('UPDATE alumno SET nombres = :nom, apellidos = :ape, correo = :cor, aficiones = :afi WHERE id_alumno = :id');
            $stmt->execute([
                ':nom' => $alumno['nombres'],
                ':ape' => $alumno['apellidos'],
                ':cor' => $alumno['correo'],
                ':afi' => $alumno['aficiones'],
                ':id' => $id
            ]);
            $pdo->commit();
            $_SESSION['success'] = 'Alumno actualizado correctamente.';
            header('Location: index.php', true, 303);
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log($e->getMessage());
            $errores[] = 'No fue posible guardar. Comprueba que el alumno siga en la lista.';
        }
    }
}
function escaparEdicion($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar alumno</title>
    <link rel="stylesheet" href="diseno.css">
</head>
<body class="pagina-edit">
<h1>Editar alumno</h1>
<p>Actualiza los datos del registro seleccionado.</p>
<?php foreach ($errores as $error): ?>
<p style="color:red" role="alert"><?= escaparEdicion($error) ?></p>
<?php endforeach; ?>
<form method="post">
    <input type="hidden" name="token_editar" value="<?= escaparEdicion($_SESSION['token_editar']) ?>">
    <p><label for="nombres">Nombres</label>
    <input id="nombres" name="nombres" type="text" maxlength="50" required value="<?= escaparEdicion($alumno['nombres']) ?>"></p>
    <p><label for="apellidos">Apellidos</label>
    <input id="apellidos" name="apellidos" type="text" maxlength="50" required value="<?= escaparEdicion($alumno['apellidos']) ?>"></p>
    <p><label for="correo">Correo</label>
    <input id="correo" name="correo" type="email" maxlength="30" required value="<?= escaparEdicion($alumno['correo']) ?>"></p>
    <p><label for="aficiones">Aficiones</label>
    <textarea id="aficiones" name="aficiones" rows="4" maxlength="100" required><?= escaparEdicion($alumno['aficiones']) ?></textarea></p>
    <p><input type="submit" value="Guardar cambios"><a href="index.php">Cancelar</a></p>
</form>
</body>
</html>
