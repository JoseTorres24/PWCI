<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';

$db = new Database();
$conn = $db->connect();

$usuario_id = $_SESSION['usuario']['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$jugador_ofrece = $_POST['jugador_ofrece'] ?? 0;
$cantidad_ofrece = $_POST['cantidad_ofrece'] ?? 1;
$jugador_pide = $_POST['jugador_pide'] ?? null;
$cantidad_pide = $_POST['cantidad_pide'] ?? 1;

if (!$jugador_ofrece) {
    echo json_encode(['error' => 'Debes seleccionar un jugador para ofrecer']);
    exit;
}

try {
    // Verificar que el usuario tiene suficientes copias
    $stmt = $conn->prepare("
        SELECT cantidad FROM estampas_usuario 
        WHERE usuario_id = ? AND jugador_id = ?
    ");
    $stmt->execute([$usuario_id, $jugador_ofrece]);
    $tiene = $stmt->fetch();
    
    if (!$tiene || $tiene['cantidad'] < $cantidad_ofrece) {
        echo json_encode(['error' => 'No tienes suficientes copias de esa estampa']);
        exit;
    }
    
    // Crear oferta
    $stmt = $conn->prepare("
        INSERT INTO ofertas (usuario_id, jugador_ofrece, cantidad_ofrece, jugador_pide, cantidad_pide, estado)
        VALUES (?, ?, ?, ?, ?, 'activa')
    ");
    $stmt->execute([$usuario_id, $jugador_ofrece, $cantidad_ofrece, $jugador_pide, $cantidad_pide]);
    
    // Registrar en historial
    $oferta_id = $conn->lastInsertId();
    $stmt = $conn->prepare("
        INSERT INTO historial (usuario_id, descripcion)
        VALUES (?, ?)
    ");
    $stmt->execute([$usuario_id, "Has creado una oferta de intercambio #{$oferta_id}"]);
    
    echo json_encode(['success' => true, 'mensaje' => 'Oferta creada correctamente']);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error al crear la oferta: ' . $e->getMessage()]);
}