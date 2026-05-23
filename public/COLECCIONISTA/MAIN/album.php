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

// ========== USAR SP_OBTENER_PROGRESO_ALBUM ==========
$stmt = $conn->prepare("CALL SP_OBTENER_PROGRESO_ALBUM(?)");
$stmt->execute([$usuario_id]);
$todos_equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->nextRowset(); // Limpiar resultados

// ========== USAR SP_OBTENER_GRUPOS ==========
$stmt = $conn->prepare("CALL SP_OBTENER_GRUPOS()");
$stmt->execute();
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->nextRowset(); // Limpiar resultados

// Pre-cargar todas las banderas de una sola vez (API externa)
function obtenerTodasLasBanderas($equipos) {
    $banderas = [];
    foreach ($equipos as $equipo) {
        $codigos = [
            'México' => 'mx', 'Mexico' => 'mx',
            'Argentina' => 'ar', 'Brasil' => 'br', 'Brazil' => 'br',
            'Francia' => 'fr', 'France' => 'fr', 'España' => 'es',
            'Spain' => 'es', 'Alemania' => 'de', 'Germany' => 'de',
            'Inglaterra' => 'gb', 'England' => 'gb', 'Italia' => 'it', 'Italy' => 'it'
        ];
        $codigo = $codigos[$equipo['nombre']] ?? 'unknown';
        $banderas[$equipo['id']] = "https://flagpedia.net/data/flags/icon/72x54/{$codigo}.png";
    }
    return $banderas;
}

$banderas_precargadas = obtenerTodasLasBanderas($todos_equipos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mi Álbum - Mundial 2026</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/COLECCIONISTA/ESTILOS/albumCSS.css?v=<?php echo time(); ?>">
<style>
    .filters-container {
        background: white;
        border-radius: 24px;
        padding: 20px 25px;
        margin-bottom: 30px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        align-items: center;
        justify-content: space-between;
    }
    .search-box {
        flex: 2;
        min-width: 200px;
    }
    .search-box input {
        width: 100%;
        padding: 12px 20px;
        border: 2px solid #e0e4e8;
        border-radius: 30px;
        font-size: 1rem;
        outline: none;
        transition: all 0.3s;
    }
    .search-box input:focus {
        border-color: var(--teal);
    }
    .filter-select {
        min-width: 180px;
    }
    .filter-select select {
        width: 100%;
        padding: 12px 20px;
        border: 2px solid #e0e4e8;
        border-radius: 30px;
        font-size: 1rem;
        background: white;
        cursor: pointer;
        outline: none;
    }
    .filter-select select:focus {
        border-color: var(--teal);
    }
    .stats-info {
        background: linear-gradient(135deg, var(--teal), var(--teal-dark));
        color: white;
        padding: 8px 20px;
        border-radius: 30px;
        font-weight: 600;
        white-space: nowrap;
    }
    .no-results {
        text-align: center;
        padding: 50px;
        color: var(--muted);
        font-size: 1.2rem;
    }
    @media (max-width: 768px) {
        .filters-container {
            flex-direction: column;
            align-items: stretch;
        }
        .stats-info {
            text-align: center;
        }
    }
</style>
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

      <!-- Contenedor de filtros - versión optimizada -->
      <div class="filters-container">
        <div class="search-box">
          <input type="text" id="searchInput" placeholder="Buscar equipo...">
        </div>
        <div class="filter-select">
          <select id="grupoSelect">
            <option value="todos">Todos los grupos</option>
            <?php foreach ($grupos as $grupo): ?>
              <option value="<?php echo $grupo['grupo']; ?>">Grupo <?php echo $grupo['grupo']; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="stats-info" id="statsInfo">
          <span id="equiposMostrados">0</span> de <span id="equiposTotal">0</span> equipos
        </div>
      </div>

      <!-- Carrusel de equipos -->
      <section>
        <div class="carousel-container">
          <button class="carousel-btn prev" id="prevBtn">❮</button>
          <div class="teams-grid" id="teamsGrid">
            <?php foreach ($todos_equipos as $equipo): ?>
            <article class="team-card" data-equipo-id="<?php echo $equipo['id']; ?>" 
                     data-equipo-nombre="<?php echo strtolower(htmlspecialchars($equipo['nombre'])); ?>" 
                     data-equipo-grupo="<?php echo $equipo['grupo']; ?>">
              <div class="team-head">
                <img class="bandera-real" src="<?php echo $banderas_precargadas[$equipo['id']]; ?>" 
                     alt="<?php echo htmlspecialchars($equipo['nombre']); ?>"
                     style="width: 50px; height: 38px; object-fit: cover; border-radius: 8px;"
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
        <div id="noResults" class="no-results" style="display: none;">
          No se encontraron equipos con esa búsqueda o filtro.
        </div>
      </section>
    </main>
  </div>

  <script>
    const grid = document.getElementById('teamsGrid');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const searchInput = document.getElementById('searchInput');
    const grupoSelect = document.getElementById('grupoSelect');
    const equiposMostradosSpan = document.getElementById('equiposMostrados');
    const equiposTotalSpan = document.getElementById('equiposTotal');
    const noResultsDiv = document.getElementById('noResults');
    
    let tarjetas = Array.from(document.querySelectorAll('.team-card'));
    let filtroGrupoActual = 'todos';
    let busquedaActual = '';
    
    function actualizarFiltros() {
      let visibles = 0;
      
      tarjetas.forEach(card => {
        const nombre = card.dataset.equipoNombre || '';
        const grupo = card.dataset.equipoGrupo || '';
        
        const coincideBusqueda = nombre.includes(busquedaActual.toLowerCase());
        const coincideGrupo = filtroGrupoActual === 'todos' || grupo === filtroGrupoActual;
        
        if (coincideBusqueda && coincideGrupo) {
          card.style.display = '';
          visibles++;
        } else {
          card.style.display = 'none';
        }
      });
      
      equiposMostradosSpan.textContent = visibles;
      equiposTotalSpan.textContent = tarjetas.length;
      noResultsDiv.style.display = visibles === 0 ? 'block' : 'none';
      
      // Reset scroll al filtrar
      scrollAmount = 0;
      grid.scrollTo({ left: 0, behavior: 'auto' });
    }
    
    // Búsqueda
    searchInput.addEventListener('input', (e) => {
      busquedaActual = e.target.value;
      actualizarFiltros();
    });
    
    // Select de grupo
    grupoSelect.addEventListener('change', (e) => {
      filtroGrupoActual = e.target.value;
      actualizarFiltros();
    });
    
    // Carrusel
    let scrollAmount = 0;
    const cardWidth = 330;
    
    function getVisibleCards() {
      return tarjetas.filter(card => card.style.display !== 'none');
    }
    
    nextBtn.addEventListener('click', () => {
      const visibles = getVisibleCards();
      if (visibles.length === 0) return;
      const maxScroll = grid.scrollWidth - grid.clientWidth;
      scrollAmount = Math.min(scrollAmount + cardWidth * 2, maxScroll);
      grid.scrollTo({ left: scrollAmount, behavior: 'smooth' });
    });
    
    prevBtn.addEventListener('click', () => {
      scrollAmount = Math.max(scrollAmount - cardWidth * 2, 0);
      grid.scrollTo({ left: scrollAmount, behavior: 'smooth' });
    });
    
    // Inicializar contador
    equiposTotalSpan.textContent = tarjetas.length;
    equiposMostradosSpan.textContent = tarjetas.length;
  </script>
</body>
</html>