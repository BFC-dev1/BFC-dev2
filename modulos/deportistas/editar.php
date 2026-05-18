<?php  

include("../../modulos/conexion_modulos.php");

// 🔍 OBTENER DATOS
if(isset($_GET['id'])){

    $txtid = $_GET['id'];

    // ✅ traer deportista + acudiente manual
    $stm = $conexion->prepare("
        SELECT 
            d.*, 
            ud.acudiente,
            ud.parentesco

        FROM deportista d

        LEFT JOIN usuario_deportista ud 
            ON d.id = ud.deportista_id

        WHERE d.id = :id
    ");

    $stm->bindParam(":id", $txtid);
    $stm->execute();

    $registro = $stm->fetch(PDO::FETCH_ASSOC);

    if($registro){

        $tipo_documento = $registro['tipo_documento'];
        $documento = $registro['documento'];
        $telefono = $registro['telefono'];
        $nombre = $registro['nombre'];
        $fecha_nacimiento = $registro['fecha_nacimiento'];
        $categoria_id = $registro['categoria_id'];

        // ✅ ESTADO
        $estado = $registro['estado'];

        // ✅ FOTO Y DOCUMENTO
        $foto = $registro['foto'] ?? "";
        $documento_pdf = $registro['documento_pdf'] ?? "";

        // ✅ ACUDIENTE MANUAL
        $acudiente = $registro['acudiente'];

        $parentesco = $registro['parentesco'];

    }

}


// 🔄 ACTUALIZAR
if($_POST){

$txtid = $_POST['id'] ?? "";

$tipo_documento = $_POST['tipo_documento'] ?? "";
$documento = $_POST['documento'] ?? "";
$telefono = $_POST['telefono'] ?? "";
$nombre = $_POST['nombre'] ?? "";
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? "";
$categoria_id = $_POST['categoria_id'] ?? "";

// ✅ ESTADO
$estado = $_POST['estado'] ?? "activo";

// ✅ ACUDIENTE MANUAL
$acudiente = trim($_POST['acudiente'] ?? "");

$parentesco = $_POST['parentesco'] ?? "";


// 🔒 VALIDACIONES

if(empty($acudiente)){
    echo "<script>alert('Ingresa un acudiente');</script>";
    exit;
}

if(empty($categoria_id)){
    echo "<script>alert('Selecciona una categoría');</script>";
    exit;
}


// validar categoría
$stmt_cat = $conexion->prepare("
SELECT id 
FROM categoria 
WHERE id = :id
");

$stmt_cat->execute([
    ":id"=>$categoria_id
]);

if(!$stmt_cat->fetch()){
    echo "<script>alert('Categoría inválida');</script>";
    exit;
}


// validar documento único
$stmt_check = $conexion->prepare("
SELECT id 
FROM deportista 
WHERE documento = :documento 
AND id != :id
");

$stmt_check->execute([
    ":documento"=>$documento,
    ":id"=>$txtid
]);

if($stmt_check->fetch()){
    echo "<script>alert('Este documento ya está registrado');</script>";
    exit;
}


// ✅ MANTENER ARCHIVOS ACTUALES
$stmt_actual = $conexion->prepare("
SELECT foto, documento_pdf
FROM deportista
WHERE id = :id
");

$stmt_actual->execute([
    ":id"=>$txtid
]);

$actual = $stmt_actual->fetch(PDO::FETCH_ASSOC);

$foto = $actual['foto'] ?? "";
$documento_pdf = $actual['documento_pdf'] ?? "";


// ✅ SUBIR FOTO
if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0){

    $nombreFoto = time() . "_" . $_FILES['foto']['name'];

    move_uploaded_file(
        $_FILES['foto']['tmp_name'],
        "../../uploads/fotos/" . $nombreFoto
    );

    $foto = $nombreFoto;
}


// ✅ SUBIR PDF
if(isset($_FILES['documento_pdf']) && $_FILES['documento_pdf']['error'] == 0){

    $nombrePdf = time() . "_" . $_FILES['documento_pdf']['name'];

    move_uploaded_file(
        $_FILES['documento_pdf']['tmp_name'],
        "../../uploads/documentos/" . $nombrePdf
    );

    $documento_pdf = $nombrePdf;
}


// ✅ actualizar deportista
$stm = $conexion->prepare("
UPDATE deportista 
SET 
    tipo_documento = :tipo_documento,
    documento = :documento,
    telefono = :telefono,
    nombre = :nombre,
    fecha_nacimiento = :fecha_nacimiento,
    categoria_id = :categoria_id,
    estado = :estado,
    foto = :foto,
    documento_pdf = :documento_pdf

WHERE id = :id
");

$stm->execute([
    ":tipo_documento"=>$tipo_documento,
    ":documento"=>$documento,
    ":telefono"=>$telefono,
    ":nombre"=>$nombre,
    ":fecha_nacimiento"=>$fecha_nacimiento,
    ":categoria_id"=>$categoria_id,
    ":estado"=>$estado,
    ":foto"=>$foto,
    ":documento_pdf"=>$documento_pdf,
    ":id"=>$txtid
]);


// ✅ actualizar acudiente manual
$stmt_rel = $conexion->prepare("
UPDATE usuario_deportista 

SET 
    acudiente = :acudiente,
    parentesco = :parentesco

WHERE deportista_id = :deportista_id
");

$stmt_rel->execute([
    ":acudiente"=>$acudiente,
    ":parentesco"=>$parentesco,
    ":deportista_id"=>$txtid
]);


header("location:index.php");
exit;

}

?>

<?php include("../../template/header_modulos.php") ?>


<div class="container mt-4">

    <div class="card shadow border-0">

        <!-- HEADER -->
        <div class="card-header bg-primary text-white">

            <h4 class="mb-0">
                Editar Deportista
            </h4>

        </div>

        <div class="card-body">

            <form action="" method="post" enctype="multipart/form-data">

                <input 
                    type="hidden" 
                    name="id" 
                    value="<?php echo $txtid; ?>"
                >

                <div class="row">

                    <!-- FOTO PERFIL -->
                    <div class="col-md-12 text-center mb-4">

                        <!-- TEXTO EDITAR -->
                        <label 
                            for="fotoInput"
                            style="
                                cursor:pointer;
                                color:#0d6efd;
                                font-weight:bold;
                                display:block;
                                margin-bottom:10px;
                            "
                        >
                            Editar Foto
                        </label>

                        <?php if(!empty($foto)){ ?>

                            <img 
                                src="../../uploads/fotos/<?php echo $foto; ?>" 
                                width="150"
                                height="150"
                                class="rounded-circle shadow"
                                style="object-fit:cover;"
                            >

                        <?php }else{ ?>

                            <img 
                                src="https://via.placeholder.com/150"
                                width="150"
                                height="150"
                                class="rounded-circle shadow"
                                style="object-fit:cover;"
                            >

                        <?php } ?>

                        <!-- INPUT OCULTO -->
                        <input 
                            type="file" 
                            id="fotoInput"
                            name="foto"
                            accept="image/*"
                            style="display:none;"
                        >

                    </div>


                    <!-- DOCUMENTOS -->
                    <div class="col-md-12 mb-4">

                        <label class="form-label fw-bold">
                            Adjuntar Documentos (PDF)
                        </label>

                        <input 
                            type="file" 
                            name="documento_pdf"
                            class="form-control"
                            accept=".pdf"
                        >

                        <?php if(!empty($documento_pdf)){ ?>

                            <div class="mt-2">

                                <a 
                                    href="../../uploads/documentos/<?php echo $documento_pdf; ?>" 
                                    target="_blank"
                                    class="btn btn-outline-primary btn-sm"
                                >
                                    Ver Documento Actual
                                </a>

                            </div>

                        <?php } ?>

                    </div>


                    <!-- TIPO DOCUMENTO -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Tipo Documento
                        </label>

                        <input 
                            type="text" 
                            class="form-control" 
                            name="tipo_documento" 
                            value="<?php echo $tipo_documento; ?>"
                        >

                    </div>


                    <!-- DOCUMENTO -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Documento
                        </label>

                        <input 
                            type="text" 
                            class="form-control" 
                            name="documento" 
                            value="<?php echo $documento; ?>"
                        >

                    </div>


                    <!-- TELEFONO -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Teléfono
                        </label>

                        <input 
                            type="text" 
                            class="form-control" 
                            name="telefono" 
                            value="<?php echo $telefono; ?>"
                        >

                    </div>


                    <!-- NOMBRE -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Nombre
                        </label>

                        <input 
                            type="text" 
                            class="form-control" 
                            name="nombre" 
                            value="<?php echo $nombre; ?>"
                        >

                    </div>


                    <!-- FECHA NACIMIENTO -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Fecha de nacimiento
                        </label>

                        <input 
                            type="date" 
                            class="form-control" 
                            name="fecha_nacimiento" 
                            value="<?php echo $fecha_nacimiento; ?>"
                        >

                    </div>


                    <!-- ACUDIENTE -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Acudiente
                        </label>

                        <input 
                            type="text" 
                            name="acudiente" 
                            class="form-control" 
                            value="<?php echo $acudiente; ?>"
                            placeholder="Ingrese acudiente"
                            required
                        >

                    </div>


                    <!-- PARENTESCO -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Parentesco
                        </label>

                        <select 
                            name="parentesco" 
                            class="form-control"
                            required
                        >

                            <option value="">
                                Seleccione parentesco
                            </option>

                            <option 
                                value="Papá"
                                <?php if($parentesco=="Papá"){ echo "selected"; } ?>
                            >
                                Papá
                            </option>

                            <option 
                                value="Mamá"
                                <?php if($parentesco=="Mamá"){ echo "selected"; } ?>
                            >
                                Mamá
                            </option>

                            <option 
                                value="Acudiente"
                                <?php if($parentesco=="Acudiente"){ echo "selected"; } ?>
                            >
                                Acudiente
                            </option>

                        </select>

                    </div>


                    <!-- ESTADO -->
                    <div class="col-md-3 mb-3">

                        <label class="form-label">
                            Estado
                        </label>

                        <select 
                            name="estado" 
                            class="form-control"
                            required
                        >

                            <option 
                                value="activo"
                                <?php if($estado=="activo"){ echo "selected"; } ?>
                            >
                                Activo
                            </option>

                            <option 
                                value="inactivo"
                                <?php if($estado=="inactivo"){ echo "selected"; } ?>
                            >
                                Inactivo
                            </option>

                        </select>

                    </div>


                    <!-- CATEGORIA -->
                    <div class="col-md-3 mb-3">

                        <label class="form-label">
                            Categoría
                        </label>

                        <select 
                            name="categoria_id" 
                            class="form-control" 
                            required
                        >

                            <option value="">
                                Seleccionar categoría
                            </option>

                            <?php

                            $stmt = $conexion->query("
                            SELECT id, nombre 
                            FROM categoria
                            ");

                            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){

                                $selected = ($row['id'] == $categoria_id) ? "selected" : "";

                                echo "
                                <option value='".$row['id']."' $selected>
                                    ".$row['nombre']."
                                </option>
                                ";
                            }

                            ?>

                        </select>

                    </div>

                </div>


                <!-- BOTONES -->
                <div class="d-flex gap-2 mt-3">

                    <a href="index.php" class="btn btn-danger">
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Actualizar Deportista
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<?php include("../../template/footer_modulos.php") ?>