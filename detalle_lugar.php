<?php
require_once 'includes/conexion.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

$id = $_GET['id'] ?? 0;

// Obtener lugar
$stmt = $pdo->prepare("SELECT * FROM lugares WHERE id = ?");
$stmt->execute([$id]);
$lugar = $stmt->fetch();

if (!$lugar) {
    header('Location: /FLOWZONE/lugares.php');
    exit;
}

// Obtener calificación promedio
$stmt = $pdo->prepare("SELECT AVG(calificacion) as promedio, COUNT(*) as total FROM calificaciones WHERE tipo = 'lugar' AND item_id = ?");
$stmt->execute([$id]);
$calificacion = $stmt->fetch();

// Obtener comentarios
$stmt = $pdo->prepare("
    SELECT c.*, u.nombre as usuario_nombre 
    FROM comentarios c 
    JOIN usuarios u ON c.usuario_id = u.id 
    WHERE c.lugar_id = ? 
    ORDER BY c.fecha DESC
");
$stmt->execute([$id]);
$comentarios = $stmt->fetchAll();

// Verificar si está en favoritos
$es_favorito = false;
if (estaAutenticado()) {
    $stmt = $pdo->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND tipo = 'lugar' AND item_id = ?");
    $stmt->execute([obtenerUsuarioId(), $id]);
    $es_favorito = $stmt->fetch() ? true : false;
}
?>

<section class="detalle-header" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('<?php echo htmlspecialchars($lugar['imagen']); ?>');">
    <div class="container">
        <h1><?php echo htmlspecialchars($lugar['nombre']); ?></h1>
        <p class="categoria"><?php echo htmlspecialchars($lugar['categoria']); ?></p>
    </div>
</section>

<section class="container section">
    <div class="detalle-content">
        <div class="detalle-main">
            <div class="detalle-info">
                <h2>Descripción</h2>
                <p><?php echo nl2br(htmlspecialchars($lugar['descripcion'])); ?></p>
                
                <div class="info-grid">
                    <div class="info-item">
                        <strong>📍 Ubicación:</strong>
                        <p><?php echo htmlspecialchars($lugar['ubicacion']); ?></p>
                    </div>
                    
                    <?php if ($lugar['horario']): ?>
                        <div class="info-item">
                            <strong>🕐 Horario:</strong>
                            <p><?php echo htmlspecialchars($lugar['horario']); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <strong>💵 Entrada:</strong>
                        <p><?php echo $lugar['precio_entrada'] > 0 ? '$' . number_format($lugar['precio_entrada'], 0, ',', '.') . ' COP' : 'Gratuita'; ?></p>
                    </div>
                </div>
                
                <?php if ($lugar['latitud'] && $lugar['longitud']): ?>
                    <div class="mapa">
                        <h3>Ubicación en el mapa</h3>
                        <iframe 
                            width="100%" 
                            height="400" 
                            frameborder="0" 
                            style="border:0" 
                            src="https://www.google.com/maps?q=<?php echo $lugar['latitud']; ?>,<?php echo $lugar['longitud']; ?>&output=embed"
                            allowfullscreen>
                        </iframe>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="comentarios-section">
                <h2>Comentarios</h2>
                
                <?php if (estaAutenticado()): ?>
                    <form id="form-comentario" class="comentario-form">
                        <input type="hidden" name="lugar_id" value="<?php echo $id; ?>">
                        <textarea name="comentario" placeholder="Escribe tu comentario..." required></textarea>
                        <button type="submit" class="btn btn-primary">Publicar Comentario</button>
                    </form>
                <?php else: ?>
                    <p class="login-prompt">
                        <a href="/FLOWZONE/login.php">Inicia sesión</a> para dejar un comentario
                    </p>
                <?php endif; ?>
                
                <div id="lista-comentarios">
                    <?php foreach ($comentarios as $comentario): ?>
                        <div class="comentario">
                            <div class="comentario-header">
                                <strong><?php echo htmlspecialchars($comentario['usuario_nombre']); ?></strong>
                                <span class="fecha"><?php echo date('d/m/Y H:i', strtotime($comentario['fecha'])); ?></span>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($comentario['comentario'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="detalle-sidebar">
            <div class="calificacion-box">
                <h3>Calificación</h3>
                <div class="rating-display">
                    <span class="rating-number"><?php echo $calificacion['promedio'] ? number_format($calificacion['promedio'], 1) : 'N/A'; ?></span>
                    <span class="rating-stars">⭐⭐⭐⭐⭐</span>
                    <span class="rating-count">(<?php echo $calificacion['total']; ?> valoraciones)</span>
                </div>
                
                <?php if (estaAutenticado()): ?>
                    <div class="rating-form">
                        <p>Tu calificación:</p>
                        <div class="stars" data-tipo="lugar" data-id="<?php echo $id; ?>">
                            <span class="star" data-value="1">⭐</span>
                            <span class="star" data-value="2">⭐</span>
                            <span class="star" data-value="3">⭐</span>
                            <span class="star" data-value="4">⭐</span>
                            <span class="star" data-value="5">⭐</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (estaAutenticado()): ?>
                <button class="btn btn-favorito <?php echo $es_favorito ? 'active' : ''; ?>" 
                        data-tipo="lugar" 
                        data-id="<?php echo $id; ?>">
                    <?php echo $es_favorito ? '❤️ En Favoritos' : '🤍 Agregar a Favoritos'; ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
const lugarId = <?php echo $id; ?>;
const usuarioAutenticado = <?php echo estaAutenticado() ? 'true' : 'false'; ?>;
</script>

<?php require_once 'includes/footer.php'; ?>
