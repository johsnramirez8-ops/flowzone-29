<?php
require_once 'includes/conexion.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

$busqueda  = $_GET['busqueda']  ?? '';
$precio_max = $_GET['precio_max'] ?? '';

$sql    = "SELECT * FROM hoteles WHERE disponibilidad = 1";
$params = [];

if ($busqueda) {
    $sql .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}
if ($precio_max) {
    $sql .= " AND precio <= ?";
    $params[] = $precio_max;
}
$sql .= " ORDER BY creado_en DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$hoteles = $stmt->fetchAll();
?>

<section class="page-header">
    <div class="container">
        <h1>Hoteles en Ortega</h1>
        <p>Encuentra el alojamiento perfecto para tu estadía</p>
    </div>
</section>

<section class="container section">
    <div class="filters">
        <form method="GET" action="" class="filter-form">
            <input type="text" name="busqueda" placeholder="Buscar hoteles..."
                   value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
            <input type="number" name="precio_max" placeholder="Precio máximo COP"
                   value="<?= htmlspecialchars($precio_max, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="/FLOWZONE/hoteles.php" class="btn btn-secondary">Limpiar</a>
        </form>
    </div>

    <div class="grid">
        <?php if (empty($hoteles)): ?>
            <p class="no-results">No se encontraron hoteles disponibles.</p>
        <?php else: ?>
            <?php foreach ($hoteles as $hotel): ?>
                <div class="card animate-on-scroll">
                    <img src="<?= htmlspecialchars($hotel['imagen'], ENT_QUOTES, 'UTF-8') ?>"
                         alt="<?= htmlspecialchars($hotel['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="card-content">
                        <h3><?= htmlspecialchars($hotel['nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="precio">$<?= number_format($hotel['precio'], 0, ',', '.') ?> COP / noche</p>
                        <p class="ubicacion">📍 <?= htmlspecialchars($hotel['ubicacion'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p><?= htmlspecialchars(substr($hotel['descripcion'], 0, 110), ENT_QUOTES, 'UTF-8') ?>...</p>

                        <div class="card-actions">
                            <a href="/FLOWZONE/detalle_hotel.php?id=<?= $hotel['id'] ?>"
                               class="btn btn-secondary">Ver Detalles</a>

                            <?php if (estaAutenticado()): ?>
                                <a href="/FLOWZONE/reservar.php?hotel_id=<?= $hotel['id'] ?>"
                                   class="btn btn-primary">🛒 Reservar</a>
                            <?php else: ?>
                                <a href="/FLOWZONE/login.php"
                                   class="btn btn-primary"
                                   title="Inicia sesión para reservar">🔒 Reservar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
