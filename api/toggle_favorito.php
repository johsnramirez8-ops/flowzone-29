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

if (!in_array($tipo, ['lugar', 'hotel'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo inválido']);
    exit;
}

try {
    // Verificar si ya existe en favoritos
    $stmt = $pdo->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND tipo = ? AND item_id = ?");
    $stmt->execute([$usuario_id, $tipo, $item_id]);
    
    if ($stmt->fetch()) {
        // Eliminar de favoritos
        $stmt = $pdo->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND tipo = ? AND item_id = ?");
        $stmt->execute([$usuario_id, $tipo, $item_id]);
        
        echo json_encode([
            'success' => true,
            'action' => 'removed',
            'message' => 'Eliminado de favoritos'
        ]);
    } else {
        // Agregar a favoritos
        $stmt = $pdo->prepare("INSERT INTO favoritos (usuario_id, tipo, item_id) VALUES (?, ?, ?)");
        $stmt->execute([$usuario_id, $tipo, $item_id]);
        
        echo json_encode([
            'success' => true,
            'action' => 'added',
            'message' => 'Agregado a favoritos'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al procesar favorito']);
}
?>
