<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include("../includes/conexion.php");

// =========================
// VARIABLES
// =========================

$error_documento = false;
$error_usuario = false;

$mensaje_error = "";
$mensaje_success = "";

// =========================
// REGISTRAR USUARIO
// =========================

if($_POST){

    $nombre = trim($_POST['nombre'] ?? "");
    $tipo_documento = trim($_POST['tipo_documento'] ?? "");
    $documento = trim($_POST['documento'] ?? "");
    $telefono = trim($_POST['telefono'] ?? "");
    $correo = trim($_POST['correo'] ?? "");
    $usuario = trim($_POST['usuario'] ?? "");
    $password = trim($_POST['password'] ?? "");

    // ✅ VALIDAR CAMPOS
    if(
        empty($nombre) ||
        empty($tipo_documento) ||
        empty($documento) ||
        empty($telefono) ||
        empty($correo) ||
        empty($usuario) ||
        empty($password)
    ){

        $mensaje_error = "Todos los campos son obligatorios.";

    }

    // ✅ VALIDAR DOCUMENTO
    if(empty($mensaje_error)){

        $stmt_doc = $conexion->prepare("
        SELECT id
        FROM usuario
        WHERE documento = :documento
        ");

        $stmt_doc->execute([
            ":documento"=>$documento
        ]);

        if($stmt_doc->fetch()){

            $error_documento = true;

            $mensaje_error = "El documento ya está registrado.";

        }

    }

    // ✅ VALIDAR USUARIO
    if(empty($mensaje_error)){

        $stmt_user = $conexion->prepare("
        SELECT id
        FROM usuario
        WHERE usuario = :usuario
        ");

        $stmt_user->execute([
            ":usuario"=>$usuario
        ]);

        if($stmt_user->fetch()){

            $error_usuario = true;

            $mensaje_error = "El usuario ya existe.";

        }

    }

    // ✅ INSERTAR
    if(empty($mensaje_error)){

        try{

            $stm = $conexion->prepare("
            INSERT INTO usuario(
                nombre,
                tipo_documento,
                documento,
                telefono,
                correo,
                usuario,
                password,
                rol_id,
                estado
            )
            VALUES(
                :nombre,
                :tipo_documento,
                :documento,
                :telefono,
                :correo,
                :usuario,
                :password,
                4,
                'activo'
            )
            ");

            $stm->execute([
                ":nombre"=>$nombre,
                ":tipo_documento"=>$tipo_documento,
                ":documento"=>$documento,
                ":telefono"=>$telefono,
                ":correo"=>$correo,
                ":usuario"=>$usuario,
                ":password"=>$password
            ]);

            $mensaje_success = "Usuario registrado correctamente.";

        }catch(PDOException $e){

            $mensaje_error = $e->getMessage();

        }

    }

}

$conexion = null;
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registro - Bellavista FC</title>

    <!-- CSS -->
    <link rel="stylesheet" href="/BFC-dev2/assets/usuarios.css">

</head>

<body>

<div class="contenedor">

    <div class="card">

        <h2>Registro de Usuario</h2>

        <!-- MENSAJES -->
        <?php if(!empty($mensaje_error)){ ?>

            <div class="error">

                <?php echo $mensaje_error; ?>

            </div>

        <?php } ?>

        <?php if(!empty($mensaje_success)){ ?>

            <div class="success">

                <?php echo $mensaje_success; ?>

            </div>

        <?php } ?>

        <!-- FORM -->
        <form method="POST">

            <!-- FILA -->
            <div class="row">

                <!-- NOMBRE -->
                <div class="campo">

                    <label>Nombre</label>

                    <input
                        type="text"
                        name="nombre"
                        placeholder="Ingrese Nombre"
                        value="<?php echo $_POST['nombre'] ?? ''; ?>"
                    >

                </div>

                <!-- TIPO DOCUMENTO -->
                <div class="campo">

                    <label>Tipo Documento</label>

                    <input
                        type="text"
                        name="tipo_documento"
                        placeholder="CC / TI / CE"
                        value="<?php echo $_POST['tipo_documento'] ?? ''; ?>"
                    >

                </div>

            </div>

            <!-- FILA -->
            <div class="row">

                <!-- DOCUMENTO -->
                <div class="campo">

                    <label>Documento</label>

                    <input
                        type="text"
                        name="documento"
                        placeholder="Ingrese Documento"
                        class="<?php if($error_documento){ echo 'border-error'; } ?>"
                        value="<?php echo $_POST['documento'] ?? ''; ?>"
                    >

                </div>

                <!-- TELEFONO -->
                <div class="campo">

                    <label>Teléfono</label>

                    <input
                        type="text"
                        name="telefono"
                        placeholder="Ingrese Teléfono"
                        value="<?php echo $_POST['telefono'] ?? ''; ?>"
                    >

                </div>

            </div>

            <!-- FILA -->
            <div class="row">

                <!-- CORREO -->
                <div class="campo">

                    <label>Correo</label>

                    <input
                        type="email"
                        name="correo"
                        placeholder="Ingrese Correo"
                        value="<?php echo $_POST['correo'] ?? ''; ?>"
                    >

                </div>

                <!-- USUARIO -->
                <div class="campo">

                    <label>Usuario</label>

                    <input
                        type="text"
                        name="usuario"
                        placeholder="Ingrese Usuario"
                        class="<?php if($error_usuario){ echo 'border-error'; } ?>"
                        value="<?php echo $_POST['usuario'] ?? ''; ?>"
                    >

                </div>

            </div>

            <!-- FILA -->
            <div class="row">

                <!-- PASSWORD -->
                <div class="campo">

                    <label>Contraseña</label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Ingrese Contraseña"
                    >

                </div>

            </div>

            <!-- BOTON -->
            <button
                type="submit"
                class="btn"
            >
                Registrarse
            </button>

        </form>

        <!-- LINK -->
        <div class="link">

            <a href="../auth/login.php">

                Ir al Login

            </a>

        </div>

    </div>

</div>

</body>
</html>