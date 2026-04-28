<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$db = new Database();
$conn = $db->connect();

$usuario_id = $_SESSION['usuario']['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$oferta_id = $_POST['oferta_id'] ?? 0;

if (!$oferta_id) {
    echo json_encode(['error' => 'ID de oferta no válido']);
    exit;
}

try {
    $conn->beginTransaction();
    
    // Obtener detalles de la oferta
    $stmt = $conn->prepare("
        SELECT o.*, u.nombre as usuario_nombre
        FROM ofertas o
        JOIN usuarios u ON o.usuario_id = u.id
        WHERE o.id = ? AND o.estado = 'activa'
    ");
    $stmt->execute([$oferta_id]);
    $oferta = $stmt->fetch();
    
    if (!$oferta) {
        throw new Exception('Oferta no encontrada o ya completada');
    }
    
    if ($oferta['usuario_id'] == $usuario_id) {
        throw new Exception('No puedes aceptar tu propia oferta');
    }
    
    // Verificar que el aceptante tiene el jugador que pide
    if ($oferta['jugador_pide']) {
        $stmt = $conn->prepare("
            SELECT cantidad FROM estampas_usuario
            WHERE usuario_id = ? AND jugador_id = ?
        ");
        $stmt->execute([$usuario_id, $oferta['jugador_pide']]);
        $tiene = $stmt->fetch();
        
        if (!$tiene || $tiene['cantidad'] < $oferta['cantidad_pide']) {
            throw new Exception('No tienes la estampa que piden');
        }
        
        // Restar del aceptante
        $stmt = $conn->prepare("
            UPDATE estampas_usuario 
            SET cantidad = cantidad - ?
            WHERE usuario_id = ? AND jugador_id = ?
        ");
        $stmt->execute([$oferta['cantidad_pide'], $usuario_id, $oferta['jugador_pide']]);
        
        // Eliminar si llega a 0
        $stmt = $conn->prepare("
            DELETE FROM estampas_usuario 
            WHERE usuario_id = ? AND jugador_id = ? AND cantidad <= 0
        ");
        $stmt->execute([$usuario_id, $oferta['jugador_pide']]);
    }
    
    // Dar al aceptante el jugador que ofrece
    $stmt = $conn->prepare("
        INSERT INTO estampas_usuario (usuario_id, jugador_id, cantidad)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE cantidad = cantidad + ?
    ");
    $stmt->execute([$usuario_id, $oferta['jugador_ofrece'], $oferta['cantidad_ofrece'], $oferta['cantidad_ofrece']]);
    
    // Al creador de la oferta: restar lo que ofrece
    $stmt = $conn->prepare("
        UPDATE estampas_usuario 
        SET cantidad = cantidad - ?
        WHERE usuario_id = ? AND jugador_id = ?
    ");
    $stmt->execute([$oferta['cantidad_ofrece'], $oferta['usuario_id'], $oferta['jugador_ofrece']]);
    
    // Al creador de la oferta: dar lo que pide
    if ($oferta['jugador_pide']) {
        $stmt = $conn->prepare("
            INSERT INTO estampas_usuario (usuario_id, jugador_id, cantidad)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE cantidad = cantidad + ?
        ");
        $stmt->execute([$oferta['usuario_id'], $oferta['jugador_pide'], $oferta['cantidad_pide'], $oferta['cantidad_pide']]);
    }
    
    $stmt = $conn->prepare("UPDATE ofertas SET estado = 'completada', aceptada_por = ? WHERE id = ?");
    $stmt->execute([$usuario_id, $oferta_id]);
    
    // Registrar en historial
    $stmt = $conn->prepare("
        INSERT INTO historial (usuario_id, descripcion)
        VALUES (?, ?), (?, ?)
    ");
    $stmt->execute([
        $usuario_id, "Has aceptado la oferta #{$oferta_id}",
        $oferta['usuario_id'], "Tu oferta #{$oferta_id} fue aceptada"
    ]);
    
    $conn->commit();
    echo json_encode(['success' => true, 'mensaje' => 'Intercambio completado con éxito']);
    
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}