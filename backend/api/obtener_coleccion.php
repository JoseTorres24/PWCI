<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';


$db = new Database();
$conn = $db->connect();

$usuario_id = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;

if (!$usuario_id) {
    echo json_encode(['success' => false, 'error' => 'ID de usuario no válido']);
    exit;
}

try {
    // Obtener datos del usuario
    $stmt = $conn->prepare("SELECT nombre, correo, rol, DATE_FORMAT(created_at, '%d/%m/%Y') as fecha_registro FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        exit;
    }
    
    // Obtener colección de cartas
    $stmt = $conn->prepare("
        SELECT j.id, j.nombre, j.numero, j.posicion, j.rareza, 
               COALESCE(j.foto, '/Jugadores/silueta.png') as foto,
               e.nombre as equipo_nombre, eu.cantidad,
               DATE_FORMAT(eu.fecha_obtencion, '%d/%m/%Y') as fecha_obtencion
        FROM estampas_usuario eu
        INNER JOIN jugadores j ON eu.jugador_id = j.id
        INNER JOIN equipos e ON j.equipo_id = e.id
        WHERE eu.usuario_id = ?
        ORDER BY e.nombre, j.numero
    ");
    $stmt->execute([$usuario_id]);
    $cartas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear cartas con colores de rareza
    $cartas_formateadas = [];
    foreach ($cartas as $carta) {
        switch ($carta['rareza']) {
            case 'ssr': $color = '#f1c40f'; break;
            case 'sr': $color = '#3498db'; break;
            case 'r': $color = '#2ecc71'; break;
            default: $color = '#95a5a6';
        }
        
        $cartas_formateadas[] = [
            'id' => $carta['id'],
            'nombre' => $carta['nombre'],
            'numero' => $carta['numero'],
            'posicion' => $carta['posicion'],
            'rareza' => $carta['rareza'],
            'rareza_color' => $color,
            'foto' => $carta['foto'],
            'equipo_nombre' => $carta['equipo_nombre'],
            'cantidad' => $carta['cantidad'],
            'fecha_obtencion' => $carta['fecha_obtencion']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'usuario' => $usuario,
        'fecha_registro' => $usuario['fecha_registro'],
        'total_estampas' => count($cartas_formateadas),
        'cartas' => $cartas_formateadas
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>