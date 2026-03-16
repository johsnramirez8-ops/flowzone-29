<?php
require_once 'includes/conexion.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM hoteles WHERE id = ?");
$stmt->execute([$id]);
$hotel = $stmt->fetch();

if (!$hotel) {
    header('Location: /FLOWZONE/hoteles.php');
    exit;
}

// Calificación promedio
$stmt = $pdo->prepare("SELECT AVG(calificacion) as promedio, COUNT(*) as total FROM calificaciones WHERE tipo = 'hotel' AND item_id = ?");
$stmt->execute([$id]);
$calificacion = $stmt->fetch();

// Favorito
$es_favorito = false;
if (estaAutenticado()) {
    $stmt = $pdo->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND tipo = 'hotel' AND item_id = ?");
    $stmt->execute([obtenerUsuarioId(), $id]);
    $es_favorito = (bool)$stmt->fetch();
}

$servicios = $hotel['servicios'] ? explode(',', $hotel['servicios']) : [];
?>

<section class="detalle-header" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('<?= htmlspecialchars($hotel['imagen'], ENT_QUOTES, 'UTF-8') ?>');">
    <div class="container">
        <h1><?= htmlspecialchars($hotel['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="precio-grande">$<?= number_format($hotel['precio'], 0, ',', '.') ?> COP / noche</p>
    </div>
</section>

<section class="container section">
    <div class="detalle-content">
        <div class="detalle-main">
            <div class="detalle-info">
                <h2>Descripción</h2>
                <p><?= nl2br(htmlspecialchars($hotel['descripcion'], ENT_QUOTES, 'UTF-8')) ?></p>

                <h3 style="margin-top:1.5rem;">Servicios</h3>
                <ul class="servicios-list">
                    <?php foreach ($servicios as $servicio): ?>
                        <li>✓ <?= htmlspecialchars(trim($servicio), ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>

                <div class="info-grid">
                    <div class="info-item">
                        <strong>📍 Ubicación:</strong>
                        <p><?= htmlspecialchars($hotel['ubicacion'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="info-item">
                        <strong>👥 Capacidad:</strong>
                        <p><?= $hotel['capacidad'] ?> personas</p>
                    </div>
                    <?php if ($hotel['telefono']): ?>
                    <div class="info-item">
                        <strong>📱 Teléfono:</strong>
                        <p><?= htmlspecialchars($hotel['telefono'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($hotel['email']): ?>
                    <div class="info-item">
                        <strong>📧 Email:</strong>
                        <p><?= htmlspecialchars($hotel['email'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($hotel['latitud'] && $hotel['longitud']): ?>
                <div class="mapa">
                    <h3>Ubicación en el mapa</h3>
                    <iframe width="100%" height="400" frameborder="0" style="border:0"
                        src="https://www.google.com/maps?q=<?= $hotel['latitud'] ?>,<?= $hotel['longitud'] ?>&output=embed"
                        allowfullscreen></iframe>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="detalle-sidebar">
            <!-- Calificación -->
            <div class="calificacion-box">
                <h3>Calificación</h3>
                <div class="rating-display">
                    <span class="rating-number"><?= $calificacion['promedio'] ? number_format($calificacion['promedio'], 1) : 'N/A' ?></span>
                    <span class="rating-stars">⭐⭐⭐⭐⭐</span>
                    <span class="rating-count">(<?= $calificacion['total'] ?> valoraciones)</span>
                </div>
                <?php if (estaAutenticado()): ?>
                <div class="rating-form">
                    <p>Tu calificación:</p>
                    <div class="stars" data-tipo="hotel" data-id="<?= $id ?>">
                        <span class="star" data-value="1">⭐</span>
                        <span class="star" data-value="2">⭐</span>
                        <span class="star" data-value="3">⭐</span>
                        <span class="star" data-value="4">⭐</span>
                        <span class="star" data-value="5">⭐</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Reservar -->
            <div class="reserva-box">
                <h3>Reservar ahora</h3>
                <p class="precio-destacado">$<?= number_format($hotel['precio'], 0, ',', '.') ?> COP</p>
                <p style="font-size:0.85rem;color:var(--gray);margin-bottom:1rem;">por noche</p>

                <?php if (estaAutenticado()): ?>
                    <a href="/FLOWZONE/reservar.php?hotel_id=<?= $id ?>"
                       class="btn btn-primary btn-block">
                        🛒 Hacer Reserva
                    </a>
                    <a href="/FLOWZONE/mis_reservas.php"
                       class="btn btn-secondary btn-block"
                       style="margin-top:0.5rem;text-align:center;">
                        📋 Ver mis reservas
                    </a>
                <?php else: ?>
                    <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:1rem;text-align:center;margin-bottom:1rem;">
                        <p style="font-size:0.9rem;color:#795548;margin-bottom:0.8rem;">
                            🔒 Inicia sesión para reservar
                        </p>
                        <a href="/FLOWZONE/login.php" class="btn btn-primary btn-block">
                            Iniciar Sesión
                        </a>
                        <p style="font-size:0.8rem;color:var(--gray);margin-top:0.5rem;">
                            ¿No tienes cuenta? <a href="/FLOWZONE/registro.php">Regístrate gratis</a>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Favorito -->
            <?php if (estaAutenticado()): ?>
            <button class="btn btn-favorito <?= $es_favorito ? 'active' : '' ?>"
                    data-tipo="hotel" data-id="<?= $id ?>">
                <?= $es_favorito ? '❤️ En Favoritos' : '🤍 Agregar a Favoritos' ?>
            </button>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
