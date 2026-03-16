<?php
require_once '../includes/conexion.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode(['lugares' => [], 'hoteles' => []]);
    exit;
}

try {
    // Buscar lugares
    $stmt = $pdo->prepare("SELECT id, nombre, categoria, imagen FROM lugares WHERE nombre LIKE ? OR descripcion LIKE ? LIMIT 5");
    $stmt->execute(["%$query%", "%$query%"]);
    $lugares = $stmt->fetchAll();
    
    // Buscar hoteles
    $stmt = $pdo->prepare("SELECT id, nombre, precio, imagen FROM hoteles WHERE nombre LIKE ? OR descripcion LIKE ? LIMIT 5");
    $stmt->execute(["%$query%", "%$query%"]);
    $hoteles = $stmt->fetchAll();
    
    echo json_encode([
        'lugares' => $lugares,
        'hoteles' => $hoteles
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la búsqueda']);
}
?>
