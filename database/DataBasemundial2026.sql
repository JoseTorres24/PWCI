CREATE DATABASE mundial2026;
USE mundial2026;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin','coleccionista') DEFAULT 'coleccionista',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    grupo CHAR(1) NOT NULL,
    escudo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE jugadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    numero INT NOT NULL,
    posicion VARCHAR(50) NOT NULL,
    equipo_id INT,
    foto VARCHAR(255),
    rareza ENUM('comun','r','sr','ssr') DEFAULT 'comun',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (equipo_id) REFERENCES equipos(id)
        ON DELETE CASCADE
);

CREATE TABLE estampas_usuario (
    usuario_id INT,
    jugador_id INT,
    cantidad INT DEFAULT 1,

    PRIMARY KEY (usuario_id, jugador_id),

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,

    FOREIGN KEY (jugador_id) REFERENCES jugadores(id)
        ON DELETE CASCADE
);

CREATE TABLE sobres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    cantidad_cartas INT DEFAULT 5
);

CREATE TABLE sobre_jugadores (
    sobre_id INT,
    jugador_id INT,
    probabilidad INT, -- Ejemplo: 5, 10, 50

    PRIMARY KEY (sobre_id, jugador_id),

    FOREIGN KEY (sobre_id) REFERENCES sobres(id)
        ON DELETE CASCADE,

    FOREIGN KEY (jugador_id) REFERENCES jugadores(id)
        ON DELETE CASCADE
);

CREATE TABLE historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    descripcion TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ========== LIMPIAR TABLAS PRIMERO ==========
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE estampas_usuario;
TRUNCATE TABLE sobre_jugadores;
TRUNCATE TABLE jugadores;
TRUNCATE TABLE equipos;
TRUNCATE TABLE sobres;
SET FOREIGN_KEY_CHECKS = 1;

-- ========== INSERTAR EQUIPOS ==========
INSERT INTO equipos (nombre, grupo, escudo) VALUES 
('México', 'A', '/ASSETS/escudos/mexico.png'),
('Argentina', 'B', '/ASSETS/escudos/argentina.png'),
('Brasil', 'C', '/ASSETS/escudos/brasil.png'),
('Francia', 'D', '/ASSETS/escudos/francia.png');

-- ========== JUGADORES DE MEXICO ==========
SET @mexico_id = (SELECT id FROM equipos WHERE nombre = 'México');

INSERT INTO jugadores (nombre, numero, posicion, equipo_id, rareza, foto) VALUES 
('Guillermo Ochoa', 1, 'POR', @mexico_id, 'sr', '/Jugadores/guillermo-ochoa.png'),
('Jesus Gallardo', 23, 'DEF', @mexico_id, 'sr', '/Jugadores/Jesus-Gallardo.png'),
('Orbelín Pineda', 10, 'MED', @mexico_id, 'sr', '/Jugadores/orbelin-pineda.png'),
('Santiago Giménez', 9, 'DEL', @mexico_id, 'ssr', '/Jugadores/santiago-gimenez.png');

-- ========== JUGADORES DE ARGENTINA ==========
SET @argentina_id = (SELECT id FROM equipos WHERE nombre = 'Argentina');

INSERT INTO jugadores (nombre, numero, posicion, equipo_id, rareza, foto) VALUES 
('Emiliano Martínez', 23, 'POR', @argentina_id, 'sr', '/Jugadores/emiliano-martinez.png'),
('De-Paul', 19, 'DEF', @argentina_id, 'r', '/Jugadores/de-paul.png'),
('Lionel Messi', 10, 'DEL', @argentina_id, 'ssr', '/Jugadores/lionel-messi.png');

-- ========== JUGADORES DE BRASIL ==========
SET @brasil_id = (SELECT id FROM equipos WHERE nombre = 'Brasil');

INSERT INTO jugadores (nombre, numero, posicion, equipo_id, rareza, foto) VALUES 
('Alisson Becker', 1, 'POR', @brasil_id, 'sr', '/Jugadores/alisson-becker.png'),
('Marquinhos', 4, 'DEF', @brasil_id, 'sr', '/Jugadores/marquinhos.png'),
('Vinicius Jr', 10, 'DEL', @brasil_id, 'ssr', '/Jugadores/vini.png');

-- ========== JUGADORES DE FRANCIA ==========
SET @francia_id = (SELECT id FROM equipos WHERE nombre = 'Francia');

INSERT INTO jugadores (nombre, numero, posicion, equipo_id, rareza, foto) VALUES 
('Camavinga', 16, 'POR', @francia_id, 'sr', '/Jugadores/camavinga.png'),
('Kylian Mbappé', 10, 'DEL', @francia_id, 'ssr', '/Jugadores/mbappe.png');

-- ========== INSERTAR SOBRE ==========
INSERT INTO sobres (nombre, cantidad_cartas) VALUES ('Sobre Oficial', 3);

CREATE TABLE IF NOT EXISTS ofertas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    jugador_ofrece INT NOT NULL,
    cantidad_ofrece INT DEFAULT 1,
    jugador_pide INT,
    cantidad_pide INT DEFAULT 1,
    estado ENUM('activa', 'completada', 'cancelada') DEFAULT 'activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (jugador_ofrece) REFERENCES jugadores(id) ON DELETE CASCADE,
    FOREIGN KEY (jugador_pide) REFERENCES jugadores(id) ON DELETE CASCADE
);


SET @sobre_id = 1;

-- SSR: 5% (más raros)
INSERT INTO sobre_jugadores (sobre_id, jugador_id, probabilidad)
SELECT @sobre_id, id, 5 FROM jugadores WHERE rareza = 'ssr';

-- SR: 25%
INSERT INTO sobre_jugadores (sobre_id, jugador_id, probabilidad)
SELECT @sobre_id, id, 25 FROM jugadores WHERE rareza = 'sr';

-- R: 70%
INSERT INTO sobre_jugadores (sobre_id, jugador_id, probabilidad)
SELECT @sobre_id, id, 70 FROM jugadores WHERE rareza = 'r';

SELECT COUNT(*) as total_en_sobre FROM sobre_jugadores;
SELECT COUNT(*) as total_jugadores FROM jugadores;

ALTER TABLE ofertas ADD COLUMN aceptada_por INT NULL AFTER usuario_id;
ALTER TABLE ofertas ADD FOREIGN KEY (aceptada_por) REFERENCES usuarios(id);
-- 1. Verificar cuántas estampas tiene el usuario 4
SELECT COUNT(*) as total_estampas FROM estampas_usuario WHERE usuario_id = 4;

USE mundial2026;

SELECT j.id, j.nombre, j.numero, j.posicion, j.rareza, j.foto, 
       e.nombre as equipo_nombre, eu.cantidad, eu.fecha_obtencion
FROM estampas_usuario eu
JOIN jugadores j ON eu.jugador_id = j.id
JOIN equipos e ON j.equipo_id = e.id
WHERE eu.usuario_id = 4
ORDER BY e.nombre, j.numero;


select * from mundial2026.estampas_usuario;
ALTER TABLE estampas_usuario ADD COLUMN fecha_obtencion TIMESTAMP DEFAULT CURRENT_TIMESTAMP;