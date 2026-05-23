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
$equipo_id = $_GET['id'] ?? 0;

// Obtener información del equipo
$stmt = $conn->prepare("SELECT * FROM equipos WHERE id = ?");
$stmt->execute([$equipo_id]);
$equipo = $stmt->fetch();

if (!$equipo) {
    header("Location: /album");
    exit;
}

// Obtener jugadores del equipo
$stmt = $conn->prepare("
    SELECT j.*, 
           CASE WHEN eu.usuario_id IS NOT NULL THEN 1 ELSE 0 END as obtenida,
           COALESCE(eu.cantidad, 0) as cantidad
    FROM jugadores j
    LEFT JOIN estampas_usuario eu ON j.id = eu.jugador_id AND eu.usuario_id = ?
    WHERE j.equipo_id = ?
    ORDER BY j.posicion, j.numero
");
$stmt->execute([$usuario_id, $equipo_id]);
$jugadores = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($equipo['nombre']); ?> - Mundial 2026</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/COLECCIONISTA/ESTILOS/equipoCSS.css">
    <style>
        .filters-bar {
            background: white;
            border-radius: 20px;
            padding: 15px 20px;
            margin-bottom: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: space-between;
        }
        .search-player {
            flex: 1;
            min-width: 200px;
        }
        .search-player input {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e0e4e8;
            border-radius: 30px;
            font-size: 0.9rem;
            outline: none;
        }
        .search-player input:focus {
            border-color: var(--teal);
        }
        .filter-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .filter-select {
            padding: 10px 15px;
            border: 2px solid #e0e4e8;
            border-radius: 30px;
            background: white;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            outline: none;
        }
        .filter-select:focus {
            border-color: var(--teal);
        }
        .stats-badge {
            background: var(--teal);
            color: white;
            padding: 6px 16px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.85rem;
            white-space: nowrap;
        }
        .img-area img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .product-area:hover .img-area img {
            transform: scale(1.05);
        }
        .img-area {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #1a1a2e, #16213e);
        }
        .img-area .faltante-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: #e83b57;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
            z-index: 2;
        }
        @media (max-width: 768px) {
            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .stats-badge {
                text-align: center;
            }
            .filter-group {
                justify-content: center;
            }
            .img-area img {
                height: 220px;
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
                <a href="/album">Mi Álbum</a>
                <a href="/tienda">Tienda</a>
                <a href="/mercado">Mercado</a>
                <a href="/perfil">Perfil</a>
            </nav>
        </header>

        <main class="content-wrap">
            <div class="equipo-header">
                <h1><?php echo htmlspecialchars($equipo['nombre']); ?></h1>
                <a class="back-button" href="/album">← Volver al álbum</a>
            </div>

            <div class="filters-bar">
                <div class="search-player">
                    <input type="text" id="searchJugador" placeholder="Buscar jugador...">
                </div>
                <div class="filter-group">
                    <select id="rarezaSelect" class="filter-select">
                        <option value="todos">Todas las rarezas</option>
                        <option value="ssr">SSR</option>
                        <option value="sr">SR</option>
                        <option value="r">R</option>
                        <option value="comun">Común</option>
                    </select>
                    <select id="estadoSelect" class="filter-select">
                        <option value="todos">Todos</option>
                        <option value="obtenidas">Obtenidas</option>
                        <option value="faltantes">Faltantes</option>
                    </select>
                </div>
                <div class="stats-badge" id="statsJugadores">
                    <span id="jugadoresMostrados">0</span> de <span id="jugadoresTotal">0</span>
                </div>
            </div>

            <section class="wrapper" id="jugadoresGrid">
                <?php foreach ($jugadores as $jugador): ?>
                <div class="product-area" 
                     data-jugador-id="<?php echo $jugador['id']; ?>"
                     data-jugador-nombre="<?php echo strtolower(htmlspecialchars($jugador['nombre'])); ?>"
                     data-jugador-rareza="<?php echo $jugador['rareza']; ?>"
                     data-jugador-obtenida="<?php echo $jugador['obtenida']; ?>">
                    <div class="img-area">
                        <?php if (!$jugador['obtenida']): ?>
                            <div class="faltante-overlay">Carta no obtenida</div>
                        <?php endif; ?>
                        <!-- USAR ENDPOINT PARA OBTENER IMAGEN DESDE BLOB -->
                        <img src="/backend/api/obtener_imagen.php?tipo=jugador&id=<?php echo $jugador['id']; ?>" 
                             alt="<?php echo htmlspecialchars($jugador['nombre']); ?>"
                             onerror="this.src='/Jugadores/silueta.png'">
                        <div class="product-overlay">
                            <div class="overlay">
                                <?php if ($jugador['obtenida']): ?>
                                    <a href="#" class="btn-2 obtenida">
                                        Obtenida x<?php echo $jugador['cantidad']; ?>
                                    </a>
                                <?php else: ?>
                                    <a href="#" class="btn-1 faltante">
                                        Faltante
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="info-area-<?php echo $jugador['rareza']; ?>">
                        <div class="titulo-jugador">
                            <img class="rare" src="/Jugadores/cha_rare_<?php echo $jugador['rareza']; ?>.png" alt="Rareza">
                            <h3><?php echo htmlspecialchars($jugador['nombre']); ?></h3>
                        </div>
                        <div class="product-price">#<?php echo $jugador['numero']; ?> - <?php echo $jugador['posicion']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </section>
        </main>
    </div>

    <script>
        const tarjetas = Array.from(document.querySelectorAll('.product-area'));
        const totalSpan = document.getElementById('jugadoresTotal');
        const mostradosSpan = document.getElementById('jugadoresMostrados');
        const searchInput = document.getElementById('searchJugador');
        const rarezaSelect = document.getElementById('rarezaSelect');
        const estadoSelect = document.getElementById('estadoSelect');
        
        let filtroRareza = 'todos';
        let filtroEstado = 'todos';
        let busqueda = '';
        
        function actualizarFiltros() {
            let visibles = 0;
            
            tarjetas.forEach(tarjeta => {
                const nombre = tarjeta.dataset.jugadorNombre || '';
                const rareza = tarjeta.dataset.jugadorRareza;
                const obtenida = tarjeta.dataset.jugadorObtenida === '1';
                
                const coincideBusqueda = nombre.includes(busqueda.toLowerCase());
                const coincideRareza = filtroRareza === 'todos' || rareza === filtroRareza;
                const coincideEstado = filtroEstado === 'todos' || 
                    (filtroEstado === 'obtenidas' && obtenida) ||
                    (filtroEstado === 'faltantes' && !obtenida);
                
                if (coincideBusqueda && coincideRareza && coincideEstado) {
                    tarjeta.style.display = '';
                    visibles++;
                } else {
                    tarjeta.style.display = 'none';
                }
            });
            
            mostradosSpan.textContent = visibles;
            totalSpan.textContent = tarjetas.length;
        }
        
        searchInput.addEventListener('input', (e) => {
            busqueda = e.target.value;
            actualizarFiltros();
        });
        
        rarezaSelect.addEventListener('change', (e) => {
            filtroRareza = e.target.value;
            actualizarFiltros();
        });
        
        estadoSelect.addEventListener('change', (e) => {
            filtroEstado = e.target.value;
            actualizarFiltros();
        });
        
        totalSpan.textContent = tarjetas.length;
        mostradosSpan.textContent = tarjetas.length;
    </script>
</body>
</html>