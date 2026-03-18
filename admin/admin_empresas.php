<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';

requiereAutenticacion();
requiereAdmin();

$msg  = '';
$tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    switch ($accion) {
        case 'aprobar_empresa':
            $eid = (int)($_POST['empresa_id'] ?? 0);
            if ($eid > 0) {
                $pdo->prepare('UPDATE empresas SET aprobado = 1 WHERE id = ?')->execute([$eid]);
                $pdo->prepare(
                    'UPDATE usuarios u JOIN empresas e ON e.usuario_id = u.id SET u.estado = "activo" WHERE e.id = ?'
                )->execute([$eid]);
                $msg = 'Empresa aprobada. El usuario ya puede iniciar sesión.';
                $tipo = 'success';
            }
            break;

        case 'rechazar_empresa':
            $eid = (int)($_POST['empresa_id'] ?? 0);
            if ($eid > 0) {
                $pdo->prepare(
                    'UPDATE usuarios u JOIN empresas e ON e.usuario_id = u.id SET u.estado = "bloqueado" WHERE e.id = ?'
                )->execute([$eid]);
                $msg = 'Empresa rechazada y usuario bloqueado.';
                $tipo = 'success';
            }
            break;

        case 'bloquear_usuario':
            $uid = (int)($_POST['usuario_id'] ?? 0);
            if ($uid > 0) {
                $pdo->prepare('UPDATE usuarios SET estado = "bloqueado" WHERE id = ? AND rol != "admin"')->execute([$uid]);
                $msg = 'Usuario bloqueado.';
                $tipo = 'success';
            }
            break;

        case 'activar_usuario':
            $uid = (int)($_POST['usuario_id'] ?? 0);
            if ($uid > 0) {
                $pdo->prepare('UPDATE usuarios SET estado = "activo" WHERE id = ? AND rol != "admin"')->execute([$uid]);
                $msg = 'Usuario reactivado.';
                $tipo = 'success';
            }
            break;

        case 'marcar_leida':
            $nid = (int)($_POST['notif_id'] ?? 0);
            if ($nid > 0) {
                marcarNotificacionLeida($pdo, $nid);
                $msg = 'Notificación marcada como leída.';
                $tipo = 'success';
            }
            break;

        case 'marcar_todas_leidas':
            $pdo->exec('UPDATE notificaciones_admin SET leido = 1');
            $msg = 'Todas las notificaciones marcadas como leídas.';
            $tipo = 'success';
            break;
    }
}

$notificaciones = obtenerNotificacionesPendientes($pdo);
$notif_count    = count($notificaciones);

$empresas = $pdo->query("
    SELECT e.id, e.nombre AS empresa_nombre, e.telefono, e.direccion, e.aprobado, e.creado_en AS empresa_creada,
           u.id AS usuario_id, u.nombre AS usuario_nombre, u.correo, u.estado, u.creado_en
    FROM empresas e
    JOIN usuarios u ON u.id = e.usuario_id
    ORDER BY e.aprobado ASC, e.creado_en DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empresas - FlowZone Admin</title>
    <link rel="stylesheet" href="/FLOWZONE/assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-brand"><h2>🌄 FlowZone Admin</h2></div>
        <nav class="admin-nav">
            <a href="/FLOWZONE/admin/dashboard.php">📊 Dashboard</a>
            <a href="/FLOWZONE/admin/admin_empresas.php" class="active">
                🏢 Empresas
                <?php if ($notif_count > 0): ?>
                    <span class="admin-notif-badge"><?= $notif_count ?></span>
                <?php endif; ?>
            </a>
            <a href="/FLOWZONE/admin/admin_lugares.php">📍 Lugares</a>
            <a href="/FLOWZONE/admin/admin_hoteles.php">🏨 Hoteles</a>
            <a href="/FLOWZONE/admin/admin_eventos.php">📅 Eventos</a>
            <a href="/FLOWZONE/admin/admin_reservas.php">📋 Reservas</a>
            <a href="/FLOWZONE/logout.php">🚪 Cerrar Sesión</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1>🏢 Gestión de Empresas</h1>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <!-- Notificaciones pendientes -->
        <div class="admin-section">
            <h2>
                🔔 Notificaciones sin leer
                <?php if ($notif_count > 0): ?>
                    <span class="badge badge-pendiente" style="font-size:0.9rem;margin-left:0.5rem;"><?= $notif_count ?></span>
                    <form method="POST" style="display:inline;margin-left:1rem;">
                        <input type="hidden" name="accion" value="marcar_todas_leidas">
                        <button class="btn-small btn-edit" type="submit">Marcar todas como leídas</button>
                    </form>
                <?php endif; ?>
            </h2>

            <?php if (empty($notificaciones)): ?>
                <p style="color:var(--gray)">No hay notificaciones pendientes. ✅</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead><tr><th>#</th><th>Empresa</th><th>Mensaje</th><th>Fecha</th><th>Acción</th></tr></thead>
                        <tbody>
                        <?php foreach ($notificaciones as $n): ?>
                            <tr>
                                <td><?= (int)$n['id'] ?></td>
                                <td><?= htmlspecialchars($n['empresa_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($n['mensaje'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(substr($n['creado_en'], 0, 16), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="accion"   value="marcar_leida">
                                        <input type="hidden" name="notif_id" value="<?= (int)$n['id'] ?>">
                                        <button class="btn-small btn-success" type="submit">✓ Leída</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Listado de empresas -->
        <div class="admin-section">
            <h2>Empresas registradas (<?= count($empresas) ?>)</h2>
            <?php if (empty($empresas)): ?>
                <p style="color:var(--gray)">No hay empresas registradas aún.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr><th>Empresa</th><th>Responsable</th><th>Correo</th><th>Teléfono</th><th>Estado usuario</th><th>Aprobada</th><th>Registro</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($empresas as $e): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($e['empresa_nombre'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td><?= htmlspecialchars($e['usuario_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($e['correo'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($e['telefono'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-<?= $e['estado'] ?>"><?= $e['estado'] ?></span></td>
                                <td>
                                    <?php if ($e['aprobado']): ?>
                                        <span class="badge badge-aprobado">Sí</span>
                                    <?php else: ?>
                                        <span class="badge badge-pendiente">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($e['empresa_creada'])) ?></td>
                                <td>
                                    <?php if (!$e['aprobado']): ?>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="accion"     value="aprobar_empresa">
                                            <input type="hidden" name="empresa_id" value="<?= (int)$e['id'] ?>">
                                            <button class="btn-small btn-success" type="submit">✓ Aprobar</button>
                                        </form>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="accion"     value="rechazar_empresa">
                                            <input type="hidden" name="empresa_id" value="<?= (int)$e['id'] ?>">
                                            <button class="btn-small btn-delete" type="submit"
                                                    onclick="return confirm('¿Rechazar esta empresa?')">✕ Rechazar</button>
                                        </form>
                                    <?php elseif ($e['estado'] === 'activo'): ?>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="accion"     value="bloquear_usuario">
                                            <input type="hidden" name="usuario_id" value="<?= (int)$e['usuario_id'] ?>">
                                            <button class="btn-small btn-warning" type="submit"
                                                    onclick="return confirm('¿Bloquear este usuario?')">Bloquear</button>
                                        </form>
                                    <?php elseif ($e['estado'] === 'bloqueado'): ?>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="accion"     value="activar_usuario">
                                            <input type="hidden" name="usuario_id" value="<?= (int)$e['usuario_id'] ?>">
                                            <button class="btn-small btn-success" type="submit">Reactivar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
