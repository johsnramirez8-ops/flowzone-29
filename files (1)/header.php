<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$usuario_nombre = $_SESSION['usuario_nombre'] ?? null;
$usuario_rol    = $_SESSION['usuario_rol']    ?? null;

// Contar reservas pendientes del usuario (badge del carrito)
$reservas_count = 0;
if ($usuario_nombre && isset($pdo)) {
    try {
        $stmt_r = $pdo->prepare(
            "SELECT COUNT(*) FROM reservas WHERE usuario_id = ? AND estado IN ('pendiente','confirmada')"
        );
        $stmt_r->execute([$_SESSION['usuario_id']]);
        $reservas_count = (int)$stmt_r->fetchColumn();
    } catch (PDOException $e) { /* tabla no accesible */ }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlowZone - Turismo en Ortega, Tolima</title>
    <link rel="stylesheet" href="/FLOWZONE/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="/FLOWZONE/index.php">🌄 FlowZone</a>
            </div>
            <ul class="nav-menu">
                <li><a href="/FLOWZONE/index.php">Inicio</a></li>
                <li><a href="/FLOWZONE/lugares.php">Lugares</a></li>
                <li><a href="/FLOWZONE/hoteles.php">Hoteles</a></li>
                <li><a href="/FLOWZONE/gastronomia.php">Gastronomía</a></li>
                <li><a href="/FLOWZONE/eventos.php">Eventos</a></li>
                <li><a href="/FLOWZONE/contacto.php">Contacto</a></li>

                <?php if ($usuario_nombre): ?>
                    <li><a href="/FLOWZONE/favoritos.php"> Favoritos</a></li>

                    <!-- Carrito de reservas -->
                    <li>
                        <a href="/FLOWZONE/mis_reservas.php" class="nav-carrito">
                            🛒 Mis Reservas
                            <?php if ($reservas_count > 0): ?>
                                <span class="carrito-badge"><?= $reservas_count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <?php if ($usuario_rol === 'admin'): ?>
                        <li><a href="/FLOWZONE/admin/dashboard.php">📊 Admin</a></li>
                    <?php endif; ?>

                    <?php if ($usuario_rol === 'empresa'): ?>
                        <li><a href="/FLOWZONE/empresa/dashboard.php">🏢 Mi Empresa</a></li>
                    <?php endif; ?>

                    <li class="user-menu">
                        <span>👤 <?= htmlspecialchars($usuario_nombre, ENT_QUOTES, 'UTF-8') ?></span>
                        <a href="/FLOWZONE/logout.php" class="btn-logout">Salir</a>
                    </li>
                <?php else: ?>
                    <li><a href="/FLOWZONE/login.php" class="btn-login">Iniciar Sesión</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <main class="main-content">
