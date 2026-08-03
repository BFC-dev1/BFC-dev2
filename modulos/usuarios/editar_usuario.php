<?php

session_start();

include("../conexion_modulos.php");

// Incluye el archivo con la función registrarAuditoria() para almacenar el historial de cambios del sistema.
require_once("../auditoria/funciones/registrar_auditoria.php");

// ✅ VALIDAR ID
if(!isset($_GET['id'])){

   
header("Location: index.php?actualizado=1");
exit;

}

$id = $_GET['id'];


// ✅ CONSULTAR USUARIO
$stmt = $conexion->prepare("
SELECT *
FROM usuario
WHERE id = :id
");

$stmt->execute([
    ":id"=>$id
]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

/*
=================================================
GUARDAR DATOS ANTERIORES

Se almacenan los datos originales del usuario
antes de realizar la actualización.

Estos datos se utilizarán posteriormente para
comparar qué campos cambiaron y registrar la
auditoría de edición.

=================================================
*/

$datosAnteriores = $usuario;

// =============================================
// OBTENER CATEGORÍAS ASIGNADAS AL ENTRENADOR
// =============================================

$stmtCatUsuario = $conexion->prepare("
SELECT categoria_id
FROM entrenador_categoria
WHERE usuario_id = :usuario_id
");

$stmtCatUsuario->execute([
    ":usuario_id"=>$id
]);

$categorias_usuario = $stmtCatUsuario->fetchAll(PDO::FETCH_COLUMN);

// ✅ SI NO EXISTE
if(!$usuario){

header("Location: index.php?actualizado=1");
exit;

}


// ✅ VARIABLES ERROR
$error_documento = false;
$error_usuario = false;

$mensaje_error = "";


// ✅ ACTUALIZAR
if($_POST){

    $nombre = trim($_POST['nombre'] ?? "");
    $tipo_documento = trim($_POST['tipo_documento'] ?? "");
    $documento = trim($_POST['documento'] ?? "");
    $telefono = trim($_POST['numero_telefono'] ?? "");
    $correo = trim($_POST['correo'] ?? "");
    $usuario_input = trim($_POST['usuario'] ?? "");
    $password = trim($_POST['password'] ?? "");
    $rol_id = trim($_POST['rol_id'] ?? "");
    $categorias = $_POST['categorias'] ?? [];
    $estado = trim($_POST['estado'] ?? "activo");

    // ✅ VALIDAR CAMPOS
    if(
        empty($nombre) ||
        empty($tipo_documento) ||
        empty($documento) ||
        empty($telefono) ||
        empty($correo) ||
        empty($usuario_input) ||
        empty($rol_id)
    ){

        $mensaje_error = "Todos los campos son obligatorios.";

    }


    // ✅ VALIDAR DOCUMENTO REPETIDO
    if(empty($mensaje_error)){

        $stmt_doc = $conexion->prepare("
        SELECT id
        FROM usuario
        WHERE documento = :documento
        AND id != :id
        ");

        $stmt_doc->execute([
            ":documento"=>$documento,
            ":id"=>$id
        ]);

        if($stmt_doc->fetch()){

            $error_documento = true;

            $mensaje_error = "El documento ya está registrado.";

        }

    }


    // ✅ VALIDAR USUARIO REPETIDO
    if(empty($mensaje_error)){

        $stmt_user = $conexion->prepare("
        SELECT id
        FROM usuario
        WHERE usuario = :usuario
        AND id != :id
        ");

        $stmt_user->execute([
            ":usuario"=>$usuario_input,
            ":id"=>$id
        ]);

        if($stmt_user->fetch()){

            $error_usuario = true;

            $mensaje_error = "El usuario ya existe.";

        }

    }


    // ✅ ACTUALIZAR
    if(empty($mensaje_error)){

        try{

            // ✅ SI ESCRIBE NUEVA CONTRASEÑA
            if(!empty($password)){

                $update = $conexion->prepare("
                UPDATE usuario SET
                    nombre = :nombre,
                    tipo_documento = :tipo_documento,
                    documento = :documento,
                    telefono = :telefono,
                    correo = :correo,
                    usuario = :usuario,
                    `password` = :password,
                    rol_id = :rol_id,
                    estado = :estado
                WHERE id = :id
                ");

                $update->execute([
                    ":nombre"=>$nombre,
                    ":tipo_documento"=>$tipo_documento,
                    ":documento"=>$documento,
                    ":telefono"=>$telefono,
                    ":correo"=>$correo,
                    ":usuario"=>$usuario_input,
                    ":password"=>$password,
                    ":rol_id"=>$rol_id,
                    ":estado"=>$estado,
                    ":id"=>$id
                ]);

            }else{

                // ✅ SI NO CAMBIA CONTRASEÑA
                $update = $conexion->prepare("
                UPDATE usuario SET
                    nombre = :nombre,
                    tipo_documento = :tipo_documento,
                    documento = :documento,
                    telefono = :telefono,
                    correo = :correo,
                    usuario = :usuario,
                    rol_id = :rol_id,
                    estado = :estado
                WHERE id = :id
                ");

                $update->execute([
                    ":nombre"=>$nombre,
                    ":tipo_documento"=>$tipo_documento,
                    ":documento"=>$documento,
                    ":telefono"=>$telefono,
                    ":correo"=>$correo,
                    ":usuario"=>$usuario_input,
                    ":rol_id"=>$rol_id,
                    ":estado"=>$estado,
                    ":id"=>$id
                ]);

            }

            // =============================================
// ACTUALIZAR CATEGORÍAS DEL ENTRENADOR
// =============================================

/*
=================================================
ACTUALIZAR CATEGORÍAS DEL ENTRENADOR
=================================================
*/

// Eliminar siempre las categorías actuales
$deleteCat = $conexion->prepare("
DELETE FROM entrenador_categoria
WHERE usuario_id = :usuario_id
");

$deleteCat->execute([
    ":usuario_id"=>$id
]);

// Si el usuario es entrenador, asignar las nuevas categorías
if($rol_id == 3){

    foreach($categorias as $categoria_id){

        $insertCat = $conexion->prepare("
        INSERT INTO entrenador_categoria
        (
            usuario_id,
            categoria_id
        )
        VALUES
        (
            :usuario_id,
            :categoria_id
        )
        ");

        $insertCat->execute([
            ":usuario_id"=>$id,
            ":categoria_id"=>$categoria_id
        ]);

    }

}
/*
=================================================
OBTENER CAMBIOS REALIZADOS

Se comparan los datos anteriores con los nuevos
para registrar únicamente los campos que fueron
modificados.

=================================================
*/

$cambios = [];

if($datosAnteriores["nombre"] != $nombre){

    $cambios["nombre"] = [
        "antes"=>$datosAnteriores["nombre"],
        "despues"=>$nombre
    ];

}

if($datosAnteriores["tipo_documento"] != $tipo_documento){

    $cambios["tipo_documento"] = [
        "antes"=>$datosAnteriores["tipo_documento"],
        "despues"=>$tipo_documento
    ];

}

if($datosAnteriores["documento"] != $documento){

    $cambios["documento"] = [
        "antes"=>$datosAnteriores["documento"],
        "despues"=>$documento
    ];

}

if($datosAnteriores["telefono"] != $telefono){

    $cambios["telefono"] = [
        "antes"=>$datosAnteriores["telefono"],
        "despues"=>$telefono
    ];

}

if($datosAnteriores["correo"] != $correo){

    $cambios["correo"] = [
        "antes"=>$datosAnteriores["correo"],
        "despues"=>$correo
    ];

}

if($datosAnteriores["usuario"] != $usuario_input){

    $cambios["usuario"] = [
        "antes"=>$datosAnteriores["usuario"],
        "despues"=>$usuario_input
    ];

}

if($datosAnteriores["rol_id"] != $rol_id){

    $cambios["rol_id"] = [
        "antes"=>$datosAnteriores["rol_id"],
        "despues"=>$rol_id
    ];

}

if($datosAnteriores["estado"] != $estado){

    $cambios["estado"] = [
        "antes"=>$datosAnteriores["estado"],
        "despues"=>$estado
    ];

}

/*
=================================================
REGISTRAR AUDITORÍA DE EDICIÓN

Solo se registra si hubo cambios.

=================================================
*/

if(!empty($cambios)){

    registrarAuditoria(

        $conexion,

        "usuario",

        $id,

        "EDITAR",

        $cambios,

        "Actualización del usuario: ".$nombre

    );

}

header("Location: index.php?actualizado=1");
exit;

        }catch(PDOException $e){

            $mensaje_error = $e->getMessage();

        }

    }

}

?>


<?php include("../../template/header_modulos_Usuarios.php") ?>


<div class="container mt-4">

    <div class="card shadow border-0">

        <!-- HEADER -->
        <div class="card-header bg-primary text-white">

            <h4 class="mb-0">
                Editar Usuario
            </h4>

        </div>

        <div class="card-body">

            <!-- ✅ ERROR -->
            <?php if(!empty($mensaje_error)){ ?>

                <div class="alert alert-danger">

                    <?php echo $mensaje_error; ?>

                </div>

            <?php } ?>


            <form method="post">

                <div class="row">

                    <!-- NOMBRE -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Nombre
                        </label>

                        <input
                            type="text"
                            name="nombre"
                            class="form-control"
                            value="<?php echo $_POST['nombre'] ?? $usuario['nombre']; ?>"
                        >

                    </div>


                    <!-- TIPO DOCUMENTO -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Tipo Documento
                        </label>

                        <input
                            type="text"
                            name="tipo_documento"
                            class="form-control"
                            value="<?php echo $_POST['tipo_documento'] ?? $usuario['tipo_documento']; ?>"
                        >

                    </div>


                    <!-- DOCUMENTO -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Documento
                        </label>

                        <input
                            type="text"
                            name="documento"
                            class="form-control <?php if($error_documento){ echo 'border border-danger'; } ?>"
                            value="<?php echo $_POST['documento'] ?? $usuario['documento']; ?>"
                        >

                        <?php if($error_documento){ ?>

                            <small class="text-danger">
                                Este documento ya existe.
                            </small>

                        <?php } ?>

                    </div>


                    <!-- TELEFONO -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Teléfono
                        </label>

                        <input
                            type="text"
                            name="numero_telefono"
                            class="form-control"
                            value="<?php echo $_POST['numero_telefono'] ?? $usuario['telefono']; ?>"
                        >

                    </div>


                    <!-- CORREO -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Correo
                        </label>

                        <input
                            type="email"
                            name="correo"
                            class="form-control"
                            value="<?php echo $_POST['correo'] ?? $usuario['correo']; ?>"
                        >

                    </div>


                    <!-- USUARIO -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Usuario
                        </label>

                        <input
                            type="text"
                            name="usuario"
                            class="form-control <?php if($error_usuario){ echo 'border border-danger'; } ?>"
                            value="<?php echo $_POST['usuario'] ?? $usuario['usuario']; ?>"
                        >

                        <?php if($error_usuario){ ?>

                            <small class="text-danger">
                                Este usuario ya existe.
                            </small>

                        <?php } ?>

                    </div>


                    <!-- PASSWORD -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Nueva Contraseña
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            placeholder="Dejar vacío para no cambiar"
                        >

                    </div>


                    <!-- ROL -->
                    <div class="col-md-3 mb-3">

                        <label class="form-label">
                            Rol
                        </label>

                        <select
                            name="rol_id"
                            class="form-control"
                        >

                            <option value="">
                                Seleccionar Rol
                            </option>

                            <?php

                            $roles = $conexion->query("
                            SELECT id, nombre
                            FROM rol
                            ");

                            while($row = $roles->fetch(PDO::FETCH_ASSOC)){

                                $selected = "";

                                $valor_actual = $_POST['rol_id'] ?? $usuario['rol_id'];

                                if($valor_actual == $row['id']){

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


                    <!-- CATEGORÍAS ENTRENADOR -->

<div 
class="col-12 mb-3"
id="contenedorCategorias"
>

<label class="form-label">
Categorías asignadas
</label>


<div class="row">

<?php

$stmtCategorias = $conexion->query("
SELECT id,nombre
FROM categoria
ORDER BY nombre
");


while($cat = $stmtCategorias->fetch(PDO::FETCH_ASSOC)){


$checked = "";


if(in_array($cat['id'],$categorias_usuario)){

    $checked = "checked";

}


?>

<div class="col-md-4">

<div class="form-check">

<input
class="form-check-input"
type="checkbox"
name="categorias[]"
value="<?php echo $cat['id']; ?>"
<?php echo $checked; ?>
>

<label class="form-check-label">

<?php echo $cat['nombre']; ?>

</label>


</div>

</div>


<?php } ?>

</div>

</div>


                    <!-- ESTADO -->
                    <div class="col-md-3 mb-3">

                        <label class="form-label">
                            Estado
                        </label>

                        <select
                            name="estado"
                            class="form-control"
                        >

                            <?php
                            $estado_actual = $_POST['estado'] ?? $usuario['estado'];
                            ?>

                            <option 
                                value="activo"
                                <?php if($estado_actual == "activo"){ echo "selected"; } ?>
                            >
                                Activo
                            </option>

                            <option 
                                value="inactivo"
                                <?php if($estado_actual == "inactivo"){ echo "selected"; } ?>
                            >
                                Inactivo
                            </option>

                        </select>

                    </div>

                </div>


                <!-- BOTONES -->
                <div class="d-flex gap-2 mt-3">

                    <a 
                        href="index.php"
                        class="btn btn-danger"
                    >
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Actualizar Usuario
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<?php include("../../template/footer_modulos_Usuarios.php") ?>


<script>

let rol = document.querySelector("select[name='rol_id']");
let contenedor = document.getElementById("contenedorCategorias");


function mostrarCategorias(){

    if(rol.value == "3"){

        contenedor.style.display="block";

    }else{

        contenedor.style.display="none";

    }

}


rol.addEventListener("change",mostrarCategorias);

mostrarCategorias();

</script>