<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';

requiereRol(['empresa']);

$usuarioId = obtenerUsuarioId();
$msg  = '';
$tipo = '';

// Obtener datos de la empresa
$stmt = $pdo->prepare(
    'SELECT e.*, u.correo, u.telefono AS tel_usuario, u.estado, u.nombre AS nombre_usuario
     FROM empresas e
     JOIN usuarios u ON u.id = e.usuario_id
     WHERE e.usuario_id = ? LIMIT 1'
);
$stmt->execute([$usuarioId]);
$empresa = $stmt->fetch();

// Procesar solicitud de cambio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $empresa) {
    $descripcion = trim($_POST['descripcion'] ?? '');
    if (empty($descripcion)) {
        $msg  = 'La descripción no puede estar vacía.';
        $tipo = 'error';
    } else {
        $ok = notificarAdmin($pdo, (int)$empresa['id'], "Solicitud de «{$empresa['nombre']}»: {$descripcion}");
        $msg  = $ok ? 'Solicitud enviada. El administrador la revisará pronto.' : 'Error al enviar. Intenta de nuevo.';
        $tipo = $ok ? 'success' : 'error';
    }
}

// Historial de notificaciones enviadas (últimas 10)
$historial = [];
if ($empresa) {
    $stmt = $pdo->prepare(
        'SELECT mensaje, leido, creado_en FROM notificaciones_admin
         WHERE empresa_id = ? ORDER BY creado_en DESC LIMIT 10'
    );
    $stmt->execute([(int)$empresa['id']]);
    $historial = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Empresa - FlowZone</title>
    <link rel="stylesheet" href="/FLOWZONE/assets/css/style.css">
</head>
<body>
<?php require_once '../includes/header.php'; ?>

<section class="page-header">
    <div class="container">
        <h1>🏢 Panel de Empresa</h1>
        <p>Gestiona tu información y envía solicitudes al administrador</p>
    </div>
</section>

<div class="empresa-layout">

    <?php if ($msg): ?>
        <div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($empresa): ?>

        <!-- Info de la empresa -->
        <div class="empresa-card">
            <h2>Información registrada</h2>
            <div class="empresa-info-grid">
                <div class="empresa-info-item">
                    <label>Empresa</label>
                    <span><?= htmlspecialchars($empresa['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="empresa-info-item">
                    <label>Responsable</label>
                    <span><?= htmlspecialchars($empresa['nombre_usuario'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="empresa-info-item">
                    <label>Correo</label>
                    <span><?= htmlspecialchars($empresa['correo'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="empresa-info-item">
                    <label>Teléfono</label>
                    <span><?= htmlspecialchars($empresa['telefono'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="empresa-info-item">
                    <label>Dirección</label>
                    <span><?= htmlspecialchars($empresa['direccion'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="empresa-info-item">
                    <label>Estado cuenta</label>
                    <span class="badge badge-<?= $empresa['estado'] ?>"><?= $empresa['estado'] ?></span>
                </div>
                <div class="empresa-info-item">
                    <label>Aprobada por admin</label>
                    <span class="badge <?= $empresa['aprobado'] ? 'badge-aprobado' : 'badge-pendiente' ?>">
                        <?= $empresa['aprobado'] ? 'Sí' : 'Pendiente' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Formulario de solicitud -->
        <div class="empresa-card">
            <h2>Enviar solicitud al administrador</h2>
            <p style="color:var(--gray);margin-bottom:1rem;">
                Usa este formulario para solicitar cambios en tus datos, reportar novedades o pedir información.
            </p>
            <form method="POST">
                <div class="form-group">
                    <label for="descripcion">Descripción de la solicitud *</label>
                    <textarea id="descripcion" name="descripcion" rows="4" required maxlength="1000"
                              placeholder="Ej: Necesito actualizar el número de teléfono a 3201234567..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Enviar solicitud</button>
            </form>
        </div>

        <!-- Historial -->
        <?php if (!empty($historial)): ?>
        <div class="empresa-card">
            <h2>Historial de solicitudes</h2>
            <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
                <thead>
                    <tr style="background:var(--light);">
                        <th style="padding:0.7rem;text-align:left;">Mensaje</th>
                        <th style="padding:0.7rem;text-align:left;">Fecha</th>
                        <th style="padding:0.7rem;text-align:left;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($historial as $h): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:0.7rem;"><?= htmlspecialchars($h['mensaje'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:0.7rem;white-space:nowrap;"><?= htmlspecialchars(substr($h['creado_en'], 0, 16), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:0.7rem;">
                            <span class="badge <?= $h['leido'] ? 'badge-aprobado' : 'badge-pendiente' ?>">
                                <?= $h['leido'] ? 'Revisada' : 'Pendiente' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="alert alert-error">No se encontraron datos de empresa asociados a tu cuenta. Contacta al administrador.</div>
    <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>
