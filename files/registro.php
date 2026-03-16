<?php
session_start();
require_once 'includes/conexion.php';
require_once 'includes/auth.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre           = trim($_POST['nombre']           ?? '');
    $correo           = trim($_POST['correo']           ?? '');
    $telefono         = trim($_POST['telefono']         ?? '');
    $password         = $_POST['password']              ?? '';
    $password_confirm = $_POST['password_confirm']      ?? '';
    $rol              = trim($_POST['rol']              ?? 'usuario');
    $empresa_nombre   = trim($_POST['empresa_nombre']   ?? '');
    $empresa_dir      = trim($_POST['empresa_direccion'] ?? '');

    if (empty($nombre) || empty($correo) || empty($password)) {
        $error = 'Por favor completa todos los campos obligatorios.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no tiene un formato válido.';
    } elseif ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif (!in_array($rol, ['usuario', 'empresa'], true)) {
        $error = 'Tipo de cuenta no válido.';
    } elseif ($rol === 'empresa' && empty($empresa_nombre)) {
        $error = 'El nombre de la empresa es obligatorio.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo = ? LIMIT 1');
            $stmt->execute([$correo]);
            if ($stmt->fetch()) {
                $error = 'Ese correo ya está registrado.';
            } else {
                $hash   = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

                // Detectar si existe columna estado
                $cols = $pdo->query("DESCRIBE usuarios")->fetchAll(PDO::FETCH_COLUMN);
                $tiene_estado = in_array('estado', $cols);

                $estado = ($rol === 'empresa') ? 'pendiente' : 'activo';

                if ($tiene_estado) {
                    $stmt = $pdo->prepare(
                        'INSERT INTO usuarios (nombre, correo, password, telefono, rol, estado) VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([$nombre, $correo, $hash, $telefono ?: null, $rol, $estado]);
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO usuarios (nombre, correo, password, telefono, rol) VALUES (?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([$nombre, $correo, $hash, $telefono ?: null, $rol]);
                }

                $usuarioId = (int)$pdo->lastInsertId();

                if ($rol === 'empresa') {
                    try {
                        $stmt = $pdo->prepare(
                            'INSERT INTO empresas (usuario_id, nombre, telefono, direccion, aprobado) VALUES (?, ?, ?, ?, 0)'
                        );
                        $stmt->execute([$usuarioId, $empresa_nombre, $telefono ?: null, $empresa_dir ?: null]);
                        $empresaId = (int)$pdo->lastInsertId();
                        if (function_exists('notificarAdmin')) {
                            notificarAdmin($pdo, $empresaId, "Nueva empresa registrada: «{$empresa_nombre}». Pendiente de aprobación.");
                        }
                    } catch (PDOException $e) { /* tabla empresas no existe aún */ }

                    $success = 'empresa';
                } else {
                    $success = 'usuario';
                }
            }
        } catch (PDOException $e) {
            error_log('[FlowZone][registro] ' . $e->getMessage());
            $error = 'Error en el sistema. Intenta de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlowZone — Crear cuenta</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --verde:      #1a5c35;
            --verde-med:  #2d7a3e;
            --verde-claro:#4a9d5f;
            --dorado-clr: #e8b84b;
            --crema:      #f5f0e8;
            --oscuro:     #0f1f14;
            --gris:       #6b7a6e;
        }
        html, body { min-height: 100%; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--oscuro);
            display: flex;
            min-height: 100vh;
        }
        .panel-izq {
            flex: 1;
            position: relative;
            background: var(--verde);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 3rem;
        }
        .panel-izq::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=900&q=80') center/cover no-repeat;
            opacity: 0.3;
        }
        .panel-izq::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(160deg, transparent 20%, rgba(10,30,16,0.9) 80%);
        }
        .marca { position: relative; z-index: 2; }
        .marca h1 { font-family: 'Playfair Display', serif; font-size: 3rem; font-weight: 900; color: #fff; line-height: 1; margin-bottom: 0.5rem; }
        .marca h1 span { color: var(--dorado-clr); }
        .marca p { color: rgba(255,255,255,0.55); font-size: 0.95rem; font-weight: 300; letter-spacing: 0.08em; text-transform: uppercase; }
        .panel-der {
            width: 520px;
            flex-shrink: 0;
            background: var(--crema);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2.5rem 3rem;
            overflow-y: auto;
        }
        .form-encabezado { margin-bottom: 2rem; }
        .bienvenida {
            font-size: 0.75rem; font-weight: 500; color: var(--verde-med);
            text-transform: uppercase; letter-spacing: 0.12em; margin-bottom: 0.4rem;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .bienvenida::before { content: ''; display: inline-block; width: 20px; height: 2px; background: var(--verde-med); }
        .form-encabezado h2 { font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 700; color: var(--oscuro); line-height: 1.2; }
        .form-encabezado h2 em { font-style: normal; color: var(--verde-med); }

        /* Tabs tipo */
        .tipo-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; background: #e8e2d8; border-radius: 10px; padding: 4px; }
        .tipo-tab {
            flex: 1; padding: 0.65rem 0.5rem; border: none; border-radius: 7px;
            background: transparent; color: var(--gris);
            font-family: 'DM Sans', sans-serif; font-size: 0.85rem; font-weight: 500;
            cursor: pointer; transition: all 0.25s; display: flex; align-items: center; justify-content: center; gap: 0.3rem;
        }
        .tipo-tab.activo { background: #fff; color: var(--verde); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }

        .campo { margin-bottom: 1rem; }
        .campo label { display: block; font-size: 0.75rem; font-weight: 500; color: var(--gris); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.4rem; }
        .campo-input { position: relative; }
        .campo-input .ic { position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%); font-size: 0.95rem; pointer-events: none; }
        .campo input {
            width: 100%; padding: 0.8rem 1rem 0.8rem 2.6rem;
            background: #fff; border: 1.5px solid #ddd6ca; border-radius: 10px;
            font-family: 'DM Sans', sans-serif; font-size: 0.92rem; color: var(--oscuro);
            transition: border-color 0.2s, box-shadow 0.2s; outline: none;
        }
        .campo input:focus { border-color: var(--verde-med); box-shadow: 0 0 0 3px rgba(45,122,62,0.12); }
        .campo input::placeholder { color: #b5aca0; }
        .fila-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; }
        .seccion-empresa { border-top: 1.5px solid #e0d9d0; padding-top: 1.2rem; margin-top: 0.5rem; display: none; }
        .seccion-empresa.visible { display: block; }
        .seccion-titulo { font-size: 0.75rem; font-weight: 500; color: var(--verde-med); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 1rem; }
        .alerta-error { background: #fef2f2; border: 1px solid #fecaca; border-left: 3px solid #ef4444; color: #dc2626; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.2rem; display: flex; align-items: center; gap: 0.5rem; }
        .alerta-success { background: #f0fdf4; border: 1px solid #bbf7d0; border-left: 3px solid #22c55e; color: #15803d; padding: 1rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1.2rem; }
        .btn-registrar {
            width: 100%; padding: 0.95rem; background: var(--verde); color: #fff; border: none;
            border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 1rem; font-weight: 500;
            cursor: pointer; transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            position: relative; overflow: hidden; letter-spacing: 0.03em; margin-top: 0.8rem;
        }
        .btn-registrar:hover { background: var(--verde-claro); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(45,122,62,0.3); }
        .btn-registrar::after { content: '→'; position: absolute; right: 1.5rem; top: 50%; transform: translateY(-50%); transition: right 0.2s; }
        .btn-registrar:hover::after { right: 1.2rem; }
        .enlaces { text-align: center; margin-top: 1.2rem; font-size: 0.85rem; }
        .enlaces a { color: var(--verde-med); text-decoration: none; font-weight: 500; }
        .enlaces a:hover { color: var(--verde); }
        @media (max-width: 900px) { .panel-izq { display: none; } .panel-der { width: 100%; padding: 2rem 1.5rem; } }
    </style>
</head>
<body>
    <div class="panel-izq">
        <div class="marca">
            <span style="font-size:2.5rem;display:block;margin-bottom:0.8rem">🌄</span>
            <h1>Flow<span>Zone</span></h1>
            <p>Turismo · Ortega, Tolima</p>
        </div>
    </div>

    <div class="panel-der">
        <div class="form-encabezado">
            <div class="bienvenida">Únete a nosotros</div>
            <h2>Crea tu <em>cuenta</em><br>gratis</h2>
        </div>

        <?php if ($error): ?>
            <div class="alerta-error">⚠️ <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($success === 'usuario'): ?>
            <div class="alerta-success">
                ✅ <strong>¡Registro exitoso!</strong> Ya puedes <a href="/FLOWZONE/login.php" style="color:var(--verde);font-weight:600">iniciar sesión</a>.
            </div>
        <?php elseif ($success === 'empresa'): ?>
            <div class="alerta-success">
                ✅ <strong>¡Empresa registrada!</strong> Tu cuenta está pendiente de aprobación por el administrador.
            </div>
        <?php endif; ?>

        <div class="tipo-tabs">
            <button type="button" class="tipo-tab activo" onclick="setTipo(this,'usuario')">👤 Visitante</button>
            <button type="button" class="tipo-tab" onclick="setTipo(this,'empresa')">🏢 Empresa</button>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="rol" id="campo-rol" value="<?= htmlspecialchars($_POST['rol'] ?? 'usuario', ENT_QUOTES, 'UTF-8') ?>">

            <div class="fila-2">
                <div class="campo">
                    <label>Nombre completo *</label>
                    <div class="campo-input">
                        <span class="ic">👤</span>
                        <input type="text" name="nombre" required maxlength="100"
                               placeholder="Tu nombre"
                               value="<?= htmlspecialchars($_POST['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="campo">
                    <label>Teléfono</label>
                    <div class="campo-input">
                        <span class="ic">📱</span>
                        <input type="tel" name="telefono" maxlength="20"
                               placeholder="3201234567"
                               value="<?= htmlspecialchars($_POST['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>

            <div class="campo">
                <label>Correo electrónico *</label>
                <div class="campo-input">
                    <span class="ic">✉️</span>
                    <input type="email" name="correo" required maxlength="150"
                           placeholder="tu@correo.com"
                           value="<?= htmlspecialchars($_POST['correo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div class="fila-2">
                <div class="campo">
                    <label>Contraseña *</label>
                    <div class="campo-input">
                        <span class="ic">🔒</span>
                        <input type="password" name="password" required minlength="6" placeholder="Mín. 6 chars">
                    </div>
                </div>
                <div class="campo">
                    <label>Confirmar *</label>
                    <div class="campo-input">
                        <span class="ic">🔒</span>
                        <input type="password" name="password_confirm" required minlength="6" placeholder="Repetir">
                    </div>
                </div>
            </div>

            <!-- Sección empresa -->
            <div class="seccion-empresa <?= (($_POST['rol'] ?? '') === 'empresa' ? 'visible' : '') ?>" id="sec-empresa">
                <div class="seccion-titulo">📋 Datos de la empresa</div>
                <div class="campo">
                    <label>Nombre de la empresa *</label>
                    <div class="campo-input">
                        <span class="ic">🏢</span>
                        <input type="text" name="empresa_nombre" maxlength="200"
                               placeholder="Nombre legal de tu empresa"
                               value="<?= htmlspecialchars($_POST['empresa_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="campo">
                    <label>Dirección</label>
                    <div class="campo-input">
                        <span class="ic">📍</span>
                        <input type="text" name="empresa_direccion" maxlength="400"
                               placeholder="Dirección de la empresa"
                               value="<?= htmlspecialchars($_POST['empresa_direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-registrar">Crear cuenta</button>
        </form>

        <div class="enlaces">
            ¿Ya tienes cuenta? <a href="/FLOWZONE/login.php">Inicia sesión aquí</a>
            &nbsp;·&nbsp; <a href="/FLOWZONE/index.php">← Inicio</a>
        </div>
    </div>

    <script>
    function setTipo(btn, tipo) {
        document.querySelectorAll('.tipo-tab').forEach(t => t.classList.remove('activo'));
        btn.classList.add('activo');
        document.getElementById('campo-rol').value = tipo;
        const sec = document.getElementById('sec-empresa');
        sec.classList.toggle('visible', tipo === 'empresa');
    }
    // Inicializar si venía con rol empresa del POST
    if (document.getElementById('campo-rol').value === 'empresa') {
        const tabs = document.querySelectorAll('.tipo-tab');
        tabs[0].classList.remove('activo');
        tabs[1].classList.add('activo');
    }
    </script>
</body>
</html>
