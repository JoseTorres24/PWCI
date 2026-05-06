<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    header("Location: /acceso");
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/middleware/AuthMiddleware.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/middleware/LoggingMiddleware.php';

$usuario = AuthMiddleware::verificarAutenticacion();

$db = new Database();
$conn = $db->connect();

$usuario_id = $_SESSION['usuario']['id'];
$logger = new LoggingMiddleware($conn, $usuario_id);

// Obtener sobres disponibles
$stmt = $conn->prepare("SELECT * FROM sobres");
$stmt->execute();
$sobres = $stmt->fetchAll();

// Obtener últimas cartas obtenidas (todas, no solo México)
$stmt = $conn->prepare("
    SELECT j.*, eu.cantidad, eq.nombre as equipo_nombre
    FROM estampas_usuario eu
    JOIN jugadores j ON eu.jugador_id = j.id
    JOIN equipos eq ON j.equipo_id = eq.id
    WHERE eu.usuario_id = ?
    ORDER BY eu.jugador_id DESC
    LIMIT 4
");
$stmt->execute([$usuario_id]);
$ultimas_cartas = $stmt->fetchAll();

// Procesar apertura de sobre (usando AJAX)
$mensaje = '';
$nuevas_cartas_ajax = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['abrir_sobre_ajax'])) {
    header('Content-Type: application/json');
    $sobre_id = $_POST['sobre_id'] ?? 1;
    
    // Obtener jugadores del sobre con probabilidades
    $stmt = $conn->prepare("
        SELECT sj.*, j.*, eq.nombre as equipo_nombre
        FROM sobre_jugadores sj
        JOIN jugadores j ON sj.jugador_id = j.id
        JOIN equipos eq ON j.equipo_id = eq.id
        WHERE sj.sobre_id = ?
    ");
    $stmt->execute([$sobre_id]);
    $posibles_jugadores = $stmt->fetchAll();
    
    // Seleccionar 3 cartas según probabilidades
    // Seleccionar 3 cartas según probabilidades (balanceado)
    $cartas_obtenidas = [];
    $ids_usados = [];

    for ($i = 0; $i < 3; $i++) {
        // Filtrar jugadores que no hayan salido en esta ronda
        $disponibles = array_filter($posibles_jugadores, function($j) use ($ids_usados) {
            return !in_array($j['id'], $ids_usados);
        });
        
        if (empty($disponibles)) break;
        
        // Calcular total de probabilidades de los disponibles
        $total_prob = array_sum(array_column($disponibles, 'probabilidad'));
        
        $rand = mt_rand(1, $total_prob);
        $acumulado = 0;
        
        foreach ($disponibles as $jugador) {
            $acumulado += $jugador['probabilidad'];
            if ($rand <= $acumulado) {
                $cartas_obtenidas[] = $jugador;
                $ids_usados[] = $jugador['id'];
                break;
            }
        }
    }
    
    // Guardar las cartas obtenidas
    foreach ($cartas_obtenidas as $carta) {
        $stmt = $conn->prepare("
            INSERT INTO estampas_usuario (usuario_id, jugador_id, cantidad) 
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE cantidad = cantidad + 1
        ");
        $stmt->execute([$usuario_id, $carta['id']]);
    }
    
    // Registrar en el log
    if (!empty($cartas_obtenidas)) {
        $logger->logAbrirSobre($cartas_obtenidas);
    }
    
    echo json_encode([
        'success' => true,
        'cartas' => $cartas_obtenidas,
        'mensaje' => '¡Has obtenido ' . count($cartas_obtenidas) . ' nuevas estampas!'
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Tienda - Mundial 2026</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/COLECCIONISTA/ESTILOS/tiendaCSS.css">
<style>
    /* Estilos para el modal */
    .modal-cartas {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.8);
        backdrop-filter: blur(5px);
    }
    .modal-content-cartas {
        background: linear-gradient(135deg, #1a1a2e, #16213e);
        margin: 5% auto;
        padding: 30px;
        border-radius: 30px;
        width: 90%;
        max-width: 800px;
        text-align: center;
        animation: modalAnim 0.3s ease;
    }
    @keyframes modalAnim {
        from { transform: scale(0.8); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .modal-content-cartas h2 {
        color: #f1c40f;
        margin-bottom: 20px;
        font-size: 1.8rem;
    }
    .cartas-container {
        display: flex;
        justify-content: center;
        gap: 20px;
        flex-wrap: wrap;
        margin: 25px 0;
    }
    .carta-modal {
        background: white;
        border-radius: 20px;
        padding: 15px;
        text-align: center;
        min-width: 160px;
        transition: transform 0.2s;
        animation: cartaAnim 0.3s ease;
    }
    @keyframes cartaAnim {
        from { transform: translateY(50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .carta-modal:hover {
        transform: translateY(-5px);
    }
    .carta-modal img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 15px;
        margin-bottom: 10px;
    }
    .carta-modal h4 {
        color: #333;
        margin: 5px 0;
    }
    .carta-modal p {
        font-size: 0.8rem;
        color: #666;
    }
    .carta-modal.ssr { background: linear-gradient(135deg, #f1c40f, #f39c12); }
    .carta-modal.sr { background: linear-gradient(135deg, #3498db, #2980b9); color: white; }
    .carta-modal.r { background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; }
    .close-modal {
        background: #e83b57;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 30px;
        font-size: 1rem;
        cursor: pointer;
        margin-top: 15px;
        transition: all 0.2s;
    }
    .close-modal:hover {
        transform: scale(1.05);
    }
    .loading-btn {
        opacity: 0.6;
        cursor: not-allowed;
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
        <a class="activo" href="/tienda">Tienda</a>
        <a href="/mercado">Mercado</a>
        <a href="/perfil">Perfil</a>
      </nav>
    </header>

    <main class="content-wrap">
      <section class="hero-card centered">
        <div>
          <p class="eyebrow">Sobres disponibles</p>
          <h1>Tienda de Sobres</h1>
          <p class="hero-text">Abre sobres y añade nuevas cartas a tu colección oficial.</p>
        </div>
        <a class="home-button" href="/dashboard">Regresar al inicio</a>
      </section>

      <section class="pack-area">
        <?php foreach ($sobres as $sobre): ?>
        <article class="pack-card">
          <h2><?php echo htmlspecialchars($sobre['nombre']); ?></h2>
          <p><?php echo $sobre['cantidad_cartas']; ?> estampas por sobre</p>
          <button class="abrir-sobre-btn" data-sobre-id="<?php echo $sobre['id']; ?>">Abrir Sobre</button>
          <span class="available">Sobres disponibles: ilimitados</span>
        </article>
        <?php endforeach; ?>
      </section>

      <section class="obtained-section">
        <h2>Últimas cartas obtenidas</h2>
        <div class="cards-grid" id="ultimasCartas">
          <?php if (!empty($ultimas_cartas)): ?>
            <?php foreach ($ultimas_cartas as $carta): ?>
            <article class="sticker-card <?php echo $carta['rareza']; ?>">
              <img src="<?php echo $carta['foto'] ?: '/Jugadores/silueta.png'; ?>" alt="<?php echo htmlspecialchars($carta['nombre']); ?>">
              <p><?php echo htmlspecialchars($carta['nombre']); ?></p>
              <small><?php echo htmlspecialchars($carta['equipo_nombre']); ?></small>
              <span class="badge">x<?php echo $carta['cantidad']; ?></span>
            </article>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="no-cards">Aún no tienes cartas. ¡Abre un sobre para comenzar!</p>
          <?php endif; ?>
        </div>
      </section>
    </main>
  </div>

  <!-- Modal de cartas obtenidas -->
  <div id="modalCartas" class="modal-cartas">
    <div class="modal-content-cartas">
      <span class="close-modal-btn" style="float: right; font-size: 28px; cursor: pointer; color: white;">&times;</span>
      <h2>¡Cartas Obtenidas!</h2>
      <div id="cartasContainer" class="cartas-container"></div>
      <button class="close-modal" onclick="cerrarModal()">Continuar</button>
    </div>
  </div>

  <script>
    const modal = document.getElementById('modalCartas');
    const cartasContainer = document.getElementById('cartasContainer');
    
    function cerrarModal() {
      modal.style.display = 'none';
      location.reload();
    }
    
    // Cerrar con X
    document.querySelector('.close-modal-btn').onclick = cerrarModal;
    
    // Cerrar haciendo clic fuera
    window.onclick = function(event) {
      if (event.target == modal) {
        cerrarModal();
      }
    }
    
    // Mostrar toast temporal
    function showToast(message, type) {
      const toast = document.createElement('div');
      toast.className = `toast-message ${type}`;
      toast.textContent = message;
      toast.style.position = 'fixed';
      toast.style.bottom = '30px';
      toast.style.right = '30px';
      toast.style.padding = '14px 24px';
      toast.style.borderRadius = '12px';
      toast.style.zIndex = '1001';
      toast.style.animation = 'slideIn 0.3s ease';
      document.body.appendChild(toast);
      
      setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    }
    
    // Abrir sobre con AJAX
    document.querySelectorAll('.abrir-sobre-btn').forEach(btn => {
      btn.addEventListener('click', async function() {
        const sobreId = this.dataset.sobreId;
        const originalText = this.textContent;
        
        this.textContent = 'Abriendo...';
        this.classList.add('loading-btn');
        this.disabled = true;
        
        try {
          const formData = new FormData();
          formData.append('abrir_sobre_ajax', '1');
          formData.append('sobre_id', sobreId);
          
          const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
          });
          
          const data = await response.json();
          
          if (data.success && data.cartas.length > 0) {
            // Mostrar cartas en modal
            cartasContainer.innerHTML = '';
            data.cartas.forEach((carta, index) => {
              const cartaDiv = document.createElement('div');
              cartaDiv.className = `carta-modal ${carta.rareza}`;
              cartaDiv.style.animationDelay = `${index * 0.1}s`;
              cartaDiv.innerHTML = `
                <img src="${carta.foto || '/Jugadores/silueta.png'}" alt="${carta.nombre}">
                <h4>${carta.nombre}</h4>
                <p>${carta.equipo_nombre || 'Selección'}</p>
                <p>${carta.rareza.toUpperCase()}</p>
              `;
              cartasContainer.appendChild(cartaDiv);
            });
            modal.style.display = 'block';
          } else if (data.success && data.cartas.length === 0) {
            showToast('No se obtuvieron cartas. Intenta de nuevo.', 'error');
          } else {
            showToast(data.error || 'Error al abrir el sobre', 'error');
          }
        } catch (error) {
          console.error('Error:', error);
          showToast('Error al conectar con el servidor', 'error');
        } finally {
          this.textContent = originalText;
          this.classList.remove('loading-btn');
          this.disabled = false;
        }
      });
    });
  </script>
</body>
</html>