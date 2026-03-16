<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';

requiereAutenticacion();
requiereAdmin();

// Estadísticas
$total_usuarios   = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'usuario'")->fetchColumn();
$total_empresas   = $pdo->query("SELECT COUNT(*) FROM empresas")->fetchColumn();
$empresas_pend    = $pdo->query("SELECT COUNT(*) FROM empresas WHERE aprobado = 0")->fetchColumn();
$total_lugares    = $pdo->query("SELECT COUNT(*) FROM lugares")->fetchColumn();
$total_hoteles    = $pdo->query("SELECT COUNT(*) FROM hoteles")->fetchColumn();
$reservas_pend    = $pdo->query("SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente'")->fetchColumn();
$total_comentarios= $pdo->query("SELECT COUNT(*) FROM comentarios")->fetchColumn();
$notif_count      = contarNotificacionesPendientes($pdo);

// Últimas reservas
$ultimas_reservas = $pdo->query("
    SELECT r.*, u.nombre as usuario_nombre, h.nombre as hotel_nombre
    FROM reservas r
    JOIN usuarios u ON r.usuario_id = u.id
    JOIN hoteles h ON r.hotel_id = h.id
    ORDER BY r.creado_en DESC LIMIT 5
")->fetchAll();

// Últimas notificaciones
$ultimas_notifs = obtenerNotificacionesPendientes($pdo);
$ultimas_notifs = array_slice($ultimas_notifs, 0, 5);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - FlowZone</title>
    <link rel="stylesheet" href="/FLOWZONE/assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-brand"><h2>🌄 FlowZone Admin</h2></div>
        <nav class="admin-nav">
            <a href="/FLOWZONE/admin/dashboard.php" class="active">📊 Dashboard</a>
            <a href="/FLOWZONE/admin/admin_empresas.php">
                🏢 Empresas
                <?php if ($notif_count > 0): ?>
                    <span class="admin-notif-badge"><?= $notif_count ?></span>
                <?php endif; ?>
            </a>
            <a href="/FLOWZONE/admin/admin_lugares.php">📍 Lugares</a>
            <a href="/FLOWZONE/admin/admin_hoteles.php">🏨 Hoteles</a>
            <a href="/FLOWZONE/admin/admin_eventos.php">📅 Eventos</a>
            <a href="/FLOWZONE/admin/admin_reservas.php">📋 Reservas</a>
            <a href="/FLOWZONE/index.php">🏠 Volver al Sitio</a>
            <a href="/FLOWZONE/logout.php">🚪 Cerrar Sesión</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <div>
                <h1>Dashboard</h1>
                <p>Bienvenido, <?= htmlspecialchars(obtenerUsuarioNombre(), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?php if ($notif_count > 0): ?>
                <a href="/FLOWZONE/admin/admin_empresas.php" class="btn btn-primary">
                    🔔 <?= $notif_count ?> notificación<?= $notif_count > 1 ? 'es' : '' ?> pendiente<?= $notif_count > 1 ? 's' : '' ?>
                </a>
            <?php endif; ?>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info"><h3><?= $total_usuarios ?></h3><p>Usuarios</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏢</div>
                <div class="stat-info">
                    <h3><?= $total_empresas ?></h3>
                    <p>Empresas <?php if ($empresas_pend > 0): ?><span style="color:var(--danger);font-size:0.8rem">(<?= $empresas_pend ?> pend.)</span><?php endif; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📍</div>
                <div class="stat-info"><h3><?= $total_lugares ?></h3><p>Lugares</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏨</div>
                <div class="stat-info"><h3><?= $total_hoteles ?></h3><p>Hoteles</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-info"><h3><?= $reservas_pend ?></h3><p>Reservas Pend.</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💬</div>
                <div class="stat-info"><h3><?= $total_comentarios ?></h3><p>Comentarios</p></div>
            </div>
        </div>

        <?php if (!empty($ultimas_notifs)): ?>
        <div class="admin-section">
            <h2>🔔 Notificaciones recientes <a href="/FLOWZONE/admin/admin_empresas.php" style="font-size:0.85rem;font-weight:normal;margin-left:1rem;">Ver todas →</a></h2>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead><tr><th>Empresa</th><th>Mensaje</th><th>Fecha</th></tr></thead>
                    <tbody>
                    <?php foreach ($ultimas_notifs as $n): ?>
                        <tr>
                            <td><?= htmlspecialchars($n['empresa_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($n['mensaje'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(substr($n['creado_en'], 0, 16), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div class="admin-section">
            <h2>Últimas Reservas</h2>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr><th>ID</th><th>Usuario</th><th>Hotel</th><th>Entrada</th><th>Salida</th><th>Personas</th><th>Total</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ultimas_reservas as $r): ?>
                        <tr>
                            <td><?= $r['id'] ?></td>
                            <td><?= htmlspecialchars($r['usuario_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($r['hotel_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= date('d/m/Y', strtotime($r['fecha_entrada'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($r['fecha_salida'])) ?></td>
                            <td><?= $r['num_personas'] ?></td>
                            <td>$<?= number_format($r['precio_total'], 0, ',', '.') ?></td>
                            <td><span class="badge badge-<?= $r['estado'] ?>"><?= $r['estado'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
