<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$usuario_nombre = $_SESSION['usuario_nombre'] ?? null;
$usuario_rol    = $_SESSION['usuario_rol']    ?? null;
$usuario_id_nav = $_SESSION['usuario_id']     ?? null;

// Contar reservas activas para el badge del carrito
$reservas_count = 0;
if ($usuario_nombre && $usuario_id_nav) {
    // Usar $pdo si ya existe, si no conectar propio
    try {
        $pdo_nav = isset($pdo) ? $pdo : new PDO(
            'mysql:host=localhost;dbname=flowzone;charset=utf8mb4',
            'root', '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        $s = $pdo_nav->prepare(
            "SELECT COUNT(*) FROM reservas WHERE usuario_id = ? AND estado IN ('pendiente','confirmada')"
        );
        $s->execute([$usuario_id_nav]);
        $reservas_count = (int)$s->fetchColumn();
    } catch (Exception $e) {
        $reservas_count = 0;
    }
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
                <a href="/FLOWZONE/index.php">FlowZone</a>
            </div>
            <ul class="nav-menu">
                <li><a href="/FLOWZONE/index.php">Inicio</a></li>
                <li><a href="/FLOWZONE/lugares.php">Lugares</a></li>
                <li><a href="/FLOWZONE/hoteles.php">Hoteles</a></li>
                <li><a href="/FLOWZONE/gastronomia.php">Gastronomía</a></li>
                <li><a href="/FLOWZONE/eventos.php">Eventos</a></li>
                <li><a href="/FLOWZONE/contacto.php">Contacto</a></li>

                <?php if ($usuario_nombre): ?>
                    <li><a href="/FLOWZONE/favoritos.php">Favoritos</a></li>

                    <li>
                        <a href="/FLOWZONE/mis_reservas.php" style="position:relative;display:inline-flex;align-items:center;gap:4px;">
                            Mis Reservas
                            <?php if ($reservas_count > 0): ?>
                                <span style="
                                    display:inline-flex;align-items:center;justify-content:center;
                                    background:#f39c12;color:#fff;font-size:0.65rem;font-weight:700;
                                    min-width:18px;height:18px;border-radius:50%;padding:0 3px;
                                    margin-left:2px;line-height:1;
                                "><?= $reservas_count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <?php if ($usuario_rol === 'admin'): ?>
                        <li><a href="/FLOWZONE/admin/dashboard.php"> Admin</a></li>
                    <?php endif; ?>

                    <?php if ($usuario_rol === 'empresa'): ?>
                        <li><a href="/FLOWZONE/empresa/dashboard.php">Mi Empresa</a></li>
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
