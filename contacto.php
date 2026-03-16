<?php
require_once 'includes/header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $asunto = trim($_POST['asunto'] ?? '');
    $mensaje = trim($_POST['mensaje'] ?? '');
    
    if (empty($nombre) || empty($email) || empty($asunto) || empty($mensaje)) {
        $error = 'Por favor complete todos los campos';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor ingrese un correo electrónico válido';
    } else {
        // En producción, aquí se enviaría un email
        $success = 'Mensaje enviado exitosamente. Nos pondremos en contacto pronto.';
    }
}
?>

<section class="page-header">
    <div class="container">
        <h1>Contáctanos</h1>
        <p>Estamos aquí para ayudarte</p>
    </div>
</section>

<section class="container section">
    <div class="contacto-container">
        <div class="contacto-info">
            <h2>Información de Contacto</h2>
            
            <div class="info-item">
                <h3>📍 Dirección</h3>
                <p>Parque Principal<br>Ortega, Tolima, Colombia</p>
            </div>
            
            <div class="info-item">
                <h3>📧 Email</h3>
                <p>info@flowzone.com<br>turismo@flowzone.com</p>
            </div>
            
            <div class="info-item">
                <h3>📱 Teléfono</h3>
                <p>+57 320 123 4567<br>+57 310 987 6543</p>
            </div>
            
            <div class="info-item">
                <h3>🕐 Horario de Atención</h3>
                <p>Lunes a Viernes: 8:00 AM - 6:00 PM<br>Sábados: 9:00 AM - 2:00 PM</p>
            </div>
        </div>
        
        <div class="contacto-form-card">
            <h2>Envíanos un Mensaje</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nombre">Nombre Completo</label>
                    <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="asunto">Asunto</label>
                    <input type="text" id="asunto" name="asunto" required value="<?php echo htmlspecialchars($_POST['asunto'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="mensaje">Mensaje</label>
                    <textarea id="mensaje" name="mensaje" rows="6" required><?php echo htmlspecialchars($_POST['mensaje'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Enviar Mensaje</button>
            </form>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
