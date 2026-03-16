<?php
require_once 'includes/conexion.php';
require_once 'includes/header.php';

// Obtener categorías
$stmt = $pdo->query("SELECT DISTINCT categoria FROM eventos ORDER BY categoria");
$categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Filtros
$categoria_filtro = $_GET['categoria'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Construir consulta
$sql = "SELECT * FROM eventos WHERE fecha >= CURDATE()";
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

$sql .= " ORDER BY fecha ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$eventos = $stmt->fetchAll();
?>

<section class="page-header">
    <div class="container">
        <h1>Eventos Culturales</h1>
        <p>Descubre los próximos eventos en Ortega, Tolima</p>
    </div>
</section>

<section class="container section">
    <div class="filters">
        <form method="GET" action="" class="filter-form">
            <input type="text" name="busqueda" placeholder="Buscar eventos..." value="<?php echo htmlspecialchars($busqueda); ?>">
            
            <select name="categoria">
                <option value="">Todas las categorías</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $categoria_filtro === $cat ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="/FLOWZONE/eventos.php" class="btn btn-secondary">Limpiar</a>
        </form>
    </div>
    
    <div class="grid">
        <?php if (empty($eventos)): ?>
            <p class="no-results">No hay eventos próximos programados.</p>
        <?php else: ?>
            <?php foreach ($eventos as $evento): ?>
                <div class="card animate-on-scroll">
                    <img src="<?php echo htmlspecialchars($evento['imagen']); ?>" alt="<?php echo htmlspecialchars($evento['nombre']); ?>">
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($evento['nombre']); ?></h3>
                        <p class="categoria"><?php echo htmlspecialchars($evento['categoria']); ?></p>
                        <p class="fecha">📅 <?php echo date('d/m/Y', strtotime($evento['fecha'])); ?></p>
                        <?php if ($evento['hora']): ?>
                            <p class="hora">🕐 <?php echo date('H:i', strtotime($evento['hora'])); ?></p>
                        <?php endif; ?>
                        <p class="ubicacion">📍 <?php echo htmlspecialchars($evento['ubicacion']); ?></p>
                        <?php if ($evento['precio'] > 0): ?>
                            <p class="precio">💵 $<?php echo number_format($evento['precio'], 0, ',', '.'); ?> COP</p>
                        <?php else: ?>
                            <p class="precio">✅ Entrada gratuita</p>
                        <?php endif; ?>
                        <p><?php echo htmlspecialchars(substr($evento['descripcion'], 0, 120)); ?>...</p>
                        <?php if ($evento['contacto']): ?>
                            <p class="contacto">📱 <?php echo htmlspecialchars($evento['contacto']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
