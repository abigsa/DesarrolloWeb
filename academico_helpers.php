<?php
/** Funciones compartidas por los formularios de alta y edición.
 *  Se valida en servidor aunque el navegador tenga validaciones HTML.
 */
function limpiarFilas($filas, $tipo, &$errores) {
    if (!is_array($filas)) { $errores[] = 'Formato de '.$tipo.' inválido.'; return []; }
    if (count($filas) > 30) { $errores[] = 'Se permiten hasta 30 '.$tipo.'.'; return []; }
    $resultado = []; $vistos=[];
    foreach ($filas as $fila) {
        if (!is_array($fila)) { $errores[]='Fila inválida en '.$tipo.'.'; continue; }
        $anio = filter_var($fila['anio'] ?? null, FILTER_VALIDATE_INT);
        $nombre = trim((string)($fila['nombre'] ?? ''));
        $institucion = trim((string)($fila['institucion'] ?? ''));
        $grado = trim((string)($fila['grado'] ?? ''));
        // Filas totalmente vacías son opcionales.
        if (trim((string)($fila['anio'] ?? '')) === '' && $nombre === '' && $institucion === '' && $grado === '') continue;
        if ($anio === false || $anio < 1900 || $anio > (int)date('Y') + 5) { $errores[] = 'Año inválido en '.$tipo.' (1900 a '.((int)date('Y')+5).').'; continue; }
        if ($tipo === 'cursos') {
            if ($nombre === '' || mb_strlen($nombre,'UTF-8') > 50) { $errores[]='El nombre del curso debe tener entre 1 y 50 caracteres.'; continue; }
            $clave = mb_strtolower($nombre, 'UTF-8').'|'.$anio;
            $item=['anio'=>$anio,'nombre'=>$nombre];
        } else {
            if ($institucion === '' || mb_strlen($institucion,'UTF-8') > 120 || $grado === '' || mb_strlen($grado,'UTF-8') > 120) { $errores[]='Institución y grado son obligatorios (máximo 120 caracteres).'; continue; }
            $clave = mb_strtolower($institucion.'|'.$grado,'UTF-8').'|'.$anio;
            $item=['anio'=>$anio,'institucion'=>$institucion,'grado'=>$grado];
        }
        if (isset($vistos[$clave])) { $errores[]='Hay un registro repetido en '.$tipo.'.'; continue; }
        $vistos[$clave]=true; $resultado[]=$item;
    }
    return $resultado;
}
function leerEstudiante($entrada,&$errores) {
    $datos=[];
    foreach (['nombres'=>50,'apellidos'=>50,'correo'=>30,'aficiones'=>100] as $campo=>$limite) {
        $valor=$entrada[$campo] ?? '';
        $valor=is_string($valor) ? trim($valor) : '';
        if ($valor === '' || mb_strlen($valor,'UTF-8') > $limite) $errores[] = ucfirst($campo).' es obligatorio (máximo '.$limite.' caracteres).';
        $datos[$campo]=$valor;
    }
    if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) $errores[]='Correo electrónico no válido.';
    return $datos;
}
/** Guarda cursos existentes o nuevos para el estudiante ya registrado. */
function guardarCursos(PDO $pdo, $idAlumno, $cursos) {
    $buscar=$pdo->prepare('SELECT id_curso FROM cursos WHERE nombre = ? LIMIT 1');
    $crear=$pdo->prepare('INSERT INTO cursos(nombre) VALUES (?)');
    $asociar=$pdo->prepare('INSERT INTO alumno_cursos(id_alumno,anio,id_curso) VALUES (?,?,?)');
    foreach ($cursos as $curso) {
        $buscar->execute([$curso['nombre']]); $idCurso=$buscar->fetchColumn();
        if (!$idCurso) { $crear->execute([$curso['nombre']]); $idCurso=$pdo->lastInsertId(); }
        $asociar->execute([$idAlumno,$curso['anio'],$idCurso]);
    }
}
/** Guarda múltiples estudios relacionados mediante la clave id_alumno. */
function guardarEducacion(PDO $pdo, $idAlumno, $estudios) {
    $insertar=$pdo->prepare('INSERT INTO educacion(id_alumno,anio,institucion,grado) VALUES (?,?,?,?)');
    foreach ($estudios as $estudio) $insertar->execute([$idAlumno,$estudio['anio'],$estudio['institucion'],$estudio['grado']]);
}
/** Bloques reutilizables: mismo formulario y presentación para alta y edición. */
function seccionesAcademicas($cursos, $estudios) { ?>
<section class="academic-block academic-green">
  <div class="academic-heading"><span class="academic-number">02</span><div><h2>Cursos y certificaciones</h2><p>Puedes agregar varios cursos (opcional).</p></div></div>
  <div id="lista-cursos" class="academic-items">
  <?php foreach($cursos as $curso): ?><div class="academic-row course-row">
    <div class="field"><label>Año</label><input type="number" name="cursos[anio][]" min="1900" max="<?= (int)date('Y')+5 ?>" required value="<?= e($curso['anio']) ?>"></div>
    <div class="field"><label>Nombre del curso</label><input type="text" name="cursos[nombre][]" maxlength="50" required value="<?= e($curso['nombre']) ?>"></div>
    <button type="button" class="btn btn-secondary remove-row">Quitar</button>
  </div><?php endforeach; ?>
  </div>
  <button type="button" class="btn btn-secondary" data-add="curso">+ Agregar curso</button>
</section>
<section class="academic-block academic-yellow">
  <div class="academic-heading"><span class="academic-number">03</span><div><h2>Educación</h2><p>Registra año, institución y grado académico (opcional).</p></div></div>
  <div id="lista-educacion" class="academic-items">
  <?php foreach($estudios as $estudio): ?><div class="academic-row education-row">
    <div class="field"><label>Año</label><input type="number" name="educacion[anio][]" min="1900" max="<?= (int)date('Y')+5 ?>" required value="<?= e($estudio['anio']) ?>"></div>
    <div class="field"><label>Institución</label><input type="text" name="educacion[institucion][]" maxlength="120" required value="<?= e($estudio['institucion']) ?>"></div>
    <div class="field"><label>Grado o título</label><input type="text" name="educacion[grado][]" maxlength="120" required value="<?= e($estudio['grado']) ?>"></div>
    <button type="button" class="btn btn-secondary remove-row">Quitar</button>
  </div><?php endforeach; ?>
  </div>
  <button type="button" class="btn btn-secondary" data-add="educacion">+ Agregar estudio</button>
</section>
<script src="academico.js" defer></script>
<?php }
/** Convierte arrays enviados por inputs "campo[]" en filas asociativas. */
function filasPost($grupo,$campos) {
    if (!is_array($grupo)) return [];
    $filas=[]; $cantidad=0;
    foreach ($campos as $campo) { if (!isset($grupo[$campo]) || !is_array($grupo[$campo])) return []; $cantidad=max($cantidad,count($grupo[$campo])); }
    if ($cantidad>30) return array_fill(0,31,[]); // fuerza rechazo por límite
    for($i=0;$i<$cantidad;$i++) { $fila=[]; foreach($campos as $campo) $fila[$campo]=$grupo[$campo][$i] ?? ''; $filas[]=$fila; }
    return $filas;
}
