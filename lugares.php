<?php
require_once 'includes/conexion.php';
require_once 'includes/header.php';

// Obtener categorías
$stmt = $pdo->query("SELECT DISTINCT categoria FROM lugares ORDER BY categoria");
$categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Filtros
$categoria_filtro = $_GET['categoria'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Construir consulta
$sql = "SELECT * FROM lugares WHERE 1=1";
$params = [];

if ($categoria_filtro) {
    $sql .= " AND categoria = ?";
    $params[] = $categoria_filtro;
}

if ($busqueda) {
    $sql .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

$sql .= " ORDER BY creado_en DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lugares = $stmt->fetchAll();
?>

<section class="page-header">
    <div class="container">
        <h1>Lugares Turísticos</h1>
        <p>Descubre los mejores destinos de Ortega, Tolima</p>
    </div>
</section>

<section class="container section">
    <div class="filters">
        <form method="GET" action="" class="filter-form">
            <input type="text" name="busqueda" placeholder="Buscar lugares..." value="<?php echo htmlspecialchars($busqueda); ?>">
            
            <select name="categoria">
                <option value="">Todas las categorías</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $categoria_filtro === $cat ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="/FLOWZONE/lugares.php" class="btn btn-secondary">Limpiar</a>
        </form>
    </div>
    
    <div class="grid">
        <?php if (empty($lugares)): ?>
            <p class="no-results">No se encontraron lugares con los filtros seleccionados.</p>
        <?php else: ?>
            <?php foreach ($lugares as $lugar): ?>
                <div class="card animate-on-scroll">
                    <img src="<?php echo htmlspecialchars($lugar['imagen']); ?>" alt="<?php echo htmlspecialchars($lugar['nombre']); ?>">
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($lugar['nombre']); ?></h3>
                        <p class="categoria"><?php echo htmlspecialchars($lugar['categoria']); ?></p>
                        <p class="ubicacion">📍 <?php echo htmlspecialchars($lugar['ubicacion']); ?></p>
                        <?php if ($lugar['precio_entrada'] > 0): ?>
                            <p class="precio">💵 $<?php echo number_format($lugar['precio_entrada'], 0, ',', '.'); ?> COP</p>
                        <?php else: ?>
                            <p class="precio">✅ Entrada gratuita</p>
                        <?php endif; ?>
                        <p><?php echo htmlspecialchars(substr($lugar['descripcion'], 0, 120)); ?>...</p>
                        <a href="/FLOWZONE/detalle_lugar.php?id=<?php echo $lugar['id']; ?>" class="btn btn-secondary">Ver Detalles</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
