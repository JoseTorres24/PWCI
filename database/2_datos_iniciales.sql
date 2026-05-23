-- ============================================
-- BASE DE DATOS: MUNDIAL 2026
-- ARCHIVO 2: DATOS INICIALES (SEMILLA)
-- ============================================

USE mundial2026;

-- ============================================
-- 1. LIMPIAR TABLAS (orden correcto)
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

-- ============================================
-- 2. INSERTAR EQUIPOS
-- ============================================
INSERT INTO equipos (nombre, grupo) VALUES 
('México', 'A'),
('Argentina', 'B'),
('Brasil', 'C'),
('Francia', 'D');

-- ============================================
-- 3. INSERTAR JUGADORES
-- ============================================

-- México
SET @mexico_id = (SELECT id FROM equipos WHERE nombre = 'México');
INSERT INTO jugadores (nombre, numero, posicion, equipo_id, rareza) VALUES 
('Guillermo Ochoa', 1, 'POR', @mexico_id, 'sr'),
('Jesús Gallardo', 23, 'DEF', @mexico_id, 'sr'),
('Orbelín Pineda', 10, 'MED', @mexico_id, 'sr'),
('Santiago Giménez', 9, 'DEL', @mexico_id, 'ssr');

-- Argentina
SET @argentina_id = (SELECT id FROM equipos WHERE nombre = 'Argentina');
INSERT INTO jugadores (nombre, numero, posicion, equipo_id, rareza) VALUES 
('Emiliano Martínez', 23, 'POR', @argentina_id, 'sr'),
('Rodrigo De Paul', 19, 'DEF', @argentina_id, 'r'),
('Lionel Messi', 10, 'DEL', @argentina_id, 'ssr');

-- Brasil
SET @brasil_id = (SELECT id FROM equipos WHERE nombre = 'Brasil');
INSERT INTO jugadores (nombre, numero, posicion, equipo_id, rareza) VALUES 
('Alisson Becker', 1, 'POR', @brasil_id, 'sr'),
('Marquinhos', 4, 'DEF', @brasil_id, 'sr'),
('Vinícius Jr', 10, 'DEL', @brasil_id, 'ssr');

-- Francia
SET @francia_id = (SELECT id FROM equipos WHERE nombre = 'Francia');
INSERT INTO jugadores (nombre, numero, posicion, equipo_id, rareza) VALUES 
('Eduardo Camavinga', 16, 'POR', @francia_id, 'sr'),
('Kylian Mbappé', 10, 'DEL', @francia_id, 'ssr');

-- ============================================
-- 4. INSERTAR USUARIOS
-- ============================================
-- Contraseña: admin123
INSERT INTO usuarios (nombre, correo, password, rol) VALUES 
('Administrador', 'admin@mundial2026.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Carlos López', 'carlos@mundial2026.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coleccionista'),
('Ana García', 'ana@mundial2026.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coleccionista'),
('Luis Martínez', 'luis@mundial2026.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coleccionista'),
('Mía Fernández', 'mia@mundial2026.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coleccionista');

-- ============================================
-- 5. INSERTAR SOBRE
-- ============================================
INSERT INTO sobres (id, nombre, cantidad_cartas) VALUES (1, 'Sobre Oficial', 3);

-- ============================================
-- 6. INSERTAR PROBABILIDADES
-- ============================================
-- SSR: 5%
INSERT INTO sobre_jugadores (sobre_id, jugador_id, probabilidad)
SELECT 1, id, 5 FROM jugadores WHERE rareza = 'ssr';

-- SR: 25%
INSERT INTO sobre_jugadores (sobre_id, jugador_id, probabilidad)
SELECT 1, id, 25 FROM jugadores WHERE rareza = 'sr';

-- R: 70%
INSERT INTO sobre_jugadores (sobre_id, jugador_id, probabilidad)
SELECT 1, id, 70 FROM jugadores WHERE rareza = 'r';

-- ============================================
-- 7. INSERTAR COLECCIONES INICIALES
-- ============================================
INSERT INTO estampas_usuario (usuario_id, jugador_id, cantidad) VALUES
(2, 1, 2), (2, 2, 1), (2, 4, 1), (2, 5, 1), (2, 11, 1),
(3, 3, 1), (3, 6, 2), (3, 7, 1), (3, 9, 1),
(4, 10, 1), (4, 11, 2), (4, 12, 1),
(5, 1, 1), (5, 4, 1), (5, 8, 1);

-- ============================================
-- 8. INSERTAR OFERTAS
-- ============================================
INSERT INTO ofertas (usuario_id, jugador_ofrece, cantidad_ofrece, jugador_pide, cantidad_pide, estado) VALUES
(2, 1, 1, 7, 1, 'activa'),
(3, 6, 1, NULL, 0, 'activa'),
(4, 10, 1, 11, 1, 'activa');

INSERT INTO ofertas (usuario_id, aceptada_por, jugador_ofrece, cantidad_ofrece, jugador_pide, cantidad_pide, estado) VALUES
(3, 5, 3, 1, 8, 1, 'completada');

SELECT 'Equipos:' as Tabla, COUNT(*) FROM equipos;
SELECT 'Jugadores:' as Tabla, COUNT(*) FROM jugadores;
SELECT 'Usuarios:' as Tabla, COUNT(*) FROM usuarios;