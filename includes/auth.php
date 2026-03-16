<?php
// FlowZone - Sistema de autenticación y autorización v2.0
// Compatible con todo el código existente + soporte empresa

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// ── Funciones de sesión ──────────────────────────────────────

function estaAutenticado(): bool {
    return !empty($_SESSION['usuario_id']);
}

function esAdmin(): bool {
    return ($_SESSION['usuario_rol'] ?? '') === 'admin';
}

function esEmpresa(): bool {
    return ($_SESSION['usuario_rol'] ?? '') === 'empresa';
}

function obtenerUsuarioId(): ?int {
    return isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
}

function obtenerUsuarioNombre(): string {
    return $_SESSION['usuario_nombre'] ?? 'Invitado';
}

function obtenerUsuarioRol(): string {
    return $_SESSION['usuario_rol'] ?? '';
}

// ── Protección de rutas ──────────────────────────────────────

function requiereAutenticacion(): void {
    if (!estaAutenticado()) {
        header('Location: /FLOWZONE/login.php');
        exit;
    }
}

function requiereAdmin(): void {
    requiereAutenticacion();
    if (!esAdmin()) {
        header('Location: /FLOWZONE/index.php');
        exit;
    }
}

function requiereEmpresa(): void {
    requiereAutenticacion();
    if (!esEmpresa()) {
        header('Location: /FLOWZONE/index.php');
        exit;
    }
}

function requiereRol(array $roles): void {
    requiereAutenticacion();
    if (!in_array(obtenerUsuarioRol(), $roles, true)) {
        header('Location: /FLOWZONE/index.php');
        exit;
    }
}

// ── Notificaciones al admin ──────────────────────────────────

function notificarAdmin(PDO $pdo, int $empresaId, string $mensaje): bool {
    if ($empresaId <= 0 || trim($mensaje) === '') return false;
    try {
        $pdo->prepare(
            'INSERT INTO notificaciones_admin (empresa_id, mensaje, leido) VALUES (?, ?, 0)'
        )->execute([$empresaId, trim($mensaje)]);
        return true;
    } catch (PDOException $e) {
        error_log('[FlowZone][notificarAdmin] ' . $e->getMessage());
        return false;
    }
}

function obtenerNotificacionesPendientes(PDO $pdo): array {
    try {
        $stmt = $pdo->prepare(
            'SELECT n.id, n.mensaje, n.creado_en, e.nombre AS empresa_nombre, e.id AS empresa_id
             FROM notificaciones_admin n
             JOIN empresas e ON e.id = n.empresa_id
             WHERE n.leido = 0
             ORDER BY n.creado_en DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('[FlowZone][notificaciones] ' . $e->getMessage());
        return [];
    }
}

function contarNotificacionesPendientes(PDO $pdo): int {
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM notificaciones_admin WHERE leido = 0');
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function marcarNotificacionLeida(PDO $pdo, int $notifId): void {
    try {
        $pdo->prepare('UPDATE notificaciones_admin SET leido = 1 WHERE id = ?')
            ->execute([$notifId]);
    } catch (PDOException $e) {
        error_log('[FlowZone][marcarLeida] ' . $e->getMessage());
    }
}
?>
