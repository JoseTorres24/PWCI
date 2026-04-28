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
    // Verificar que la oferta existe y pertenece al usuario
    $stmt = $conn->prepare("
        SELECT id FROM ofertas 
        WHERE id = ? AND usuario_id = ? AND estado = 'activa'
    ");
    $stmt->execute([$oferta_id, $usuario_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Oferta no encontrada o no te pertenece');
    }
    
    // Cancelar oferta
    $stmt = $conn->prepare("UPDATE ofertas SET estado = 'cancelada' WHERE id = ?");
    $stmt->execute([$oferta_id]);
    
    // Registrar en historial
    $stmt = $conn->prepare("
        INSERT INTO historial (usuario_id, descripcion)
        VALUES (?, ?)
    ");
    $stmt->execute([$usuario_id, "Has cancelado tu oferta #{$oferta_id}"]);
    
    echo json_encode(['success' => true, 'mensaje' => 'Oferta cancelada correctamente']);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}