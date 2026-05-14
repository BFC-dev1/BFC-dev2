<?php 

// ✅ VARIABLES DE ERROR
$error_documento = false;
$error_nombre = false;

$mensaje_error = "";

if($_POST){

$tipo_documento = trim($_POST['tipo_documento'] ?? "");
$documento = trim($_POST['documento'] ?? "");
$telefono = trim($_POST['telefono'] ?? "");
$nombre = trim($_POST['nombre'] ?? "");
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? "";
$categoria_id = $_POST['categoria_id'] ?? "";

// ✅ ACUDIENTE MANUAL
$acudiente = trim($_POST['acudiente'] ?? "");

$parentesco = trim($_POST['parentesco'] ?? "");

// 🔒 VALIDACIONES

if(empty($acudiente)){

    $mensaje_error = "Debe ingresar un acudiente";

}

if(empty($categoria_id)){

    $mensaje_error = "Debe seleccionar una categoría";

}

// 🔍 validar categoría
$stmt_cat = $conexion->prepare("
SELECT id 
FROM categoria 
WHERE id = :id
");

$stmt_cat->execute([
    ":id"=>$categoria_id
]);

if(!$stmt_cat->fetch()){

    $mensaje_error = "La categoría seleccionada no existe";

}

// 🔍 VALIDAR DOCUMENTO REPETIDO
$stmt_check = $conexion->prepare("
SELECT id 
FROM deportista 
WHERE documento = :documento
");

$stmt_check->execute([
    ":documento"=>$documento
]);

if($stmt_check->fetch()){

    $error_documento = true;

    $mensaje_error = "El documento ya está registrado. Cambia el número.";

}


// 🔍 VALIDAR NOMBRE REPETIDO
$stmt_nombre = $conexion->prepare("
SELECT id 
FROM deportista 
WHERE nombre = :nombre
");

$stmt_nombre->execute([
    ":nombre"=>$nombre
]);

if($stmt_nombre->fetch()){

    $error_nombre = true;

    $mensaje_error = "El nombre ya está registrado. Verifica el deportista.";

}


// ✅ SI NO HAY ERRORES → INSERTAR
if(empty($mensaje_error)){

// ✅ INSERTAR DEPORTISTA
$stm = $conexion->prepare("
INSERT INTO deportista(
    tipo_documento,
    documento,
    telefono,
    nombre,
    fecha_nacimiento,
    categoria_id,
    estado
)
VALUES(
    :tipo_documento,
    :documento,
    :telefono,
    :nombre,
    :fecha_nacimiento,
    :categoria_id,
    'activo'
)
");

$stm->execute([
    ":tipo_documento"=>$tipo_documento,
    ":documento"=>$documento,
    ":telefono"=>$telefono,
    ":nombre"=>$nombre,
    ":fecha_nacimiento"=>$fecha_nacimiento,
    ":categoria_id"=>$categoria_id
]);

// ✅ obtener ID del deportista
$deportista_id = $conexion->lastInsertId();

// ✅ guardar acudiente manual
$stmt_rel = $conexion->prepare("
INSERT INTO usuario_deportista(
    deportista_id,
    acudiente,
    parentesco
)
VALUES(
    :deportista_id,
    :acudiente,
    :parentesco
)
");

$stmt_rel->execute([
    ":deportista_id"=>$deportista_id,
    ":acudiente"=>$acudiente,
    ":parentesco"=>$parentesco
]);

header("Location: index.php?success=1");
exit;

}

}
?>

<!-- Modal create -->
<div class="modal fade <?php if(!empty($mensaje_error)){ echo 'show'; } ?>" 
     id="create" 
     tabindex="-1"
     style="<?php if(!empty($mensaje_error)){ echo 'display:block; background:rgba(0,0,0,0.5);'; } ?>"
>

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Agregar Deportista
                </h5>

                <button 
                    type="button" 
                    class="btn-close" 
                    data-bs-dismiss="modal"
                ></button>

            </div>

            <form action="" method="post">

                <div class="modal-body">

                    <!-- ✅ ALERTA ERROR -->
                    <?php if(!empty($mensaje_error)){ ?>

                        <div class="alert alert-danger">

                            <?php echo $mensaje_error; ?>

                        </div>

                    <?php } ?>

                    <!-- TIPO DOCUMENTO -->
                    <label>Tipo Documento</label>

                    <input 
                        type="text" 
                        name="tipo_documento" 
                        class="form-control"
                        value="<?php echo $_POST['tipo_documento'] ?? ''; ?>"
                        placeholder="Ingrese Tipo de Documento"
                    >

                    <!-- DOCUMENTO -->
                    <label>Documento</label>

                    <input 
                        type="text" 
                        name="documento" 
                        class="form-control <?php if($error_documento){ echo 'border border-danger'; } ?>"
                        value="<?php echo $_POST['documento'] ?? ''; ?>"
                        placeholder="Ingrese Documento"
                    >

                    <?php if($error_documento){ ?>

                        <small class="text-danger">
                            Este documento ya existe. Intenta con otro número.
                        </small>

                    <?php } ?>

                    <!-- TELÉFONO -->
                    <label>Teléfono</label>

                    <input 
                        type="text" 
                        name="telefono" 
                        class="form-control"
                        value="<?php echo $_POST['telefono'] ?? ''; ?>"
                        placeholder="Ingrese Teléfono"
                    >

                    <!-- NOMBRE -->
                    <label>Nombre</label>

                    <input 
                        type="text" 
                        name="nombre" 
                        class="form-control <?php if($error_nombre){ echo 'border border-danger'; } ?>"
                        value="<?php echo $_POST['nombre'] ?? ''; ?>"
                        placeholder="Ingrese Nombre"
                    >

                    <?php if($error_nombre){ ?>

                        <small class="text-danger">
                            Este nombre ya existe. Verifica si el deportista ya fue registrado.
                        </small>

                    <?php } ?>

                    <!-- FECHA -->
                    <label>Fecha de nacimiento</label>

                    <input 
                        type="date" 
                        class="form-control" 
                        name="fecha_nacimiento"
                        value="<?php echo $_POST['fecha_nacimiento'] ?? ''; ?>"
                    >

                    <!-- ✅ ACUDIENTE MANUAL -->
                    <label>Acudiente</label>

                    <input 
                        type="text" 
                        name="acudiente" 
                        class="form-control"
                        value="<?php echo $_POST['acudiente'] ?? ''; ?>"
                        placeholder="Ingrese nombre del acudiente"
                        required
                    >

                    <!-- ✅ PARENTESCO -->
                    <label>Parentesco</label>

                    <select 
                        name="parentesco" 
                        class="form-control"
                        required
                    >

                        <option value="">
                            Seleccione parentesco
                        </option>

                        <option value="Papá"
                        <?php if(($_POST['parentesco'] ?? '') == 'Papá'){ echo 'selected'; } ?>
                        >
                            Papá
                        </option>

                        <option value="Mamá"
                        <?php if(($_POST['parentesco'] ?? '') == 'Mamá'){ echo 'selected'; } ?>
                        >
                            Mamá
                        </option>

                        <option value="Acudiente"
                        <?php if(($_POST['parentesco'] ?? '') == 'Acudiente'){ echo 'selected'; } ?>
                        >
                            Acudiente
                        </option>

                    </select>

                    <!-- ✅ CATEGORIA -->
                    <label>Categoria</label>

                    <select 
                        name="categoria_id" 
                        class="form-control" 
                        required
                    >

                        <option value="">
                            Seleccionar categoria
                        </option>

                        <?php

                        $stmt = $conexion->query("
                        SELECT id, nombre 
                        FROM categoria
                        ");

                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){

                            $selected = "";

                            if(($_POST['categoria_id'] ?? '') == $row['id']){
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

                <div class="modal-footer">

                    <button 
                        type="button" 
                        class="btn btn-secondary" 
                        data-bs-dismiss="modal"
                    >
                        Cerrar
                    </button>

                    <button 
                        type="submit" 
                        class="btn btn-primary"
                    >
                        Guardar
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- ✅ POPUP CONFIRMAR ELIMINACIÓN -->

<script>

function confirmarEliminacion(id){

    let confirmar = confirm("¿Seguro que deseas eliminar este deportista?");

    if(confirmar){

        window.location = "index.php?id=" + id;

    }

}

</script>