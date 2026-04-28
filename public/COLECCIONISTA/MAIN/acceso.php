
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
      <form class="sign-in" method="POST" action="/backend/api/login.php">
        <h2>Iniciar Sesión</h2>
          <div class="social-networks">
          <ion-icon name="logo-facebook"></ion-icon>
          <ion-icon name="logo-google"></ion-icon>
          <ion-icon name="logo-twitter"></ion-icon>
          <ion-icon name="logo-instagram"></ion-icon>
        </div>
        <span>Use su correo y contraseña</span>

        <div class="container-input">
          <input type="email" name="correo" placeholder="Email" required>
        </div>

        <div class="container-input">
          <input type="password" name="password" placeholder="Password" required>
        </div>

        <button class="button" type="submit">Iniciar Sesión</button>
      </form>
    </div>

    <!-- ================= REGISTRO ================= -->
    <div class="container-form">
      <form class="sign-up" method="POST" action="/backend/api/registro.php">
        <h2>Registrarse</h2>

        <span>Use su correo electrónico</span>

        <div class="container-input">
          <input type="text" name="nombre" placeholder="Nombre completo" required>
        </div>

        <div class="container-input">
          <input type="email" name="correo" placeholder="Email" required>
        </div>

        <div class="container-input">
          <input type="password" name="password" placeholder="Password" required>
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

  <!-- ================= MENSAJES ================= -->
  <?php if(isset($_GET['error'])): ?>
    <div style="position:absolute; top:20px; right:20px; background:red; color:white; padding:10px;">
      Credenciales incorrectas
    </div>
  <?php endif; ?>

  <?php if(isset($_GET['success'])): ?>
    <div style="position:absolute; top:20px; right:20px; background:green; color:white; padding:10px;">
      Registro exitoso
    </div>
  <?php endif; ?>

  <?php if(isset($_GET['error']) && $_GET['error'] == 'login'): ?>
  <div style="position:absolute; top:20px; right:20px; background:red; color:white; padding:10px;">
    Credenciales incorrectas
  </div>
  <?php endif; ?>

  <!-- ================= SCRIPT UI ================= -->
  <script>
    const container = document.querySelector('.container');
    const btnSignIn = document.getElementById('btn-sign-in');
    const btnSignUp = document.getElementById('btn-sign-up');

    btnSignIn.addEventListener('click', () => {
      container.classList.remove('toggle');
    });

    btnSignUp.addEventListener('click', () => {
      container.classList.add('toggle');
    });
  </script>

  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

</body>
</html>