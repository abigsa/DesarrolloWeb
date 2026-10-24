<?php

require_once __DIR__ . '/db.php';
session_start();
require_once __DIR__ . '/partials/layout.php';

$alumnos = $pdo->query(
    'SELECT id_alumno, nombres, apellidos, correo, aficiones
     FROM alumno
     ORDER BY apellidos, nombres'
)->fetchAll(PDO::FETCH_ASSOC);

$cursos = $pdo->query(
    'SELECT ac.anio, c.nombre, a.nombres, a.apellidos
     FROM alumno_cursos ac
     INNER JOIN cursos c ON c.id_curso = ac.id_curso
     INNER JOIN alumno a ON a.id_alumno = ac.id_alumno
     ORDER BY ac.anio DESC, c.nombre ASC'
)->fetchAll(PDO::FETCH_ASSOC);

$educacion = $pdo->query(
    'SELECT e.id_alumno, e.anio, e.institucion, e.grado,
            a.nombres, a.apellidos
     FROM educacion e
     INNER JOIN alumno a ON a.id_alumno = e.id_alumno
     ORDER BY e.anio DESC, a.apellidos'
)->fetchAll(PDO::FETCH_ASSOC);

abrirPagina('Inicio', 'lista');
?>

<div class="student-banner">
    <div class="student-banner-icon" aria-hidden="true">🎓</div>
    <div>
        <p class="eyebrow">Sistema de gestión académica</p>
        <h1>Registro de estudiantes</h1>
        <p>
            Información personal, cursos y formación académica
            en un solo lugar.
        </p>
    </div>
</div>

<section class="school-section" aria-labelledby="integrantes-grupo">
    <div class="school-section-head">
        <span class="num" aria-hidden="true">🎓</span>
        <h2 id="integrantes-grupo">Integrantes del grupo</h2>
        <span class="helper">5 integrantes</span>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre completo</th>
                    <th>Carné</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>ANGEL EMANUEL GALEANO REYES</td>
                    <td>5190-23-12087</td>
                </tr>
                <tr>
                    <td>KEVIN AARON ENRÍQUEZ DE LEÓN</td>
                    <td>5190-23-247</td>
                </tr>
                <tr>
                    <td>JOSE FERNANDO LOPEZ REYES</td>
                    <td>5190-23-1799</td>
                </tr>
                <tr>
                    <td>JENNIFER AMANDA SANTOS COLOMA</td>
                    <td>5190-22-12996</td>
                </tr>
                <tr>
                    <td>LAURA MARIA ABIGAIL CALVILLO SALAZAR</td>
                    <td>5190-23-10356</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<?php mostrarAvisos(); ?>

<?php if (isset($_SESSION['id_usuario'])): ?>
    <div class="actions">
        <a class="btn" href="nuevo.php">+ Registrar estudiante</a>
    </div>
<?php endif; ?>

<section class="school-section" aria-labelledby="datos-estudiantes">
    <div class="school-section-head">
        <span class="num">01</span>
        <h2 id="datos-estudiantes">Datos de estudiantes</h2>
        <span class="helper"><?= count($alumnos) ?> registrados</span>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Correo electrónico</th>
                    <th>Aficiones</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alumnos as $alumno): ?>
                    <tr>
                        <td><?= e($alumno['nombres']) ?></td>
                        <td><?= e($alumno['apellidos']) ?></td>
                        <td><?= e($alumno['correo']) ?></td>
                        <td><?= e($alumno['aficiones']) ?></td>
                        <td>
                            <div class="table-actions">
                                <a href="ver.php?id_alumno=<?= (int)$alumno['id_alumno'] ?>">
                                    Ver
                                </a>

                                <?php if (isset($_SESSION['id_usuario'])): ?>
                                    <a href="edit.php?id_alumno=<?= (int)$alumno['id_alumno'] ?>">
                                        Editar
                                    </a>
                                    <a class="danger-link"
                                       href="delete.php?id_alumno=<?= (int)$alumno['id_alumno'] ?>">
                                        Eliminar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$alumnos): ?>
                    <tr>
                        <td colspan="5" class="empty-state">
                            Todavía no hay estudiantes registrados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="school-section" aria-labelledby="cursos-certificaciones">
    <div class="school-section-head green">
        <span class="num">02</span>
        <h2 id="cursos-certificaciones">Cursos y Certificaciones</h2>
        <span class="helper"><?= count($cursos) ?> inscripciones</span>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Año</th>
                    <th>Nombre</th>
                    <th>Estudiante</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cursos as $curso): ?>
                    <tr>
                        <td><?= e($curso['anio']) ?></td>
                        <td><?= e($curso['nombre']) ?></td>
                        <td>
                            <?= e($curso['nombres'] . ' ' . $curso['apellidos']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$cursos): ?>
                    <tr>
                        <td colspan="3" class="empty-state">
                            No hay cursos registrados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="school-section" aria-labelledby="educacion">
    <div class="school-section-head yellow">
        <span class="num">03</span>
        <h2 id="educacion">Educación</h2>
        <span class="helper"><?= count($educacion) ?> estudios</span>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Año</th>
                    <th>Institución</th>
                    <th>Grado</th>
                    <th>Estudiante</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($educacion as $estudio): ?>
                    <tr>
                        <td><?= e($estudio['anio']) ?></td>
                        <td><?= e($estudio['institucion']) ?></td>
                        <td><?= e($estudio['grado']) ?></td>
                        <td>
                            <a href="educacion.php?id_alumno=<?= (int)$estudio['id_alumno'] ?>">
                                <?= e($estudio['nombres'] . ' ' . $estudio['apellidos']) ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$educacion): ?>
                    <tr>
                        <td colspan="4" class="section-empty">
                            Todavía no se han registrado estudios.
                            Abre la ficha de un estudiante para agregar su educación.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php cerrarPagina(); ?>