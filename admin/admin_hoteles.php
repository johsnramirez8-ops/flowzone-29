<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';
requiereAutenticacion();
requiereAdmin();

$mensaje = '';

if (isset($_GET['eliminar'])) {
    $stmt = $pdo->prepare("DELETE FROM hoteles WHERE id = ?");
    $stmt->execute([$_GET['eliminar']]);
    $mensaje = 'Hotel eliminado';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $datos = [$_POST['nombre'], $_POST['descripcion'], $_POST['precio'], $_POST['ubicacion'], 
              $_POST['latitud'], $_POST['longitud'], $_POST['imagen'], $_POST['servicios'], 
              $_POST['capacidad'], isset($_POST['disponibilidad']) ? 1 : 0, $_POST['telefono'], $_POST['email']];
    
    if ($id) {
        $stmt = $pdo->prepare("UPDATE hoteles SET nombre=?, descripcion=?, precio=?, ubicacion=?, latitud=?, longitud=?, imagen=?, servicios=?, capacidad=?, disponibilidad=?, telefono=?, email=? WHERE id=?");
        $stmt->execute([...$datos, $id]);
        $mensaje = 'Hotel actualizado';
    } else {
        $stmt = $pdo->prepare("INSERT INTO hoteles (nombre, descripcion, precio, ubicacion, latitud, longitud, imagen, servicios, capacidad, disponibilidad, telefono, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($datos);
        $mensaje = 'Hotel agregado';
    }
}

$hotel_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM hoteles WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $hotel_editar = $stmt->fetch();
}

$stmt = $pdo->query("SELECT * FROM hoteles ORDER BY creado_en DESC");
$hoteles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Hoteles - FlowZone Admin</title>
    <link rel="stylesheet" href="/FLOWZONE/assets/css/style.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-brand"><h2> FlowZone Admin</h2></div>
            <nav class="admin-nav">
                <a href="/FLOWZONE/admin/dashboard.php"> Dashboard</a>
                <a href="/FLOWZONE/admin/admin_lugares.php"> Lugares</a>
                <a href="/FLOWZONE/admin/admin_hoteles.php" class="active"> Hoteles</a>
                <a href="/FLOWZONE/admin/admin_eventos.php"> Eventos</a>
                <a href="/FLOWZONE/admin/admin_empresas.php"> Empresas</a>
                <a href="/FLOWZONE/admin/admin_reservas.php"> Reservas</a>
                <a href="/FLOWZONE/index.php"> Volver al Sitio</a>
                <a href="/FLOWZONE/logout.php"> Cerrar Sesión</a>
            </nav>
        </aside>
        
        <main class="admin-main">
            <div class="admin-header"><h1>Gestión de Hoteles</h1></div>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            
            <div class="admin-section">
                <h2><?php echo $hotel_editar ? 'Editar Hotel' : 'Agregar Hotel'; ?></h2>
                <form method="POST" class="admin-form">
                    <?php if ($hotel_editar): ?>
                        <input type="hidden" name="id" value="<?php echo $hotel_editar['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="nombre" required value="<?php echo $hotel_editar['nombre'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Precio por Noche</label>
                            <input type="number" name="precio" required value="<?php echo $hotel_editar['precio'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="descripcion" rows="3" required><?php echo $hotel_editar['descripcion'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ubicación</label>
                            <input type="text" name="ubicacion" required value="<?php echo $hotel_editar['ubicacion'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Capacidad</label>
                            <input type="number" name="capacidad" value="<?php echo $hotel_editar['capacidad'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Latitud</label>
                            <input type="number" step="0.00000001" name="latitud" value="<?php echo $hotel_editar['latitud'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Longitud</label>
                            <input type="number" step="0.00000001" name="longitud" value="<?php echo $hotel_editar['longitud'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Servicios (separados por coma)</label>
                        <input type="text" name="servicios" value="<?php echo $hotel_editar['servicios'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" value="<?php echo $hotel_editar['telefono'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo $hotel_editar['email'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>URL de Imagen</label>
                        <input type="url" name="imagen" required value="<?php echo $hotel_editar['imagen'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="disponibilidad" <?php echo ($hotel_editar['disponibilidad'] ?? 1) ? 'checked' : ''; ?>>
                            Disponible
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><?php echo $hotel_editar ? 'Actualizar' : 'Agregar'; ?></button>
                    <?php if ($hotel_editar): ?>
                        <a href="/FLOWZONE/admin/admin_hoteles.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="admin-section">
                <h2>Hoteles Registrados</h2>
                <table class="admin-table">
                    <thead>
                        <tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Ubicación</th><th>Disponible</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hoteles as $hotel): ?>
                            <tr>
                                <td><?php echo $hotel['id']; ?></td>
                                <td><?php echo htmlspecialchars($hotel['nombre']); ?></td>
                                <td>$<?php echo number_format($hotel['precio'], 0); ?></td>
                                <td><?php echo htmlspecialchars($hotel['ubicacion']); ?></td>
                                <td><?php echo $hotel['disponibilidad'] ? '✓' : '✗'; ?></td>
                                <td>
                                    <a href="?editar=<?php echo $hotel['id']; ?>" class="btn-small btn-edit">Editar</a>
                                    <a href="?eliminar=<?php echo $hotel['id']; ?>" class="btn-small btn-delete" onclick="return confirm('¿Eliminar?')">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
