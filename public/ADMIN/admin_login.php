<?php
session_start();

// Si ya está logueado como admin, redirigir al dashboard
if (isset($_SESSION['usuario']) && $_SESSION['usuario']['rol'] === 'admin') {
    header('Location: /admin/dashboard');
    exit;
}

// Si hay un mensaje de error en la sesión, mostrarlo
$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Mundial 2026</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Tus estilos existentes... */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 480px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 32px;
            padding: 48px 40px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-logo {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #08b8b3, #069792);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(8, 184, 179, 0.3);
        }

        .login-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .login-card h2 {
            color: #163049;
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .login-card p {
            color: #46637a;
            font-size: 0.9rem;
            margin-bottom: 32px;
        }

        .input-group {
            margin-bottom: 24px;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #163049;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .input-group input {
            width: 100%;
            padding: 16px 20px;
            font-size: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .input-group input:focus {
            outline: none;
            border-color: #08b8b3;
            box-shadow: 0 0 0 3px rgba(8, 184, 179, 0.1);
            background: white;
        }

        .login-btn {
            width: 100%;
            padding: 16px 20px;
            background: linear-gradient(135deg, #08b8b3, #069792);
            color: white;
            border: none;
            border-radius: 40px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(8, 184, 179, 0.3);
        }

        .login-footer {
            margin-top: 28px;
            font-size: 0.75rem;
            color: #46637a;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }

        .error-message {
            background: linear-gradient(135deg, #f12b4d, #c0392b);
            color: white;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            text-align: center;
        }

        .toast-message {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 14px 24px;
            border-radius: 16px;
            display: none;
            z-index: 1000;
            font-weight: 600;
            animation: slideIn 0.3s ease;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .toast-message.success {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
        }

        .toast-message.error {
            background: linear-gradient(135deg, #f12b4d, #c0392b);
            color: white;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @media (max-width: 640px) {
            .login-card { padding: 32px 24px; }
            .input-group input { padding: 14px 16px; }
            .toast-message { left: 20px; right: 20px; bottom: 20px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <img src="/ASSETS/FIFAlogocopa.jpeg" alt="ADMIN">
            </div>
            <h2>Panel de Administración</h2>
            <p>Ingresa tus credenciales para acceder al sistema</p>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form id="adminLoginForm">
                <div class="input-group">
                    <label>Correo electrónico</label>
                    <input type="email" id="adminEmail" name="correo" placeholder="admin@mundial2026.com" required autocomplete="email">
                </div>
                
                <div class="input-group">
                    <label>Contraseña</label>
                    <input type="password" id="adminPassword" name="password" placeholder="••••••••" required autocomplete="current-password">
                </div>
                
                <button type="submit" class="login-btn">Ingresar al Panel</button>
            </form>
            
            <div class="login-footer">
                <p>Solo personal autorizado</p>
            </div>
        </div>
    </div>
    
    <div id="toastMessage" class="toast-message"></div>
    
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        document.getElementById('adminLoginForm').onsubmit = async (e) => {
            e.preventDefault();
            
            const correo = document.getElementById('adminEmail').value;
            const password = document.getElementById('adminPassword').value;
            
            const formData = new URLSearchParams();
            formData.append('correo', correo);
            formData.append('password', password);
            
            const btn = document.querySelector('.login-btn');
            const originalText = btn.textContent;
            btn.textContent = 'Verificando...';
            btn.disabled = true;
            
            try {
                const response = await fetch('/backend/api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData.toString()
                });
                const data = await response.json();
                
                if (data.success) {
                    if (data.rol === 'admin') {
                        showToast('Acceso concedido, redirigiendo...', 'success');
                        setTimeout(() => {
                            window.location.href = '/admin/dashboard';
                        }, 1000);
                    } else {
                        showToast('Acceso denegado. Esta área es solo para administradores', 'error');
                    }
                } else {
                    showToast((data.error || 'Credenciales incorrectas'), 'error');
                }
            } catch (error) {
                showToast('Error al conectar con el servidor', 'error');
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        };
        
        function showToast(message, type) {
            const toast = document.getElementById('toastMessage');
            toast.textContent = message;
            toast.className = `toast-message ${type}`;
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 4000);
        }
    </script>
</body>
</html>