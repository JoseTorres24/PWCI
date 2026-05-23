-- ============================================
-- BASE DE DATOS: MUNDIAL 2026
-- ARCHIVO 3: STORED PROCEDURES
-- ============================================

USE mundial2026;

-- ============================================
-- SP_LOGIN: Verifica credenciales
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
    SELECT COUNT(*) INTO user_exists FROM usuarios WHERE correo = p_correo;
    
    IF user_exists > 0 THEN
        SELECT id, nombre, rol, password INTO p_id, p_nombre, p_rol, p_password_hash
        FROM usuarios WHERE correo = p_correo;
        SET p_success = 1;
    ELSE
        SET p_id = NULL; SET p_nombre = NULL; SET p_rol = NULL;
        SET p_password_hash = NULL; SET p_success = 0;
    END IF;
END$$
DELIMITER ;

-- ============================================
-- SP_REGISTRO: Registra nuevo usuario
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
    SELECT COUNT(*) INTO user_exists FROM usuarios WHERE correo = p_correo;
    
    IF user_exists > 0 THEN
        SET p_usuario_id = NULL; SET p_success = 0;
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
-- SP_OBTENER_OFERTAS_ACTIVAS
-- ============================================
DROP PROCEDURE IF EXISTS SP_OBTENER_OFERTAS_ACTIVAS;
DELIMITER $$
CREATE PROCEDURE SP_OBTENER_OFERTAS_ACTIVAS(IN p_usuario_id INT)
BEGIN
    SELECT o.*, u.nombre as usuario_nombre, jo.nombre as jugador_ofrece_nombre,
           jo.rareza as ofrece_rareza, jp.nombre as jugador_pide_nombre,
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
-- ============================================
DROP PROCEDURE IF EXISTS SP_OBTENER_MIS_JUGADORES_DISPONIBLES;
DELIMITER $$
CREATE PROCEDURE SP_OBTENER_MIS_JUGADORES_DISPONIBLES(IN p_usuario_id INT)
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
-- ============================================
DROP PROCEDURE IF EXISTS SP_OBTENER_PROGRESO_ALBUM;
DELIMITER $$
CREATE PROCEDURE SP_OBTENER_PROGRESO_ALBUM(IN p_usuario_id INT)
BEGIN
    SELECT e.id, e.nombre, e.grupo, e.escudo,
           COUNT(DISTINCT j.id) as total_jugadores,
           COUNT(CASE WHEN eu.usuario_id IS NOT NULL THEN 1 END) as obtenidas,
           ROUND(IF(COUNT(DISTINCT j.id) > 0, 
              (COUNT(CASE WHEN eu.usuario_id IS NOT NULL THEN 1 END) / COUNT(DISTINCT j.id)) * 100, 0), 2) as progreso
    FROM equipos e
    LEFT JOIN jugadores j ON j.equipo_id = e.id
    LEFT JOIN estampas_usuario eu ON j.id = eu.jugador_id AND eu.usuario_id = p_usuario_id
    GROUP BY e.id, e.nombre, e.grupo, e.escudo
    ORDER BY e.nombre;
END$$
DELIMITER ;

-- ============================================
-- SP_OBTENER_GRUPOS
-- ============================================
DROP PROCEDURE IF EXISTS SP_OBTENER_GRUPOS;
DELIMITER $$
CREATE PROCEDURE SP_OBTENER_GRUPOS()
BEGIN
    SELECT DISTINCT grupo FROM equipos ORDER BY grupo;
END$$
DELIMITER ;

-- ============================================
-- SP_OBTENER_TOTAL_ESTAMPAS_USUARIO
-- ============================================
DROP PROCEDURE IF EXISTS SP_OBTENER_TOTAL_ESTAMPAS_USUARIO;
DELIMITER $$
CREATE PROCEDURE SP_OBTENER_TOTAL_ESTAMPAS_USUARIO(IN p_usuario_id INT, OUT p_total INT)
BEGIN
    SELECT COALESCE(SUM(cantidad), 0) INTO p_total
    FROM estampas_usuario WHERE usuario_id = p_usuario_id;
END$$
DELIMITER ;

-- ============================================
-- SP_OBTENER_ESTAMPAS_ESPECIALES_USUARIO
-- ============================================
DROP PROCEDURE IF EXISTS SP_OBTENER_ESTAMPAS_ESPECIALES_USUARIO;
DELIMITER $$
CREATE PROCEDURE SP_OBTENER_ESTAMPAS_ESPECIALES_USUARIO(IN p_usuario_id INT, OUT p_total INT)
BEGIN
    SELECT COALESCE(SUM(eu.cantidad), 0) INTO p_total
    FROM estampas_usuario eu
    INNER JOIN jugadores j ON eu.jugador_id = j.id
    WHERE eu.usuario_id = p_usuario_id AND j.rareza IN ('ssr', 'sr');
END$$
DELIMITER ;

-- ============================================
-- SP_OBTENER_TOTAL_INTERCAMBIOS_USUARIO
-- ============================================
DROP PROCEDURE IF EXISTS SP_OBTENER_TOTAL_INTERCAMBIOS_USUARIO;
DELIMITER $$
CREATE PROCEDURE SP_OBTENER_TOTAL_INTERCAMBIOS_USUARIO(IN p_usuario_id INT, OUT p_total INT)
BEGIN
    DECLARE v_total INT DEFAULT 0;
    SELECT COUNT(*) INTO v_total FROM ofertas
    WHERE estado = 'completada' AND (usuario_id = p_usuario_id OR aceptada_por = p_usuario_id);
    IF v_total = 0 THEN
        SELECT COUNT(*) INTO v_total FROM ofertas
        WHERE estado = 'completada' AND usuario_id = p_usuario_id;
    END IF;
    SET p_total = v_total;
END$$
DELIMITER ;

-- ============================================
-- SP_GUARDAR_CARTAS_OBTENIDAS
-- ============================================
DROP PROCEDURE IF EXISTS SP_GUARDAR_CARTAS_OBTENIDAS;
DELIMITER $$
CREATE PROCEDURE SP_GUARDAR_CARTAS_OBTENIDAS(IN p_usuario_id INT, IN p_jugador_id INT, IN p_cantidad INT)
BEGIN
    INSERT INTO estampas_usuario (usuario_id, jugador_id, cantidad, fecha_obtencion)
    VALUES (p_usuario_id, p_jugador_id, p_cantidad, NOW())
    ON DUPLICATE KEY UPDATE cantidad = cantidad + p_cantidad, fecha_obtencion = NOW();
END$$
DELIMITER ;

-- ============================================
-- SP_ABRIR_SOBRE_COMPLETO
-- ============================================
DROP PROCEDURE IF EXISTS SP_ABRIR_SOBRE_COMPLETO;
DELIMITER $$
CREATE PROCEDURE SP_ABRIR_SOBRE_COMPLETO(
    IN p_usuario_id INT, IN p_sobre_id INT,
    OUT p_cartas_json JSON, OUT p_mensaje VARCHAR(255)
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
    BEGIN SET p_mensaje = 'Error al abrir el sobre'; SET p_cartas_json = NULL; ROLLBACK; END;
    
    START TRANSACTION;
    
    SELECT cantidad_cartas INTO v_cantidad_cartas FROM sobres WHERE id = p_sobre_id;
    
    DROP TEMPORARY TABLE IF EXISTS temp_cartas_abrir;
    CREATE TEMPORARY TABLE temp_cartas_abrir (id INT, nombre VARCHAR(100), rareza VARCHAR(10), equipo VARCHAR(100));
    
    WHILE v_contador < v_cantidad_cartas DO
        SELECT j.id, j.nombre, j.rareza, e.nombre
        INTO v_jugador_id, v_jugador_nombre, v_jugador_rareza, v_equipo_nombre
        FROM sobre_jugadores sj
        JOIN jugadores j ON sj.jugador_id = j.id
        JOIN equipos e ON j.equipo_id = e.id
        WHERE sj.sobre_id = p_sobre_id
        ORDER BY RAND() * (100 / sj.probabilidad) LIMIT 1;
        
        INSERT INTO temp_cartas_abrir VALUES (v_jugador_id, v_jugador_nombre, v_jugador_rareza, v_equipo_nombre);
        
        INSERT INTO estampas_usuario (usuario_id, jugador_id, cantidad, fecha_obtencion)
        VALUES (p_usuario_id, v_jugador_id, 1, NOW())
        ON DUPLICATE KEY UPDATE cantidad = cantidad + 1, fecha_obtencion = NOW();
        
        IF v_contador > 0 THEN SET v_temp_json = CONCAT(v_temp_json, ','); END IF;
        SET v_temp_json = CONCAT(v_temp_json, '{"id":', v_jugador_id, ',"nombre":"', v_jugador_nombre,
            '","rareza":"', v_jugador_rareza, '","equipo":"', v_equipo_nombre, '"}');
        SET v_contador = v_contador + 1;
    END WHILE;
    
    SET v_temp_json = CONCAT(v_temp_json, ']');
    SET p_cartas_json = CAST(v_temp_json AS JSON);
    SET p_mensaje = CONCAT('¡Has obtenido ', v_cantidad_cartas, ' nuevas estampas!');
    
    INSERT INTO historial (usuario_id, descripcion, fecha)
    VALUES (p_usuario_id, CONCAT('Abrió un sobre y obtuvo ', v_cantidad_cartas, ' estampas'), NOW());
    
    DROP TEMPORARY TABLE IF EXISTS temp_cartas_abrir;
    COMMIT;
END$$
DELIMITER ;

-- ============================================
-- SP_CREAR_EQUIPO_CON_IMAGEN
-- ============================================
DROP PROCEDURE IF EXISTS SP_CREAR_EQUIPO_CON_IMAGEN;
DELIMITER $$
CREATE PROCEDURE SP_CREAR_EQUIPO_CON_IMAGEN(
    IN p_nombre VARCHAR(100), IN p_grupo CHAR(1), IN p_escudo LONGBLOB,
    OUT p_id INT, OUT p_success TINYINT, OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN SET p_success = 0; SET p_mensaje = 'Error al crear el equipo'; ROLLBACK; END;
    START TRANSACTION;
    INSERT INTO equipos (nombre, grupo, escudo, created_at) VALUES (p_nombre, p_grupo, p_escudo, NOW());
    SET p_id = LAST_INSERT_ID(); SET p_success = 1; SET p_mensaje = 'Equipo creado exitosamente';
    COMMIT;
END$$
DELIMITER ;

-- ============================================
-- SP_CREAR_JUGADOR_CON_IMAGEN
-- ============================================
DROP PROCEDURE IF EXISTS SP_CREAR_JUGADOR_CON_IMAGEN;
DELIMITER $$
CREATE PROCEDURE SP_CREAR_JUGADOR_CON_IMAGEN(
    IN p_nombre VARCHAR(100), IN p_numero INT, IN p_posicion VARCHAR(50),
    IN p_equipo_id INT, IN p_rareza VARCHAR(10), IN p_foto LONGBLOB,
    OUT p_id INT, OUT p_success TINYINT, OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE v_probabilidad INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN SET p_success = 0; SET p_mensaje = 'Error al crear el jugador'; ROLLBACK; END;
    START TRANSACTION;
    CASE p_rareza WHEN 'ssr' THEN SET v_probabilidad = 5; WHEN 'sr' THEN SET v_probabilidad = 25;
        WHEN 'r' THEN SET v_probabilidad = 70; ELSE SET v_probabilidad = 70;
    END CASE;
    INSERT INTO jugadores (nombre, numero, posicion, equipo_id, rareza, foto, created_at)
    VALUES (p_nombre, p_numero, p_posicion, p_equipo_id, p_rareza, p_foto, NOW());
    SET p_id = LAST_INSERT_ID();
    INSERT INTO sobre_jugadores (sobre_id, jugador_id, probabilidad) VALUES (1, p_id, v_probabilidad);
    SET p_success = 1; SET p_mensaje = 'Jugador creado exitosamente';
    COMMIT;
END$$
DELIMITER ;

-- ============================================
-- SP_ACTUALIZAR_EQUIPO_CON_IMAGEN
-- ============================================
DROP PROCEDURE IF EXISTS SP_ACTUALIZAR_EQUIPO_CON_IMAGEN;
DELIMITER $$
CREATE PROCEDURE SP_ACTUALIZAR_EQUIPO_CON_IMAGEN(
    IN p_id INT, IN p_nombre VARCHAR(100), IN p_grupo CHAR(1),
    IN p_escudo LONGBLOB, IN p_actualizar_imagen TINYINT
)
BEGIN
    IF p_actualizar_imagen = 1 THEN
        UPDATE equipos SET nombre = p_nombre, grupo = p_grupo, escudo = p_escudo WHERE id = p_id;
    ELSE
        UPDATE equipos SET nombre = p_nombre, grupo = p_grupo WHERE id = p_id;
    END IF;
END$$
DELIMITER ;

-- ============================================
-- SP_ACTUALIZAR_JUGADOR_CON_IMAGEN
-- ============================================
DROP PROCEDURE IF EXISTS SP_ACTUALIZAR_JUGADOR_CON_IMAGEN;
DELIMITER $$
CREATE PROCEDURE SP_ACTUALIZAR_JUGADOR_CON_IMAGEN(
    IN p_id INT, IN p_nombre VARCHAR(100), IN p_numero INT, IN p_posicion VARCHAR(50),
    IN p_equipo_id INT, IN p_rareza VARCHAR(10), IN p_foto LONGBLOB, IN p_actualizar_imagen TINYINT
)
BEGIN
    IF p_actualizar_imagen = 1 THEN
        UPDATE jugadores SET nombre = p_nombre, numero = p_numero, posicion = p_posicion,
            equipo_id = p_equipo_id, rareza = p_rareza, foto = p_foto WHERE id = p_id;
    ELSE
        UPDATE jugadores SET nombre = p_nombre, numero = p_numero, posicion = p_posicion,
            equipo_id = p_equipo_id, rareza = p_rareza WHERE id = p_id;
    END IF;
END$$
DELIMITER ;

-- ============================================
-- SP_ELIMINAR_EQUIPO_SEGURO
-- ============================================
DROP PROCEDURE IF EXISTS SP_ELIMINAR_EQUIPO_SEGURO;
DELIMITER $$
CREATE PROCEDURE SP_ELIMINAR_EQUIPO_SEGURO(IN p_id INT, OUT p_success TINYINT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE v_total_jugadores INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN SET p_success = 0; SET p_mensaje = 'Error al eliminar el equipo'; ROLLBACK; END;
    START TRANSACTION;
    SELECT COUNT(*) INTO v_total_jugadores FROM jugadores WHERE equipo_id = p_id;
    IF v_total_jugadores > 0 THEN
        SET p_success = 0; SET p_mensaje = CONCAT('No se puede eliminar el equipo porque tiene ', v_total_jugadores, ' jugadores asociados');
        ROLLBACK;
    ELSE
        DELETE FROM equipos WHERE id = p_id;
        SET p_success = 1; SET p_mensaje = 'Equipo eliminado exitosamente';
        COMMIT;
    END IF;
END$$
DELIMITER ;

-- ============================================
-- SP_ELIMINAR_JUGADOR_SEGURO
-- ============================================
DROP PROCEDURE IF EXISTS SP_ELIMINAR_JUGADOR_SEGURO;
DELIMITER $$
CREATE PROCEDURE SP_ELIMINAR_JUGADOR_SEGURO(IN p_id INT, OUT p_success TINYINT, OUT p_mensaje VARCHAR(255))
BEGIN
    DECLARE v_total_colecciones INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN SET p_success = 0; SET p_mensaje = 'Error al eliminar el jugador'; ROLLBACK; END;
    START TRANSACTION;
    SELECT COUNT(*) INTO v_total_colecciones FROM estampas_usuario WHERE jugador_id = p_id;
    IF v_total_colecciones > 0 THEN
        SET p_success = 0; SET p_mensaje = CONCAT('No se puede eliminar el jugador porque está en ', v_total_colecciones, ' colecciones');
        ROLLBACK;
    ELSE
        DELETE FROM sobre_jugadores WHERE jugador_id = p_id;
        DELETE FROM jugadores WHERE id = p_id;
        SET p_success = 1; SET p_mensaje = 'Jugador eliminado exitosamente';
        COMMIT;
    END IF;
END$$
DELIMITER ;

-- ============================================
-- Verificar SPs creados
-- ============================================
SHOW PROCEDURE STATUS WHERE Db = 'mundial2026';
