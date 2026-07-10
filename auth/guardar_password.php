<?php

session_start();

include("../includes/conexion.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    try {

        $documento = trim($_POST['documento']);
        $correo = trim($_POST['correo']);
        $password = trim($_POST['password']);
        $confirmar = trim($_POST['confirmar']);

        // Validar campos
        if (empty($documento) || empty($correo) || empty($password) || empty($confirmar)) {

            echo "<script>
                alert('Todos los campos son obligatorios.');
                history.back();
            </script>";
            exit;

        }

        // Validar contraseñas
        if ($password != $confirmar) {

            echo "<script>
                alert('Las contraseñas no coinciden.');
                history.back();
            </script>";
            exit;

        }

        // Buscar usuario
        $sql = "SELECT *
                FROM usuario
                WHERE documento = :documento
                AND correo = :correo";

        $stmt = $conexion->prepare($sql);

        $stmt->execute([
            ":documento" => $documento,
            ":correo" => $correo
        ]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Documento o correo incorrectos
        if (!$usuario) {

            echo "<script>
                alert('Documento o correo incorrectos.');
                history.back();
            </script>";
            exit;

        }

        // Usuario inactivo
        if ($usuario['estado'] != 'activo') {

            echo "<script>
                alert('Usuario inactivo. Contacte al administrador del sistema.');
                window.location='login.php';
            </script>";
            exit;

        }

        // Actualizar contraseña
        $update = $conexion->prepare("
            UPDATE usuario
            SET password = :password
            WHERE id = :id
        ");

        $update->execute([
            ":password" => $password,
            ":id" => $usuario['id']
        ]);

        // Mensaje de éxito
        echo "<script>
            alert('La contraseña fue actualizada correctamente.');
            window.location='login.php';
        </script>";
        exit;

    } catch (PDOException $e) {

        echo "<script>
            alert('Ocurrió un error al actualizar la contraseña.');
            history.back();
        </script>";
        exit;

    }

}

?>