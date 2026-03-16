<?php
session_start();
require_once 'includes/conexion.php';

if (!empty($_SESSION['usuario_id'])) {
    $rol = $_SESSION['usuario_rol'] ?? '';
    if ($rol === 'admin')   { header('Location: /FLOWZONE/admin/dashboard.php'); exit; }
    if ($rol === 'empresa') { header('Location: /FLOWZONE/empresa/dashboard.php'); exit; }
    header('Location: /FLOWZONE/index.php'); exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo   = trim($_POST['correo']   ?? '');
    $password = $_POST['password']      ?? '';

    if (empty($correo) || empty($password)) {
        $error = 'Por favor completa todos los campos.';
    } else {
        try {
            // Detectar si existe columna estado
            $cols = $pdo->query("DESCRIBE usuarios")->fetchAll(PDO::FETCH_COLUMN);
            $tiene_estado = in_array('estado', $cols);

            $sql = $tiene_estado
                ? 'SELECT id, nombre, correo, password, rol, estado FROM usuarios WHERE correo = ? LIMIT 1'
                : 'SELECT id, nombre, correo, password, rol FROM usuarios WHERE correo = ? LIMIT 1';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$correo]);
            $usuario = $stmt->fetch();

            $hash_ficticio = '$2y$10$uselesshashtopreventtimingattack000000000000000000000000';
            $hash_real     = $usuario ? $usuario['password'] : $hash_ficticio;

            if (!$usuario || !password_verify($password, $hash_real)) {
                $error = 'Correo o contraseña incorrectos.';
            } else {
                $estado = $usuario['estado'] ?? 'activo';
                if ($estado === 'bloqueado') {
                    $error = 'Tu cuenta ha sido bloqueada. Contacta al administrador.';
                } elseif ($estado === 'pendiente') {
                    $error = 'Tu cuenta está pendiente de aprobación.';
                } else {
                    // Verificar aprobación de empresa
                    if ($usuario['rol'] === 'empresa') {
                        try {
                            $stmtE = $pdo->prepare('SELECT aprobado FROM empresas WHERE usuario_id = ? LIMIT 1');
                            $stmtE->execute([$usuario['id']]);
                            $emp = $stmtE->fetch();
                            if ($emp && !$emp['aprobado']) {
                                $error = 'Tu empresa aún no ha sido aprobada por el administrador.';
                            }
                        } catch (PDOException $e) { /* tabla no existe aún */ }
                    }

                    if (empty($error)) {
                        session_regenerate_id(true);
                        $_SESSION['usuario_id']     = $usuario['id'];
                        $_SESSION['usuario_nombre'] = $usuario['nombre'];
                        $_SESSION['usuario_rol']    = $usuario['rol'];

                        if ($usuario['rol'] === 'admin')   { header('Location: /FLOWZONE/admin/dashboard.php');   exit; }
                        if ($usuario['rol'] === 'empresa') { header('Location: /FLOWZONE/empresa/dashboard.php'); exit; }
                        header('Location: /FLOWZONE/index.php'); exit;
                    }
                }
            }
        } catch (PDOException $e) {
            error_log('[FlowZone][login] ' . $e->getMessage());
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
    <title>FlowZone — Ingresar</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --verde:      #1a5c35;
            --verde-med:  #2d7a3e;
            --verde-claro:#4a9d5f;
            --dorado:     #c9922a;
            --dorado-clr: #e8b84b;
            --crema:      #f5f0e8;
            --oscuro:     #0f1f14;
            --gris:       #6b7a6e;
        }

        html, body { height: 100%; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--oscuro);
            display: flex;
            min-height: 100vh;
            overflow: hidden;
        }

        /* ── Panel izquierdo — imagen/marca ── */
        .panel-izq {
            flex: 1.1;
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
            background:
                url('https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=900&q=80') center/cover no-repeat;
            opacity: 0.35;
        }

        .panel-izq::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                170deg,
                transparent 30%,
                rgba(10,30,16,0.85) 70%,
                rgba(10,30,16,0.98) 100%
            );
        }

        .marca {
            position: relative;
            z-index: 2;
        }

        .marca-icono {
            font-size: 3rem;
            display: block;
            margin-bottom: 1rem;
            animation: flotar 3s ease-in-out infinite;
        }

        @keyframes flotar {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-8px); }
        }

        .marca h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 900;
            color: #fff;
            line-height: 1;
            margin-bottom: 0.5rem;
            letter-spacing: -1px;
        }

        .marca h1 span { color: var(--dorado-clr); }

        .marca p {
            color: rgba(255,255,255,0.65);
            font-size: 1rem;
            font-weight: 300;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 2.5rem;
        }

        .pilares {
            display: flex;
            gap: 1.5rem;
            position: relative;
            z-index: 2;
        }

        .pilar {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            padding: 1rem 1.2rem;
            backdrop-filter: blur(10px);
            flex: 1;
        }

        .pilar .icono { font-size: 1.4rem; margin-bottom: 0.3rem; }
        .pilar .titulo { font-size: 0.75rem; font-weight: 500; color: var(--dorado-clr); text-transform: uppercase; letter-spacing: 0.06em; }
        .pilar .desc   { font-size: 0.82rem; color: rgba(255,255,255,0.55); margin-top: 0.2rem; }

        /* ── Panel derecho — formulario ── */
        .panel-der {
            width: 480px;
            flex-shrink: 0;
            background: var(--crema);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 3.5rem;
            position: relative;
            overflow-y: auto;
        }

        .panel-der::before {
            content: '';
            position: absolute;
            top: -80px; right: -80px;
            width: 250px; height: 250px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(45,122,62,0.12), transparent 70%);
            pointer-events: none;
        }

        .form-encabezado { margin-bottom: 2.5rem; }

        .bienvenida {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--verde-med);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .bienvenida::before {
            content: '';
            display: inline-block;
            width: 20px; height: 2px;
            background: var(--verde-med);
        }

        .form-encabezado h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--oscuro);
            line-height: 1.15;
        }

        .form-encabezado h2 em {
            font-style: normal;
            color: var(--verde-med);
        }

        /* Tabs de rol */
        .rol-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            background: #e8e2d8;
            border-radius: 10px;
            padding: 4px;
        }

        .rol-tab {
            flex: 1;
            padding: 0.6rem 0.5rem;
            border: none;
            border-radius: 7px;
            background: transparent;
            color: var(--gris);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.82rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
        }

        .rol-tab.activo {
            background: #fff;
            color: var(--verde);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* Campos */
        .campo {
            margin-bottom: 1.3rem;
            position: relative;
        }

        .campo label {
            display: block;
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--gris);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.5rem;
        }

        .campo-input {
            position: relative;
        }

        .campo-input .icono-campo {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            pointer-events: none;
        }

        .campo input {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.8rem;
            background: #fff;
            border: 1.5px solid #ddd6ca;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            color: var(--oscuro);
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .campo input:focus {
            border-color: var(--verde-med);
            box-shadow: 0 0 0 3px rgba(45,122,62,0.12);
        }

        .campo input::placeholder { color: #b5aca0; }

        /* Error */
        .alerta-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 3px solid #ef4444;
            color: #dc2626;
            padding: 0.8rem 1rem;
            border-radius: 8px;
            font-size: 0.88rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Botón */
        .btn-ingresar {
            width: 100%;
            padding: 1rem;
            background: var(--verde);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.03em;
            margin-top: 0.5rem;
        }

        .btn-ingresar:hover {
            background: var(--verde-claro);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(45,122,62,0.35);
        }

        .btn-ingresar:active { transform: translateY(0); }

        .btn-ingresar::after {
            content: '→';
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            transition: right 0.2s;
        }

        .btn-ingresar:hover::after { right: 1.2rem; }

        /* Links */
        .enlaces {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            font-size: 0.85rem;
        }

        .enlaces a {
            color: var(--verde-med);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .enlaces a:hover { color: var(--verde); }

        .divisor {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
            color: #c5bdb3;
            font-size: 0.8rem;
        }

        .divisor::before, .divisor::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e0d9d0;
        }

        .hint-credenciales {
            background: rgba(45,122,62,0.06);
            border: 1px solid rgba(45,122,62,0.15);
            border-radius: 8px;
            padding: 0.8rem 1rem;
            font-size: 0.78rem;
            color: var(--gris);
            margin-top: 1rem;
        }

        .hint-credenciales strong { color: var(--verde); }

        /* Responsive */
        @media (max-width: 900px) {
            .panel-izq { display: none; }
            .panel-der { width: 100%; padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>

    <!-- Panel izquierdo -->
    <div class="panel-izq">
        <div class="marca">
            <span class="marca-icono">🌄</span>
            <h1>Flow<span>Zone</span></h1>
            <p>Turismo · Ortega, Tolima</p>
        </div>
        <div class="pilares">
            <div class="pilar">
                <div class="icono">🏔️</div>
                <div class="titulo">Naturaleza</div>
                <div class="desc">Cascadas y miradores</div>
            </div>
            <div class="pilar">
                <div class="icono">🍽️</div>
                <div class="titulo">Gastronomía</div>
                <div class="desc">Sabores del Tolima</div>
            </div>
            <div class="pilar">
                <div class="icono">🏨</div>
                <div class="titulo">Hospedaje</div>
                <div class="desc">Hoteles y posadas</div>
            </div>
        </div>
    </div>

    <!-- Panel derecho -->
    <div class="panel-der">

        <div class="form-encabezado">
            <div class="bienvenida">Bienvenido de vuelta</div>
            <h2>Ingresa a tu<br><em>cuenta</em></h2>
        </div>

        <!-- Tabs de rol informativo -->
        <div class="rol-tabs">
            <button type="button" class="rol-tab activo" onclick="selRol(this,'usuario')">
                👤 Visitante
            </button>
            <button type="button" class="rol-tab" onclick="selRol(this,'empresa')">
                🏢 Empresa
            </button>
            <button type="button" class="rol-tab" onclick="selRol(this,'admin')">
                ⚙️ Admin
            </button>
        </div>

        <?php if ($error): ?>
            <div class="alerta-error">
                <span>⚠️</span>
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="campo">
                <label>Correo electrónico</label>
                <div class="campo-input">
                    <span class="icono-campo">✉️</span>
                    <input type="email" name="correo" id="campo-correo" required autocomplete="email"
                           placeholder="tu@correo.com"
                           value="<?= htmlspecialchars($_POST['correo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <div class="campo">
                <label>Contraseña</label>
                <div class="campo-input">
                    <span class="icono-campo">🔒</span>
                    <input type="password" name="password" required autocomplete="current-password"
                           placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="btn-ingresar">Ingresar</button>
        </form>

        <div class="enlaces">
            <a href="/FLOWZONE/registro.php">¿No tienes cuenta? Regístrate</a>
            <a href="/FLOWZONE/index.php">← Inicio</a>
        </div>

        <div class="divisor">credenciales de prueba</div>

        <div class="hint-credenciales">
            <strong>Admin:</strong> admin@flowzone.com / admin123<br>
            <strong>Usuario:</strong> juan@example.com / admin123<br>
            <strong>Empresa:</strong> empresa@example.com / admin123
        </div>

    </div>

    <script>
    const correos = {
        usuario: 'juan@example.com',
        empresa: 'empresa@example.com',
        admin:   'admin@flowzone.com'
    };

    function selRol(btn, rol) {
        document.querySelectorAll('.rol-tab').forEach(t => t.classList.remove('activo'));
        btn.classList.add('activo');
        const campo = document.getElementById('campo-correo');
        if (!campo.value || Object.values(correos).includes(campo.value)) {
            campo.value = correos[rol];
        }
    }
    </script>

</body>
</html>
