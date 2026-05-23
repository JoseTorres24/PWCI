<?php
session_start();

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($correo) || empty($password)) {
    echo json_encode(['error' => 'Todos los campos son obligatorios']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    // ========== USAR SP_LOGIN ==========
    $stmt = $conn->prepare("CALL SP_LOGIN(?, @p_id, @p_nombre, @p_rol, @p_password_hash, @p_success)");
    $stmt->execute([$correo]);
    
    // Obtener los valores de salida
    $stmt = $conn->query("SELECT @p_id as id, @p_nombre as nombre, @p_rol as rol, @p_password_hash as password_hash, @p_success as success");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->nextRowset(); // Limpiar resultados
    
    if ($result['success'] == 1 && password_verify($password, $result['password_hash'])) {
        $_SESSION['usuario'] = [
            'id' => $result['id'],
            'nombre' => $result['nombre'],
            'correo' => $correo,
            'rol' => $result['rol']
        ];
        
        echo json_encode([
            'success' => true, 
            'message' => 'Login exitoso', 
            'rol' => $result['rol']
        ]);
    } else {
        echo json_encode(['error' => 'Correo o contraseña incorrectos']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>