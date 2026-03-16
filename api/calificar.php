<?php
session_start();
require_once '../includes/conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$tipo = $_POST['tipo'] ?? '';
$item_id = $_POST['item_id'] ?? 0;
$calificacion = $_POST['calificacion'] ?? 0;

if (!in_array($tipo, ['lugar', 'hotel'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo inválido']);
    exit;
}

if ($calificacion < 1 || $calificacion > 5) {
    echo json_encode(['success' => false, 'message' => 'Calificación inválida']);
    exit;
}

try {
    // Verificar si ya existe una calificación
    $stmt = $pdo->prepare("SELECT id FROM calificaciones WHERE usuario_id = ? AND tipo = ? AND item_id = ?");
    $stmt->execute([$usuario_id, $tipo, $item_id]);
    
    if ($stmt->fetch()) {
        // Actualizar calificación existente
        $stmt = $pdo->prepare("UPDATE calificaciones SET calificacion = ? WHERE usuario_id = ? AND tipo = ? AND item_id = ?");
        $stmt->execute([$calificacion, $usuario_id, $tipo, $item_id]);
    } else {
        // Insertar nueva calificación
        $stmt = $pdo->prepare("INSERT INTO calificaciones (usuario_id, tipo, item_id, calificacion) VALUES (?, ?, ?, ?)");
        $stmt->execute([$usuario_id, $tipo, $item_id, $calificacion]);
    }
    
    // Obtener promedio actualizado
    $stmt = $pdo->prepare("SELECT AVG(calificacion) as promedio, COUNT(*) as total FROM calificaciones WHERE tipo = ? AND item_id = ?");
    $stmt->execute([$tipo, $item_id]);
    $resultado = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Calificación guardada',
        'promedio' => round($resultado['promedio'], 1),
        'total' => $resultado['total']
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar calificación']);
}
?>
