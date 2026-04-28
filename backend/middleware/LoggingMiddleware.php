<?php
// backend/middleware/LoggingMiddleware.php
class LoggingMiddleware {
    private $conn;
    private $usuario_id;
    
    public function __construct($conn, $usuario_id = null) {
        $this->conn = $conn;
        $this->usuario_id = $usuario_id;
    }
    
    public function log($accion, $detalle = null) {
        if (!$this->usuario_id) return;
        
        $stmt = $this->conn->prepare("
            INSERT INTO historial (usuario_id, descripcion, fecha)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$this->usuario_id, $accion . ($detalle ? ": " . $detalle : '')]);
    }
    
    public function logAbrirSobre($jugadores_obtenidos) {
        $nombres = implode(', ', array_column($jugadores_obtenidos, 'nombre'));
        $this->log("Abrió un sobre", "Obtuvo: {$nombres}");
    }
    
    public function logIntercambio($oferta_id, $jugador_dado, $jugador_recibido) {
        $this->log("Realizó un intercambio", "Oferta #{$oferta_id}: Dio {$jugador_dado}, Recibió {$jugador_recibido}");
    }
    

    public function logCrearOferta($ofrece, $pide) {
        $this->log("Creó una oferta de intercambio", "Ofrece: {$ofrece}, Busca: " . ($pide ?: 'Cualquier estampa'));
    }
}
?>