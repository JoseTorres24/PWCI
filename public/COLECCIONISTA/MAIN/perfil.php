<?php
// Verificar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    header("Location: /acceso");
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';

$db = new Database();
$conn = $db->connect();

$usuario = $_SESSION['usuario'];
$usuario_id = $usuario['id'];
$nombre_usuario = $usuario['nombre'];
$correo_usuario = $usuario['correo'];
$inicial = strtoupper(substr($nombre_usuario, 0, 1));

// ========== USAR SP_OBTENER_TOTAL_ESTAMPAS_USUARIO ==========
$stmt = $conn->prepare("CALL SP_OBTENER_TOTAL_ESTAMPAS_USUARIO(?, @total)");
$stmt->execute([$usuario_id]);
$stmt = $conn->query("SELECT @total as total");
$total_estampas = $stmt->fetch()['total'] ?? 0;
$stmt->nextRowset(); // Limpiar resultados
$stmt->nextRowset(); // Limpiar resultados

// ========== USAR SP_OBTENER_ESTAMPAS_ESPECIALES_USUARIO ==========
$stmt = $conn->prepare("CALL SP_OBTENER_ESTAMPAS_ESPECIALES_USUARIO(?, @total)");
$stmt->execute([$usuario_id]);
$stmt = $conn->query("SELECT @total as total");
$estampas_especiales = $stmt->fetch()['total'] ?? 0;
$stmt->nextRowset(); // Limpiar resultados
$stmt->nextRowset(); // Limpiar resultados

// ========== USAR SP_OBTENER_TOTAL_INTERCAMBIOS_USUARIO ==========
$stmt = $conn->prepare("CALL SP_OBTENER_TOTAL_INTERCAMBIOS_USUARIO(?, @total)");
$stmt->execute([$usuario_id]);
$stmt = $conn->query("SELECT @total as total");
$intercambios = $stmt->fetch()['total'] ?? 0;
$stmt->nextRowset(); // Limpiar resultados
$stmt->nextRowset(); // Limpiar resultados

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Perfil - Mundial 2026</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/COLECCIONISTA/ESTILOS/perfilCSS.css">
</head>
<body>
  <div class="page-shell">
    <header class="topbar">
      <div class="brand">FIFA World Cup 2026</div>
      <nav class="topnav">
        <a href="/dashboard">Inicio</a>
        <a href="/album">Mi Álbum</a>
        <a href="/tienda">Tienda</a>
        <a href="/mercado">Mercado</a>
        <a class="activo" href="/perfil">Perfil</a>
      </nav>
    </header>

    <main class="content-wrap profile-layout">
      <section class="profile-header">
        <div class="avatar"><?php echo $inicial; ?></div>
        <h1><?php echo htmlspecialchars($nombre_usuario); ?></h1>
        <p><?php echo htmlspecialchars($correo_usuario); ?></p>
        <div class="button-group">
          <a class="home-button" href="/dashboard">Regresar al inicio</a>
          <a class="logout-button" href="/logout">Cerrar sesión</a>
        </div>
      </section>

      <section class="stats-grid">
        <article class="stat-card">
          <span>Total de Estampas</span>
          <strong><?php echo $total_estampas; ?></strong>
        </article>
        <article class="stat-card accent-yellow">
          <span>Estampas Especiales</span>
          <strong><?php echo $estampas_especiales; ?></strong>
        </article>
        <article class="stat-card accent-red">
          <span>Intercambios</span>
          <strong><?php echo $intercambios; ?></strong>
        </article>
      </section>

      <section class="form-card">
        <h2>Cambiar contraseña</h2>
        <form method="POST" action="/backend/api/cambiar_password.php" id="passwordForm">
          <div class="form-group">
            <label for="actual">Contraseña actual *</label>
            <input id="actual" name="actual" type="password" placeholder="Ingresa tu contraseña actual" required>
            <span class="error-message" id="error-actual"></span>
          </div>

          <div class="form-group">
            <label for="nueva">Nueva contraseña *</label>
            <input id="nueva" name="nueva" type="password" placeholder="Mínimo 6 caracteres" required>
            <span class="error-message" id="error-nueva"></span>
          </div>

          <div class="form-group">
            <label for="confirmar">Confirmar contraseña *</label>
            <input id="confirmar" name="confirmar" type="password" placeholder="Confirma tu nueva contraseña" required>
            <span class="error-message" id="error-confirmar"></span>
          </div>

          <button type="submit" id="btnGuardar">Guardar cambios</button>
        </form>
      </section>
    </main>
  </div>

  <?php if(isset($_GET['error'])): ?>
  <div class="toast-message error" id="toastMessage">
    <?php 
      switch($_GET['error']):
        case 'campos_vacios': echo 'Todos los campos son obligatorios'; break;
        case 'password_mismatch': echo 'Las contraseñas nuevas no coinciden'; break;
        case 'password_short': echo 'La contraseña debe tener al menos 6 caracteres'; break;
        case 'incorrect': echo 'Contraseña actual incorrecta'; break;
        case 'usuario_no_encontrado': echo 'Usuario no encontrado'; break;
        case 'bd': echo 'Error en la base de datos'; break;
        default: echo 'Error al actualizar la contraseña';
      endswitch;
    ?>
  </div>
  <?php endif; ?>

  <?php if(isset($_GET['success']) && $_GET['success'] == 1): ?>
  <div class="toast-message success" id="toastMessage">
    ¡Contraseña actualizada correctamente!
  </div>
  <?php endif; ?>

  <script>
    // Auto-ocultar toast después de 3 segundos
    setTimeout(function() {
      var toast = document.getElementById('toastMessage');
      if (toast) {
        toast.style.opacity = '0';
        setTimeout(function() {
          if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 300);
      }
    }, 3000);

    // Validación en tiempo real
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
      const nueva = document.getElementById('nueva').value;
      const confirmar = document.getElementById('confirmar').value;
      let valid = true;

      document.getElementById('error-nueva').textContent = '';
      document.getElementById('error-confirmar').textContent = '';

      if (nueva.length > 0 && nueva.length < 6) {
        document.getElementById('error-nueva').textContent = 'La contraseña debe tener al menos 6 caracteres';
        valid = false;
      }

      if (nueva !== confirmar && confirmar.length > 0) {
        document.getElementById('error-confirmar').textContent = 'Las contraseñas no coinciden';
        valid = false;
      }

      if (!valid) {
        e.preventDefault();
      }
    });

    // Mostrar fuerza de la contraseña (opcional)
    document.getElementById('nueva').addEventListener('input', function() {
      const password = this.value;
      const strengthEl = document.getElementById('error-nueva');
      
      if (password.length === 0) {
        strengthEl.textContent = '';
      } else if (password.length < 6) {
        strengthEl.textContent = 'Débil - Mínimo 6 caracteres';
      } else if (password.length < 8) {
        strengthEl.textContent = 'Media - Buena';
      } else {
        strengthEl.textContent = 'Fuerte - Excelente';
      }
    });
  </script>
</body>
</html>