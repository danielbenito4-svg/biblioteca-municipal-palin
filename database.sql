-- Biblioteca Municipal de Palín · Checkpoint 1
-- Importar una vez en una instalación nueva. No elimina bases ni tablas existentes.
SET NAMES utf8mb4;
SET time_zone = '-06:00';
CREATE DATABASE IF NOT EXISTS biblioteca_palin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biblioteca_palin;

CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    rol ENUM('bibliotecario','auxiliar') NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_usuario_activo CHECK (activo IN (0,1))
) ENGINE=InnoDB;

CREATE TABLE libros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(20) NOT NULL UNIQUE,
    titulo VARCHAR(150) NOT NULL,
    autor VARCHAR(150) NOT NULL,
    categoria VARCHAR(80) NOT NULL,
    anio SMALLINT UNSIGNED NOT NULL,
    ejemplares_totales INT NOT NULL,
    CONSTRAINT chk_ejemplares CHECK (ejemplares_totales >= 0),
    CONSTRAINT chk_anio CHECK (anio BETWEEN 1 AND 9999)
) ENGINE=InnoDB;

CREATE TABLE lectores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    carne VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(120) NOT NULL,
    telefono VARCHAR(20) NULL,
    tipo ENUM('estudiante','vecino') NOT NULL,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB;

CREATE TABLE prestamos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libro_id INT UNSIGNED NOT NULL,
    lector_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    fecha_prestamo DATE NOT NULL,
    fecha_limite DATE NOT NULL,
    fecha_devolucion DATE NULL,
    multa DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_prestamo_libro FOREIGN KEY (libro_id) REFERENCES libros(id) ON DELETE RESTRICT,
    CONSTRAINT fk_prestamo_lector FOREIGN KEY (lector_id) REFERENCES lectores(id) ON DELETE RESTRICT,
    CONSTRAINT fk_prestamo_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    CONSTRAINT chk_plazo CHECK (fecha_limite = DATE_ADD(fecha_prestamo, INTERVAL 7 DAY)),
    CONSTRAINT chk_devolucion CHECK (fecha_devolucion IS NULL OR fecha_devolucion >= fecha_prestamo),
    CONSTRAINT chk_multa CHECK (multa >= 0),
    INDEX idx_libro_activo (libro_id,fecha_devolucion),
    INDEX idx_lector_activo (lector_id,fecha_devolucion,fecha_limite)
) ENGINE=InnoDB;

-- Datos ficticios de demostración. Los ISBN son identificadores de prueba.
INSERT INTO usuarios (id,usuario,password_hash,nombre,rol,activo) VALUES
(1,'bibliotecario','$2y$10$xt4gC7F8zFUheJ0OF1hzN.w87dmAQIV/ivl26WBKeT.fZE.l4dmGq','Bibliotecario de prueba','bibliotecario',1),
(2,'auxiliar','$2y$10$ulxGCy1ZpyfpbRh7lepkjeMNKjNQ1TzJxmQ.LUVNs4ghc1vRzB.L2','Auxiliar de prueba','auxiliar',1);

INSERT INTO libros (id,isbn,titulo,autor,categoria,anio,ejemplares_totales) VALUES
(1,'DEMO-001','El principito','Antoine de Saint-Exupéry','Literatura',1943,3),
(2,'DEMO-002','El señor presidente','Miguel Ángel Asturias','Literatura guatemalteca',1946,2),
(3,'DEMO-003','Hombres de maíz','Miguel Ángel Asturias','Literatura guatemalteca',1949,1),
(4,'DEMO-004','Popol Vuh','Tradición maya k’iche’','Historia y cultura',1701,3),
(5,'DEMO-005','La mansión del pájaro serpiente','Virgilio Rodríguez Macal','Literatura guatemalteca',1939,2),
(6,'DEMO-006','Don Quijote de la Mancha','Miguel de Cervantes','Literatura',1605,2),
(7,'DEMO-007','La isla del tesoro','Robert Louis Stevenson','Aventura',1883,3),
(8,'DEMO-008','Alicia en el país de las maravillas','Lewis Carroll','Literatura',1865,2),
(9,'DEMO-009','El mundo de Sofía','Jostein Gaarder','Filosofía',1991,0),
(10,'DEMO-010','La Odisea','Homero','Literatura clásica',1900,2);

INSERT INTO lectores (id,carne,nombre,telefono,tipo,estado) VALUES
(1,'L-001','Ana López','5555-0101','estudiante','activo'),
(2,'L-002','Carlos Pérez','5555-0102','vecino','activo'),
(3,'L-003','Roberto Díaz','5555-0103','estudiante','activo'),
(4,'L-004','Lucía Gómez','5555-0104','estudiante','activo'),
(5,'L-005','María Santos',NULL,'vecino','activo'),
(6,'L-006','Diego Morales','5555-0106','estudiante','activo'),
(7,'L-007','Sofía Herrera','5555-0107','vecino','activo'),
(8,'L-008','Pedro Ramos',NULL,'vecino','activo'),
(9,'L-009','Elena Castillo','5555-0109','estudiante','inactivo'),
(10,'L-010','Luis Méndez','5555-0110','estudiante','activo');

-- Fechas relativas al día de importación: siempre hay casos en curso, vencidos y devueltos.
INSERT INTO prestamos (id,libro_id,lector_id,usuario_id,fecha_prestamo,fecha_limite,fecha_devolucion,multa) VALUES
(1,1,1,1,CURDATE()-INTERVAL 2 DAY,CURDATE()+INTERVAL 5 DAY,NULL,0),
(2,2,2,1,CURDATE()-INTERVAL 10 DAY,CURDATE()-INTERVAL 3 DAY,NULL,0),
(3,3,3,1,CURDATE()-INTERVAL 1 DAY,CURDATE()+INTERVAL 6 DAY,NULL,0),
(4,4,3,1,CURDATE()-INTERVAL 2 DAY,CURDATE()+INTERVAL 5 DAY,NULL,0),
(5,5,3,1,CURDATE()-INTERVAL 3 DAY,CURDATE()+INTERVAL 4 DAY,NULL,0),
(6,6,4,1,CURDATE()-INTERVAL 14 DAY,CURDATE()-INTERVAL 7 DAY,CURDATE()-INTERVAL 9 DAY,0),
(7,1,5,2,CURDATE()-INTERVAL 20 DAY,CURDATE()-INTERVAL 13 DAY,CURDATE()-INTERVAL 10 DAY,6),
(8,2,6,2,CURDATE()-INTERVAL 15 DAY,CURDATE()-INTERVAL 8 DAY,CURDATE()-INTERVAL 9 DAY,0),
(9,4,7,1,CURDATE()-INTERVAL 17 DAY,CURDATE()-INTERVAL 10 DAY,CURDATE()-INTERVAL 7 DAY,6),
(10,5,8,1,CURDATE()-INTERVAL 10 DAY,CURDATE()-INTERVAL 3 DAY,CURDATE()-INTERVAL 4 DAY,0),
(11,7,6,2,CURDATE()-INTERVAL 1 DAY,CURDATE()+INTERVAL 6 DAY,NULL,0),
(12,8,7,1,CURDATE()-INTERVAL 4 DAY,CURDATE()+INTERVAL 3 DAY,NULL,0);

