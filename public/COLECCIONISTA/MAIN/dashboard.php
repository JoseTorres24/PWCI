<?php

if (!isset($_SESSION['usuario'])) {
    header("Location: /acceso");
    exit;
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/middleware/AuthMiddleware.php';

$usuario = AuthMiddleware::verificarAutenticacion();

require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';
$db = new Database();
$conn = $db->connect();

AuthMiddleware::registrarAccion($conn, $usuario['id'], "Accedió al dashboard");
$usuario_id = $_SESSION['usuario']['id']; 

$stmt = $conn->prepare("SELECT nombre FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$user = $stmt->fetch();

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT jugador_id) as total
    FROM estampas_usuario
    WHERE usuario_id = ?
");
$stmt->execute([$usuario_id]);
$total_obtenidas = $stmt->fetch()['total'] ?? 0;

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM jugadores");
$stmt->execute();
$total_album = $stmt->fetch()['total'] ?? 12;

$porcentaje = $total_album > 0 ? ($total_obtenidas / $total_album) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Dashboard - Mundial 2026</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/COLECCIONISTA/ESTILOS/dashboardCSS.css">
<style>
    .noticias-section {
        margin-top: 30px;
        background: white;
        border-radius: 24px;
        padding: 20px;
        box-shadow: 0 10px 26px rgba(16,27,61,.08);
    }
    .noticias-section h3 {
        color: var(--teal);
        margin-bottom: 15px;
        font-size: 1.3rem;
    }
    .noticias-container {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .noticia-item {
        padding: 12px;
        background: #f8f9fa;
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    .noticia-item:hover {
        background: #e9ecef;
        transform: translateX(5px);
    }
    .noticia-item a {
        text-decoration: none;
        color: var(--text);
        display: block;
    }
    .noticia-item a:hover {
        color: var(--teal);
    }
    .cargando-noticias {
        text-align: center;
        padding: 20px;
        color: var(--muted);
    }
</style>
</head>
<body>
  <div class="page-shell">
    <header class="topbar">
      <div class="brand">FIFA World Cup 2026</div>
      <nav class="topnav">
        <a class="activo" href="/dashboard">Inicio</a>
        <a href="/album">Mi Álbum</a>
        <a href="/tienda">Tienda</a>
        <a href="/mercado">Mercado</a>
        <a href="/perfil">Perfil</a>
      </nav>
    </header>

    <main class="content-wrap">
      <section class="hero-card">
        <div>
          <p class="eyebrow">Álbum Mundial 2026</p>
          <h1>Bienvenido, <?php echo htmlspecialchars($user['nombre'] ?? $_SESSION['usuario']['nombre']); ?></h1>
          <p class="hero-text">Tu colección sigue creciendo. Consulta tu progreso, revisa intercambios y abre nuevos sobres.</p>
        </div>
        <a class="home-button" href="/">Regresar al inicio</a>
      </section>

      <section class="dashboard-grid">
        <article class="panel-card progress-card">
          <h2>Progreso del Álbum</h2>
          <p class="big-number"><?php echo $total_obtenidas; ?></p>
          <p class="subtext">de <?php echo $total_album; ?> estampas</p>
          <div class="progress-track">
            <span style="width: <?php echo $porcentaje; ?>%;"></span>
          </div>
          <p class="small-note">
            <?php echo round($porcentaje); ?>% completado
          </p>
        </article>

        <article class="panel-card notifications-card">
          <h2>Notificaciones</h2>
          <ul>
            <li>
              <strong>Nuevas ofertas de intercambio</strong>
              <span>Recibiste una propuesta por una estampa especial.</span>
            </li>
            <li>
              <strong>Nuevo sobre disponible</strong>
              <span>Ya puedes abrir un sobre nuevo en la tienda.</span>
            </li>
          </ul>
        </article>
      </section>

      <section class="noticias-section">
        <h3>Noticias del Mundial</h3>
        <div id="noticias-container" class="noticias-container">
          <div class="cargando-noticias">Cargando noticias...</div>
        </div>
      </section>
    </main>
  </div>

  <script>
    async function cargarNoticias() {
      const container = document.getElementById('noticias-container');
      
      try {
        const response = await fetch('/backend/api/noticias.php');
        const data = await response.json();
        
        if (data.success && data.noticias && data.noticias.length > 0) {
          container.innerHTML = data.noticias.map(noticia => `
            <div class="noticia-item">
              <a href="${noticia.link || '#'}" target="_blank" rel="noopener noreferrer">
                ${noticia.title}
              </a>
            </div>
          `).join('');
        } else {
          container.innerHTML = `
            <div class="noticia-item">
              ¡Prepárate para el Mundial 2026! Las selecciones ya se están preparando.
            </div>
            <div class="noticia-item">
              México, Argentina, Brasil y Francia, favoritos para el título.
            </div>
            <div class="noticia-item">
              Ya están a la venta los boletos para los partidos del Mundial.
            </div>
          `;
        }
      } catch (error) {
        console.error('Error cargando noticias:', error);
        container.innerHTML = `
          <div class="noticia-item">
            ¡Prepárate para el Mundial 2026! Las selecciones ya se están preparando.
          </div>
          <div class="noticia-item">
            México, Argentina, Brasil y Francia, favoritos para el título.
          </div>
        `;
      }
    }
    
    cargarNoticias();
  </script>
</body>
</html>