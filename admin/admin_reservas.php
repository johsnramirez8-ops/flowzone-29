<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';
requiereAutenticacion();
requiereAdmin();

$mensaje = '';

if (isset($_GET['cambiar_estado'])) {
    $id = $_GET['cambiar_estado'];
    $estado = $_GET['estado'];
    $stmt = $pdo->prepare("UPDATE reservas SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $id]);
    $mensaje = 'Estado actualizado';
}

$stmt = $pdo->query("
    SELECT r.*, u.nombre as usuario_nombre, u.correo as usuario_correo, h.nombre as hotel_nombre 
    FROM reservas r 
    JOIN usuarios u ON r.usuario_id = u.id 
    JOIN hoteles h ON r.hotel_id = h.id 
    ORDER BY r.creado_en DESC
");
$reservas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Reservas - FlowZone Admin</title>
    <link rel="stylesheet" href="/FLOWZONE/assets/css/style.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-brand"><h2>🌄 FlowZone Admin</h2></div>
            <nav class="admin-nav">
                <a href="/FLOWZONE/admin/dashboard.php">📊 Dashboard</a>
                <a href="/FLOWZONE/admin/admin_lugares.php">📍 Lugares</a>
                <a href="/FLOWZONE/admin/admin_hoteles.php">🏨 Hoteles</a>
                <a href="/FLOWZONE/admin/admin_eventos.php">📅 Eventos</a>
                <a href="/FLOWZONE/admin/admin_reservas.php" class="active">📋 Reservas</a>
                <a href="/FLOWZONE/logout.php">🚪 Cerrar Sesión</a>
            </nav>
        </aside>
        
        <main class="admin-main">
            <div class="admin-header"><h1>Gestión de Reservas</h1></div>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            
            <div class="admin-section">
                <h2>Todas las Reservas</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Hotel</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Personas</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservas as $reserva): ?>
                            <tr>
                                <td><?php echo $reserva['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($reserva['usuario_nombre']); ?><br>
                                    <small><?php echo htmlspecialchars($reserva['usuario_correo']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($reserva['hotel_nombre']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($reserva['fecha_entrada'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($reserva['fecha_salida'])); ?></td>
                                <td><?php echo $reserva['num_personas']; ?></td>
                                <td>$<?php echo number_format($reserva['precio_total'], 0, ',', '.'); ?></td>
                                <td><span class="badge badge-<?php echo $reserva['estado']; ?>"><?php echo $reserva['estado']; ?></span></td>
                                <td>
                                    <?php if ($reserva['estado'] === 'pendiente'): ?>
                                        <a href="?cambiar_estado=<?php echo $reserva['id']; ?>&estado=confirmada" class="btn-small btn-success">Confirmar</a>
                                        <a href="?cambiar_estado=<?php echo $reserva['id']; ?>&estado=cancelada" class="btn-small btn-delete">Cancelar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
