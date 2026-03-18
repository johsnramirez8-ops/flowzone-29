<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';

// Proteger: solo empresas autenticadas
if (!estaAutenticado() || obtenerUsuarioRol() !== 'empresa') {
    header('Location: /FLOWZONE/login.php');
    exit;
}

$usuarioId = obtenerUsuarioId();
$msg  = '';
$tipo_msg = '';

// Obtener datos de la empresa
$stmt = $pdo->prepare(
    'SELECT e.*, u.correo, u.nombre AS nombre_usuario, u.telefono AS tel_usuario, u.estado
     FROM empresas e
     JOIN usuarios u ON u.id = e.usuario_id
     WHERE e.usuario_id = ? LIMIT 1'
);
$stmt->execute([$usuarioId]);
$empresa = $stmt->fetch();

// Procesar solicitud de cambio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitud'])) {
    $descripcion = trim($_POST['descripcion'] ?? '');
    if (empty($descripcion)) {
        $msg = 'La descripción no puede estar vacía.';
        $tipo_msg = 'error';
    } elseif ($empresa) {
        try {
            $pdo->prepare(
                'INSERT INTO notificaciones_admin (empresa_id, mensaje, leido) VALUES (?, ?, 0)'
            )->execute([(int)$empresa['id'], "Solicitud de «{$empresa['nombre']}»: {$descripcion}"]);
            $msg = ' Solicitud enviada. El administrador la revisará pronto.';
            $tipo_msg = 'success';
        } catch (PDOException $e) {
            $msg = 'Error al enviar. Intenta de nuevo.';
            $tipo_msg = 'error';
        }
    }
}

// Historial de solicitudes
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
        <h1> Panel de Empresa</h1>
        <p>Gestiona tu información y comunícate con el administrador</p>
    </div>
</section>

<div class="container section">
    <div style="max-width:800px;margin:0 auto;">

        <?php if ($msg): ?>
            <div class="alert alert-<?= $tipo_msg === 'success' ? 'success' : 'error' ?>" style="margin-bottom:1.5rem;">
                <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($empresa): ?>

            <!-- Info empresa -->
            <div style="background:#fff;border-radius:12px;padding:2rem;margin-bottom:1.5rem;box-shadow:0 2px 12px rgba(0,0,0,0.07);">
                <h2 style="color:var(--primary);margin-bottom:1.2rem;font-size:1.3rem;"> Información registrada</h2>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;">
                    <?php
                    $campos = [
                        ['Empresa',   $empresa['nombre']],
                        ['Responsable', $empresa['nombre_usuario']],
                        ['Correo',    $empresa['correo']],
                        ['Teléfono',  $empresa['telefono'] ?? '—'],
                        ['Dirección', $empresa['direccion'] ?? '—'],
                    ];
                    foreach ($campos as [$label, $valor]):
                    ?>
                    <div style="background:#f8f8f6;border-radius:8px;padding:0.9rem 1rem;">
                        <div style="font-size:0.72rem;color:#888;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.2rem;"><?= $label ?></div>
                        <div style="font-weight:600;color:#1a1a1a;font-size:0.9rem;"><?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <?php endforeach; ?>
                    <div style="background:#f8f8f6;border-radius:8px;padding:0.9rem 1rem;">
                        <div style="font-size:0.72rem;color:#888;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.2rem;">Estado</div>
                        <span class="badge badge-<?= $empresa['estado'] ?>"><?= $empresa['estado'] ?></span>
                    </div>
                    <div style="background:#f8f8f6;border-radius:8px;padding:0.9rem 1rem;">
                        <div style="font-size:0.72rem;color:#888;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.2rem;">Aprobada</div>
                        <span class="badge <?= $empresa['aprobado'] ? 'badge-confirmada' : 'badge-pendiente' ?>">
                            <?= $empresa['aprobado'] ? '✓ Sí' : 'Pendiente' ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Solicitar cambio -->
            <div style="background:#fff;border-radius:12px;padding:2rem;margin-bottom:1.5rem;box-shadow:0 2px 12px rgba(0,0,0,0.07);">
                <h2 style="color:var(--primary);margin-bottom:0.5rem;font-size:1.3rem;">📨 Enviar solicitud al administrador</h2>
                <p style="color:#666;font-size:0.9rem;margin-bottom:1.2rem;">Solicita cambios en tus datos, reporta novedades o pide información.</p>
                <form method="POST">
                    <input type="hidden" name="solicitud" value="1">
                    <div class="form-group">
                        <label for="descripcion">Descripción *</label>
                        <textarea id="descripcion" name="descripcion" rows="4" required maxlength="1000"
                                  style="width:100%;padding:0.8rem;border:1px solid #ddd;border-radius:8px;font-family:inherit;font-size:0.95rem;resize:vertical;"
                                  placeholder="Ej: Necesito actualizar el número de teléfono a 3201234567..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Enviar solicitud</button>
                </form>
            </div>

            <!-- Historial -->
            <?php if (!empty($historial)): ?>
            <div style="background:#fff;border-radius:12px;padding:2rem;box-shadow:0 2px 12px rgba(0,0,0,0.07);">
                <h2 style="color:var(--primary);margin-bottom:1.2rem;font-size:1.3rem;">📂 Historial de solicitudes</h2>
                <table style="width:100%;border-collapse:collapse;font-size:0.88rem;">
                    <thead>
                        <tr style="background:#f4f4f4;">
                            <th style="padding:0.7rem 1rem;text-align:left;font-weight:600;">Mensaje</th>
                            <th style="padding:0.7rem 1rem;text-align:left;font-weight:600;white-space:nowrap;">Fecha</th>
                            <th style="padding:0.7rem 1rem;text-align:left;font-weight:600;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($historial as $h): ?>
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td style="padding:0.7rem 1rem;"><?= htmlspecialchars($h['mensaje'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="padding:0.7rem 1rem;white-space:nowrap;color:#666;"><?= substr($h['creado_en'], 0, 16) ?></td>
                            <td style="padding:0.7rem 1rem;">
                                <span class="badge <?= $h['leido'] ? 'badge-confirmada' : 'badge-pendiente' ?>">
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
            <div class="alert alert-error">
                No se encontraron datos de empresa asociados a tu cuenta.
                Contacta al administrador en <a href="/FLOWZONE/contacto.php">esta página</a>.
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
