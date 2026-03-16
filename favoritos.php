<?php
require_once 'includes/conexion.php';
require_once 'includes/auth.php';

requiereAutenticacion();

require_once 'includes/header.php';

$usuario_id = obtenerUsuarioId();

// Obtener lugares favoritos
$stmt = $pdo->prepare("
    SELECT l.*, 'lugar' as tipo_item 
    FROM favoritos f 
    JOIN lugares l ON f.item_id = l.id 
    WHERE f.usuario_id = ? AND f.tipo = 'lugar'
    ORDER BY f.fecha DESC
");
$stmt->execute([$usuario_id]);
$lugares_favoritos = $stmt->fetchAll();

// Obtener hoteles favoritos
$stmt = $pdo->prepare("
    SELECT h.*, 'hotel' as tipo_item 
    FROM favoritos f 
    JOIN hoteles h ON f.item_id = h.id 
    WHERE f.usuario_id = ? AND f.tipo = 'hotel'
    ORDER BY f.fecha DESC
");
$stmt->execute([$usuario_id]);
$hoteles_favoritos = $stmt->fetchAll();
?>

<section class="page-header">
    <div class="container">
        <h1>❤️ Mis Favoritos</h1>
        <p>Lugares y hoteles que has guardado</p>
    </div>
</section>

<section class="container section">
    <?php if (empty($lugares_favoritos) && empty($hoteles_favoritos)): ?>
        <div class="empty-state">
            <p>No tienes favoritos guardados aún.</p>
            <a href="/FLOWZONE/lugares.php" class="btn btn-primary">Explorar Lugares</a>
        </div>
    <?php else: ?>
        <?php if (!empty($lugares_favoritos)): ?>
            <h2 class="section-title">Lugares Favoritos</h2>
            <div class="grid">
                <?php foreach ($lugares_favoritos as $lugar): ?>
                    <div class="card animate-on-scroll">
                        <img src="<?php echo htmlspecialchars($lugar['imagen']); ?>" alt="<?php echo htmlspecialchars($lugar['nombre']); ?>">
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($lugar['nombre']); ?></h3>
                            <p class="categoria"><?php echo htmlspecialchars($lugar['categoria']); ?></p>
                            <p class="ubicacion">📍 <?php echo htmlspecialchars($lugar['ubicacion']); ?></p>
                            <p><?php echo htmlspecialchars(substr($lugar['descripcion'], 0, 120)); ?>...</p>
                            <div class="card-actions">
                                <a href="/FLOWZONE/detalle_lugar.php?id=<?php echo $lugar['id']; ?>" class="btn btn-secondary">Ver Detalles</a>
                                <button class="btn btn-favorito active" data-tipo="lugar" data-id="<?php echo $lugar['id']; ?>">
                                    ❤️ Quitar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($hoteles_favoritos)): ?>
            <h2 class="section-title">Hoteles Favoritos</h2>
            <div class="grid">
                <?php foreach ($hoteles_favoritos as $hotel): ?>
                    <div class="card animate-on-scroll">
                        <img src="<?php echo htmlspecialchars($hotel['imagen']); ?>" alt="<?php echo htmlspecialchars($hotel['nombre']); ?>">
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($hotel['nombre']); ?></h3>
                            <p class="precio">$<?php echo number_format($hotel['precio'], 0, ',', '.'); ?> COP / noche</p>
                            <p class="ubicacion">📍 <?php echo htmlspecialchars($hotel['ubicacion']); ?></p>
                            <p><?php echo htmlspecialchars(substr($hotel['descripcion'], 0, 120)); ?>...</p>
                            <div class="card-actions">
                                <a href="/FLOWZONE/detalle_hotel.php?id=<?php echo $hotel['id']; ?>" class="btn btn-secondary">Ver Detalles</a>
                                <button class="btn btn-favorito active" data-tipo="hotel" data-id="<?php echo $hotel['id']; ?>">
                                    ❤️ Quitar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
