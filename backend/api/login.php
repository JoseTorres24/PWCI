<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';

$db = new Database();
$conn = $db->connect();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /acceso");
    exit;
}

$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($correo) || empty($password)) {
    header("Location: /acceso?error=campos");
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['usuario'] = [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'correo' => $user['correo'],
            'rol' => $user['rol']
        ];
        
        if ($user['rol'] === 'admin') {
            header("Location: /admin/dashboard");
        } else {
            header("Location: /dashboard");
        }
        exit;
    } else {
        header("Location: /acceso?error=login");
        exit;
    }
} catch (PDOException $e) {
    header("Location: /acceso?error=bd");
    exit;
}
?>