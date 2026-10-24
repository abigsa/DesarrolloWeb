<?php
/**
 * Funciones compartidas por los formularios de alta y edición.
 */

function limpiarFilas($filas, $tipo, &$errores)
{
    if (!is_array($filas)) {
        $errores[] = 'Formato de ' . $tipo . ' inválido.';
        return [];
    }

    if (count($filas) > 30) {
        $errores[] = 'Se permiten hasta 30 ' . $tipo . '.';
        return [];
    }

    $resultado = [];
    $vistos = [];

    foreach ($filas as $fila) {
        if (!is_array($fila)) {
            $errores[] = 'Fila inválida en ' . $tipo . '.';
            continue;
        }

        $valores = [];

        foreach (['anio', 'nombre', 'institucion', 'grado'] as $campo) {
            $valor = $fila[$campo] ?? '';

            if (!is_scalar($valor)) {
                $errores[] = 'Valor inválido en ' . $tipo . '.';
                continue 2;
            }

            $valores[$campo] = trim((string) $valor);
        }

        $anioTexto = $valores['anio'];
        $nombre = $valores['nombre'];
        $institucion = $valores['institucion'];
        $grado = $valores['grado'];

        // Las filas totalmente vacías son opcionales.
        if (
            $anioTexto === '' &&
            $nombre === '' &&
            $institucion === '' &&
            $grado === ''
        ) {
            continue;
        }

        $anio = filter_var($anioTexto, FILTER_VALIDATE_INT);
        $anioMaximo = (int) date('Y') + 5;

        if (
            $anio === false ||
            $anio < 1900 ||
            $anio > $anioMaximo
        ) {
            $errores[] =
                'Año inválido en ' . $tipo .
                ' (1900 a ' . $anioMaximo . ').';
            continue;
        }

        if ($tipo === 'cursos') {
            if (
                $nombre === '' ||
                mb_strlen($nombre, 'UTF-8') > 50
            ) {
                $errores[] =
                    'El nombre del curso debe tener entre 1 y 50 caracteres.';
                continue;
            }

            $clave = mb_strtolower($nombre, 'UTF-8') . '|' . $anio;

            $item = [
                'anio' => $anio,
                'nombre' => $nombre
            ];
        } else {
            if (
                $institucion === '' ||
                mb_strlen($institucion, 'UTF-8') > 120 ||
                $grado === '' ||
                mb_strlen($grado, 'UTF-8') > 120
            ) {
                $errores[] =
                    'Institución y grado son obligatorios (máximo 120 caracteres).';
                continue;
            }

            $clave = mb_strtolower(
                $institucion . '|' . $grado,
                'UTF-8'
            ) . '|' . $anio;

            $item = [
                'anio' => $anio,
                'institucion' => $institucion,
                'grado' => $grado
            ];
        }

        if (isset($vistos[$clave])) {
            $errores[] = 'Hay un registro repetido en ' . $tipo . '.';
            continue;
        }

        $vistos[$clave] = true;
        $resultado[] = $item;
    }

    return $resultado;
}

function leerEstudiante($entrada, &$errores)
{
    $datos = [];

    $limites = [
        'nombres' => 50,
        'apellidos' => 50,
        'correo' => 30,
        'aficiones' => 100
    ];

    foreach ($limites as $campo => $limite) {
        $valor = $entrada[$campo] ?? '';
        $valor = is_string($valor) ? trim($valor) : '';

        if (
            $valor === '' ||
            mb_strlen($valor, 'UTF-8') > $limite
        ) {
            $errores[] =
                ucfirst($campo) .
                ' es obligatorio (máximo ' . $limite . ' caracteres).';
        }

        $datos[$campo] = $valor;
    }

    if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Correo electrónico no válido.';
    }

    return $datos;
}

/**
 * Reutiliza cursos existentes o crea cursos nuevos.
 */
function guardarCursos(PDO $pdo, $idAlumno, $cursos)
{
    $buscar = $pdo->prepare(
        'SELECT id_curso FROM cursos WHERE nombre = ? LIMIT 1'
    );

    $crear = $pdo->prepare(
        'INSERT INTO cursos(nombre) VALUES (?)'
    );

    $asociar = $pdo->prepare(
        'INSERT INTO alumno_cursos(id_alumno, anio, id_curso)
         VALUES (?, ?, ?)'
    );

    foreach ($cursos as $curso) {
        $buscar->execute([$curso['nombre']]);
        $idCurso = $buscar->fetchColumn();

        if ($idCurso === false) {
            $crear->execute([$curso['nombre']]);
            $idCurso = $pdo->lastInsertId();
        }

        $asociar->execute([
            $idAlumno,
            $curso['anio'],
            $idCurso
        ]);
    }
}

/**
 * Guarda los estudios relacionados con el alumno.
 */
function guardarEducacion(PDO $pdo, $idAlumno, $estudios)
{
    $insertar = $pdo->prepare(
        'INSERT INTO educacion(id_alumno, anio, institucion, grado)
         VALUES (?, ?, ?, ?)'
    );

    foreach ($estudios as $estudio) {
        $insertar->execute([
            $idAlumno,
            $estudio['anio'],
            $estudio['institucion'],
            $estudio['grado']
        ]);
    }
}

/**
 * Secciones compartidas para alta y edición.
 */
function seccionesAcademicas($cursos, $estudios, $catalogoCursos = [])
{
?>
<section class="academic-block academic-green">
    <div class="academic-heading">
        <span class="academic-number">02</span>
        <div>
            <h2>Cursos y certificaciones</h2>
            <p>
                Selecciona un curso del listado o escribe uno nuevo
                (opcional).
            </p>
        </div>
    </div>

    <datalist id="catalogo-cursos">
        <?php foreach ($catalogoCursos as $nombre): ?>
            <option value="<?= e($nombre) ?>"></option>
        <?php endforeach; ?>
    </datalist>

    <div id="lista-cursos" class="academic-items">
        <?php foreach ($cursos as $indice => $curso): ?>
            <div class="academic-row course-row">
                <div class="field">
                    <label for="curso-anio-<?= (int) $indice ?>">
                        Año
                    </label>

                    <input
                        id="curso-anio-<?= (int) $indice ?>"
                        type="number"
                        name="cursos[anio][]"
                        min="1900"
                        max="<?= (int) date('Y') + 5 ?>"
                        required
                        value="<?= e($curso['anio']) ?>"
                    >
                </div>

                <div class="field">
                    <label for="curso-nombre-<?= (int) $indice ?>">
                        Nombre del curso
                    </label>

                    <input
                        id="curso-nombre-<?= (int) $indice ?>"
                        type="text"
                        name="cursos[nombre][]"
                        list="catalogo-cursos"
                        autocomplete="off"
                        maxlength="50"
                        placeholder="Selecciona o escribe un curso"
                        required
                        value="<?= e($curso['nombre']) ?>"
                    >
                </div>

                <button
                    type="button"
                    class="btn btn-secondary remove-row"
                >
                    Quitar
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <button
        type="button"
        class="btn btn-secondary"
        data-add="curso"
    >
        + Agregar curso
    </button>
</section>

<section class="academic-block academic-yellow">
    <div class="academic-heading">
        <span class="academic-number">03</span>
        <div>
            <h2>Educación</h2>
            <p>
                Registra año, institución y grado académico (opcional).
            </p>
        </div>
    </div>

    <div id="lista-educacion" class="academic-items">
        <?php foreach ($estudios as $indice => $estudio): ?>
            <div class="academic-row education-row">
                <div class="field">
                    <label for="estudio-anio-<?= (int) $indice ?>">
                        Año
                    </label>

                    <input
                        id="estudio-anio-<?= (int) $indice ?>"
                        type="number"
                        name="educacion[anio][]"
                        min="1900"
                        max="<?= (int) date('Y') + 5 ?>"
                        required
                        value="<?= e($estudio['anio']) ?>"
                    >
                </div>

                <div class="field">
                    <label for="estudio-institucion-<?= (int) $indice ?>">
                        Institución
                    </label>

                    <input
                        id="estudio-institucion-<?= (int) $indice ?>"
                        type="text"
                        name="educacion[institucion][]"
                        maxlength="120"
                        required
                        value="<?= e($estudio['institucion']) ?>"
                    >
                </div>

                <div class="field">
                    <label for="estudio-grado-<?= (int) $indice ?>">
                        Grado o título
                    </label>

                    <input
                        id="estudio-grado-<?= (int) $indice ?>"
                        type="text"
                        name="educacion[grado][]"
                        maxlength="120"
                        required
                        value="<?= e($estudio['grado']) ?>"
                    >
                </div>

                <button
                    type="button"
                    class="btn btn-secondary remove-row"
                >
                    Quitar
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <button
        type="button"
        class="btn btn-secondary"
        data-add="educacion"
    >
        + Agregar estudio
    </button>
</section>

<script>
    // Conecta al catálogo las filas nuevas creadas por academico.js.
    document.getElementById('lista-cursos').addEventListener(
        'focusin',
        function (event) {
            if (
                event.target.matches(
                    'input[name="cursos[nombre][]"]'
                )
            ) {
                event.target.setAttribute('list', 'catalogo-cursos');
                event.target.setAttribute('autocomplete', 'off');
                event.target.setAttribute(
                    'placeholder',
                    'Selecciona o escribe un curso'
                );
            }
        }
    );
</script>

<script src="academico.js" defer></script>
<?php
}

/**
 * Convierte los campos enviados en arrays a filas asociativas.
 */
function filasPost($grupo, $campos)
{
    if (!is_array($grupo)) {
        return [null];
    }

    if ($grupo === []) {
        return [];
    }

    $filas = [];
    $cantidad = null;

    foreach ($campos as $campo) {
        if (
            !isset($grupo[$campo]) ||
            !is_array($grupo[$campo])
        ) {
            return [null];
        }

        $grupo[$campo] = array_values($grupo[$campo]);
        $total = count($grupo[$campo]);

        if ($cantidad !== null && $total !== $cantidad) {
            return [null];
        }

        $cantidad = $total;
    }

    if ($cantidad > 30) {
        // Provoca el rechazo por límite en limpiarFilas().
        return array_fill(0, 31, []);
    }

    for ($i = 0; $i < $cantidad; $i++) {
        $fila = [];

        foreach ($campos as $campo) {
            $fila[$campo] = $grupo[$campo][$i];
        }

        $filas[] = $fila;
    }

    return $filas;
}