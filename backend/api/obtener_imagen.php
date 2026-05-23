<?php
header('Content-Type: image/jpeg');
session_start();

require_once __DIR__ . '/../config/db.php';

$tipo = $_GET['tipo'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    // Imagen por defecto
    $defaultImage = __DIR__ . '/../../public/Jugadores/silueta.png';
    if (file_exists($defaultImage)) {
        readfile($defaultImage);
    } else {
        echo "Imagen no disponible";
    }
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    if ($tipo === 'jugador') {
        $stmt = $conn->prepare("SELECT foto FROM jugadores WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['foto'])) {
            echo $result['foto'];
        } else {
            $defaultImage = __DIR__ . '/../../public/Jugadores/silueta.png';
            if (file_exists($defaultImage)) {
                readfile($defaultImage);
            } else {
                echo "Imagen no disponible";
            }
        }
    } elseif ($tipo === 'equipo') {
        $stmt = $conn->prepare("SELECT escudo FROM equipos WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['escudo'])) {
            echo $result['escudo'];
        } else {
            $defaultImage = __DIR__ . '/../../public/ASSETS/escudos/default.png';
            if (file_exists($defaultImage)) {
                readfile($defaultImage);
            } else {
                echo "Imagen no disponible";
            }
        }
    } else {
        $defaultImage = __DIR__ . '/../../public/Jugadores/silueta.png';
        if (file_exists($defaultImage)) {
            readfile($defaultImage);
        } else {
            echo "Imagen no disponible";
        }
    }
    
} catch (PDOException $e) {
    $defaultImage = __DIR__ . '/../../public/Jugadores/silueta.png';
    if (file_exists($defaultImage)) {
        readfile($defaultImage);
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>