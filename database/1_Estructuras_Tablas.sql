-- ============================================
-- BASE DE DATOS: MUNDIAL 2026
-- ARCHIVO 1: ESTRUCTURA DE TABLAS
-- ============================================

-- Crear base de datos
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
-- 2. TABLA: equipos
-- ============================================
CREATE TABLE equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    grupo CHAR(1) NOT NULL,
    escudo LONGBLOB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 3. TABLA: jugadores
-- ============================================
CREATE TABLE jugadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    numero INT NOT NULL,
    posicion VARCHAR(50) NOT NULL,
    equipo_id INT,
    foto LONGBLOB,
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
