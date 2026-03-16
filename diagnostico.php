<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>FlowZone - Diagnóstico</title>
    <style>
        body { font-family: monospace; max-width: 900px; margin: 2rem auto; padding: 1rem; background: #1e1e1e; color: #d4d4d4; }
        h2 { color: #4ec9b0; border-bottom: 1px solid #333; padding-bottom: 0.5rem; }
        .ok    { color: #4ec9b0; font-weight: bold; }
        .error { color: #f44747; font-weight: bold; }
        .warn  { color: #dcdcaa; font-weight: bold; }
        .box   { background: #252526; border: 1px solid #3c3c3c; padding: 1rem; border-radius: 6px; margin: 0.5rem 0; }
        .label { color: #9cdcfe; }
        .val   { color: #ce9178; }
        form   { background: #252526; border: 1px solid #3c3c3c; padding: 1.5rem; border-radius: 6px; margin-top: 1rem; }
        input  { background: #3c3c3c; border: 1px solid #555; color: #d4d4d4; padding: 0.5rem; border-radius: 4px; width: 300px; }
        button { background: #0e639c; color: #fff; border: none; padding: 0.6rem 1.5rem; border-radius: 4px; cursor: pointer; margin-top: 0.5rem; }
    </style>
</head>
<body>
<?php
// ⚠️ SOLO PARA DIAGNÓSTICO - ELIMINAR DESPUÉS DE USARLO
// Coloca este archivo en: C:\xampp\htdocs\FLOWZONE\diagnostico.php
// Accede desde: http://localhost/FLOWZONE/diagnostico.php

define('DB_HOST', 'localhost');
define('DB_NAME', 'flowzone');
define('DB_USER', 'root');
define('DB_PASS', '');

echo "<h2>1. Conexión a la base de datos</h2>";
try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<div class='box'><span class='ok'>✓ Conexión exitosa</span> — BD: <span class='val'>".DB_NAME."</span> en <span class='val'>".DB_HOST."</span></div>";
} catch (PDOException $e) {
    echo "<div class='box'><span class='error'>✗ Error de conexión:</span> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p class='warn'>⚠️ Revisa que XAMPP MySQL esté corriendo y que DB_USER/DB_PASS sean correctos en este archivo.</p>";
    die("</body></html>");
}

echo "<h2>2. Tablas existentes</h2>";
$tablas_requeridas = ['usuarios', 'empresas', 'notificaciones_admin', 'lugares', 'hoteles', 'eventos', 'reservas'];
$tablas_existentes = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "<div class='box'>";
foreach ($tablas_requeridas as $t) {
    if (in_array($t, $tablas_existentes)) {
        echo "<span class='ok'>✓ $t</span><br>";
    } else {
        echo "<span class='error'>✗ $t — NO EXISTE</span><br>";
    }
}
echo "</div>";

echo "<h2>3. Columnas de la tabla usuarios</h2>";
try {
    $cols = $pdo->query("DESCRIBE usuarios")->fetchAll();
    echo "<div class='box'>";
    $col_names = array_column($cols, 'Field');
    foreach ($cols as $c) {
        echo "<span class='label'>{$c['Field']}</span>: <span class='val'>{$c['Type']}</span>";
        if ($c['Field'] === 'estado') echo " <span class='ok'>← columna nueva ✓</span>";
        if ($c['Field'] === 'rol')    echo " — valores: <span class='val'>{$c['Type']}</span>";
        echo "<br>";
    }
    echo "</div>";

    if (!in_array('estado', $col_names)) {
        echo "<div class='box'><span class='error'>✗ Falta la columna 'estado' — ejecuta CORRECCION_BD.sql en phpMyAdmin</span></div>";
    }
} catch (PDOException $e) {
    echo "<div class='box'><span class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</span></div>";
}

echo "<h2>4. Usuarios registrados y sus contraseñas</h2>";
try {
    $usuarios = $pdo->query("SELECT id, nombre, correo, rol, password, " .
        (in_array('estado', array_column($pdo->query('DESCRIBE usuarios')->fetchAll(), 'Field')) ? 'estado' : "'activo' AS estado") .
        " FROM usuarios")->fetchAll();

    echo "<div class='box'>";
    if (empty($usuarios)) {
        echo "<span class='error'>No hay usuarios en la BD</span>";
    }

    $passwords_prueba = ['admin123', 'password', '123456', 'admin', 'flowzone'];

    foreach ($usuarios as $u) {
        echo "<br><b style='color:#4fc1ff'>{$u['correo']}</b> — rol: <span class='val'>{$u['rol']}</span> — estado: <span class='val'>{$u['estado']}</span><br>";
        echo "<span class='label'>Hash guardado:</span> <span style='font-size:0.8rem;color:#888'>" . substr($u['password'], 0, 30) . "...</span><br>";

        $encontrada = false;
        foreach ($passwords_prueba as $p) {
            if (password_verify($p, $u['password'])) {
                echo "<span class='ok'>✓ Contraseña correcta: <b>$p</b></span><br>";
                $encontrada = true;
                break;
            }
        }
        if (!$encontrada) {
            echo "<span class='warn'>⚠️ Contraseña no es ninguna de las comunes probadas: " . implode(', ', $passwords_prueba) . "</span><br>";
            echo "<span class='warn'>   → Usa el formulario de abajo para probar una contraseña específica</span><br>";
        }
    }
    echo "</div>";
} catch (PDOException $e) {
    echo "<div class='box'><span class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</span></div>";
}

echo "<h2>5. Probar login manualmente</h2>";

$test_resultado = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_correo'])) {
    $tc = trim($_POST['test_correo']);
    $tp = $_POST['test_password'];

    try {
        $has_estado = in_array('estado', array_column($pdo->query('DESCRIBE usuarios')->fetchAll(), 'Field'));
        $sel = $has_estado
            ? 'SELECT id, nombre, correo, password, rol, estado FROM usuarios WHERE correo = ? LIMIT 1'
            : 'SELECT id, nombre, correo, password, rol FROM usuarios WHERE correo = ? LIMIT 1';
        $stmt = $pdo->prepare($sel);
        $stmt->execute([$tc]);
        $u = $stmt->fetch();

        if (!$u) {
            $test_resultado = "<span class='error'>✗ No existe ningún usuario con ese correo</span>";
        } elseif (!password_verify($tp, $u['password'])) {
            $test_resultado = "<span class='error'>✗ Contraseña incorrecta para ese usuario</span><br>";
            $test_resultado .= "<span class='warn'>Hash en BD: " . substr($u['password'], 0, 40) . "...</span><br>";
            $test_resultado .= "<span class='warn'>Intenta con: password, admin, 123456, admin123</span>";
        } else {
            $test_resultado = "<span class='ok'>✓ LOGIN EXITOSO — usuario: {$u['nombre']} — rol: {$u['rol']}</span>";
        }
    } catch (PDOException $e) {
        $test_resultado = "<span class='error'>Error PDO: " . htmlspecialchars($e->getMessage()) . "</span>";
    }
}

echo "<form method='POST'>";
echo "<div><label class='label'>Correo: </label><br><input type='email' name='test_correo' value='" . htmlspecialchars($_POST['test_correo'] ?? 'admin@flowzone.com') . "' required></div><br>";
echo "<div><label class='label'>Contraseña: </label><br><input type='password' name='test_password' required></div>";
echo "<button type='submit'>Probar</button>";
if ($test_resultado) echo "<div class='box' style='margin-top:1rem'>$test_resultado</div>";
echo "</form>";

echo "<h2>6. Generar hash correcto</h2>";
if (isset($_POST['nueva_password']) && !empty($_POST['nueva_password'])) {
    $np = $_POST['nueva_password'];
    $nh = password_hash($np, PASSWORD_BCRYPT, ['cost' => 10]);
    echo "<div class='box'>";
    echo "<span class='label'>Hash para '$np':</span><br>";
    echo "<span class='val' style='word-break:break-all'>$nh</span><br><br>";
    echo "<span class='warn'>SQL para actualizar admin:</span><br>";
    echo "<code style='color:#ce9178'>UPDATE usuarios SET password = '$nh' WHERE correo = 'admin@flowzone.com';</code>";
    echo "</div>";
}
echo "<form method='POST'>";
echo "<div><label class='label'>Escribe una contraseña para generar su hash: </label><br>";
echo "<input type='text' name='nueva_password' placeholder='ej: admin123'></div>";
echo "<button type='submit'>Generar hash</button>";
echo "</form>";

echo "<br><br><div style='color:#555;font-size:0.8rem'>⚠️ Elimina este archivo (diagnostico.php) después de usarlo.</div>";
?>
</body>
</html>
