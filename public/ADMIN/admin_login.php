<?php
session_start();

// Si ya está logueado como admin, redirigir al dashboard
if (isset($_SESSION['usuario']) && $_SESSION['usuario']['rol'] === 'admin') {
    header('Location: /admin/dashboard');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Admin Login - Mundial 2026</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/ADMIN/adminCSS.css">
</head>
<body>

<div class="login-container" style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#163049,#0a1a2e);">
    <div class="login-card" style="background:white;padding:40px;border-radius:28px;width:100%;max-width:400px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <h2 style="color:#163049;margin-bottom:10px;">Panel de Administración</h2>
        <p style="color:#46637a;margin-bottom:25px;">Ingresa tus credenciales para acceder</p>
        
        <form method="POST" action="/backend/api/login.php">
            <input type="email" name="correo" placeholder="Correo electrónico" required style="width:100%;padding:12px;margin-bottom:15px;border:1px solid #ddd;border-radius:12px;">
            <input type="password" name="password" placeholder="Contraseña" required style="width:100%;padding:12px;margin-bottom:15px;border:1px solid #ddd;border-radius:12px;">
            <button type="submit" style="width:100%;padding:12px;background:#08b8b3;color:white;border:none;border-radius:12px;font-weight:700;cursor:pointer;">Ingresar como Admin</button>
        </form>
    </div>
</div>

</body>
</html>