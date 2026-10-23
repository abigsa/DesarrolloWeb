-- Ejecutar SOLO una vez dentro de la base de datos alumnos.
-- No borra ni modifica registros existentes.
CREATE DATABASE IF NOT EXISTS `alumnos` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `alumnos`;

CREATE TABLE IF NOT EXISTS `educacion` (
  `id_educacion` INT NOT NULL AUTO_INCREMENT,
  `id_alumno` INT NOT NULL,
  `anio` SMALLINT UNSIGNED NOT NULL,
  `institucion` VARCHAR(120) NOT NULL,
  `grado` VARCHAR(120) NOT NULL,
  PRIMARY KEY (`id_educacion`),
  KEY `idx_educacion_alumno` (`id_alumno`),
  CONSTRAINT `fk_educacion_alumno` FOREIGN KEY (`id_alumno`)
    REFERENCES `alumno` (`id_alumno`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
