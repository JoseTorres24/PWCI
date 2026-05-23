<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    header("Location: /acceso");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /perfil?error=metodo");
    exit;
}


require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';

$db = new Database();
$conn = $db->connect();

$usuario_id = $_SESSION['usuario']['id'];


$password_actual = $_POST['actual'] ?? '';
$password_nueva = $_POST['nueva'] ?? '';
$password_confirmar = $_POST['confirmar'] ?? '';


if (empty($password_actual) || empty($password_nueva) || empty($password_confirmar)) {
    header("Location: /perfil?error=campos_vacios");
    exit;
}


$password_errores = [];


if (strlen($password_nueva) < 7) {
    $password_errores[] = 'La contraseña debe tener al menos 7 caracteres';
}


if (!preg_match('/[A-Z]/', $password_nueva)) {
    $password_errores[] = 'Debe contener al menos una letra mayúscula';
}


if (!preg_match('/[a-z]/', $password_nueva)) {
    $password_errores[] = 'Debe contener al menos una letra minúscula';
}


if (!preg_match('/[0-9]/', $password_nueva)) {
    $password_errores[] = 'Debe contener al menos un número';
}


if (!preg_match('/[!@#$%^&*()\-_=+\[\]{};:,.?\/]/', $password_nueva)) {
    $password_errores[] = 'Debe contener al menos un carácter especial (!@#$%^&* etc.)';
}


if ($password_nueva !== $password_confirmar) {
    $password_errores[] = 'Las contraseñas nuevas no coinciden';
}

if (!empty($password_errores)) {
    $error_msg = implode('; ', $password_errores);
    header("Location: /perfil?error=password_invalid&msg=" . urlencode($error_msg));
    exit;
}


try {
    $stmt = $conn->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        header("Location: /perfil?error=usuario_no_encontrado");
        exit;
    }
    
    if (!password_verify($password_actual, $usuario['password'])) {
        header("Location: /perfil?error=incorrect");
        exit;
    }
    
    $nueva_password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    $stmt->execute([$nueva_password_hash, $usuario_id]);
    
    $stmt = $conn->prepare("
        INSERT INTO historial (usuario_id, descripcion) 
        VALUES (?, 'Contraseña actualizada correctamente')
    ");
    $stmt->execute([$usuario_id]);
    
    header("Location: /perfil?success=1");
    exit;
    
} catch (PDOException $e) {
    header("Location: /perfil?error=bd&msg=" . urlencode($e->getMessage()));
    exit;
}
?>