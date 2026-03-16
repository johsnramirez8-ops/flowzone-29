<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - FlowZone</title>
    <style>
        body { font-family: monospace; max-width: 600px; margin: 3rem auto; padding: 1rem; background: #1e1e1e; color: #d4d4d4; }
        .ok    { color: #4ec9b0; font-weight: bold; font-size: 1.2rem; }
        .error { color: #f44747; font-weight: bold; }
        .box   { background: #252526; border: 1px solid #3c3c3c; padding: 1.5rem; border-radius: 6px; margin: 1rem 0; }
        input  { background: #3c3c3c; border: 1px solid #555; color: #d4d4d4; padding: 0.6rem; border-radius: 4px; width: 100%; margin-top: 0.3rem; font-size: 1rem; }
        button { background: #0e639c; color: #fff; border: none; padding: 0.8rem 2rem; border-radius: 4px; cursor: pointer; font-size: 1rem; margin-top: 1rem; width: 100%; }
        label  { color: #9cdcfe; font-size: 0.9rem; }
    </style>
</head>
<body>
<?php
// ⚠️ ELIMINAR ESTE ARCHIVO DESPUÉS DE USARLO
// Coloca en: C:\xampp\htdocs\FLOWZONE\reset.php
// Accede en: http://localhost/FLOWZONE/reset.php
 
$DB_HOST = 'localhost';
$DB_NAME = 'flowzone';
$DB_USER = 'root';
$DB_PASS = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva = $_POST['nueva_password'] ?? '';
    $correo = trim($_POST['correo'] ?? '');
 
    if (empty($nueva) || strlen($nueva) < 4) {
        echo "<div class='box'><span class='error'>La contraseña debe tener al menos 4 caracteres.</span></div>";
    } else {
        try {
            $pdo = new PDO(
                "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
                $DB_USER, $DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
 
            // Generar hash en este servidor
            $hash = password_hash($nueva, PASSWORD_BCRYPT, ['cost' => 10]);
 
            if (!empty($correo)) {
                // Actualizar solo ese correo
                $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE correo = ?");
                $stmt->execute([$hash, $correo]);
                $filas = $stmt->rowCount();
            } else {
                // Actualizar todos los usuarios de prueba
                $stmt = $pdo->prepare("UPDATE usuarios SET password = ?");
                $stmt->execute([$hash]);
                $filas = $stmt->rowCount();
            }
 
            if ($filas > 0) {
                echo "<div class='box'>";
                echo "<span class='ok'>✓ Contraseña actualizada en $filas usuario(s)</span><br><br>";
                echo "<span style='color:#9cdcfe'>Hash generado:</span><br>";
                echo "<span style='color:#ce9178;font-size:0.85rem;word-break:break-all'>$hash</span><br><br>";
                echo "<span style='color:#4ec9b0'>Ahora prueba el login con la contraseña: <b>$nueva</b></span>";
                echo "</div>";
 
                // Verificar inmediatamente
                $stmt2 = $pdo->prepare("SELECT id, correo, password FROM usuarios" . (!empty($correo) ? " WHERE correo = ?" : ""));
                if (!empty($correo)) $stmt2->execute([$correo]);
                else $stmt2->execute();
                $usuarios = $stmt2->fetchAll();
 
                echo "<div class='box'><span style='color:#9cdcfe'>Verificación:</span><br>";
                foreach ($usuarios as $u) {
                    $ok = password_verify($nueva, $u['password']);
                    echo "{$u['correo']}: " . ($ok ? "<span class='ok'>✓ CORRECTO</span>" : "<span class='error'>✗ FALLO</span>") . "<br>";
                }
                echo "</div>";
            } else {
                echo "<div class='box'><span class='error'>✗ No se actualizó ningún usuario. ¿Existe ese correo en la BD?</span></div>";
            }
 
        } catch (PDOException $e) {
            echo "<div class='box'><span class='error'>Error BD: " . htmlspecialchars($e->getMessage()) . "</span></div>";
        }
    }
}
?>
 
<div class='box'>
    <h2 style="color:#4ec9b0;margin-top:0">🔑 Resetear contraseña</h2>
    <form method="POST">
        <div>
            <label>Correo (dejar vacío = actualizar TODOS los usuarios):</label>
            <input type="email" name="correo" placeholder="admin@flowzone.com — o vacío para todos"
                   value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
        </div>
        <div style="margin-top:1rem">
            <label>Nueva contraseña:</label>
            <input type="text" name="nueva_password" placeholder="admin123" required
                   value="<?= htmlspecialchars($_POST['nueva_password'] ?? 'admin123') ?>">
        </div>
        <button type="submit">Actualizar contraseña en la BD</button>
    </form>
</div>
 
<p style="color:#555;font-size:0.8rem;margin-top:2rem">⚠️ Elimina este archivo después de usarlo.</p>
</body>
</html>