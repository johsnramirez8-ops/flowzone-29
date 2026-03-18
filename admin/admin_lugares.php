<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';

requiereAutenticacion();
requiereAdmin();

$mensaje = '';

// Eliminar lugar
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $pdo->prepare("DELETE FROM lugares WHERE id = ?");
    $stmt->execute([$id]);
    $mensaje = 'Lugar eliminado exitosamente';
}

// Agregar/Editar lugar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $ubicacion = $_POST['ubicacion'];
    $latitud = $_POST['latitud'];
    $longitud = $_POST['longitud'];
    $categoria = $_POST['categoria'];
    $imagen = $_POST['imagen'];
    $precio_entrada = $_POST['precio_entrada'];
    $horario = $_POST['horario'];
    
    if ($id) {
        $stmt = $pdo->prepare("UPDATE lugares SET nombre=?, descripcion=?, ubicacion=?, latitud=?, longitud=?, categoria=?, imagen=?, precio_entrada=?, horario=? WHERE id=?");
        $stmt->execute([$nombre, $descripcion, $ubicacion, $latitud, $longitud, $categoria, $imagen, $precio_entrada, $horario, $id]);
        $mensaje = 'Lugar actualizado exitosamente';
    } else {
        $stmt = $pdo->prepare("INSERT INTO lugares (nombre, descripcion, ubicacion, latitud, longitud, categoria, imagen, precio_entrada, horario) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $descripcion, $ubicacion, $latitud, $longitud, $categoria, $imagen, $precio_entrada, $horario]);
        $mensaje = 'Lugar agregado exitosamente';
    }
}

// Obtener lugar para editar
$lugar_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM lugares WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $lugar_editar = $stmt->fetch();
}

// Listar lugares
$stmt = $pdo->query("SELECT * FROM lugares ORDER BY creado_en DESC");
$lugares = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Lugares - FlowZone Admin</title>
    <link rel="stylesheet" href="/FLOWZONE/assets/css/style.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <h2>🌄 FlowZone Admin</h2>
            </div>
            <nav class="admin-nav">
                <a href="/FLOWZONE/admin/dashboard.php">📊 Dashboard</a>
                <a href="/FLOWZONE/admin/admin_lugares.php" class="active">📍 Lugares</a>
                <a href="/FLOWZONE/admin/admin_hoteles.php">🏨 Hoteles</a>
                <a href="/FLOWZONE/admin/admin_eventos.php">📅 Eventos</a>
                <a href="/FLOWZONE/admin/admin_empresas.php">🏢 Empresas</a>
                <a href="/FLOWZONE/admin/admin_reservas.php">📋 Reservas</a>
                <a href="/FLOWZONE/logout.php">🚪 Cerrar Sesión</a>
            </nav>
        </aside>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>Gestión de Lugares Turísticos</h1>
            </div>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            
            <div class="admin-section">
                <h2><?php echo $lugar_editar ? 'Editar Lugar' : 'Agregar Nuevo Lugar'; ?></h2>
                <form method="POST" class="admin-form">
                    <?php if ($lugar_editar): ?>
                        <input type="hidden" name="id" value="<?php echo $lugar_editar['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="nombre" required value="<?php echo $lugar_editar['nombre'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Categoría</label>
                            <input type="text" name="categoria" required value="<?php echo $lugar_editar['categoria'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="descripcion" rows="4" required><?php echo $lugar_editar['descripcion'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ubicación</label>
                            <input type="text" name="ubicacion" required value="<?php echo $lugar_editar['ubicacion'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Horario</label>
                            <input type="text" name="horario" value="<?php echo $lugar_editar['horario'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Latitud</label>
                            <input type="number" step="0.00000001" name="latitud" value="<?php echo $lugar_editar['latitud'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Longitud</label>
                            <input type="number" step="0.00000001" name="longitud" value="<?php echo $lugar_editar['longitud'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Precio Entrada</label>
                            <input type="number" step="0.01" name="precio_entrada" value="<?php echo $lugar_editar['precio_entrada'] ?? '0'; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>URL de Imagen</label>
                        <input type="url" name="imagen" required value="<?php echo $lugar_editar['imagen'] ?? ''; ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><?php echo $lugar_editar ? 'Actualizar' : 'Agregar'; ?> Lugar</button>
                    <?php if ($lugar_editar): ?>
                        <a href="/FLOWZONE/admin/admin_lugares.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="admin-section">
                <h2>Lugares Registrados</h2>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Ubicación</th>
                                <th>Precio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lugares as $lugar): ?>
                                <tr>
                                    <td><?php echo $lugar['id']; ?></td>
                                    <td><?php echo htmlspecialchars($lugar['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($lugar['categoria']); ?></td>
                                    <td><?php echo htmlspecialchars($lugar['ubicacion']); ?></td>
                                    <td>$<?php echo number_format($lugar['precio_entrada'], 0); ?></td>
                                    <td>
                                        <a href="?editar=<?php echo $lugar['id']; ?>" class="btn-small btn-edit">Editar</a>
                                        <a href="?eliminar=<?php echo $lugar['id']; ?>" class="btn-small btn-delete" onclick="return confirm('¿Eliminar este lugar?')">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
