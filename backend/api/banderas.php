<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');


function getFlagUrl($pais) {
    // Mapeo de nombres a códigos de país
    $codigos = [
        'México' => 'mx',
        'Mexico' => 'mx',
        'Argentina' => 'ar',
        'Brasil' => 'br',
        'Brazil' => 'br',
        'Francia' => 'fr',
        'France' => 'fr',
        'España' => 'es',
        'Spain' => 'es',
        'Alemania' => 'de',
        'Germany' => 'de',
        'Inglaterra' => 'gb',
        'England' => 'gb',
        'Italia' => 'it',
        'Italy' => 'it'
    ];
    
    $codigo = $codigos[$pais] ?? 'unknown';
    return "https://flagpedia.net/data/flags/icon/72x54/{$codigo}.png";
}

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
        $stmt = $conn->prepare("SELECT DISTINCT nombre FROM equipos");
        $stmt->execute();
        $equipos = $stmt->fetchAll();
        
        $banderas = [];
        foreach ($equipos as $equipo) {
            $banderas[$equipo['nombre']] = getFlagUrl($equipo['nombre']);
        }
        
        echo json_encode([
            'success' => true,
            'banderas' => $banderas
        ]);
    }
}
?>