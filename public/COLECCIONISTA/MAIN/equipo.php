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
           eu.cantidad
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


            <section class="wrapper">
                <?php foreach ($jugadores as $jugador): ?>
                <div class="product-area">
                    <div class="img-area">
                        <?php if ($jugador['foto'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $jugador['foto'])): ?>
                            <img src="<?php echo $jugador['foto']; ?>" alt="<?php echo htmlspecialchars($jugador['nombre']); ?>">
                        <?php else: ?>
                            <img src="/Jugadores/silueta.png" alt="Jugador desconocido">
                        <?php endif; ?>
                        <div class="product-overlay">
                            <div class="overlay">
                                <?php if ($jugador['obtenida']): ?>
                                    <a href="#" class="btn-2 obtenida" data-jugador-id="<?php echo $jugador['id']; ?>">
                                         Obtenida x<?php echo $jugador['cantidad']; ?>
                                    </a>
                                <?php else: ?>
                                    <a href="#" class="btn-1 faltante" data-jugador-id="<?php echo $jugador['id']; ?>">
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
</body>
</html>