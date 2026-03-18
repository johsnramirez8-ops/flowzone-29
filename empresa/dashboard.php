<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';
 
requiereRol(['empresa']);
 
$usuarioId = obtenerUsuarioId();
$msg  = '';
$tipo = '';
 
$stmt = $pdo->prepare(
    'SELECT e.*, u.correo, u.telefono AS tel_usuario, u.estado, u.nombre AS nombre_usuario
     FROM empresas e
     JOIN usuarios u ON u.id = e.usuario_id
     WHERE e.usuario_id = ? LIMIT 1'
);
$stmt->execute([$usuarioId]);
$empresa = $stmt->fetch();
 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $empresa) {
    $tipoSolicitud = trim($_POST['tipo_solicitud'] ?? '');
 
    switch ($tipoSolicitud) {
        case 'hotel':
            $nombre      = trim($_POST['hotel_nombre'] ?? '');
            $precio      = trim($_POST['hotel_precio'] ?? '');
            $descripcion = trim($_POST['hotel_descripcion'] ?? '');
            $imagen      = trim($_POST['hotel_imagen'] ?? '');
            if (empty($nombre)) {
                $msg = 'El nombre del hotel es obligatorio.';
                $tipo = 'error';
            } else {
                $mensaje = "SOLICITUD NUEVO HOTEL\n"
                    . "Nombre: $nombre\n"
                    . "Precio aprox: $precio\n"
                    . "Descripción: $descripcion\n"
                    . "Imagen: $imagen";
                $ok  = notificarAdmin($pdo, (int)$empresa['id'], $mensaje);
                $msg = $ok ? 'Solicitud de hotel enviada. El administrador la revisará pronto.' : 'Error al enviar. Intenta de nuevo.';
                $tipo = $ok ? 'success' : 'error';
            }
            break;
 
        case 'restaurante':
            $nombre      = trim($_POST['rest_nombre'] ?? '');
            $precio      = trim($_POST['rest_precio'] ?? '');
            $descripcion = trim($_POST['rest_descripcion'] ?? '');
            $imagen      = trim($_POST['rest_imagen'] ?? '');
            if (empty($nombre)) {
                $msg = 'El nombre del restaurante es obligatorio.';
                $tipo = 'error';
            } else {
                $mensaje = "SOLICITUD NUEVO RESTAURANTE\n"
                    . "Nombre: $nombre\n"
                    . "Tipo de cocina: " . trim($_POST['rest_cocina'] ?? '') . "\n"
                    . "Dirección: " . trim($_POST['rest_direccion'] ?? '') . "\n"
                    . "Horario: " . trim($_POST['rest_horario'] ?? '') . "\n"
                    . "Precio promedio: $precio\n"
                    . "Descripción: $descripcion\n"
                    . "Imagen: $imagen";
                $ok  = notificarAdmin($pdo, (int)$empresa['id'], $mensaje);
                $msg = $ok ? 'Solicitud de restaurante enviada. El administrador la revisará pronto.' : 'Error al enviar. Intenta de nuevo.';
                $tipo = $ok ? 'success' : 'error';
            }
            break;
 
        case 'actualizar':
            $campo  = trim($_POST['act_campo'] ?? '');
            $nuevo  = trim($_POST['act_valor'] ?? '');
            $motivo = trim($_POST['act_motivo'] ?? '');
            if (empty($campo) || empty($nuevo)) {
                $msg = 'Debes indicar el campo y el nuevo valor.';
                $tipo = 'error';
            } else {
                $mensaje = "SOLICITUD ACTUALIZACIÓN DE DATOS\n"
                    . "Campo: $campo\n"
                    . "Nuevo valor: $nuevo\n"
                    . "Motivo: $motivo";
                $ok  = notificarAdmin($pdo, (int)$empresa['id'], $mensaje);
                $msg = $ok ? 'Solicitud de actualización enviada.' : 'Error al enviar. Intenta de nuevo.';
                $tipo = $ok ? 'success' : 'error';
            }
            break;
 
        case 'novedad':
            $asunto      = trim($_POST['nov_asunto'] ?? '');
            $descripcion = trim($_POST['nov_descripcion'] ?? '');
            if (empty($descripcion)) {
                $msg = 'La descripción de la novedad no puede estar vacía.';
                $tipo = 'error';
            } else {
                $mensaje = "NOVEDAD / REPORTE\n"
                    . "Asunto: $asunto\n"
                    . "Descripción: $descripcion";
                $ok  = notificarAdmin($pdo, (int)$empresa['id'], $mensaje);
                $msg = $ok ? 'Novedad reportada correctamente.' : 'Error al enviar. Intenta de nuevo.';
                $tipo = $ok ? 'success' : 'error';
            }
            break;
 
        default:
            $msg  = 'Selecciona un tipo de solicitud válido.';
            $tipo = 'error';
    }
}
 
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
    <style>
        .solicitud-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:1.5rem; }
        .solicitud-tab { display:flex; align-items:center; gap:8px; padding:10px 18px; border:1.5px solid #dde1ea; border-radius:10px; background:#fff; cursor:pointer; font-size:0.92rem; font-weight:500; color:#555; transition:all 0.18s; font-family:inherit; }
        .solicitud-tab:hover { border-color:#6c8ebf; color:#2d4a7a; background:#f0f4fb; }
        .solicitud-tab.active { border-color:#4267b2; background:#eaf0fb; color:#1a3a6e; }
        .form-panel { display:none; }
        .form-panel.active { display:block; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .form-row .full { grid-column:1/-1; }
        .img-preview-box { display:none; margin-top:8px; }
        .img-preview-box img { width:100%; max-height:200px; object-fit:cover; border-radius:8px; border:1px solid #dde1ea; }
        .field-hint { font-size:0.76rem; color:#888; margin-top:3px; }
        @media(max-width:600px){ .form-row{grid-template-columns:1fr} .form-row .full{grid-column:1} }
    </style>
</head>
<body>
<?php require_once '../includes/header.php'; ?>
 
<section class="page-header">
    <div class="container">
        <h1> Panel de Empresa</h1>
        <p>Gestiona tu información y envía solicitudes al administrador</p>
    </div>
</section>
 
<div class="empresa-layout">
 
    <?php if ($msg): ?>
        <div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
 
    <?php if ($empresa): ?>
 
    <div class="empresa-card">
        <h2>Información registrada</h2>
        <div class="empresa-info-grid">
            <div class="empresa-info-item"><label>Empresa</label><span><?= htmlspecialchars($empresa['nombre'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="empresa-info-item"><label>Responsable</label><span><?= htmlspecialchars($empresa['nombre_usuario'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="empresa-info-item"><label>Correo</label><span><?= htmlspecialchars($empresa['correo'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="empresa-info-item"><label>Teléfono</label><span><?= htmlspecialchars($empresa['telefono'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="empresa-info-item"><label>Dirección</label><span><?= htmlspecialchars($empresa['direccion'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="empresa-info-item"><label>Estado cuenta</label><span class="badge badge-<?= $empresa['estado'] ?>"><?= $empresa['estado'] ?></span></div>
            <div class="empresa-info-item"><label>Aprobada por admin</label>
                <span class="badge <?= $empresa['aprobado'] ? 'badge-aprobado' : 'badge-pendiente' ?>">
                    <?= $empresa['aprobado'] ? 'Sí' : 'Pendiente' ?>
                </span>
            </div>
        </div>
    </div>
 
    <div class="empresa-card">
        <h2>Enviar solicitud al administrador</h2>
        <p style="color:var(--gray);margin-bottom:1.2rem;">Selecciona el tipo de solicitud que deseas enviar.</p>
 
        <div class="solicitud-tabs">
            <button type="button" class="solicitud-tab active" onclick="mostrarPanel('hotel', this)">🏨 Nuevo hotel</button>
            <button type="button" class="solicitud-tab" onclick="mostrarPanel('restaurante', this)">🍽️ Nuevo restaurante</button>
            <button type="button" class="solicitud-tab" onclick="mostrarPanel('actualizar', this)">✏️ Actualizar datos</button>
            <button type="button" class="solicitud-tab" onclick="mostrarPanel('novedad', this)">📢 Reportar novedad</button>
        </div>
 
        <form method="POST">
        <input type="hidden" name="tipo_solicitud" id="tipo_solicitud" value="hotel">
 
        <!-- Panel: Hotel -->
        <div id="panel-hotel" class="form-panel active">
            <div class="form-row">
                <div class="form-group full">
                    <label for="hotel_nombre">Nombre del hotel *</label>
                    <input type="text" id="hotel_nombre" name="hotel_nombre" placeholder="Ej. Hotel Caribe Real" maxlength="120">
                </div>
                <div class="form-group">
                    <label for="hotel_precio">Precio aprox. por noche *</label>
                    <input type="text" id="hotel_precio" name="hotel_precio" placeholder="Ej. $150.000">
                </div>
                <div class="form-group">
                    <label for="hotel_imagen">URL de imagen</label>
                    <input type="url" id="hotel_imagen" name="hotel_imagen"
                           placeholder="https://ejemplo.com/imagen.jpg"
                           oninput="previsualizarImagen(this, 'prev-hotel')">
                    <p class="field-hint">Pega el enlace directo de una imagen</p>
                    <div class="img-preview-box" id="prev-hotel"><img src="" alt="Vista previa hotel"></div>
                </div>
                <div class="form-group full">
                    <label for="hotel_descripcion">Descripción / servicios *</label>
                    <textarea id="hotel_descripcion" name="hotel_descripcion" rows="4" maxlength="800"
                              placeholder="Describe el hotel: habitaciones, servicios, ambiente, ubicación..."></textarea>
                </div>
            </div>
        </div>
 
        <!-- Panel: Restaurante -->
        <div id="panel-restaurante" class="form-panel">
            <div class="form-row">
                <div class="form-group full">
                    <label for="rest_nombre">Nombre del restaurante *</label>
                    <input type="text" id="rest_nombre" name="rest_nombre" placeholder="Ej. La Fogata" maxlength="120">
                </div>
                <div class="form-group">
                    <label for="rest_precio">Precio promedio por persona *</label>
                    <input type="text" id="rest_precio" name="rest_precio" placeholder="Ej. $35.000">
                </div>
                <div class="form-group">
                    <label for="rest_cocina">Tipo de cocina</label>
                    <select id="rest_cocina" name="rest_cocina">
                        <option value="">Seleccionar...</option>
                        <option>Colombiana</option><option>Internacional</option><option>Italiana</option>
                        <option>Mexicana</option><option>Mariscos</option><option>Vegana / vegetariana</option>
                        <option>Parrilla / asados</option><option>Fusión</option><option>Otra</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="rest_direccion">Dirección</label>
                    <input type="text" id="rest_direccion" name="rest_direccion" placeholder="Calle, ciudad" maxlength="200">
                </div>
                <div class="form-group">
                    <label for="rest_horario">Horario de atención</label>
                    <input type="text" id="rest_horario" name="rest_horario" placeholder="Ej. Lun–Sáb 12:00–22:00">
                </div>
                <div class="form-group">
                    <label for="rest_imagen">URL de imagen</label>
                    <input type="url" id="rest_imagen" name="rest_imagen"
                           placeholder="https://ejemplo.com/imagen.jpg"
                           oninput="previsualizarImagen(this, 'prev-rest')">
                    <p class="field-hint">Pega el enlace directo de una imagen</p>
                    <div class="img-preview-box" id="prev-rest"><img src="" alt="Vista previa restaurante"></div>
                </div>
                <div class="form-group full">
                    <label for="rest_descripcion">Descripción / especialidades *</label>
                    <textarea id="rest_descripcion" name="rest_descripcion" rows="4" maxlength="800"
                              placeholder="Describe el restaurante: platos estrella, ambiente, especialidades..."></textarea>
                </div>
            </div>
        </div>
 
        <!-- Panel: Actualizar datos -->
        <div id="panel-actualizar" class="form-panel">
            <div class="form-row">
                <div class="form-group">
                    <label for="act_campo">Campo a actualizar *</label>
                    <select id="act_campo" name="act_campo">
                        <option value="">Seleccionar...</option>
                        <option>Nombre de la empresa</option><option>Teléfono</option><option>Dirección</option>
                        <option>Correo electrónico</option><option>Representante legal</option><option>NIT / RUT</option><option>Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="act_valor">Nuevo valor *</label>
                    <input type="text" id="act_valor" name="act_valor" placeholder="Ingresa el nuevo dato" maxlength="200">
                </div>
                <div class="form-group full">
                    <label for="act_motivo">Motivo del cambio</label>
                    <textarea id="act_motivo" name="act_motivo" rows="2" maxlength="500"
                              placeholder="Explica brevemente por qué necesitas este cambio..."></textarea>
                </div>
            </div>
        </div>
 
        <!-- Panel: Novedad -->
        <div id="panel-novedad" class="form-panel">
            <div class="form-row">
                <div class="form-group full">
                    <label for="nov_asunto">Asunto</label>
                    <input type="text" id="nov_asunto" name="nov_asunto" placeholder="Resumen breve de la novedad" maxlength="150">
                </div>
                <div class="form-group full">
                    <label for="nov_descripcion">Descripción detallada *</label>
                    <textarea id="nov_descripcion" name="nov_descripcion" rows="5" maxlength="1000"
                              placeholder="Describe la situación, problema o información que deseas reportar al administrador..."></textarea>
                </div>
            </div>
        </div>
 
        <div style="margin-top:1.2rem;">
            <button type="submit" class="btn btn-primary">Enviar solicitud</button>
        </div>
        </form>
    </div>
 
    <!-- Historial -->
    <?php if (!empty($historial)): ?>
    <div class="empresa-card">
        <h2>Historial de solicitudes</h2>
        <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
            <thead>
                <tr style="background:var(--light);">
                    <th style="padding:0.7rem;text-align:left;">Solicitud</th>
                    <th style="padding:0.7rem;text-align:left;">Fecha</th>
                    <th style="padding:0.7rem;text-align:left;">Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($historial as $h):
                $lineas  = explode("\n", $h['mensaje']);
                $titulo  = htmlspecialchars($lineas[0] ?? $h['mensaje'], ENT_QUOTES, 'UTF-8');
                $detalle = htmlspecialchars(implode(' · ', array_slice($lineas, 1, 3)), ENT_QUOTES, 'UTF-8');
            ?>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:0.7rem;">
                        <strong><?= $titulo ?></strong>
                        <?php if ($detalle): ?><br><small style="color:#888;"><?= $detalle ?></small><?php endif; ?>
                    </td>
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
 
<script>
function mostrarPanel(tipo, btn) {
    document.querySelectorAll('.form-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.solicitud-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('panel-' + tipo).classList.add('active');
    btn.classList.add('active');
    document.getElementById('tipo_solicitud').value = tipo;
}
 
function previsualizarImagen(input, previewId) {
    const box = document.getElementById(previewId);
    const img = box.querySelector('img');
    const url = input.value.trim();
    if (url) {
        img.src = url;
        img.onload  = () => box.style.display = 'block';
        img.onerror = () => box.style.display = 'none';
    } else {
        box.style.display = 'none';
    }
}
</script>
</body>
</html>