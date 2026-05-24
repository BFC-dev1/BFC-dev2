<?php
session_start();

if(!isset($_SESSION['usuario'])){
    header("Location: /BFC-dev2/auth/login.php");
    exit;
}

include("../includes/conexion.php");

// ✅ VARIABLES DE ERROR
$error_documento = false;
$error_usuario = false;

$mensaje_error = "";

// ✅ GUARDAR USUARIO
if($_POST){

    $nombre = trim($_POST['nombre'] ?? "");
    $tipo_documento = trim($_POST['tipo_documento'] ?? "");
    $documento = trim($_POST['documento'] ?? "");
    $telefono = trim($_POST['numero_telefono'] ?? "");
    $correo = trim($_POST['correo'] ?? "");
    $usuario = trim($_POST['usuario'] ?? "");
    $password = trim($_POST['password'] ?? "");
    $rol_id = trim($_POST['rol_id'] ?? "");

    // 🔒 VALIDACIONES

    if(
        empty($nombre) ||
        empty($tipo_documento) ||
        empty($documento) ||
        empty($telefono) ||
        empty($correo) ||
        empty($usuario) ||
        empty($password) ||
        empty($rol_id)
    ){

        $mensaje_error = "Todos los campos son obligatorios.";

    }

    // ✅ VALIDAR DOCUMENTO REPETIDO
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

    // ✅ VALIDAR USUARIO REPETIDO
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

    // ✅ INSERTAR
    if(empty($mensaje_error)){

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
            :rol_id,
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
            ":password"=>$password,
            ":rol_id"=>$rol_id
        ]);

        header("Location: usuarios.php?success=1");
        exit;
    }
}
?>

<?php include("../includes/header_dashboard.php"); ?>
<?php include("../modulos/Dashboard/sidebar.php"); ?>

<div class="container-fluid">

    <div class="card shadow border-0">

        <div class="card-header bg-primary text-white">

            <h4 class="mb-0">
                Agregar Usuario

                <a href="usuarios.php" class="btn btn-light float-end">
                    Atrás
                </a>
            </h4>

        </div>

        <div class="card-body">

            <!-- ✅ ALERTA ERROR -->
            <?php if(!empty($mensaje_error)){ ?>

                <div class="alert alert-danger">

                    <?php echo $mensaje_error; ?>

                </div>

            <?php } ?>

            <form action="" method="post">

                <div class="row">

                    <!-- NOMBRE -->
                    <div class="col-md-6 mb-3">

                        <label>Nombre</label>

                        <input
                            type="text"
                            name="nombre"
                            class="form-control"
                            placeholder="Ingrese Nombre"
                            value="<?php echo $_POST['nombre'] ?? ''; ?>"
                        >

                    </div>

                    <!-- TIPO DOCUMENTO -->
                    <div class="col-md-6 mb-3">

                        <label>Tipo de documento</label>

                        <input
                            type="text"
                            name="tipo_documento"
                            class="form-control"
                            placeholder="Ingrese Tipo de Documento"
                            value="<?php echo $_POST['tipo_documento'] ?? ''; ?>"
                        >

                    </div>

                    <!-- DOCUMENTO -->
                    <div class="col-md-6 mb-3">

                        <label>Documento</label>

                        <input
                            type="text"
                            name="documento"
                            class="form-control <?php if($error_documento){ echo 'border border-danger'; } ?>"
                            placeholder="Ingrese Documento"
                            value="<?php echo $_POST['documento'] ?? ''; ?>"
                        >

                        <?php if($error_documento){ ?>

                            <small class="text-danger">
                                Este documento ya existe.
                            </small>

                        <?php } ?>

                    </div>

                    <!-- TELEFONO -->
                    <div class="col-md-6 mb-3">

                        <label>Número de Teléfono</label>

                        <input
                            type="text"
                            name="numero_telefono"
                            class="form-control"
                            placeholder="Ingrese Número de Teléfono"
                            value="<?php echo $_POST['numero_telefono'] ?? ''; ?>"
                        >

                    </div>

                    <!-- CORREO -->
                    <div class="col-md-6 mb-3">

                        <label>Correo</label>

                        <input
                            type="email"
                            name="correo"
                            class="form-control"
                            placeholder="Ingrese Correo"
                            value="<?php echo $_POST['correo'] ?? ''; ?>"
                        >

                    </div>

                    <!-- USUARIO -->
                    <div class="col-md-6 mb-3">

                        <label>Usuario</label>

                        <input
                            type="text"
                            name="usuario"
                            class="form-control <?php if($error_usuario){ echo 'border border-danger'; } ?>"
                            placeholder="Ingrese Usuario"
                            value="<?php echo $_POST['usuario'] ?? ''; ?>"
                        >

                        <?php if($error_usuario){ ?>

                            <small class="text-danger">
                                Este usuario ya existe.
                            </small>

                        <?php } ?>

                    </div>

                    <!-- PASSWORD -->
                    <div class="col-md-6 mb-3">

                        <label>Contraseña</label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            placeholder="Ingrese Contraseña"
                        >

                    </div>

                    <!-- ROL -->
                    <div class="col-md-6 mb-3">

                        <label>Seleccionar Rol</label>

                        <select
                            name="rol_id"
                            class="form-control"
                        >

                            <option value="">
                                Seleccionar Rol
                            </option>

                            <?php

                            $stmt = $conexion->query("
                            SELECT id, nombre
                            FROM rol
                            ");

                            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){

                                $selected = "";

                                if(($_POST['rol_id'] ?? '') == $row['id']){
                                    $selected = "selected";
                                }

                                echo "
                                <option value='".$row['id']."' $selected>
                                    ".$row['nombre']."
                                </option>
                                ";
                            }

                            ?>

                        </select>

                    </div>

                    <!-- BOTON -->
                    <div class="col-md-12 text-end">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Guardar Usuario
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>

<?php include("../includes/footer_dashboard.php"); ?>