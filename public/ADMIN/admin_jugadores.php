<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: /admin/login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';
$db = new Database();
$conn = $db->connect();

// Crear jugador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_jugador'])) {
    $nombre = trim($_POST['nombre']);
    $numero = $_POST['numero'];
    $posicion = $_POST['posicion'];
    $equipo_id = $_POST['equipo_id'];
    $rareza = $_POST['rareza'];
    $foto = trim($_POST['foto']);
    
    $stmt = $conn->prepare("INSERT INTO jugadores (nombre, numero, posicion, equipo_id, rareza, foto) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nombre, $numero, $posicion, $equipo_id, $rareza, $foto]);
    header('Location: /admin/jugadores?success=creado');
    exit;
}

// Eliminar jugador
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $conn->prepare("DELETE FROM jugadores WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: /admin/jugadores?success=eliminado');
    exit;
}

$equipos = $conn->query("SELECT id, nombre FROM equipos ORDER BY nombre")->fetchAll();
$jugadores = $conn->query("SELECT j.*, e.nombre as equipo_nombre FROM jugadores j JOIN equipos e ON j.equipo_id = e.id ORDER BY e.nombre, j.numero")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Jugadores</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        --turquesa: #08b8b3;
        --turquesa-oscuro: #069792;
        --rojo: #f12b4d;
        --amarillo: #f7c600;
        --blanco: #ffffff;
        --texto: #163049;
        --texto-suave: #46637a;
        --panel: rgba(255, 255, 255, 0.95);
        --borde: rgba(255, 255, 255, 0.25);
        --sombra: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Montserrat', sans-serif;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        min-height: 100vh;
    }

    /* Header */
    .admin-hero {
        background: linear-gradient(135deg, var(--turquesa), var(--turquesa-oscuro));
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .hero-contenido {
        max-width: 1280px;
        margin: 0 auto;
        padding: 24px 32px;
    }

    .admin-etiqueta {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.75rem;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    h1 {
        color: white;
        font-size: 1.8rem;
        font-weight: 800;
    }

    .admin-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 20px;
    }

    .admin-nav a {
        padding: 10px 24px;
        border-radius: 40px;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
    }

    .admin-nav a:hover {
        background: rgba(255, 255, 255, 0.25);
    }

    .admin-nav a.activo {
        background: white;
        color: var(--turquesa);
    }

    .admin-nav a.salir {
        background: rgba(241, 43, 77, 0.9);
    }

    /* Main Layout */
    .admin-layout {
        max-width: 1280px;
        margin: 32px auto 48px;
        padding: 0 32px;
    }

    /* Cards */
    .card-panel {
        background: var(--panel);
        backdrop-filter: blur(12px);
        border-radius: 28px;
        padding: 32px;
        margin-bottom: 32px;
        border: 1px solid var(--borde);
    }

    .card-panel h2 {
        color: var(--turquesa);
        font-size: 1.5rem;
        margin-bottom: 24px;
        border-left: 4px solid var(--turquesa);
        padding-left: 16px;
    }

    /* Formulario */
    form {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .campo, .campo-completo {
        display: flex;
        flex-direction: column;
    }

    .campo-completo {
        grid-column: 1 / -1;
    }

    label {
        font-weight: 600;
        font-size: 0.8rem;
        margin-bottom: 6px;
        color: var(--texto);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    input, select {
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        font-family: inherit;
        font-size: 0.9rem;
        transition: all 0.3s;
        background: white;
    }

    input:focus, select:focus {
        outline: none;
        border-color: var(--turquesa);
    }

    .form-acciones {
        grid-column: 1 / -1;
        display: flex;
        justify-content: flex-end;
        margin-top: 10px;
    }

    button {
        border: none;
        border-radius: 40px;
        padding: 12px 28px;
        font-family: inherit;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        background: linear-gradient(135deg, var(--turquesa), var(--turquesa-oscuro));
        color: white;
    }

    button:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(8, 184, 179, 0.3);
    }

    /* Filtros */
    .filters-bar {
        background: white;
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 25px;
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
        justify-content: space-between;
    }

    .search-box {
        flex: 2;
        min-width: 200px;
    }

    .search-box input {
        width: 100%;
        padding: 12px 18px;
        border: 2px solid #e2e8f0;
        border-radius: 30px;
        font-size: 0.9rem;
    }

    .filter-group {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .filter-select {
        padding: 10px 18px;
        border: 2px solid #e2e8f0;
        border-radius: 30px;
        background: white;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .stats-badge {
        background: linear-gradient(135deg, var(--turquesa), var(--turquesa-oscuro));
        color: white;
        padding: 8px 18px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 0.85rem;
        white-space: nowrap;
    }

    /* Tabla Responsive */
    .table-container {
        overflow-x: auto;
        border-radius: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 20px;
        overflow: hidden;
    }

    th {
        background: linear-gradient(135deg, var(--turquesa), var(--turquesa-oscuro));
        color: white;
        padding: 15px 12px;
        font-weight: 700;
        font-size: 0.85rem;
        text-align: left;
    }

    td {
        padding: 12px;
        border-bottom: 1px solid #e2e8f0;
        color: var(--texto);
        font-size: 0.85rem;
    }

    tr:hover td {
        background: #f8fafc;
    }

    .rareza-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: bold;
    }

    .rareza-ssr { background: linear-gradient(135deg, #f1c40f, #f39c12); color: #2c3e50; }
    .rareza-sr { background: linear-gradient(135deg, #3498db, #2980b9); color: white; }
    .rareza-r { background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; }
    .rareza-comun { background: #95a5a6; color: white; }

    .btn-editar, .btn-eliminar {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        margin: 0 3px;
        transition: all 0.2s;
    }

    .btn-editar {
        background: linear-gradient(135deg, var(--amarillo), #e5b800);
        color: #3c3200;
    }

    .btn-eliminar {
        background: linear-gradient(135deg, var(--rojo), #c0392b);
        color: white;
    }

    .btn-editar:hover, .btn-eliminar:hover {
        transform: translateY(-1px);
    }

    .toast-message {
        padding: 12px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-weight: 600;
    }

    .toast-message.success {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
        color: white;
    }

    .toast-message.error {
        background: linear-gradient(135deg, var(--rojo), #c0392b);
        color: white;
    }

    /* Responsive */
    @media (max-width: 900px) {
        .admin-layout, .hero-contenido {
            padding-left: 24px;
            padding-right: 24px;
        }
        
        form {
            grid-template-columns: 1fr;
        }
        
        .filters-bar {
            flex-direction: column;
            align-items: stretch;
        }
        
        .stats-badge {
            text-align: center;
        }
    }

    @media (max-width: 768px) {
        .card-panel {
            padding: 24px;
        }
        
        .admin-nav {
            flex-direction: column;
        }
        
        .admin-nav a {
            text-align: center;
        }
        
        th, td {
            padding: 10px 8px;
            font-size: 0.75rem;
        }
        
        .btn-editar, .btn-eliminar {
            padding: 4px 10px;
            font-size: 0.65rem;
        }
    }

    @media (max-width: 640px) {
        .admin-layout {
            padding: 0 20px 30px;
        }
        
        .card-panel {
            padding: 20px;
        }
        
        .filter-group {
            flex-direction: column;
        }
        
        .filter-select {
            width: 100%;
        }
        
        table, thead, tbody, th, td, tr {
            display: block;
        }
        
        thead {
            display: none;
        }
        
        tr {
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 12px;
            background: white;
        }
        
        td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: none;
            padding: 8px 0;
            font-size: 0.8rem;
        }
        
        td:before {
            content: attr(data-label);
            font-weight: 700;
            color: var(--turquesa);
            margin-right: 15px;
        }
        
        .acciones {
            justify-content: flex-end;
            gap: 10px;
        }
    }
</style>
</head>
<body>

<header class="admin-hero">
  <div class="hero-contenido">
    <p class="admin-etiqueta">FIFA World Cup 2026</p>
    <h1>Gestión de Jugadores</h1>
    <nav class="admin-nav">
      <a href="/admin/dashboard">Panel Principal</a>
      <a href="/admin/jugadores" class="activo">Gestionar Jugadores</a>
      <a href="/admin/equipos">Gestionar Equipos</a>
      <a href="/admin/logout" class="salir">Cerrar sesión</a>
    </nav>
  </div>
</header>

<main class="admin-layout">
  <?php if (isset($_GET['success'])): ?>
    <div class="toast-message success">Operación exitosa</div>
  <?php endif; ?>

  <!-- Formulario de registro -->
  <section class="card-panel">
    <h2>Registrar Jugador</h2>
    <form method="POST">
      <div class="campo">
        <label>Nombre</label>
        <input type="text" name="nombre" required>
      </div>
      <div class="campo">
        <label>Número</label>
        <input type="number" name="numero" required>
      </div>
      <div class="campo">
        <label>Posición</label>
        <select name="posicion">
          <option>POR</option><option>DEF</option><option>MED</option><option>DEL</option>
        </select>
      </div>
      <div class="campo">
        <label>Equipo</label>
        <select name="equipo_id">
          <?php foreach($equipos as $e): ?>
            <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['nombre']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="campo">
        <label>Rareza</label>
        <select name="rareza">
          <option value="comun">Común</option>
          <option value="r">R</option>
          <option value="sr">SR</option>
          <option value="ssr">SSR</option>
        </select>
      </div>
      <div class="campo-completo">
        <label>Foto URL</label>
        <input type="text" name="foto" placeholder="/Jugadores/nombre.png">
      </div>
      <div class="form-acciones">
        <button type="submit" name="crear_jugador">Guardar Jugador</button>
      </div>
    </form>
  </section>

  <!-- Lista de jugadores con filtros -->
  <section class="card-panel">
    <h2>Lista de Jugadores</h2>
    
    <div class="filters-bar">
      <div class="search-box">
        <input type="text" id="searchJugador" placeholder="Buscar jugador...">
      </div>
      <div class="filter-group">
        <select id="filterEquipo" class="filter-select">
          <option value="todos">Todos los equipos</option>
          <?php foreach($equipos as $e): ?>
            <option value="<?php echo strtolower(htmlspecialchars($e['nombre'])); ?>"><?php echo htmlspecialchars($e['nombre']); ?></option>
          <?php endforeach; ?>
        </select>
        <select id="filterRareza" class="filter-select">
          <option value="todos">Todas las rarezas</option>
          <option value="ssr">SSR</option>
          <option value="sr">SR</option>
          <option value="r">R</option>
          <option value="comun">Común</option>
        </select>
        <select id="filterPosicion" class="filter-select">
          <option value="todos">Todas las posiciones</option>
          <option value="POR">Portero</option>
          <option value="DEF">Defensa</option>
          <option value="MED">Mediocampista</option>
          <option value="DEL">Delantero</option>
        </select>
      </div>
      <div class="stats-badge">
        <span id="jugadoresMostrados">0</span> de <span id="jugadoresTotal">0</span>
      </div>
    </div>

    <div class="table-container">
      <table id="tablaJugadores">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Número</th>
            <th>Pos</th>
            <th>Equipo</th>
            <th>Rareza</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($jugadores as $j): ?>
          <tr data-id="<?php echo $j['id']; ?>" 
              data-nombre="<?php echo strtolower(htmlspecialchars($j['nombre'])); ?>"
              data-equipo="<?php echo strtolower(htmlspecialchars($j['equipo_nombre'])); ?>"
              data-rareza="<?php echo $j['rareza']; ?>"
              data-posicion="<?php echo $j['posicion']; ?>">
            <td data-label="ID"><?php echo $j['id']; ?></td>
            <td data-label="Nombre"><?php echo htmlspecialchars($j['nombre']); ?></td>
            <td data-label="Número"><?php echo $j['numero']; ?></td>
            <td data-label="Pos"><?php echo $j['posicion']; ?></td>
            <td data-label="Equipo"><?php echo htmlspecialchars($j['equipo_nombre']); ?></td>
            <td data-label="Rareza"><span class="rareza-badge rareza-<?php echo $j['rareza']; ?>"><?php echo strtoupper($j['rareza']); ?></span></td>
            <td data-label="Acciones" class="acciones">
              <a href="/admin/editar_jugador?id=<?php echo $j['id']; ?>"><button type="button" class="btn-editar">Editar</button></a>
              <a href="?eliminar=<?php echo $j['id']; ?>" onclick="return confirm('¿Eliminar jugador?')"><button type="button" class="btn-eliminar">Eliminar</button></a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<script>
    const filas = Array.from(document.querySelectorAll('#tablaJugadores tbody tr'));
    const searchInput = document.getElementById('searchJugador');
    const filterEquipo = document.getElementById('filterEquipo');
    const filterRareza = document.getElementById('filterRareza');
    const filterPosicion = document.getElementById('filterPosicion');
    const jugadoresMostradosSpan = document.getElementById('jugadoresMostrados');
    const jugadoresTotalSpan = document.getElementById('jugadoresTotal');
    
    let busquedaActual = '';
    let filtroEquipoActual = 'todos';
    let filtroRarezaActual = 'todos';
    let filtroPosicionActual = 'todos';
    
    function actualizarFiltros() {
        let visibles = 0;
        
        filas.forEach(fila => {
            const nombre = fila.dataset.nombre || '';
            const equipo = fila.dataset.equipo || '';
            const rareza = fila.dataset.rareza || '';
            const posicion = fila.dataset.posicion || '';
            
            const coincideBusqueda = nombre.includes(busquedaActual.toLowerCase());
            const coincideEquipo = filtroEquipoActual === 'todos' || equipo === filtroEquipoActual;
            const coincideRareza = filtroRarezaActual === 'todos' || rareza === filtroRarezaActual;
            const coincidePosicion = filtroPosicionActual === 'todos' || posicion === filtroPosicionActual;
            
            if (coincideBusqueda && coincideEquipo && coincideRareza && coincidePosicion) {
                fila.style.display = '';
                visibles++;
            } else {
                fila.style.display = 'none';
            }
        });
        
        jugadoresMostradosSpan.textContent = visibles;
        jugadoresTotalSpan.textContent = filas.length;
    }
    
    searchInput.addEventListener('input', (e) => {
        busquedaActual = e.target.value;
        actualizarFiltros();
    });
    
    filterEquipo.addEventListener('change', (e) => {
        filtroEquipoActual = e.target.value;
        actualizarFiltros();
    });
    
    filterRareza.addEventListener('change', (e) => {
        filtroRarezaActual = e.target.value;
        actualizarFiltros();
    });
    
    filterPosicion.addEventListener('change', (e) => {
        filtroPosicionActual = e.target.value;
        actualizarFiltros();
    });
    
    jugadoresTotalSpan.textContent = filas.length;
    jugadoresMostradosSpan.textContent = filas.length;
</script>

</body>
</html>