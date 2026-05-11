<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso de Usuario</title>
    <link rel="stylesheet" href="/COLECCIONISTA/ESTILOS/acceso.css">
</head>
<body>

<?php if(isset($_GET['error'])): ?>
    <div style="position:fixed; top:20px; right:20px; background:red; color:white; padding:15px; border-radius:8px; z-index:1000;">
        <?php 
            switch($_GET['error']):
                case 'campos': echo 'Todos los campos son obligatorios'; break;
                case 'correo': echo 'Correo electrónico inválido'; break;
                case 'password': echo 'La contraseña debe tener al menos 6 caracteres'; break;
                case 'existe': echo 'El correo ya está registrado'; break;
                case 'login': echo 'Credenciales incorrectas'; break;
                default: echo 'Error desconocido';
            endswitch;
        ?>
    </div>
<?php endif; ?>

<?php if(isset($_GET['registro']) && $_GET['registro'] == 'exitoso'): ?>
    <div style="position:fixed; top:20px; right:20px; background:green; color:white; padding:15px; border-radius:8px; z-index:1000;">
        ¡Registro exitoso! Bienvenido.
    </div>
<?php endif; ?>

<video autoplay muted loop id="video-fondo">
    <source src="../ASSETS/FIFAcortinilla.mp4" type="video/mp4">
</video>

<div class="container">

    <!-- ================= LOGIN ================= -->
    <div class="container-form">
        <form class="sign-in" id="loginFormColeccionista">
            <h2>Iniciar Sesión</h2>
            <div class="social-networks">
                <ion-icon name="logo-facebook"></ion-icon>
                <ion-icon name="logo-google"></ion-icon>
                <ion-icon name="logo-twitter"></ion-icon>
                <ion-icon name="logo-instagram"></ion-icon>
            </div>
            <span>Use su correo y contraseña</span>

            <div class="container-input">
                <input type="email" id="loginEmail" name="correo" placeholder="Email" required>
            </div>

            <div class="container-input">
                <input type="password" id="loginPassword" name="password" placeholder="Password" required>
            </div>

            <button class="button" type="submit">Iniciar Sesión</button>
        </form>
    </div>

    <!-- ================= REGISTRO ================= -->
    <div class="container-form">
        <form class="sign-up" id="registroForm">
            <h2>Registrarse</h2>
            <span>Use su correo electrónico</span>

            <div class="container-input">
                <input type="text" id="regNombre" name="nombre" placeholder="Nombre completo" required>
            </div>

            <div class="container-input">
                <input type="email" id="regEmail" name="correo" placeholder="Email" required>
            </div>

            <div class="container-input">
                <input type="password" id="regPassword" name="password" placeholder="Password" required>
            </div>

            <button class="button" type="submit">Registrarse</button>
        </form>
    </div>

    <!-- ================= PANEL SWITCH ================= -->
    <div class="container-welcome">
        <div class="welcome-sign-up welcome">
            <h3>Bienvenido</h3>
            <p>Ingrese sus datos para registrarse</p>
            <button class="button" id="btn-sign-up">Registrarse</button>
        </div>
        <div class="welcome-sign-in welcome">
            <h3>Bienvenido</h3>
            <p>Ingrese sus datos para iniciar sesión</p>
            <button class="button" id="btn-sign-in">Iniciar Sesión</button>
        </div>
    </div>
</div>

<div id="toastMessage" style="position:fixed; bottom:20px; right:20px; background:#2ecc71; color:white; padding:12px 20px; border-radius:12px; display:none; z-index:1001;"></div>

<script>
    // UI Switch
    const container = document.querySelector('.container');
    const btnSignIn = document.getElementById('btn-sign-in');
    const btnSignUp = document.getElementById('btn-sign-up');

    btnSignIn.addEventListener('click', () => {
        container.classList.remove('toggle');
    });

    btnSignUp.addEventListener('click', () => {
        container.classList.add('toggle');
    });

    function showToast(message, type) {
        const toast = document.getElementById('toastMessage');
        toast.textContent = message;
        toast.style.backgroundColor = type === 'success' ? '#2ecc71' : '#e83b57';
        toast.style.display = 'block';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
    }

    // Login para coleccionistas (SOLO coleccionistas)
    document.getElementById('loginFormColeccionista').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const correo = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;
        
        const formData = new URLSearchParams();
        formData.append('correo', correo);
        formData.append('password', password);
        
        const btn = document.querySelector('.sign-in .button');
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
                    showToast('Los administradores deben usar el panel de administración', 'error');
                } else if (data.rol === 'coleccionista') {
                    showToast('¡Bienvenido! Redirigiendo...', 'success');
                    setTimeout(() => {
                        window.location.href = '/dashboard';
                    }, 1000);
                }
            } else {
                showToast(data.error || 'Credenciales incorrectas', 'error');
            }
        } catch (error) {
            showToast('Error al conectar con el servidor', 'error');
        } finally {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    });

    // Registro
    document.getElementById('registroForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const nombre = document.getElementById('regNombre').value;
        const correo = document.getElementById('regEmail').value;
        const password = document.getElementById('regPassword').value;
        
        const formData = new URLSearchParams();
        formData.append('nombre', nombre);
        formData.append('correo', correo);
        formData.append('password', password);
        
        const btn = document.querySelector('.sign-up .button');
        const originalText = btn.textContent;
        btn.textContent = 'Registrando...';
        btn.disabled = true;
        
        try {
            const response = await fetch('/backend/api/registro.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString()
            });
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message || 'Registro exitoso', 'success');
                setTimeout(() => {
                    window.location.href = '/dashboard';
                }, 1000);
            } else {
                showToast(data.error || 'Error en el registro', 'error');
            }
        } catch (error) {
            showToast('Error al conectar con el servidor', 'error');
        } finally {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    });
</script>

<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

</body>
</html>