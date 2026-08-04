<?php

session_start();

if(!isset($_SESSION['id_usuario'])){

    header("Location: /BFC-dev2/auth/login.php");
    exit;

}

include("../conexion_modulos.php");

if($_SERVER["REQUEST_METHOD"]=="POST"){

    $actual = trim($_POST["actual"]);
    $password = trim($_POST["password"]);
    $confirmar = trim($_POST["confirmar"]);

    if(empty($actual) || empty($password) || empty($confirmar)){

        echo "<script>

        alert('Todos los campos son obligatorios.');

        history.back();

        </script>";

        exit;

    }

    if($password != $confirmar){

        echo "<script>

        alert('Las nuevas contraseñas no coinciden.');

        history.back();

        </script>";

        exit;

    }

    $stmt = $conexion->prepare("

        SELECT password

        FROM usuario

        WHERE id = :id

    ");

    $stmt->execute([

        ":id" => $_SESSION["id_usuario"]

    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$usuario){

        session_destroy();

        header("Location: /BFC-dev2/auth/login.php");

        exit;

    }

    if($actual != $usuario["password"]){

        echo "<script>

        alert('La contraseña actual es incorrecta.');

        history.back();

        </script>";

        exit;

    }

    $update = $conexion->prepare("

        UPDATE usuario

        SET password = :password

        WHERE id = :id

    ");

    $update->execute([

        ":password" => $password,

        ":id" => $_SESSION["id_usuario"]

    ]);

    echo "<script>

    alert('Contraseña actualizada correctamente.');

    window.location='/BFC-dev2/modulos/dashboard/index.php';

    </script>";

    exit;

}

?>