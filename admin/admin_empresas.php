<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';
 
requiereAutenticacion();
requiereAdmin();
 
$msg  = '';
$tipo = '';
 
// ── Función para parsear mensaje ──────────────────────────────
function parsearMensaje(string $msg): array {
    $datos = ['tipo' => '', 'campos' => []];
    $lineas = explode("\n", trim($msg));
    $primera = strtoupper(trim($lineas[0] ?? ''));
    if (str_contains($primera, 'HOTEL'))           $datos['tipo'] = 'hotel';
    elseif (str_contains($primera, 'RESTAURANTE')) $datos['tipo'] = 'restaurante';
    elseif (str_contains($primera, 'ACTUALIZA'))   $datos['tipo'] = 'actualizacion';
    elseif (str_contains($primera, 'NOVEDAD'))     $datos['tipo'] = 'novedad';
    foreach (array_slice($lineas, 1) as $linea) {
        if (str_contains($linea, ':')) {
            [$k, $v] = explode(':', $linea, 2);
            $datos['campos'][mb_strtolower(trim($k))] = trim($v);
        }
    }
    return $datos;
}
 
// ── Acciones POST ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
 
    // Aprobar hotel
    if ($accion === 'aprobar_hotel') {
        $notif_id   = (int)$_POST['notif_id'];
        $empresa_id = (int)$_POST['empresa_id'];
        try {
            $pdo->prepare("
                INSERT INTO hoteles (nombre, descripcion, precio, ubicacion, capacidad, empresa_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([
                $_POST['nombre']      ?? '',
                $_POST['descripcion'] ?? '',
                (float)preg_replace('/[^0-9.]/', '', $_POST['precio'] ?? '0'),
                $_POST['direccion']   ?? '',
                (int)($_POST['habitaciones'] ?? 0),
                $empresa_id
            ]);
            $pdo->prepare('UPDATE notificaciones_admin SET leido = 1 WHERE id = ?')->execute([$notif_id]);
            $msg  = ' Hotel aprobado y registrado correctamente.';
            $tipo = 'success';
        } catch (PDOException $e) {
            $msg  = 'Error: ' . $e->getMessage();
            $tipo = 'error';
        }
    }
 
    // Aprobar restaurante
    if ($accion === 'aprobar_restaurante') {
        $notif_id   = (int)$_POST['notif_id'];
        $empresa_id = (int)$_POST['empresa_id'];
        try {
            $pdo->prepare("
                INSERT INTO gastronomia (nombre, tipo, restaurante, direccion, precio_promedio, descripcion, empresa_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $_POST['nombre']      ?? '',
                $_POST['cocina']      ?? '',
                $_POST['nombre']      ?? '',
                $_POST['direccion']   ?? '',
                (float)preg_replace('/[^0-9.]/', '', $_POST['precio'] ?? '0'),
                $_POST['descripcion'] ?? '',
                $empresa_id
            ]);
            $pdo->prepare('UPDATE notificaciones_admin SET leido = 1 WHERE id = ?')->execute([$notif_id]);
            $msg  = ' Restaurante aprobado y registrado correctamente.';
            $tipo = 'success';
        } catch (PDOException $e) {
            $msg  = 'Error: ' . $e->getMessage();
            $tipo = 'error';
        }
    }
 
    // Rechazar solicitud
    if ($accion === 'rechazar_solicitud') {
        $nid = (int)$_POST['notif_id'];
        $pdo->prepare('UPDATE notificaciones_admin SET leido = 1 WHERE id = ?')->execute([$nid]);
        $msg  = 'Solicitud rechazada.';
        $tipo = 'success';
    }
 
    // Aprobar empresa
    if ($accion === 'aprobar_empresa') {
        $eid = (int)$_POST['empresa_id'];
        $pdo->prepare('UPDATE empresas SET aprobado = 1 WHERE id = ?')->execute([$eid]);
        $pdo->prepare('UPDATE usuarios u JOIN empresas e ON e.usuario_id = u.id SET u.estado = "activo" WHERE e.id = ?')->execute([$eid]);
        $msg  = 'Empresa aprobada correctamente.';
        $tipo = 'success';
    }
 
    // Rechazar empresa
    if ($accion === 'rechazar_empresa') {
        $eid = (int)$_POST['empresa_id'];
        $pdo->prepare('UPDATE usuarios u JOIN empresas e ON e.usuario_id = u.id SET u.estado = "bloqueado" WHERE e.id = ?')->execute([$eid]);
        $msg  = 'Empresa rechazada y usuario bloqueado.';
        $tipo = 'success';
    }
 
    // Bloquear usuario
    if ($accion === 'bloquear_usuario') {
        $pdo->prepare('UPDATE usuarios SET estado = "bloqueado" WHERE id = ? AND rol != "admin"')->execute([(int)$_POST['usuario_id']]);
        $msg  = 'Usuario bloqueado.';
        $tipo = 'success';
    }
 
    // Activar usuario
    if ($accion === 'activar_usuario') {
        $pdo->prepare('UPDATE usuarios SET estado = "activo" WHERE id = ? AND rol != "admin"')->execute([(int)$_POST['usuario_id']]);
        $msg  = 'Usuario reactivado.';
        $tipo = 'success';
    }
 
    // Marcar leída
    if ($accion === 'marcar_leida') {
        marcarNotificacionLeida($pdo, (int)$_POST['notif_id']);
        $msg  = 'Notificación marcada como leída.';
        $tipo = 'success';
    }
 
    // Marcar todas leídas
    if ($accion === 'marcar_todas_leidas') {
        $pdo->exec('UPDATE notificaciones_admin SET leido = 1');
        $msg  = 'Todas las notificaciones marcadas como leídas.';
        $tipo = 'success';
    }
}
 
// ── Datos ─────────────────────────────────────────────────────
$notificaciones = $pdo->query("
    SELECT n.*, e.nombre AS empresa_nombre, e.id AS emp_id
    FROM notificaciones_admin n
    JOIN empresas e ON e.id = n.empresa_id
    WHERE n.leido = 0
    ORDER BY n.creado_en DESC
")->fetchAll();
 
$notif_count = count($notificaciones);
 
$empresas = $pdo->query("
    SELECT e.id, e.nombre AS empresa_nombre, e.telefono, e.direccion, e.aprobado,
           e.creado_en AS empresa_creada,
           u.id AS usuario_id, u.nombre AS usuario_nombre, u.correo, u.estado
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
    <style>
        .sol-card { border:1px solid #e5e7eb; border-radius:12px; padding:1.2rem; margin-bottom:1rem; background:#fffbeb; }
        .sol-card.hotel-card     { border-left:4px solid #3b82f6; }
        .sol-card.rest-card      { border-left:4px solid #f59e0b; }
        .sol-card.otra-card      { border-left:4px solid #9ca3af; background:#f9fafb; }
        .sol-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:.8rem; flex-wrap:wrap; gap:8px; }
        .sol-empresa { font-weight:700; font-size:.95rem; color:#1a2744; }
        .sol-fecha   { font-size:.75rem; color:#9ca3af; margin-top:2px; }
        .sol-tipo    { font-size:.75rem; font-weight:600; padding:3px 10px; border-radius:20px; }
        .tipo-hotel  { background:#dbeafe; color:#1e40af; }
        .tipo-rest   { background:#fef3c7; color:#92400e; }
        .tipo-otra   { background:#f3f4f6; color:#374151; }
        .sol-campos  { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:8px; margin:.8rem 0; }
        .sol-campo   { background:#f9fafb; border-radius:8px; padding:6px 10px; border:1px solid #e5e7eb; }
        .sol-campo .cl { font-size:.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; }
        .sol-campo .cv { font-size:.87rem; color:#1a2744; font-weight:500; margin-top:2px; }
        .sol-acciones { display:flex; gap:8px; margin-top:.8rem; padding-top:.8rem; border-top:1px solid #e5e7eb; flex-wrap:wrap; }
        .btn-aprobar  { background:#10b981; color:#fff; padding:6px 14px; border:none; border-radius:8px; font-size:.82rem; font-weight:600; cursor:pointer; transition:opacity .15s; }
        .btn-rechazar { background:#ef4444; color:#fff; padding:6px 14px; border:none; border-radius:8px; font-size:.82rem; font-weight:600; cursor:pointer; transition:opacity .15s; }
        .btn-aprobar:hover, .btn-rechazar:hover { opacity:.85; }
        .empty-notif  { text-align:center; padding:2rem; color:#6b7280; background:#f9fafb; border-radius:10px; border:1px dashed #e5e7eb; }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-brand"><h2> FlowZone Admin</h2></div>
        <nav class="admin-nav">
            <a href="/FLOWZONE/admin/dashboard.php"> Dashboard</a>
            <a href="/FLOWZONE/admin/admin_empresas.php" class="active">
                🏢 Empresas
                <?php if ($notif_count > 0): ?>
                    <span class="admin-notif-badge"><?= $notif_count ?></span>
                <?php endif; ?>
            </a>
            <a href="/FLOWZONE/admin/admin_lugares.php"> Lugares</a>
            <a href="/FLOWZONE/admin/admin_hoteles.php">Hoteles</a>
            <a href="/FLOWZONE/admin/admin_eventos.php"> Eventos</a>
            <a href="/FLOWZONE/admin/admin_reservas.php"> Reservas</a>
            <a href="/FLOWZONE/index.php"> Volver al Sitio</a>
            <a href="/FLOWZONE/logout.php"> Cerrar Sesión</a>
        </nav>
    </aside>
 
    <main class="admin-main">
        <div class="admin-header">
            <h1> Gestión de Empresas</h1>
            <?php if ($notif_count > 0): ?>
                <span class="badge badge-pendiente"><?= $notif_count ?> solicitud<?= $notif_count > 1 ? 'es' : '' ?> nueva<?= $notif_count > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </div>
 
        <?php if ($msg): ?>
            <div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
 
        <!-- Solicitudes pendientes -->
        <div class="admin-section">
            <h2>
                🔔 Solicitudes pendientes
                <?php if ($notif_count > 0): ?>
                    <form method="POST" style="display:inline;margin-left:1rem;">
                        <input type="hidden" name="accion" value="marcar_todas_leidas">
                        <button class="btn-small btn-edit" type="submit">Marcar todas como leídas</button>
                    </form>
                <?php endif; ?>
            </h2>
 
            <?php if (empty($notificaciones)): ?>
                <div class="empty-notif"> No hay solicitudes pendientes.</div>
            <?php else: ?>
                <?php foreach ($notificaciones as $n):
                    $p   = parsearMensaje($n['mensaje']);
                    $tp  = $p['tipo'];
                    $c   = $p['campos'];
                    $cc  = $tp === 'hotel' ? 'hotel-card' : ($tp === 'restaurante' ? 'rest-card' : 'otra-card');
                    $tl  = $tp === 'hotel' ? ' Hotel' : ($tp === 'restaurante' ? ' Restaurante' : ($tp === 'actualizacion' ? '✏️ Actualización' : '📢 Novedad'));
                    $tcl = $tp === 'hotel' ? 'tipo-hotel' : ($tp === 'restaurante' ? 'tipo-rest' : 'tipo-otra');
                ?>
                <div class="sol-card <?= $cc ?>">
                    <div class="sol-header">
                        <div>
                            <div class="sol-empresa"><?= htmlspecialchars($n['empresa_nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="sol-fecha"><?= substr($n['creado_en'], 0, 16) ?></div>
                        </div>
                        <span class="sol-tipo <?= $tcl ?>"><?= $tl ?></span>
                    </div>
 
                    <?php if (!empty($c)): ?>
                    <div class="sol-campos">
                        <?php foreach ($c as $k => $v): ?>
                        <div class="sol-campo">
                            <div class="cl"><?= htmlspecialchars(ucfirst($k), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="cv"><?= htmlspecialchars($v ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p style="font-size:.87rem;color:#374151;white-space:pre-line;margin:.5rem 0"><?= htmlspecialchars($n['mensaje'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
 
                    <div class="sol-acciones">
                        <?php if ($tp === 'hotel'): ?>
                        <form method="POST" style="display:contents">
                            <input type="hidden" name="accion"       value="aprobar_hotel">
                            <input type="hidden" name="notif_id"     value="<?= $n['id'] ?>">
                            <input type="hidden" name="empresa_id"   value="<?= $n['emp_id'] ?>">
                            <input type="hidden" name="nombre"       value="<?= htmlspecialchars($c['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="descripcion"  value="<?= htmlspecialchars($c['descripción'] ?? $c['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="precio"       value="<?= htmlspecialchars($c['precio aprox'] ?? $c['precio'] ?? '0', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="direccion"    value="<?= htmlspecialchars($c['dirección'] ?? $c['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="habitaciones" value="<?= htmlspecialchars($c['habitaciones'] ?? '0', ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn-aprobar"
                                    onclick="return confirm('¿Aprobar y registrar este hotel?')">
                                 Aprobar hotel
                            </button>
                        </form>
 
                        <?php elseif ($tp === 'restaurante'): ?>
                        <form method="POST" style="display:contents">
                            <input type="hidden" name="accion"      value="aprobar_restaurante">
                            <input type="hidden" name="notif_id"    value="<?= $n['id'] ?>">
                            <input type="hidden" name="empresa_id"  value="<?= $n['emp_id'] ?>">
                            <input type="hidden" name="nombre"      value="<?= htmlspecialchars($c['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="cocina"      value="<?= htmlspecialchars($c['tipo de cocina'] ?? $c['cocina'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="direccion"   value="<?= htmlspecialchars($c['dirección'] ?? $c['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="horario"     value="<?= htmlspecialchars($c['horario'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="precio"      value="<?= htmlspecialchars($c['precio promedio'] ?? $c['precio'] ?? '0', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="descripcion" value="<?= htmlspecialchars($c['descripción'] ?? $c['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn-aprobar"
                                    onclick="return confirm('¿Aprobar y registrar este restaurante?')">
                                ✅ Aprobar restaurante
                            </button>
                        </form>
 
                        <?php else: ?>
                        <form method="POST" style="display:contents">
                            <input type="hidden" name="accion"   value="marcar_leida">
                            <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                            <button type="submit" class="btn-aprobar">✓ Marcar leída</button>
                        </form>
                        <?php endif; ?>
 
                        <form method="POST" style="display:contents">
                            <input type="hidden" name="accion"   value="rechazar_solicitud">
                            <input type="hidden" name="notif_id" value="<?= $n['id'] ?>">
                            <button type="submit" class="btn-rechazar"
                                    onclick="return confirm('¿Rechazar esta solicitud?')">
                                ❌ Rechazar
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
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
                        <tr>
                            <th>Empresa</th>
                            <th>Responsable</th>
                            <th>Correo</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Aprobada</th>
                            <th>Registro</th>
                            <th>Acciones</th>
                        </tr>
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
                                <span class="badge <?= $e['aprobado'] ? 'badge-aprobado' : 'badge-pendiente' ?>">
                                    <?= $e['aprobado'] ? 'Sí' : 'Pendiente' ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($e['empresa_creada'])) ?></td>
                            <td style="display:flex;gap:5px;flex-wrap:wrap;">
                                <?php if (!$e['aprobado']): ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="accion"     value="aprobar_empresa">
                                        <input type="hidden" name="empresa_id" value="<?= $e['id'] ?>">
                                        <button class="btn-small btn-success" type="submit">✓ Aprobar</button>
                                    </form>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="accion"     value="rechazar_empresa">
                                        <input type="hidden" name="empresa_id" value="<?= $e['id'] ?>">
                                        <button class="btn-small btn-delete" type="submit"
                                                onclick="return confirm('¿Rechazar esta empresa?')">✕ Rechazar</button>
                                    </form>
                                <?php elseif ($e['estado'] === 'activo'): ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="accion"     value="bloquear_usuario">
                                        <input type="hidden" name="usuario_id" value="<?= $e['usuario_id'] ?>">
                                        <button class="btn-small btn-warning" type="submit"
                                                onclick="return confirm('¿Bloquear este usuario?')">Bloquear</button>
                                    </form>
                                <?php elseif ($e['estado'] === 'bloqueado'): ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="accion"     value="activar_usuario">
                                        <input type="hidden" name="usuario_id" value="<?= $e['usuario_id'] ?>">
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
 