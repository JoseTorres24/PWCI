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
-- SP_OBTENER_PROGRESO_ALBUM
-- Obtiene el progreso de colección del usuario por equipo
-- ============================================

DROP PROCEDURE IF EXISTS SP_OBTENER_PROGRESO_ALBUM;

DELIMITER $$
CREATE PROCEDURE SP_OBTENER_PROGRESO_ALBUM(
    IN p_usuario_id INT
)
BEGIN
    SELECT 
        e.id,
        e.nombre,
        e.grupo,
        e.escudo,
        COUNT(DISTINCT j.id) as total_jugadores,
        COUNT(CASE WHEN eu.usuario_id IS NOT NULL THEN 1 END) as obtenidas,
        ROUND(
            IF(COUNT(DISTINCT j.id) > 0, 
               (COUNT(CASE WHEN eu.usuario_id IS NOT NULL THEN 1 END) / COUNT(DISTINCT j.id)) * 100, 
               0
            ), 2
        ) as progreso
    FROM equipos e
    LEFT JOIN jugadores j ON j.equipo_id = e.id
    LEFT JOIN estampas_usuario eu ON j.id = eu.jugador_id AND eu.usuario_id = p_usuario_id
    GROUP BY e.id, e.nombre, e.grupo, e.escudo
    ORDER BY e.nombre;
END$$
DELIMITER ;

-- ============================================
-- SP_OBTENER_GRUPOS
-- Obtiene los grupos únicos para los filtros
-- ============================================
DROP PROCEDURE IF EXISTS SP_OBTENER_GRUPOS;

DELIMITER $$
CREATE PROCEDURE SP_OBTENER_GRUPOS()
BEGIN
    SELECT DISTINCT grupo FROM equipos ORDER BY grupo;
END$$
DELIMITER ;


-- ============================================
-- STORED PROCEDURES PARA PERFIL DE USUARIO
-- ============================================

-- ============================================
-- SP_OBTENER_TOTAL_ESTAMPAS_USUARIO
-- Obtiene la suma total de estampas del usuario (incluyendo repetidas)
-- ============================================
DROP PROCEDURE IF EXISTS SP_OBTENER_TOTAL_ESTAMPAS_USUARIO;

DELIMITER $$
CREATE PROCEDURE SP_OBTENER_TOTAL_ESTAMPAS_USUARIO(
    IN p_usuario_id INT,
    OUT p_total INT
)
BEGIN
    SELECT COALESCE(SUM(cantidad), 0) INTO p_total
    FROM estampas_usuario
    WHERE usuario_id = p_usuario_id;
END$$
DELIMITER ;

-- ============================================
-- SP_OBTENER_ESTAMPAS_ESPECIALES_USUARIO
-- Obtiene la suma de estampas SSR y SR del usuario
-- ============================================
DROP PROCEDURE IF EXISTS SP_OBTENER_ESTAMPAS_ESPECIALES_USUARIO;

DELIMITER $$
CREATE PROCEDURE SP_OBTENER_ESTAMPAS_ESPECIALES_USUARIO(
    IN p_usuario_id INT,
    OUT p_total INT
)
BEGIN
    SELECT COALESCE(SUM(eu.cantidad), 0) INTO p_total
    FROM estampas_usuario eu
    INNER JOIN jugadores j ON eu.jugador_id = j.id
    WHERE eu.usuario_id = p_usuario_id AND j.rareza IN ('ssr', 'sr');
END$$
DELIMITER ;

-- ============================================
-- SP_OBTENER_TOTAL_INTERCAMBIOS_USUARIO
-- Obtiene la cantidad de intercambios completados del usuario
-- ============================================
DROP PROCEDURE IF EXISTS SP_OBTENER_TOTAL_INTERCAMBIOS_USUARIO;

DELIMITER $$
CREATE PROCEDURE SP_OBTENER_TOTAL_INTERCAMBIOS_USUARIO(
    IN p_usuario_id INT,
    OUT p_total INT
)
BEGIN
    DECLARE v_total INT DEFAULT 0;
    
    -- Primero intentar con aceptada_por
    SELECT COUNT(*) INTO v_total
    FROM ofertas
    WHERE estado = 'completada' 
    AND (usuario_id = p_usuario_id OR aceptada_por = p_usuario_id);
    
    -- Si no hay resultados, intentar solo con usuario_id
    IF v_total = 0 THEN
        SELECT COUNT(*) INTO v_total
        FROM ofertas
        WHERE estado = 'completada' AND usuario_id = p_usuario_id;
    END IF;
    
    SET p_total = v_total;
END$$
DELIMITER ;

-- ============================================
-- SP_OBTENER_DATOS_PERFIL
-- Obtiene todos los datos del perfil en una sola llamada
-- ============================================
DROP PROCEDURE IF EXISTS SP_OBTENER_DATOS_PERFIL;

DELIMITER $$
CREATE PROCEDURE SP_OBTENER_DATOS_PERFIL(
    IN p_usuario_id INT,
    OUT p_nombre VARCHAR(100),
    OUT p_correo VARCHAR(100),
    OUT p_rol VARCHAR(20),
    OUT p_fecha_registro VARCHAR(20),
    OUT p_total_estampas INT,
    OUT p_estampas_especiales INT,
    OUT p_intercambios INT
)
BEGIN
    -- Datos del usuario
    SELECT nombre, correo, rol, DATE_FORMAT(created_at, '%d/%m/%Y') 
    INTO p_nombre, p_correo, p_rol, p_fecha_registro
    FROM usuarios WHERE id = p_usuario_id;
    
    -- Total de estampas
    SELECT COALESCE(SUM(cantidad), 0) INTO p_total_estampas
    FROM estampas_usuario WHERE usuario_id = p_usuario_id;
    
    -- Estampas especiales (SSR + SR)
    SELECT COALESCE(SUM(eu.cantidad), 0) INTO p_estampas_especiales
    FROM estampas_usuario eu
    INNER JOIN jugadores j ON eu.jugador_id = j.id
    WHERE eu.usuario_id = p_usuario_id AND j.rareza IN ('ssr', 'sr');
    
    -- Intercambios completados
    SELECT COUNT(*) INTO p_intercambios
    FROM ofertas
    WHERE estado = 'completada' 
    AND (usuario_id = p_usuario_id OR aceptada_por = p_usuario_id);
    
    -- Si es 0, intentar solo con usuario_id
    IF p_intercambios = 0 THEN
        SELECT COUNT(*) INTO p_intercambios
        FROM ofertas
        WHERE estado = 'completada' AND usuario_id = p_usuario_id;
    END IF;
END$$
DELIMITER ;
-- ============================================
-- SP_GUARDAR_CARTAS_OBTENIDAS
-- Guarda o actualiza las estampas obtenidas por el usuario
-- ============================================
USE mundial2026;

DROP PROCEDURE IF EXISTS SP_GUARDAR_CARTAS_OBTENIDAS;

DELIMITER $$
CREATE PROCEDURE SP_GUARDAR_CARTAS_OBTENIDAS(
    IN p_usuario_id INT,
    IN p_jugador_id INT,
    IN p_cantidad INT
)
BEGIN
    INSERT INTO estampas_usuario (usuario_id, jugador_id, cantidad, fecha_obtencion)
    VALUES (p_usuario_id, p_jugador_id, p_cantidad, NOW())
    ON DUPLICATE KEY UPDATE 
        cantidad = cantidad + p_cantidad,
        fecha_obtencion = NOW();
END$$
DELIMITER ;

-- ============================================
-- SP_ABRIR_SOBRE_COMPLETO
-- Proceso completo de abrir un sobre (selección + guardado)
-- ============================================
DROP PROCEDURE IF EXISTS SP_ABRIR_SOBRE_COMPLETO;

DELIMITER $$
CREATE PROCEDURE SP_ABRIR_SOBRE_COMPLETO(
    IN p_usuario_id INT,
    IN p_sobre_id INT,
    OUT p_cartas_json JSON,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE v_cantidad_cartas INT;
    DECLARE v_contador INT DEFAULT 0;
    DECLARE v_jugador_id INT;
    DECLARE v_jugador_nombre VARCHAR(100);
    DECLARE v_jugador_rareza VARCHAR(10);
    DECLARE v_equipo_nombre VARCHAR(100);
    DECLARE v_temp_json TEXT DEFAULT '[';
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_mensaje = 'Error al abrir el sobre';
        SET p_cartas_json = NULL;
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    -- Obtener cantidad de cartas del sobre
    SELECT cantidad_cartas INTO v_cantidad_cartas
    FROM sobres WHERE id = p_sobre_id;
    
    -- Crear tabla temporal
    DROP TEMPORARY TABLE IF EXISTS temp_cartas_abrir;
    CREATE TEMPORARY TABLE temp_cartas_abrir (
        id INT,
        nombre VARCHAR(100),
        rareza VARCHAR(10),
        equipo VARCHAR(100)
    );
    
    -- Seleccionar cartas según probabilidades
    WHILE v_contador < v_cantidad_cartas DO
        -- Seleccionar un jugador aleatorio basado en probabilidades
        SELECT j.id, j.nombre, j.rareza, e.nombre
        INTO v_jugador_id, v_jugador_nombre, v_jugador_rareza, v_equipo_nombre
        FROM sobre_jugadores sj
        JOIN jugadores j ON sj.jugador_id = j.id
        JOIN equipos e ON j.equipo_id = e.id
        WHERE sj.sobre_id = p_sobre_id
        ORDER BY RAND() * (100 / sj.probabilidad)
        LIMIT 1;
        
        -- Insertar en tabla temporal
        INSERT INTO temp_cartas_abrir VALUES (v_jugador_id, v_jugador_nombre, v_jugador_rareza, v_equipo_nombre);
        
        -- Guardar en la colección del usuario
        INSERT INTO estampas_usuario (usuario_id, jugador_id, cantidad, fecha_obtencion)
        VALUES (p_usuario_id, v_jugador_id, 1, NOW())
        ON DUPLICATE KEY UPDATE 
            cantidad = cantidad + 1,
            fecha_obtencion = NOW();
        
        -- Construir JSON
        IF v_contador > 0 THEN
            SET v_temp_json = CONCAT(v_temp_json, ',');
        END IF;
        
        SET v_temp_json = CONCAT(v_temp_json, 
            '{"id":', v_jugador_id, 
            ',"nombre":"', v_jugador_nombre, 
            '","rareza":"', v_jugador_rareza, 
            '","equipo":"', v_equipo_nombre, '"}');
        
        SET v_contador = v_contador + 1;
    END WHILE;
    
    SET v_temp_json = CONCAT(v_temp_json, ']');
    SET p_cartas_json = CAST(v_temp_json AS JSON);
    SET p_mensaje = CONCAT('¡Has obtenido ', v_cantidad_cartas, ' nuevas estampas!');
    
    -- Registrar en historial
    INSERT INTO historial (usuario_id, descripcion, fecha)
    VALUES (p_usuario_id, CONCAT('Abrió un sobre y obtuvo ', v_cantidad_cartas, ' estampas'), NOW());
    
    DROP TEMPORARY TABLE IF EXISTS temp_cartas_abrir;
    
    COMMIT;
END$$
DELIMITER ;

-- ============================================
-- SP_ACTUALIZAR_EQUIPO_CON_IMAGEN
-- Actualiza equipo con opción de cambiar imagen
-- ============================================

DROP PROCEDURE IF EXISTS SP_ACTUALIZAR_EQUIPO_CON_IMAGEN;

DELIMITER $$
CREATE PROCEDURE SP_ACTUALIZAR_EQUIPO_CON_IMAGEN(
    IN p_id INT,
    IN p_nombre VARCHAR(100),
    IN p_grupo CHAR(1),
    IN p_escudo LONGBLOB,
    IN p_actualizar_imagen TINYINT
)
BEGIN
    IF p_actualizar_imagen = 1 THEN
        UPDATE equipos 
        SET nombre = p_nombre, grupo = p_grupo, escudo = p_escudo
        WHERE id = p_id;
    ELSE
        UPDATE equipos 
        SET nombre = p_nombre, grupo = p_grupo
        WHERE id = p_id;
    END IF;
END$$
DELIMITER ;

-- ============================================
-- SP_ACTUALIZAR_JUGADOR_CON_IMAGEN
-- Actualiza jugador con opción de cambiar imagen
-- ============================================
DROP PROCEDURE IF EXISTS SP_ACTUALIZAR_JUGADOR_CON_IMAGEN;

DELIMITER $$
CREATE PROCEDURE SP_ACTUALIZAR_JUGADOR_CON_IMAGEN(
    IN p_id INT,
    IN p_nombre VARCHAR(100),
    IN p_numero INT,
    IN p_posicion VARCHAR(50),
    IN p_equipo_id INT,
    IN p_rareza VARCHAR(10),
    IN p_foto LONGBLOB,
    IN p_actualizar_imagen TINYINT
)
BEGIN
    IF p_actualizar_imagen = 1 THEN
        UPDATE jugadores 
        SET nombre = p_nombre, numero = p_numero, posicion = p_posicion, 
            equipo_id = p_equipo_id, rareza = p_rareza, foto = p_foto
        WHERE id = p_id;
    ELSE
        UPDATE jugadores 
        SET nombre = p_nombre, numero = p_numero, posicion = p_posicion, 
            equipo_id = p_equipo_id, rareza = p_rareza
        WHERE id = p_id;
    END IF;
END$$
DELIMITER ;


-- ============================================
-- SP_CREAR_EQUIPO_CON_IMAGEN
-- Crea un nuevo equipo con opción de imagen
-- ============================================

DROP PROCEDURE IF EXISTS SP_CREAR_EQUIPO_CON_IMAGEN;

DELIMITER $$
CREATE PROCEDURE SP_CREAR_EQUIPO_CON_IMAGEN(
    IN p_nombre VARCHAR(100),
    IN p_grupo CHAR(1),
    IN p_escudo LONGBLOB,
    OUT p_id INT,
    OUT p_success TINYINT,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_success = 0;
        SET p_mensaje = 'Error al crear el equipo';
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    INSERT INTO equipos (nombre, grupo, escudo, created_at)
    VALUES (p_nombre, p_grupo, p_escudo, NOW());
    
    SET p_id = LAST_INSERT_ID();
    SET p_success = 1;
    SET p_mensaje = 'Equipo creado exitosamente';
    
    COMMIT;
END$$
DELIMITER ;

-- ============================================
-- SP_CREAR_JUGADOR_CON_IMAGEN
-- Crea un nuevo jugador con opción de imagen
-- ============================================
DROP PROCEDURE IF EXISTS SP_CREAR_JUGADOR_CON_IMAGEN;

DELIMITER $$
CREATE PROCEDURE SP_CREAR_JUGADOR_CON_IMAGEN(
    IN p_nombre VARCHAR(100),
    IN p_numero INT,
    IN p_posicion VARCHAR(50),
    IN p_equipo_id INT,
    IN p_rareza VARCHAR(10),
    IN p_foto LONGBLOB,
    OUT p_id INT,
    OUT p_success TINYINT,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE v_probabilidad INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_success = 0;
        SET p_mensaje = 'Error al crear el jugador';
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    -- Determinar probabilidad según rareza
    CASE p_rareza
        WHEN 'ssr' THEN SET v_probabilidad = 5;
        WHEN 'sr' THEN SET v_probabilidad = 25;
        WHEN 'r' THEN SET v_probabilidad = 70;
        ELSE SET v_probabilidad = 70;
    END CASE;
    
    -- Insertar jugador
    INSERT INTO jugadores (nombre, numero, posicion, equipo_id, rareza, foto, created_at)
    VALUES (p_nombre, p_numero, p_posicion, p_equipo_id, p_rareza, p_foto, NOW());
    
    SET p_id = LAST_INSERT_ID();
    
    -- Insertar en sobre_jugadores (sobre por defecto = 1)
    INSERT INTO sobre_jugadores (sobre_id, jugador_id, probabilidad)
    VALUES (1, p_id, v_probabilidad);
    
    SET p_success = 1;
    SET p_mensaje = 'Jugador creado exitosamente';
    
    COMMIT;
END$$
DELIMITER ;


-- ============================================
-- Verificar que los SP se crearon
-- ============================================
SHOW PROCEDURE STATUS WHERE Db = 'mundial2026';
