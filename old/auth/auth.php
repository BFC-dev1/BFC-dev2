<?php
session_start();

// Verifica si inició sesión
if (!isset($_SESSION['admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Verifica rol
if ($_SESSION['rol'] != 'admin') {
    echo "No tienes permisos";
    exit();
}
