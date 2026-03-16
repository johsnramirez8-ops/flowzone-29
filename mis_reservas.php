<?php
require_once 'includes/conexion.php';
require_once 'includes/auth.php';

requiereAutenticacion();
require_once 'includes/header.php';

$usuario_id = obtenerUsuarioId();
$msg = '';
$tipo_msg = '';

// Cancelar reserva
if (isset($_GET['cancelar']) && is_numeric($_GET['cancelar'])) {
    $rid = (int)$_GET['cancelar'];
    try {
        // Solo cancelar si pertenece al usuario y está pendiente
        $stmt = $pdo->prepare(
            "UPDATE reservas SET estado = 'cancelada'
             WHERE id = ? AND usuario_id = ? AND estado = 'pendiente'"
        );
        $stmt->execute([$rid, $usuario_id]);
        if ($stmt->rowCount() > 0) {
            $msg = 'Reserva cancelada correctamente.';
            $tipo_msg = 'success';
        } else {
            $msg = 'No se pudo cancelar. Solo puedes cancelar reservas pendientes.';
            $tipo_msg = 'error';
        }
    } catch (PDOException $e) {
        $msg = 'Error al cancelar. Intenta de nuevo.';
        $tipo_msg = 'error';
    }
}

// Obtener todas las reservas del usuario con info del hotel
$stmt = $pdo->prepare("
    SELECT r.*, h.nombre AS hotel_nombre, h.imagen AS hotel_imagen,
           h.ubicacion AS hotel_ubicacion, h.precio AS precio_noche
    FROM reservas r
    JOIN hoteles h ON r.hotel_id = h.id
    WHERE r.usuario_id = ?
    ORDER BY r.creado_en DESC
");
$stmt->execute([$usuario_id]);
$reservas = $stmt->fetchAll();

// Separar por estado
$pendientes  = array_filter($reservas, fn($r) => $r['estado'] === 'pendiente');
$confirmadas = array_filter($reservas, fn($r) => $r['estado'] === 'confirmada');
$canceladas  = array_filter($reservas, fn($r) => $r['estado'] === 'cancelada');

// Totales
$total_gastado = array_sum(array_column(
    array_filter($reservas, fn($r) => $r['estado'] !== 'cancelada'),
    'precio_total'
));
?>

<section class="page-header">
    <div class="container">
        <h1>🛒 Mis Reservas</h1>
        <p>Gestiona tus reservas de alojamiento</p>
    </div>
</section>

<section class="container section">

    <?php if ($msg): ?>
        <div class="alert alert-<?= $tipo_msg ?>" style="margin-bottom:1.5rem;">
            <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- Resumen estadísticas -->
    <div class="reservas-stats">
        <div class="stat-reserva">
            <span class="stat-num"><?= count($reservas) ?></span>
            <span class="stat-label">Total reservas</span>
        </div>
        <div class="stat-reserva pendiente">
            <span class="stat-num"><?= count($pendientes) ?></span>
            <span class="stat-label">Pendientes</span>
        </div>
        <div class="stat-reserva confirmada">
            <span class="stat-num"><?= count($confirmadas) ?></span>
            <span class="stat-label">Confirmadas</span>
        </div>
        <div class="stat-reserva total">
            <span class="stat-num">$<?= number_format($total_gastado, 0, ',', '.') ?></span>
            <span class="stat-label">Total COP</span>
        </div>
    </div>

    <?php if (empty($reservas)): ?>
        <div class="reservas-empty">
            <div class="empty-icon">🏨</div>
            <h3>Aún no tienes reservas</h3>
            <p>Explora nuestros hoteles y haz tu primera reserva</p>
            <a href="/FLOWZONE/hoteles.php" class="btn btn-primary">Ver Hoteles</a>
        </div>
    <?php else: ?>

        <!-- Reservas pendientes -->
        <?php if (!empty($pendientes)): ?>
        <div class="reservas-grupo">
            <h2 class="grupo-titulo pendiente-titulo">⏳ Pendientes de confirmación</h2>
            <div class="reservas-lista">
                <?php foreach ($pendientes as $r): ?>
                    <?php
                    $dias = (strtotime($r['fecha_salida']) - strtotime($r['fecha_entrada'])) / 86400;
                    ?>
                    <div class="reserva-card pendiente-card">
                        <div class="reserva-img">
                            <img src="<?= htmlspecialchars($r['hotel_imagen'], ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars($r['hotel_nombre'], ENT_QUOTES, 'UTF-8') ?>">
                            <span class="reserva-badge badge-pendiente">Pendiente</span>
                        </div>
                        <div class="reserva-info">
                            <h3><?= htmlspecialchars($r['hotel_nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="reserva-ubicacion">📍 <?= htmlspecialchars($r['hotel_ubicacion'], ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="reserva-fechas">
                                <div class="fecha-item">
                                    <span class="fecha-label">Entrada</span>
                                    <span class="fecha-val"><?= date('d/m/Y', strtotime($r['fecha_entrada'])) ?></span>
                                </div>
                                <div class="fecha-sep">→</div>
                                <div class="fecha-item">
                                    <span class="fecha-label">Salida</span>
                                    <span class="fecha-val"><?= date('d/m/Y', strtotime($r['fecha_salida'])) ?></span>
                                </div>
                                <div class="fecha-item">
                                    <span class="fecha-label">Duración</span>
                                    <span class="fecha-val"><?= $dias ?> noche<?= $dias > 1 ? 's' : '' ?></span>
                                </div>
                                <div class="fecha-item">
                                    <span class="fecha-label">Personas</span>
                                    <span class="fecha-val">👥 <?= $r['num_personas'] ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="reserva-precio-col">
                            <div class="reserva-total">
                                <span class="total-label">Total</span>
                                <span class="total-val">$<?= number_format($r['precio_total'], 0, ',', '.') ?></span>
                                <span class="total-sub">COP</span>
                            </div>
                            <div class="reserva-acciones">
                                <a href="/FLOWZONE/detalle_hotel.php?id=<?= $r['hotel_id'] ?>"
                                   class="btn btn-secondary btn-sm">Ver hotel</a>
                                <a href="/FLOWZONE/mis_reservas.php?cancelar=<?= $r['id'] ?>"
                                   class="btn btn-cancelar btn-sm"
                                   onclick="return confirm('¿Cancelar esta reserva?')">Cancelar</a>
                            </div>
                            <p class="reserva-fecha-creacion">Reservado el <?= date('d/m/Y', strtotime($r['creado_en'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reservas confirmadas -->
        <?php if (!empty($confirmadas)): ?>
        <div class="reservas-grupo">
            <h2 class="grupo-titulo confirmada-titulo">✅ Confirmadas</h2>
            <div class="reservas-lista">
                <?php foreach ($confirmadas as $r): ?>
                    <?php $dias = (strtotime($r['fecha_salida']) - strtotime($r['fecha_entrada'])) / 86400; ?>
                    <div class="reserva-card confirmada-card">
                        <div class="reserva-img">
                            <img src="<?= htmlspecialchars($r['hotel_imagen'], ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars($r['hotel_nombre'], ENT_QUOTES, 'UTF-8') ?>">
                            <span class="reserva-badge badge-confirmada">Confirmada ✓</span>
                        </div>
                        <div class="reserva-info">
                            <h3><?= htmlspecialchars($r['hotel_nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="reserva-ubicacion">📍 <?= htmlspecialchars($r['hotel_ubicacion'], ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="reserva-fechas">
                                <div class="fecha-item">
                                    <span class="fecha-label">Entrada</span>
                                    <span class="fecha-val"><?= date('d/m/Y', strtotime($r['fecha_entrada'])) ?></span>
                                </div>
                                <div class="fecha-sep">→</div>
                                <div class="fecha-item">
                                    <span class="fecha-label">Salida</span>
                                    <span class="fecha-val"><?= date('d/m/Y', strtotime($r['fecha_salida'])) ?></span>
                                </div>
                                <div class="fecha-item">
                                    <span class="fecha-label">Duración</span>
                                    <span class="fecha-val"><?= $dias ?> noche<?= $dias > 1 ? 's' : '' ?></span>
                                </div>
                                <div class="fecha-item">
                                    <span class="fecha-label">Personas</span>
                                    <span class="fecha-val">👥 <?= $r['num_personas'] ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="reserva-precio-col">
                            <div class="reserva-total">
                                <span class="total-label">Total</span>
                                <span class="total-val">$<?= number_format($r['precio_total'], 0, ',', '.') ?></span>
                                <span class="total-sub">COP</span>
                            </div>
                            <div class="reserva-acciones">
                                <a href="/FLOWZONE/detalle_hotel.php?id=<?= $r['hotel_id'] ?>"
                                   class="btn btn-secondary btn-sm">Ver hotel</a>
                            </div>
                            <p class="reserva-fecha-creacion">Reservado el <?= date('d/m/Y', strtotime($r['creado_en'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reservas canceladas -->
        <?php if (!empty($canceladas)): ?>
        <div class="reservas-grupo">
            <h2 class="grupo-titulo cancelada-titulo">❌ Canceladas</h2>
            <div class="reservas-lista canceladas-lista">
                <?php foreach ($canceladas as $r): ?>
                    <?php $dias = (strtotime($r['fecha_salida']) - strtotime($r['fecha_entrada'])) / 86400; ?>
                    <div class="reserva-card cancelada-card">
                        <div class="reserva-img">
                            <img src="<?= htmlspecialchars($r['hotel_imagen'], ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars($r['hotel_nombre'], ENT_QUOTES, 'UTF-8') ?>">
                            <span class="reserva-badge badge-cancelada">Cancelada</span>
                        </div>
                        <div class="reserva-info">
                            <h3><?= htmlspecialchars($r['hotel_nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="reserva-ubicacion">📍 <?= htmlspecialchars($r['hotel_ubicacion'], ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="reserva-fechas">
                                <div class="fecha-item">
                                    <span class="fecha-label">Entrada</span>
                                    <span class="fecha-val"><?= date('d/m/Y', strtotime($r['fecha_entrada'])) ?></span>
                                </div>
                                <div class="fecha-sep">→</div>
                                <div class="fecha-item">
                                    <span class="fecha-label">Salida</span>
                                    <span class="fecha-val"><?= date('d/m/Y', strtotime($r['fecha_salida'])) ?></span>
                                </div>
                                <div class="fecha-item">
                                    <span class="fecha-label">Noches</span>
                                    <span class="fecha-val"><?= $dias ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="reserva-precio-col">
                            <div class="reserva-total cancelado-total">
                                <span class="total-label">Total</span>
                                <span class="total-val">$<?= number_format($r['precio_total'], 0, ',', '.') ?></span>
                                <span class="total-sub">COP</span>
                            </div>
                            <div class="reserva-acciones">
                                <a href="/FLOWZONE/reservar.php?hotel_id=<?= $r['hotel_id'] ?>"
                                   class="btn btn-primary btn-sm">Reservar de nuevo</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div style="text-align:center;margin-top:2rem;">
            <a href="/FLOWZONE/hoteles.php" class="btn btn-primary">➕ Hacer otra reserva</a>
        </div>

    <?php endif; ?>
</section>

<style>
/* ── Estadísticas ── */
.reservas-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
    margin-bottom: 2.5rem;
}
.stat-reserva {
    background: #fff;
    border-radius: 12px;
    padding: 1.2rem 1.5rem;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.07);
    border-top: 4px solid var(--gray);
}
.stat-reserva.pendiente  { border-top-color: #f59e0b; }
.stat-reserva.confirmada { border-top-color: var(--success); }
.stat-reserva.total      { border-top-color: var(--primary); }
.stat-num  { display: block; font-size: 1.8rem; font-weight: 700; color: var(--dark); }
.stat-label{ display: block; font-size: 0.8rem; color: var(--gray); margin-top: 0.2rem; text-transform: uppercase; letter-spacing: 0.05em; }

/* ── Empty state ── */
.reservas-empty {
    text-align: center;
    padding: 4rem 2rem;
    background: #fff;
    border-radius: 12px;
}
.empty-icon { font-size: 4rem; margin-bottom: 1rem; }
.reservas-empty h3 { font-size: 1.4rem; color: var(--dark); margin-bottom: 0.5rem; }
.reservas-empty p  { color: var(--gray); margin-bottom: 1.5rem; }

/* ── Grupo de reservas ── */
.reservas-grupo   { margin-bottom: 2.5rem; }
.grupo-titulo     { font-size: 1.15rem; font-weight: 600; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--light); }
.pendiente-titulo { color: #b45309; border-color: #fde68a; }
.confirmada-titulo{ color: var(--success); border-color: #bbf7d0; }
.cancelada-titulo { color: var(--danger); border-color: #fecaca; }

/* ── Card de reserva ── */
.reservas-lista { display: flex; flex-direction: column; gap: 1rem; }
.reserva-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    display: grid;
    grid-template-columns: 200px 1fr auto;
    border-left: 4px solid var(--gray);
    transition: box-shadow 0.2s;
}
.reserva-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.12); }
.pendiente-card  { border-left-color: #f59e0b; }
.confirmada-card { border-left-color: var(--success); }
.cancelada-card  { border-left-color: var(--danger); opacity: 0.75; }

.reserva-img { position: relative; }
.reserva-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.reserva-badge {
    position: absolute; top: 0.7rem; left: 0.7rem;
    padding: 0.25rem 0.6rem; border-radius: 20px;
    font-size: 0.72rem; font-weight: 600;
}

.reserva-info { padding: 1.2rem 1.5rem; }
.reserva-info h3 { font-size: 1.1rem; color: var(--dark); margin-bottom: 0.3rem; }
.reserva-ubicacion { font-size: 0.85rem; color: var(--gray); margin-bottom: 1rem; }

.reserva-fechas {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: center;
    background: var(--light);
    border-radius: 8px;
    padding: 0.8rem 1rem;
}
.fecha-item { display: flex; flex-direction: column; }
.fecha-label{ font-size: 0.7rem; color: var(--gray); text-transform: uppercase; letter-spacing: 0.06em; }
.fecha-val  { font-size: 0.9rem; font-weight: 600; color: var(--dark); }
.fecha-sep  { font-size: 1.2rem; color: var(--gray); }

.reserva-precio-col {
    padding: 1.2rem 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    justify-content: space-between;
    min-width: 160px;
    border-left: 1px solid var(--light);
}

.reserva-total { text-align: right; }
.total-label { display: block; font-size: 0.7rem; color: var(--gray); text-transform: uppercase; letter-spacing: 0.06em; }
.total-val   { display: block; font-size: 1.6rem; font-weight: 700; color: var(--primary); line-height: 1.1; }
.total-sub   { display: block; font-size: 0.75rem; color: var(--gray); }
.cancelado-total .total-val { color: var(--gray); text-decoration: line-through; }

.reserva-acciones { display: flex; flex-direction: column; gap: 0.5rem; width: 100%; }
.btn-sm { padding: 0.5rem 0.8rem !important; font-size: 0.85rem !important; text-align: center; }
.btn-cancelar { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; border-radius: 5px; text-decoration: none; display: inline-block; cursor: pointer; transition: background 0.2s; }
.btn-cancelar:hover { background: #fecaca; }

.reserva-fecha-creacion { font-size: 0.72rem; color: var(--gray); }

.canceladas-lista { opacity: 0.8; }

/* Responsive */
@media (max-width: 768px) {
    .reserva-card { grid-template-columns: 1fr; }
    .reserva-img  { height: 160px; }
    .reserva-precio-col { border-left: none; border-top: 1px solid var(--light); flex-direction: row; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between; }
    .reserva-acciones { flex-direction: row; width: auto; }
}
</style>

<?php require_once 'includes/footer.php'; ?>
