-- ============================================
-- BASE DE DATOS: MUNDIAL 2026
-- CON LONGBLOB PARA IMÁGENES (NULLABLE)
-- ============================================

DROP DATABASE IF EXISTS mundial2026;
CREATE DATABASE mundial2026 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE mundial2026;

-- ============================================
-- 1. TABLA: usuarios
-- ============================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'coleccionista') DEFAULT 'coleccionista',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 2. TABLA: equipos (escudo como LONGBLOB)
-- ============================================
CREATE TABLE equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    grupo CHAR(1) NOT NULL,
    escudo LONGBLOB,  -- Ahora es LONGBLOB, NULL por defecto
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 3. TABLA: jugadores (foto como LONGBLOB)
-- ============================================
CREATE TABLE jugadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    numero INT NOT NULL,
    posicion VARCHAR(50) NOT NULL,
    equipo_id INT,
    foto LONGBLOB,  -- LONGBLOB, NULL por defecto
    rareza ENUM('comun', 'r', 'sr', 'ssr') DEFAULT 'comun',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE CASCADE
);

-- ============================================
-- 4. TABLA: estampas_usuario
-- ============================================
CREATE TABLE estampas_usuario (
    usuario_id INT,
    jugador_id INT,
    cantidad INT DEFAULT 1,
    fecha_obtencion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, jugador_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (jugador_id) REFERENCES jugadores(id) ON DELETE CASCADE
);

-- ============================================
-- 5. TABLA: sobres
-- ============================================
CREATE TABLE sobres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    cantidad_cartas INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 6. TABLA: sobre_jugadores
-- ============================================
CREATE TABLE sobre_jugadores (
    sobre_id INT,
    jugador_id INT,
    probabilidad INT,
    PRIMARY KEY (sobre_id, jugador_id),
    FOREIGN KEY (sobre_id) REFERENCES sobres(id) ON DELETE CASCADE,
    FOREIGN KEY (jugador_id) REFERENCES jugadores(id) ON DELETE CASCADE
);

-- ============================================
-- 7. TABLA: ofertas
-- ============================================
CREATE TABLE ofertas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    aceptada_por INT NULL,
    jugador_ofrece INT NOT NULL,
    cantidad_ofrece INT DEFAULT 1,
    jugador_pide INT,
    cantidad_pide INT DEFAULT 1,
    estado ENUM('activa', 'completada', 'cancelada') DEFAULT 'activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aceptada_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (jugador_ofrece) REFERENCES jugadores(id) ON DELETE CASCADE,
    FOREIGN KEY (jugador_pide) REFERENCES jugadores(id) ON DELETE CASCADE
);

-- ============================================
-- 8. TABLA: historial
-- ============================================
CREATE TABLE historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    descripcion TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- ============================================
-- 9. ÍNDICES
-- ============================================
ALTER TABLE jugadores ADD INDEX idx_nombre (nombre);
ALTER TABLE jugadores ADD INDEX idx_equipo (equipo_id);
ALTER TABLE jugadores ADD INDEX idx_rareza (rareza);
ALTER TABLE equipos ADD INDEX idx_nombre_equipo (nombre);
ALTER TABLE estampas_usuario ADD INDEX idx_usuario (usuario_id);
ALTER TABLE ofertas ADD INDEX idx_estado (estado);
ALTER TABLE ofertas ADD INDEX idx_usuario_ofertas (usuario_id);

-- ============================================
-- 10. DATOS INICIALES (sin imágenes)
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE estampas_usuario;
TRUNCATE TABLE sobre_jugadores;
TRUNCATE TABLE ofertas;
TRUNCATE TABLE historial;
TRUNCATE TABLE jugadores;
TRUNCATE TABLE equipos;
TRUNCATE TABLE sobres;
TRUNCATE TABLE usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- Equipos (sin escudo aún)
INSERT INTO equipos (nombre, grupo) VALUES 
('México', 'A'),
('Argentina', 'B'),
('Brasil', 'C'),
('Francia', 'D');

-- Jugadores (sin foto aún)
SET @mexico_id = (SELECT id FROM equipos WHERE nombre = 'México');
INSERT INTO jugadores (nombre, numero, posicion, equipo_id, rareza) VALUES 
('Guillermo Ochoa', 1, 'POR', @mexico_id, 'sr'),
('Jesús Gallardo', 23, 'DEF', @mexico_id, 'sr'),
('Orbelín Pineda', 10, 'MED', @mexico_id, 'sr'),
('Santiago Giménez', 9, 'DEL', @mexico_id, 'ssr');

SET @argentina_id = (SELECT id FROM equipos WHERE nombre = 'Argentina');
INSERT INTO jugadores (nombre, numero, posicion, equipo_id, rareza) VALUES 
('Emiliano Martínez', 23, 'POR', @argentina_id, 'sr'),
('Rodrigo De Paul', 19, 'DEF', @argentina_id, 'r'),
('Lionel Messi', 10, 'DEL', @argentina_id, 'ssr');

SET @brasil_id = (SELECT id FROM equipos WHERE nombre = 'Brasil');
INSERT INTO jugadores (nombre, numero, posicion, equipo_id, rareza) VALUES 
('Alisson Becker', 1, 'POR', @brasil_id, 'sr'),
('Marquinhos', 4, 'DEF', @brasil_id, 'sr'),
('Vinícius Jr', 10, 'DEL', @brasil_id, 'ssr');

SET @francia_id = (SELECT id FROM equipos WHERE nombre = 'Francia');
INSERT INTO jugadores (nombre, numero, posicion, equipo_id, rareza) VALUES 
('Eduardo Camavinga', 16, 'POR', @francia_id, 'sr'),
('Kylian Mbappé', 10, 'DEL', @francia_id, 'ssr');

-- Usuarios
INSERT INTO usuarios (nombre, correo, password, rol) VALUES 
('Administrador', 'admin@mundial2026.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Carlos López', 'carlos@mundial2026.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coleccionista'),
('Ana García', 'ana@mundial2026.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coleccionista'),
('Luis Martínez', 'luis@mundial2026.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coleccionista'),
('Mía Fernández', 'mia@mundial2026.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coleccionista');

-- Sobre
INSERT INTO sobres (id, nombre, cantidad_cartas) VALUES (1, 'Sobre Oficial', 3);

-- Probabilidades
INSERT INTO sobre_jugadores (sobre_id, jugador_id, probabilidad)
SELECT 1, id, 5 FROM jugadores WHERE rareza = 'ssr';

INSERT INTO sobre_jugadores (sobre_id, jugador_id, probabilidad)
SELECT 1, id, 25 FROM jugadores WHERE rareza = 'sr';

INSERT INTO sobre_jugadores (sobre_id, jugador_id, probabilidad)
SELECT 1, id, 70 FROM jugadores WHERE rareza = 'r';

-- Colecciones iniciales
INSERT INTO estampas_usuario (usuario_id, jugador_id, cantidad) VALUES
(2, 1, 2), (2, 2, 1), (2, 4, 1), (2, 5, 1), (2, 11, 1),
(3, 3, 1), (3, 6, 2), (3, 7, 1), (3, 9, 1),
(4, 10, 1), (4, 11, 2), (4, 12, 1),
(5, 1, 1), (5, 4, 1), (5, 8, 1);

-- Ofertas
INSERT INTO ofertas (usuario_id, jugador_ofrece, cantidad_ofrece, jugador_pide, cantidad_pide, estado) VALUES
(2, 1, 1, 7, 1, 'activa'),
(3, 6, 1, NULL, 0, 'activa'),
(4, 10, 1, 11, 1, 'activa');

INSERT INTO ofertas (usuario_id, aceptada_por, jugador_ofrece, cantidad_ofrece, jugador_pide, cantidad_pide, estado) VALUES
(3, 5, 3, 1, 8, 1, 'completada');

-- ============================================
-- STORED PROCEDURES PARA LOGIN Y REGISTRO
-- ============================================
-- ============================================
-- SP_LOGIN: Verifica credenciales y devuelve datos del usuario
-- ============================================
DROP PROCEDURE IF EXISTS SP_LOGIN;

DELIMITER $$
CREATE PROCEDURE SP_LOGIN(
    IN p_correo VARCHAR(100),
    OUT p_id INT,
    OUT p_nombre VARCHAR(100),
    OUT p_rol VARCHAR(20),
    OUT p_password_hash VARCHAR(255),
    OUT p_success TINYINT
)
BEGIN
    DECLARE user_exists INT DEFAULT 0;
    
    -- Verificar si el usuario existe
    SELECT COUNT(*) INTO user_exists FROM usuarios WHERE correo = p_correo;
    
    IF user_exists > 0 THEN
        SELECT id, nombre, rol, password INTO p_id, p_nombre, p_rol, p_password_hash
        FROM usuarios WHERE correo = p_correo;
        SET p_success = 1;
    ELSE
        SET p_id = NULL;
        SET p_nombre = NULL;
        SET p_rol = NULL;
        SET p_password_hash = NULL;
        SET p_success = 0;
    END IF;
END$$
DELIMITER ;

-- ============================================
-- SP_REGISTRO: Registra un nuevo usuario
-- ============================================
DROP PROCEDURE IF EXISTS SP_REGISTRO;

DELIMITER $$
CREATE PROCEDURE SP_REGISTRO(
    IN p_nombre VARCHAR(100),
    IN p_correo VARCHAR(100),
    IN p_password_hash VARCHAR(255),
    OUT p_usuario_id INT,
    OUT p_success TINYINT,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE user_exists INT DEFAULT 0;
    
    -- Verificar si el correo ya existe
    SELECT COUNT(*) INTO user_exists FROM usuarios WHERE correo = p_correo;
    
    IF user_exists > 0 THEN
        SET p_usuario_id = NULL;
        SET p_success = 0;
        SET p_mensaje = 'El correo ya está registrado';
    ELSE
        INSERT INTO usuarios (nombre, correo, password, rol, created_at)
        VALUES (p_nombre, p_correo, p_password_hash, 'coleccionista', NOW());
        
        SET p_usuario_id = LAST_INSERT_ID();
        SET p_success = 1;
        SET p_mensaje = 'Usuario registrado exitosamente';
    END IF;
END$$
DELIMITER ;

-- ============================================
-- STORED PROCEDURES PARA MERCADO
-- ============================================

-- ============================================
-- SP_OBTENER_OFERTAS_ACTIVAS
-- Obtiene todas las ofertas activas (excluyendo las del usuario actual)
-- ============================================
DROP PROCEDURE IF EXISTS SP_OBTENER_OFERTAS_ACTIVAS;

DELIMITER $$
CREATE PROCEDURE SP_OBTENER_OFERTAS_ACTIVAS(
    IN p_usuario_id INT
)
BEGIN
    SELECT o.*, 
           u.nombre as usuario_nombre,
           jo.nombre as jugador_ofrece_nombre,
           jo.rareza as ofrece_rareza,
           jp.nombre as jugador_pide_nombre,
           CASE WHEN o.usuario_id = p_usuario_id THEN 1 ELSE 0 END as es_mia
    FROM ofertas o
    INNER JOIN usuarios u ON o.usuario_id = u.id
    INNER JOIN jugadores jo ON o.jugador_ofrece = jo.id
    LEFT JOIN jugadores jp ON o.jugador_pide = jp.id
    WHERE o.estado = 'activa'
    ORDER BY o.created_at DESC;
END$$
DELIMITER ;

-- ============================================
-- SP_OBTENER_MIS_JUGADORES_DISPONIBLES
-- Obtiene los jugadores que tiene el usuario (con cantidad >= 1)
-- ============================================
DROP PROCEDURE IF EXISTS SP_OBTENER_MIS_JUGADORES_DISPONIBLES;

DELIMITER $$
CREATE PROCEDURE SP_OBTENER_MIS_JUGADORES_DISPONIBLES(
    IN p_usuario_id INT
)
BEGIN
    SELECT j.id, j.nombre, j.rareza, eu.cantidad, j.foto
    FROM estampas_usuario eu
    INNER JOIN jugadores j ON eu.jugador_id = j.id
    WHERE eu.usuario_id = p_usuario_id AND eu.cantidad >= 1
    ORDER BY j.nombre;
END$$
DELIMITER ;

-- ============================================
-- SP_OBTENER_TODOS_JUGADORES
-- Obtiene todos los jugadores (para el selector de búsqueda)
-- ============================================
DROP PROCEDURE IF EXISTS SP_OBTENER_TODOS_JUGADORES;

DELIMITER $$
CREATE PROCEDURE SP_OBTENER_TODOS_JUGADORES()
BEGIN
    SELECT id, nombre, rareza FROM jugadores ORDER BY nombre;
END$$
DELIMITER ;

-- ============================================
-- Verificar que los SP se crearon
-- ============================================
SHOW PROCEDURE STATUS WHERE Db = 'mundial2026';
