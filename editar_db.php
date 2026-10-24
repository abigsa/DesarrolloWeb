<?php
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/partials/layout.php';
require_once __DIR__ . '/academico_helpers.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

$idAlumno = filter_input(
    INPUT_GET,
    'id_alumno',
    FILTER_VALIDATE_INT
);

if (!$idAlumno || $idAlumno < 1) {
    $_SESSION['error'] = 'Selecciona un estudiante válido.';
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id_alumno, nombres, apellidos, correo, aficiones
     FROM alumno
     WHERE id_alumno = ?'
);

$stmt->execute([$idAlumno]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alumno) {
    $_SESSION['error'] = 'El estudiante no existe.';
    header('Location: index.php');
    exit;
}

$_SESSION['csrf_editar'] ??= bin2hex(random_bytes(32));

$errores = [];

$datos = [
    'nombres' => $alumno['nombres'],
    'apellidos' => $alumno['apellidos'],
    'correo' => $alumno['correo'],
    'aficiones' => $alumno['aficiones']
];

$stmt = $pdo->prepare(
    'SELECT ac.anio, c.nombre
     FROM alumno_cursos ac
     INNER JOIN cursos c ON c.id_curso = ac.id_curso
     WHERE ac.id_alumno = ?
     ORDER BY ac.anio DESC, c.nombre'
);

$stmt->execute([$idAlumno]);
$cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    'SELECT anio, institucion, grado
     FROM educacion
     WHERE id_alumno = ?
     ORDER BY anio DESC, institucion, grado'
);

$stmt->execute([$idAlumno]);
$estudios = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_editar'], $token)
    ) {
        http_response_code(403);
        exit('Formulario vencido. Regresa e intenta nuevamente.');
    }

    $datos = leerEstudiante($_POST, $errores);

    $cursos = limpiarFilas(
        filasPost($_POST['cursos'] ?? [], ['anio', 'nombre']),
        'cursos',
        $errores
    );

    $estudios = limpiarFilas(
        filasPost(
            $_POST['educacion'] ?? [],
            ['anio', 'institucion', 'grado']
        ),
        'educacion',
        $errores
    );

    if (!$errores) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'UPDATE alumno
                 SET nombres = ?,
                     apellidos = ?,
                     correo = ?,
                     aficiones = ?
                 WHERE id_alumno = ?'
            );

            $stmt->execute([
                $datos['nombres'],
                $datos['apellidos'],
                $datos['correo'],
                $datos['aficiones'],
                $idAlumno
            ]);

            // Reemplazar las asociaciones del alumno.
            // El catálogo general de cursos se conserva.
            $stmt = $pdo->prepare(
                'DELETE FROM alumno_cursos WHERE id_alumno = ?'
            );

            $stmt->execute([$idAlumno]);
            guardarCursos($pdo, $idAlumno, $cursos);

            $stmt = $pdo->prepare(
                'DELETE FROM educacion WHERE id_alumno = ?'
            );

            $stmt->execute([$idAlumno]);
            guardarEducacion($pdo, $idAlumno, $estudios);

            $pdo->commit();

            $_SESSION['success'] =
                'Estudiante, cursos y educación actualizados correctamente.';

            header('Location: index.php', true, 303);
            exit;

        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($ex->getMessage());

            $errores[] =
                'No fue posible actualizar. Revisa la conexión y la estructura de las tablas.';
        }
    }
}

$catalogoCursos = $pdo->query(
    "SELECT DISTINCT nombre
     FROM cursos
     WHERE nombre IS NOT NULL AND TRIM(nombre) <> ''
     ORDER BY nombre"
)->fetchAll(PDO::FETCH_COLUMN);

abrirPagina('Editar estudiante', 'editar');
?>

<div class="page-heading">
    <div>
        <p class="eyebrow">Gestión académica</p>
        <h1>Editar estudiante</h1>
        <p class="subtitle">
            Actualiza los datos personales, cursos y estudios.
        </p>
    </div>

    <a class="btn btn-secondary" href="index.php">← Volver</a>
</div>

<?php foreach ($errores as $error): ?>
    <div class="alert alert-danger" role="alert">
        <?= e($error) ?>
    </div>
<?php endforeach; ?>

<form
    method="post"
    action="editar_db.php?id_alumno=<?= (int) $idAlumno ?>"
    class="card integrated-form"
>
    <input
        type="hidden"
        name="csrf_token"
        value="<?= e($_SESSION['csrf_editar']) ?>"
    >

    <section class="academic-block academic-blue">
        <div class="academic-heading">
            <span class="academic-number">01</span>
            <div>
                <h2>Datos del estudiante</h2>
                <p>Información personal obligatoria.</p>
            </div>
        </div>

        <div class="field-grid">
            <div class="field">
                <label for="nombres">Nombres</label>
                <input
                    id="nombres"
                    name="nombres"
                    type="text"
                    maxlength="50"
                    required
                    value="<?= e($datos['nombres']) ?>"
                >
            </div>

            <div class="field">
                <label for="apellidos">Apellidos</label>
                <input
                    id="apellidos"
                    name="apellidos"
                    type="text"
                    maxlength="50"
                    required
                    value="<?= e($datos['apellidos']) ?>"
                >
            </div>
        </div>

        <div class="field">
            <label for="correo">Correo electrónico</label>
            <input
                id="correo"
                name="correo"
                type="email"
                maxlength="30"
                required
                value="<?= e($datos['correo']) ?>"
            >
        </div>

        <div class="field">
            <label for="aficiones">Aficiones</label>
            <textarea
                id="aficiones"
                name="aficiones"
                maxlength="100"
                required
            ><?= e($datos['aficiones']) ?></textarea>
        </div>
    </section>

    <?php seccionesAcademicas($cursos, $estudios, $catalogoCursos); ?>

    <div class="form-actions">
        <button type="submit">Guardar cambios</button>
        <a class="btn btn-secondary" href="index.php">Cancelar</a>
    </div>
</form>

<?php cerrarPagina(); ?>