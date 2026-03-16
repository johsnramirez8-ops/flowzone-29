<?php
require_once 'includes/conexion.php';
require_once 'includes/header.php';

// Obtener tipos
$stmt = $pdo->query("SELECT DISTINCT tipo FROM gastronomia ORDER BY tipo");
$tipos = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Filtros
$tipo_filtro = $_GET['tipo'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Construir consulta
$sql = "SELECT * FROM gastronomia WHERE 1=1";
$params = [];

if ($tipo_filtro) {
    $sql .= " AND tipo = ?";
    $params[] = $tipo_filtro;
}

if ($busqueda) {
    $sql .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

$sql .= " ORDER BY creado_en DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$platos = $stmt->fetchAll();
?>

<section class="page-header">
    <div class="container">
        <h1>Gastronomía Local</h1>
        <p>Descubre los sabores tradicionales de Ortega, Tolima</p>
    </div>
</section>

<section class="container section">
    <div class="filters">
        <form method="GET" action="" class="filter-form">
            <input type="text" name="busqueda" placeholder="Buscar platos..." value="<?php echo htmlspecialchars($busqueda); ?>">
            
            <select name="tipo">
                <option value="">Todos los tipos</option>
                <?php foreach ($tipos as $tipo): ?>
                    <option value="<?php echo htmlspecialchars($tipo); ?>" <?php echo $tipo_filtro === $tipo ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tipo); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="/FLOWZONE/gastronomia.php" class="btn btn-secondary">Limpiar</a>
        </form>
    </div>
    
    <div class="grid">
        <?php if (empty($platos)): ?>
            <p class="no-results">No se encontraron platos con los filtros seleccionados.</p>
        <?php else: ?>
            <?php foreach ($platos as $plato): ?>
                <div class="card animate-on-scroll">
                    <img src="<?php echo htmlspecialchars($plato['imagen']); ?>" alt="<?php echo htmlspecialchars($plato['nombre']); ?>">
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($plato['nombre']); ?></h3>
                        <p class="categoria"><?php echo htmlspecialchars($plato['tipo']); ?></p>
                        <p class="precio">💵 $<?php echo number_format($plato['precio_promedio'], 0, ',', '.'); ?> COP</p>
                        <p><?php echo htmlspecialchars(substr($plato['descripcion'], 0, 120)); ?>...</p>
                        <div class="restaurante-info">
                            <p><strong>🍽️ <?php echo htmlspecialchars($plato['restaurante']); ?></strong></p>
                            <?php if ($plato['direccion']): ?>
                                <p class="ubicacion">📍 <?php echo htmlspecialchars($plato['direccion']); ?></p>
                            <?php endif; ?>
                            <?php if ($plato['telefono']): ?>
                                <p>📱 <?php echo htmlspecialchars($plato['telefono']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
