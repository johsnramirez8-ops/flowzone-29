<?php
require_once 'includes/conexion.php';
require_once 'includes/header.php';

// Obtener lugares destacados
$stmt = $pdo->query("SELECT * FROM lugares ORDER BY creado_en DESC LIMIT 3");
$lugares_destacados = $stmt->fetchAll();

// Obtener hoteles destacados
$stmt = $pdo->query("SELECT * FROM hoteles WHERE disponibilidad = 1 ORDER BY creado_en DESC LIMIT 3");
$hoteles_destacados = $stmt->fetchAll();

// Obtener próximos eventos
$stmt = $pdo->query("SELECT * FROM eventos WHERE fecha >= CURDATE() ORDER BY fecha ASC LIMIT 3");
$eventos_proximos = $stmt->fetchAll();
?>

<section class="hero">
    <div class="hero-content">
        <h1 class="fade-in">Descubre Ortega, Tolima</h1>
        <p class="fade-in">Un paraíso natural en el corazón de Colombia</p>
        <a href="/FLOWZONE/lugares.php" class="btn btn-primary fade-in">Explorar Lugares</a>
    </div>
</section>

<section class="container section">
    <h2 class="section-title">Lugares Destacados</h2>
    <div class="grid">
        <?php foreach ($lugares_destacados as $lugar): ?>
            <div class="card animate-on-scroll">
                <img src="<?php echo htmlspecialchars($lugar['imagen']); ?>" alt="<?php echo htmlspecialchars($lugar['nombre']); ?>">
                <div class="card-content">
                    <h3><?php echo htmlspecialchars($lugar['nombre']); ?></h3>
                    <p class="categoria"><?php echo htmlspecialchars($lugar['categoria']); ?></p>
                    <p><?php echo htmlspecialchars(substr($lugar['descripcion'], 0, 100)); ?>...</p>
                    <a href="/FLOWZONE/detalle_lugar.php?id=<?php echo $lugar['id']; ?>" class="btn btn-secondary">Ver Detalles</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="container section bg-light">
    <h2 class="section-title">Hoteles Recomendados</h2>
    <div class="grid">
        <?php foreach ($hoteles_destacados as $hotel): ?>
            <div class="card animate-on-scroll">
                <img src="<?php echo htmlspecialchars($hotel['imagen']); ?>" alt="<?php echo htmlspecialchars($hotel['nombre']); ?>">
                <div class="card-content">
                    <h3><?php echo htmlspecialchars($hotel['nombre']); ?></h3>
                    <p class="precio">$<?php echo number_format($hotel['precio'], 0, ',', '.'); ?> COP / noche</p>
                    <p><?php echo htmlspecialchars(substr($hotel['descripcion'], 0, 100)); ?>...</p>
                    <a href="/FLOWZONE/detalle_hotel.php?id=<?php echo $hotel['id']; ?>" class="btn btn-secondary">Ver Detalles</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="container section">
    <h2 class="section-title">Próximos Eventos</h2>
    <div class="grid">
        <?php foreach ($eventos_proximos as $evento): ?>
            <div class="card animate-on-scroll">
                <img src="<?php echo htmlspecialchars($evento['imagen']); ?>" alt="<?php echo htmlspecialchars($evento['nombre']); ?>">
                <div class="card-content">
                    <h3><?php echo htmlspecialchars($evento['nombre']); ?></h3>
                    <p class="fecha">📅 <?php echo date('d/m/Y', strtotime($evento['fecha'])); ?></p>
                    <p><?php echo htmlspecialchars(substr($evento['descripcion'], 0, 100)); ?>...</p>
                    <a href="/FLOWZONE/eventos.php" class="btn btn-secondary">Ver Más</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
