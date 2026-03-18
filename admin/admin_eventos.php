<?php
require_once '../includes/conexion.php';
require_once '../includes/auth.php';
requiereAutenticacion();
requiereAdmin();

$mensaje = '';

if (isset($_GET['eliminar'])) {
    $stmt = $pdo->prepare("DELETE FROM eventos WHERE id = ?");
    $stmt->execute([$_GET['eliminar']]);
    $mensaje = 'Evento eliminado';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $datos = [$_POST['nombre'], $_POST['descripcion'], $_POST['fecha'], $_POST['hora'], 
              $_POST['ubicacion'], $_POST['categoria'], $_POST['imagen'], $_POST['precio'], 
              $_POST['organizador'], $_POST['contacto']];
    
    if ($id) {
        $stmt = $pdo->prepare("UPDATE eventos SET nombre=?, descripcion=?, fecha=?, hora=?, ubicacion=?, categoria=?, imagen=?, precio=?, organizador=?, contacto=? WHERE id=?");
        $stmt->execute([...$datos, $id]);
        $mensaje = 'Evento actualizado';
    } else {
        $stmt = $pdo->prepare("INSERT INTO eventos (nombre, descripcion, fecha, hora, ubicacion, categoria, imagen, precio, organizador, contacto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($datos);
        $mensaje = 'Evento agregado';
    }
}

$evento_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM eventos WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $evento_editar = $stmt->fetch();
}

$stmt = $pdo->query("SELECT * FROM eventos ORDER BY fecha DESC");
$eventos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Eventos - FlowZone Admin</title>
    <link rel="stylesheet" href="/FLOWZONE/assets/css/style.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-brand"><h2>🌄 FlowZone Admin</h2></div>
            <nav class="admin-nav">
                <a href="/FLOWZONE/admin/dashboard.php">📊 Dashboard</a>
                <a href="/FLOWZONE/admin/admin_lugares.php">📍 Lugares</a>
                <a href="/FLOWZONE/admin/admin_hoteles.php">🏨 Hoteles</a>
                <a href="/FLOWZONE/admin/admin_eventos.php" class="active">📅 Eventos</a>
                <a href="/FLOWZONE/admin/admin_empresas.php">🏢 Empresas</a>
                <a href="/FLOWZONE/admin/admin_reservas.php">📋 Reservas</a>
                <a href="/FLOWZONE/logout.php">🚪 Cerrar Sesión</a>
            </nav>
        </aside>
        
        <main class="admin-main">
            <div class="admin-header"><h1>Gestión de Eventos</h1></div>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            
            <div class="admin-section">
                <h2><?php echo $evento_editar ? 'Editar Evento' : 'Agregar Evento'; ?></h2>
                <form method="POST" class="admin-form">
                    <?php if ($evento_editar): ?>
                        <input type="hidden" name="id" value="<?php echo $evento_editar['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="nombre" required value="<?php echo $evento_editar['nombre'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Categoría</label>
                            <input type="text" name="categoria" required value="<?php echo $evento_editar['categoria'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="descripcion" rows="3" required><?php echo $evento_editar['descripcion'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Fecha</label>
                            <input type="date" name="fecha" required value="<?php echo $evento_editar['fecha'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Hora</label>
                            <input type="time" name="hora" value="<?php echo $evento_editar['hora'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Precio</label>
                            <input type="number" name="precio" value="<?php echo $evento_editar['precio'] ?? '0'; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ubicación</label>
                            <input type="text" name="ubicacion" required value="<?php echo $evento_editar['ubicacion'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Organizador</label>
                            <input type="text" name="organizador" value="<?php echo $evento_editar['organizador'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Contacto</label>
                            <input type="text" name="contacto" value="<?php echo $evento_editar['contacto'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>URL de Imagen</label>
                        <input type="url" name="imagen" required value="<?php echo $evento_editar['imagen'] ?? ''; ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><?php echo $evento_editar ? 'Actualizar' : 'Agregar'; ?></button>
                    <?php if ($evento_editar): ?>
                        <a href="/FLOWZONE/admin/admin_eventos.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="admin-section">
                <h2>Eventos Registrados</h2>
                <table class="admin-table">
                    <thead>
                        <tr><th>ID</th><th>Nombre</th><th>Fecha</th><th>Ubicación</th><th>Precio</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eventos as $evento): ?>
                            <tr>
                                <td><?php echo $evento['id']; ?></td>
                                <td><?php echo htmlspecialchars($evento['nombre']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($evento['fecha'])); ?></td>
                                <td><?php echo htmlspecialchars($evento['ubicacion']); ?></td>
                                <td>$<?php echo number_format($evento['precio'], 0); ?></td>
                                <td>
                                    <a href="?editar=<?php echo $evento['id']; ?>" class="btn-small btn-edit">Editar</a>
                                    <a href="?eliminar=<?php echo $evento['id']; ?>" class="btn-small btn-delete" onclick="return confirm('¿Eliminar?')">Eliminar</a>
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
