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
$lugar_id = $_POST['lugar_id'] ?? 0;
$comentario = trim($_POST['comentario'] ?? '');

if (empty($comentario)) {
    echo json_encode(['success' => false, 'message' => 'El comentario no puede estar vacío']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO comentarios (usuario_id, lugar_id, comentario) VALUES (?, ?, ?)");
    $stmt->execute([$usuario_id, $lugar_id, $comentario]);
    
    // Obtener el comentario recién insertado con el nombre del usuario
    $comentario_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("
        SELECT c.*, u.nombre as usuario_nombre 
        FROM comentarios c 
        JOIN usuarios u ON c.usuario_id = u.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$comentario_id]);
    $nuevo_comentario = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Comentario publicado',
        'comentario' => $nuevo_comentario
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al publicar comentario']);
}
?>
