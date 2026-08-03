<?php

// ✅ VARIABLES ERROR
$error_documento = false;
$error_usuario = false;

$mensaje_error = "";

/*
=================================================
OBTENER ID ROL ENTRENADOR
=================================================
*/
$rol_entrenador_id = 0;

$stmtRolEntrenador = $conexion->prepare("
SELECT id
FROM rol
WHERE LOWER(nombre) = 'entrenador'
LIMIT 1
");

$stmtRolEntrenador->execute();

$rolEntrenador = $stmtRolEntrenador->fetch(PDO::FETCH_ASSOC);

if($rolEntrenador){

    $rol_entrenador_id = $rolEntrenador['id'];

}


/*
=================================================
GUARDAR USUARIO
=================================================
*/
if($_POST){

    $nombre = trim($_POST['nombre'] ?? "");
    $tipo_documento = trim($_POST['tipo_documento'] ?? "");
    $documento = trim($_POST['documento'] ?? "");
    $telefono = trim($_POST['numero_telefono'] ?? "");
    $correo = trim($_POST['correo'] ?? "");
    $usuario = trim($_POST['usuario'] ?? "");
    $password = trim($_POST['password'] ?? "");
    $rol_id = trim($_POST['rol_id'] ?? "");

    /*
    =============================================
    VALIDAR CAMPOS
    =============================================
    */
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


    /*
    =============================================
    VALIDAR DOCUMENTO
    =============================================
    */
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


    /*
    =============================================
    VALIDAR USUARIO
    =============================================
    */
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


    /*
    =============================================
    INSERTAR USUARIO
    =============================================
    */
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
                `password`,
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


            /*
            =============================================
            ID USUARIO CREADO
            =============================================
            */
            $usuario_id = $conexion->lastInsertId();


            /*
            =============================================
            SI EL ROL ES ENTRENADOR
            GUARDAR TAMBIÉN EN TABLA entrenador
            =============================================
            */
            if($rol_id == $rol_entrenador_id){

             /*
                =========================================
                GUARDAR CATEGORÍAS DEL ENTRENADOR
                =========================================
                */
                if(!empty($_POST['categorias'])){

                    foreach($_POST['categorias'] as $categoria_id){

                        $stmtCategoria = $conexion->prepare("
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

                        $stmtCategoria->execute([
                            ":usuario_id"=>$usuario_id,
                            ":categoria_id"=>$categoria_id
                        ]);

                    }

                }

            }   // <-- AQUÍ recién termina if($rol_id == $rol_entrenador_id)

header("Location: index.php");
exit;

        }catch(PDOException $e){

            $mensaje_error = $e->getMessage();

        }

    }

}

?>

<?php

/*
=============================================
CONSULTAR CATEGORÍAS
=============================================
*/

$stmtCategorias = $conexion->prepare("
SELECT id, nombre
FROM categoria
ORDER BY nombre
");

$stmtCategorias->execute();

$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- ✅ MODAL CREAR -->

<div 
    class="modal fade <?php if(!empty($mensaje_error)){ echo 'show'; } ?>" 
    id="create" 
    tabindex="-1"
    style="<?php if(!empty($mensaje_error)){ echo 'display:block; background:rgba(0,0,0,0.5);'; } ?>"
>

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Crear Usuario
                </h5>

                <button 
                    type="button" 
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>

            <form action="index.php" method="post">

                <div class="modal-body">

                    <!-- ✅ MENSAJE ERROR -->
                    <?php if(!empty($mensaje_error)){ ?>

                        <div class="alert alert-danger">

                            <?php echo $mensaje_error; ?>

                        </div>

                    <?php } ?>


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

                            <label>Tipo Documento</label>

                            <input
                                type="text"
                                name="tipo_documento"
                                class="form-control"
                                placeholder="Ingrese Tipo Documento"
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

                            <label>Teléfono</label>

                            <input
                                type="text"
                                name="numero_telefono"
                                class="form-control"
                                placeholder="Ingrese Teléfono"
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
                            id="rol_id"
                            name="rol_id"
                            class="form-control"
                            required
                        >

                                <option value="">
                                    Seleccionar Rol
                                </option>

                                <?php

                                $stmt = $conexion->query("
                                SELECT id, nombre
                                FROM rol
                                ORDER BY nombre ASC
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

                        <!-- CATEGORÍAS DEL ENTRENADOR -->

<div
    class="col-12 mb-3"
    id="contenedorCategorias"
    style="display:none;"
>

    <label>Categorías asignadas</label>

    <div class="row">

        <?php foreach($categorias as $categoria){ ?>

            <div class="col-md-4">

                <div class="form-check">

                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="categorias[]"
                        value="<?php echo $categoria['id']; ?>"
                        id="cat<?php echo $categoria['id']; ?>"
                    >

                    <label
                        class="form-check-label"
                        for="cat<?php echo $categoria['id']; ?>"
                    >
                        <?php echo $categoria['nombre']; ?>
                    </label>

                </div>

            </div>

        <?php } ?>

    </div>

</div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-danger"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Crear Usuario
                    </button>

                </div>

             </form>

        </div>

    </div>

</div>

<script>

const rol = document.querySelector("select[name='rol_id']");
const contenedor = document.getElementById("contenedorCategorias");

function mostrarCategorias(){

    if(rol.value == "<?php echo $rol_entrenador_id; ?>"){

        contenedor.style.display = "block";

    }else{

        contenedor.style.display = "none";

    }

}

rol.addEventListener("change", mostrarCategorias);

// Ejecutar al abrir el modal
mostrarCategorias();

</script>