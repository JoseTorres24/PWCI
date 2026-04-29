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

$sobre_id = $_POST['sobre_id'] ?? 1;

try {
    $stmt = $conn->prepare("
        SELECT sj.probabilidad, j.* 
        FROM sobre_jugadores sj
        JOIN jugadores j ON sj.jugador_id = j.id
        WHERE sj.sobre_id = ?
    ");
    $stmt->execute([$sobre_id]);
    $jugadores = $stmt->fetchAll();
    
    // Seleccionar 3 cartas
    $cartas_obtenidas = [];
    
    for ($i = 0; $i < 3; $i++) {
        $rand = mt_rand(1, 100);
        $acumulado = 0;
        
        foreach ($jugadores as $jugador) {
            $acumulado += $jugador['probabilidad'];
            if ($rand <= $acumulado) {
                $cartas_obtenidas[] = $jugador;
                
                // Guardar en estampas_usuario
                $stmt2 = $conn->prepare("
                    INSERT INTO estampas_usuario (usuario_id, jugador_id, cantidad) 
                    VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE cantidad = cantidad + 1
                ");
                $stmt2->execute([$usuario_id, $jugador['id']]);
                
                break;
            }
        }
    }
    

    echo json_encode([
        'success' => true,
        'cartas' => $cartas_obtenidas,
        'mensaje' => '¡Has obtenido ' . count($cartas_obtenidas) . ' nuevas estampas!'
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error al abrir sobre: ' . $e->getMessage()]);
}