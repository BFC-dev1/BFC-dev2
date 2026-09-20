<?php
session_start();

// Ikabil ti config tapno maala ti $url_base para iti web ken local
require_once(__DIR__ . "/../../includes/config.php");
require_once(__DIR__ . "/../conexion_modulos.php");

/** @var PDO $conexion */

// Veripikasion ti sesion babaen ti $url_base
if (!isset($_SESSION['id_usuario'])) {
    header("Location: " . $url_base . "/auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $actual = trim($_POST["actual"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $confirmar = trim($_POST["confirmar"] ?? "");

    if (empty($actual) || empty($password) || empty($confirmar)) {
        echo "<script>
            alert('Todos los campos son obligatorios.');
            history.back();
        </script>";
        exit;
    }

    if ($password !== $confirmar) {
        echo "<script>
            alert('Las nuevas contraseñas no coinciden.');
            history.back();
        </script>";
        exit;
    }

    // Biroken ti usuario iti database
    $stmt = $conexion->prepare("
        SELECT password
        FROM usuario
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ":id" => $_SESSION["id_usuario"]
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        session_destroy();
        header("Location: " . $url_base . "/auth/login.php");
        exit;
    }

    // 1. Veripikasion ti password nga usaren ti password_verify()
    if (!password_verify($actual, $usuario["password"])) {
        echo "<script>
            alert('La contraseña actual es incorrecta.');
            history.back();
        </script>";
        exit;
    }

    // 2. I-hash ti baro a password sakbay nga i-update
    $nuevo_hash = password_hash($password, PASSWORD_BCRYPT);

    $update = $conexion->prepare("
        UPDATE usuario
        SET password = :password
        WHERE id = :id
    ");

    $update->execute([
        ":password" => $nuevo_hash,
        ":id" => $_SESSION["id_usuario"]
    ]);

    // 3. Panang-redirect nga usaren ti dinamiko a $url_base
    echo "<script>
        alert('Contraseña actualizada correctamente.');
        window.location = '" . $url_base . "/modulos/dashboard/index.php';
    </script>";
    exit;
}
?>