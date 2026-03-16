<?php
require_once 'includes/conexion.php';
require_once 'includes/auth.php';

requiereAutenticacion();

require_once 'includes/header.php';

$hotel_id = $_GET['hotel_id'] ?? 0;
$error = '';
$success = '';

// Obtener hotel
$stmt = $pdo->prepare("SELECT * FROM hoteles WHERE id = ? AND disponibilidad = 1");
$stmt->execute([$hotel_id]);
$hotel = $stmt->fetch();

if (!$hotel) {
    header('Location: /FLOWZONE/hoteles.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha_entrada = $_POST['fecha_entrada'] ?? '';
    $fecha_salida = $_POST['fecha_salida'] ?? '';
    $num_personas = $_POST['num_personas'] ?? 0;
    
    if (empty($fecha_entrada) || empty($fecha_salida) || $num_personas < 1) {
        $error = 'Por favor complete todos los campos';
    } elseif (strtotime($fecha_entrada) < strtotime('today')) {
        $error = 'La fecha de entrada no puede ser anterior a hoy';
    } elseif (strtotime($fecha_salida) <= strtotime($fecha_entrada)) {
        $error = 'La fecha de salida debe ser posterior a la fecha de entrada';
    } elseif ($num_personas > $hotel['capacidad']) {
        $error = 'El número de personas excede la capacidad del hotel';
    } else {
        // Calcular precio total
        $dias = (strtotime($fecha_salida) - strtotime($fecha_entrada)) / (60 * 60 * 24);
        $precio_total = $dias * $hotel['precio'];
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO reservas (usuario_id, hotel_id, fecha_entrada, fecha_salida, num_personas, precio_total, estado) 
                VALUES (?, ?, ?, ?, ?, ?, 'pendiente')
            ");
            $stmt->execute([
                obtenerUsuarioId(),
                $hotel_id,
                $fecha_entrada,
                $fecha_salida,
                $num_personas,
                $precio_total
            ]);
            
            $success = 'Reserva realizada exitosamente. Total: $' . number_format($precio_total, 0, ',', '.') . ' COP';
        } catch (PDOException $e) {
            $error = 'Error al procesar la reserva. Intente nuevamente.';
        }
    }
}
?>

<section class="page-header">
    <div class="container">
        <h1>Reservar Hotel</h1>
        <p><?php echo htmlspecialchars($hotel['nombre']); ?></p>
    </div>
</section>

<section class="container section">
    <div class="reserva-container">
        <div class="hotel-info-card">
            <img src="<?php echo htmlspecialchars($hotel['imagen']); ?>" alt="<?php echo htmlspecialchars($hotel['nombre']); ?>">
            <h3><?php echo htmlspecialchars($hotel['nombre']); ?></h3>
            <p class="precio">$<?php echo number_format($hotel['precio'], 0, ',', '.'); ?> COP / noche</p>
            <p>📍 <?php echo htmlspecialchars($hotel['ubicacion']); ?></p>
            <p>👥 Capacidad: <?php echo $hotel['capacidad']; ?> personas</p>
        </div>
        
        <div class="reserva-form-card">
            <h2>Datos de la Reserva</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <a href="/FLOWZONE/hoteles.php" class="btn btn-primary">Ver más hoteles</a>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="fecha_entrada">Fecha de Entrada</label>
                        <input type="date" id="fecha_entrada" name="fecha_entrada" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_salida">Fecha de Salida</label>
                        <input type="date" id="fecha_salida" name="fecha_salida" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="num_personas">Número de Personas</label>
                        <input type="number" id="num_personas" name="num_personas" required min="1" max="<?php echo $hotel['capacidad']; ?>">
                    </div>
                    
                    <div id="precio-calculado" class="precio-calculado"></div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Confirmar Reserva</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
const precioPorNoche = <?php echo $hotel['precio']; ?>;

document.getElementById('fecha_entrada')?.addEventListener('change', calcularPrecio);
document.getElementById('fecha_salida')?.addEventListener('change', calcularPrecio);

function calcularPrecio() {
    const entrada = document.getElementById('fecha_entrada').value;
    const salida = document.getElementById('fecha_salida').value;
    
    if (entrada && salida) {
        const dias = (new Date(salida) - new Date(entrada)) / (1000 * 60 * 60 * 24);
        if (dias > 0) {
            const total = dias * precioPorNoche;
            document.getElementById('precio-calculado').innerHTML = 
                `<strong>Total: $${total.toLocaleString('es-CO')} COP</strong> (${dias} ${dias === 1 ? 'noche' : 'noches'})`;
        }
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
