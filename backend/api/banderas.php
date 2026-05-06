<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/config/db.php';

function getFlagUrl($pais) {
    $codigos = [
        'México' => 'mx', 'Mexico' => 'mx',
        'Argentina' => 'ar', 'Brasil' => 'br', 'Brazil' => 'br',
        'Francia' => 'fr', 'France' => 'fr', 'España' => 'es',
        'Spain' => 'es', 'Alemania' => 'de', 'Germany' => 'de',
        'Inglaterra' => 'gb', 'England' => 'gb', 'Italia' => 'it', 'Italy' => 'it'
    ];
    
    $codigo = $codigos[$pais] ?? 'unknown';
    return "https://flagpedia.net/data/flags/icon/72x54/{$codigo}.png";
}

// Cache simple en sesión para evitar múltiples llamadas
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pais = $_GET['pais'] ?? '';
    
    if ($pais) {
        $url = getFlagUrl($pais);
        echo json_encode([
            'success' => true,
            'pais' => $pais,
            'flag_url' => $url
        ]);
    } else {
        // Verificar si ya tenemos las banderas en sesión
        if (isset($_SESSION['banderas_cache']) && !empty($_SESSION['banderas_cache'])) {
            echo json_encode([
                'success' => true,
                'banderas' => $_SESSION['banderas_cache'],
                'cached' => true
            ]);
        } else {
            $db = new Database();
            $conn = $db->connect();
            
            $stmt = $conn->prepare("SELECT DISTINCT id, nombre FROM equipos");
            $stmt->execute();
            $equipos = $stmt->fetchAll();
            
            $banderas = [];
            foreach ($equipos as $equipo) {
                $banderas[$equipo['nombre']] = getFlagUrl($equipo['nombre']);
            }
            
            // Guardar en sesión para futuras llamadas
            $_SESSION['banderas_cache'] = $banderas;
            
            echo json_encode([
                'success' => true,
                'banderas' => $banderas,
                'cached' => false
            ]);
        }
    }
}
?>