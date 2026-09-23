<?php
// =============================================
//  AUTH.PHP - Control de acceso del sistema
//  Las credenciales se obtienen de la tabla
//  `tecnico` (campos User y pass). El rol
//  (admin/tecnico) controla los permisos.
// =============================================
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['tecnico_id'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function usuarioLogueado() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['tecnico_id']) ? $_SESSION : null;
}

// Indica si el usuario actual tiene rol de administrador
function esAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['role'])) {
        return $_SESSION['role'] === 'admin';
    }
    // Sesiones creadas antes de existir el rol: se consulta la BD
    if (isset($_SESSION['tecnico_id'])) {
        $conn = conectarBD();
        $r = $conn->query("SELECT `role` FROM tecnico WHERE Id_tecnico = " . (int)$_SESSION['tecnico_id']);
        if ($r && $r->num_rows) {
            $fila = $r->fetch_assoc();
            $_SESSION['role'] = $fila['role'];
            return $fila['role'] === 'admin';
        }
    }
    return false;
}

// Redirige si el usuario no es administrador
function requiereAdmin() {
    if (!esAdmin()) {
        header('Location: ' . BASE_URL . 'index.php?err=' . urlencode('Acción restringida al administrador.'));
        exit;
    }
}

// ID del técnico con la sesión iniciada
function tecnicoIdActual() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['tecnico_id']) ? (int)$_SESSION['tecnico_id'] : 0;
}

// Registra un cambio en la bitácora de modificaciones
function registrarBitacora($conn, $modulo, $accion, $id_registro, $descripcion) {
    $usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Sistema';
    $stmt = $conn->prepare("INSERT INTO bitacora (usuario, modulo, accion, id_registro, descripcion, fecha) VALUES (?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param('sssis', $usuario, $modulo, $accion, $id_registro, $descripcion);
        $stmt->execute();
        $stmt->close();
    }
}