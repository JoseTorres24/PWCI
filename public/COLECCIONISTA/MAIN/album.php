<?php
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

$usuario_id = $_SESSION['usuario']['id'];

// Obtener banderas desde API externa grrr
function obtenerBandera($pais) {
    $codigos = [
        'México' => 'mx',
        'Argentina' => 'ar',
        'Brasil' => 'br',
        'Francia' => 'fr',
    ];
    $codigo = $codigos[$pais] ?? 'unknown';
    return "https://flagpedia.net/data/flags/icon/72x54/{$codigo}.png";
}

$stmt = $conn->prepare("SELECT id, nombre, grupo, escudo FROM equipos ORDER BY nombre DESC");
$stmt->execute();
$todos_equipos = $stmt->fetchAll();

foreach ($todos_equipos as $key => $equipo) {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT j.id) as total_jugadores,
               COUNT(CASE WHEN eu.usuario_id IS NOT NULL THEN 1 END) as obtenidas
        FROM jugadores j
        LEFT JOIN estampas_usuario eu ON j.id = eu.jugador_id AND eu.usuario_id = ?
        WHERE j.equipo_id = ?
    ");
    $stmt->execute([$usuario_id, $equipo['id']]);
    $stats = $stmt->fetch();
    
    $todos_equipos[$key]['total_jugadores'] = $stats['total_jugadores'] ?? 0;
    $todos_equipos[$key]['obtenidas'] = $stats['obtenidas'] ?? 0;
    $todos_equipos[$key]['progreso'] = $todos_equipos[$key]['total_jugadores'] > 0 ? 
                          ($todos_equipos[$key]['obtenidas'] / $todos_equipos[$key]['total_jugadores']) * 100 : 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mi Álbum - Mundial 2026</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/COLECCIONISTA/ESTILOS/albumCSS.css?v=<?php echo time(); ?>">
</head>
<body>
  <div class="page-shell">
    <header class="topbar">
      <div class="brand">FIFA World Cup 2026</div>
      <nav class="topnav">
        <a href="/dashboard">Inicio</a>
        <a class="activo" href="/album">Mi Álbum</a>
        <a href="/tienda">Tienda</a>
        <a href="/mercado">Mercado</a>
        <a href="/perfil">Perfil</a>
      </nav>
    </header>

    <main class="content-wrap">
      <section class="hero-card centered">
        <div>
          <p class="eyebrow">Colección oficial</p>
          <h1>Mi Álbum</h1>
          <p class="hero-text">Selecciona un equipo y revisa el avance de tus estampas coleccionadas.</p>
        </div>
        <a class="home-button" href="/dashboard">Regresar al inicio</a>
      </section>

      <section>
        <div class="carousel-container">
          <button class="carousel-btn prev" id="prevBtn">❮</button>
          <?php $contador = 0; ?>

          <div class="teams-grid" id="teamsGrid">
            <?php foreach ($todos_equipos as $equipo): ?>
              <?php $contador++; ?>
            <article class="team-card" data-equipo-id="<?php echo $equipo['id']; ?>">
            <!--Esto papus funciona para mostrar equipos grrr-->
              <div class="team-head">
                  <img class="bandera-real" src="<?php echo obtenerBandera($equipo['nombre']); ?>" 
                      alt="<?php echo htmlspecialchars($equipo['nombre']); ?>"
                      onerror="this.src='/ASSETS/escudos/<?php echo strtolower($equipo['nombre']); ?>.png'">
                  <h3><?php echo htmlspecialchars($equipo['nombre']); ?></h3>
              </div>
              <p><?php echo $equipo['obtenidas']; ?> / <?php echo $equipo['total_jugadores']; ?> estampas</p>
              <div class="progress-track">
                <span style="width: <?php echo $equipo['progreso']; ?>%;"></span>
              </div>
              <a href="/equipo?id=<?php echo $equipo['id']; ?>">Ver jugadores</a>
            </article>
            <?php endforeach; ?>
          </div>
          <button class="carousel-btn next" id="nextBtn">❯</button>
        </div>
      </section>
    </main>
  </div>



  <script>
    const grid = document.getElementById('teamsGrid');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    let scrollAmount = 0;
    const cardWidth = 320;

    nextBtn.addEventListener('click', () => {
      scrollAmount += cardWidth * 2;
      if (scrollAmount > grid.scrollWidth - grid.clientWidth) {
        scrollAmount = grid.scrollWidth - grid.clientWidth;
      }
      grid.scrollTo({ left: scrollAmount, behavior: 'smooth' });
    });

    prevBtn.addEventListener('click', () => {
      scrollAmount -= cardWidth * 2;
      if (scrollAmount < 0) {
        scrollAmount = 0;
      }
      grid.scrollTo({ left: scrollAmount, behavior: 'smooth' });
    });
  </script>
</body>
</html>