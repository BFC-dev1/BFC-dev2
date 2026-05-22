<?php  

include("../../modulos/conexion_modulos.php");

// =========================
// OBTENER DATOS
// =========================

$txtid = $_GET['id'] ?? "";

if($txtid != ""){

    // ✅ TRAER DEPORTISTA
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

        $estado = $registro['estado'];

        $foto = $registro['foto'] ?? "";

        $acudiente = $registro['acudiente'];
        $parentesco = $registro['parentesco'];

    }

}


// =========================
// ELIMINAR DOCUMENTO
// =========================

if(isset($_GET['eliminar_doc'])){

    $doc_id = $_GET['eliminar_doc'];

    // buscar documento
    $stmtDoc = $conexion->prepare("
    SELECT archivo
    FROM deportista_documentos
    WHERE id = :id
    ");

    $stmtDoc->execute([
        ":id"=>$doc_id
    ]);

    $doc = $stmtDoc->fetch(PDO::FETCH_ASSOC);

    if($doc){

        $ruta = "../../uploads/documentos/" . $doc['archivo'];

        // eliminar archivo físico
        if(file_exists($ruta)){
            unlink($ruta);
        }

        // eliminar registro BD
        $stmtDelete = $conexion->prepare("
        DELETE FROM deportista_documentos
        WHERE id = :id
        ");

        $stmtDelete->execute([
            ":id"=>$doc_id
        ]);

    }

    header("Location: editar.php?id=".$txtid);
    exit;

}


// =========================
// ACTUALIZAR
// =========================

if($_POST){

    $txtid = $_POST['id'] ?? "";

    $tipo_documento = $_POST['tipo_documento'] ?? "";
    $documento = $_POST['documento'] ?? "";
    $telefono = $_POST['telefono'] ?? "";
    $nombre = $_POST['nombre'] ?? "";
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? "";
    $categoria_id = $_POST['categoria_id'] ?? "";

    $estado = $_POST['estado'] ?? "activo";

    $acudiente = trim($_POST['acudiente'] ?? "");

    $parentesco = $_POST['parentesco'] ?? "";


    // =========================
    // VALIDACIONES
    // =========================

    if(empty($acudiente)){
        echo "<script>alert('Ingresa un acudiente');</script>";
        exit;
    }

    if(empty($categoria_id)){
        echo "<script>alert('Selecciona una categoría');</script>";
        exit;
    }


    // =========================
    // VALIDAR DOCUMENTO
    // =========================

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


    // =========================
    // FOTO ACTUAL
    // =========================

    $stmt_actual = $conexion->prepare("
    SELECT foto
    FROM deportista
    WHERE id = :id
    ");

    $stmt_actual->execute([
        ":id"=>$txtid
    ]);

    $actual = $stmt_actual->fetch(PDO::FETCH_ASSOC);

    $foto = $actual['foto'] ?? "";


    // =========================
    // SUBIR FOTO
    // =========================

    if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0){

        $nombreFoto = time() . "_" . $_FILES['foto']['name'];

        move_uploaded_file(
            $_FILES['foto']['tmp_name'],
            "../../uploads/fotos/" . $nombreFoto
        );

        $foto = $nombreFoto;
    }


    // =========================
    // ACTUALIZAR DEPORTISTA
    // =========================

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
        foto = :foto

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
        ":id"=>$txtid
    ]);


    // =========================
    // ACTUALIZAR ACUDIENTE
    // =========================

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


    // =========================
    // SUBIR MULTIPLES PDFs
    // =========================

    if(isset($_FILES['documentos'])){

        foreach($_FILES['documentos']['tmp_name'] as $key => $tmp_name){

            if($_FILES['documentos']['error'][$key] == 0){

                $archivoOriginal = $_FILES['documentos']['name'][$key];

                $extension = strtolower(pathinfo($archivoOriginal, PATHINFO_EXTENSION));

                // validar PDF
                if($extension == "pdf"){

                    $nuevoNombre = time() . "_" . uniqid() . ".pdf";

                    move_uploaded_file(
                        $tmp_name,
                        "../../uploads/documentos/" . $nuevoNombre
                    );

                    // guardar BD
                    $stmtInsert = $conexion->prepare("
                    INSERT INTO deportista_documentos(
                        deportista_id,
                        archivo
                    )
                    VALUES(
                        :deportista_id,
                        :archivo
                    )
                    ");

                    $stmtInsert->execute([
                        ":deportista_id"=>$txtid,
                        ":archivo"=>$nuevoNombre
                    ]);

                }

            }

        }

    }


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

                    <!-- FOTO -->
                    <div class="col-md-12 text-center mb-4">

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
                            Adjuntar Documentos PDF
                        </label>

                        <input 
                            type="file" 
                            name="documentos[]"
                            class="form-control"
                            accept=".pdf"
                            multiple
                        >

                    </div>


                    <!-- LISTAR DOCUMENTOS -->
                    <div class="col-md-12 mb-4">

                        <div class="border rounded p-3 bg-light">

                            <h5 class="fw-bold mb-3">
                                📁 Documentos Adjuntos
                            </h5>

                            <?php

                            $stmtDocs = $conexion->prepare("
                            SELECT *
                            FROM deportista_documentos
                            WHERE deportista_id = :id
                            ORDER BY id DESC
                            ");

                            $stmtDocs->execute([
                                ":id"=>$txtid
                            ]);

                            $documentos = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

                            ?>

                            <?php if(count($documentos) > 0){ ?>

                                <?php foreach($documentos as $doc){ ?>

                                    <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2 bg-white">

                                        <!-- NOMBRE -->
                                        <div>

                                            <span class="fw-bold text-danger">
                                                📄 PDF
                                            </span>

                                            <br>

                                            <small class="text-muted">
                                                <?php echo $doc['archivo']; ?>
                                            </small>

                                        </div>

                                        <!-- BOTONES -->
                                        <div class="d-flex gap-2">

                                            <!-- VER -->
                                            <a 
                                                href="../../uploads/documentos/<?php echo $doc['archivo']; ?>"
                                                target="_blank"
                                                class="btn btn-primary btn-sm"
                                            >
                                                👁 Ver
                                            </a>

                                            <!-- DESCARGAR -->
                                            <a 
                                                href="../../uploads/documentos/<?php echo $doc['archivo']; ?>"
                                                download
                                                class="btn btn-success btn-sm"
                                            >
                                                ⬇ Descargar
                                            </a>

                                            <!-- ELIMINAR -->
                                            <a 
                                                href="?id=<?php echo $txtid; ?>&eliminar_doc=<?php echo $doc['id']; ?>"
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('¿Eliminar documento?')"
                                            >
                                                🗑 Eliminar
                                            </a>

                                        </div>

                                    </div>

                                <?php } ?>

                            <?php }else{ ?>

                                <div class="alert alert-warning mb-0">

                                    No hay documentos adjuntos.

                                </div>

                            <?php } ?>

                        </div>

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


                    <!-- CATEGORIA -->
                    <div class="col-md-6 mb-3">

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
                            ORDER BY nombre ASC
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


                    <!-- ESTADO -->
                    <div class="col-md-6 mb-3">

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