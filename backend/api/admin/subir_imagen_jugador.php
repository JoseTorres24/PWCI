<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once '../../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$jugador_id = $_POST['jugador_id'] ?? 0;

if (!$jugador_id) {
    echo json_encode(['error' => 'ID de jugador no válido']);
    exit;
}

if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'No se ha subido ningún archivo']);
    exit;
}

// Validar tipo de imagen
$allowed = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $_FILES['foto']['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed)) {
    echo json_encode(['error' => 'Formato no permitido. Use JPG, PNG, GIF o WEBP']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    $foto_blob = file_get_contents($_FILES['foto']['tmp_name']);
    
    $stmt = $conn->prepare("UPDATE jugadores SET foto = ? WHERE id = ?");
    $stmt->bindParam(1, $foto_blob, PDO::PARAM_LOB);
    $stmt->execute([$jugador_id]);
    
    echo json_encode(['success' => true, 'mensaje' => 'Foto actualizada correctamente']);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>