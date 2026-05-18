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

    // ✅ FOTO Y DOCUMENTOS
    $foto = "";
    $documento_pdf = "";

    // 🔒 VALIDACIONES

    if(empty($acudiente)){

        $mensaje_error = "Debe ingresar un acudiente";

    }

    if(empty($categoria_id)){

        $mensaje_error = "Debe seleccionar una categoría";

    }

    // 🔍 validar categoría
    if(empty($mensaje_error)){

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

    }

    // 🔍 VALIDAR DOCUMENTO REPETIDO
    if(empty($mensaje_error)){

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

    }

    // 🔍 VALIDAR NOMBRE REPETIDO
    if(empty($mensaje_error)){

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

            $mensaje_error = "El nombre ya está registrado.";

        }

    }

    // ✅ SUBIR FOTO
    if(empty($mensaje_error) && isset($_FILES['foto'])){

        if($_FILES['foto']['error'] == 0){

            $carpeta_fotos = "../../uploads/fotos/";

            if(!file_exists($carpeta_fotos)){
                mkdir($carpeta_fotos, 0777, true);
            }

            $nombre_foto = time() . "_" . $_FILES['foto']['name'];

            $ruta_foto = $carpeta_fotos . $nombre_foto;

            move_uploaded_file(
                $_FILES['foto']['tmp_name'],
                $ruta_foto
            );

            $foto = $nombre_foto;

        }

    }

    // ✅ SUBIR DOCUMENTO PDF
    if(empty($mensaje_error) && isset($_FILES['documento_pdf'])){

        if($_FILES['documento_pdf']['error'] == 0){

            $carpeta_docs = "../../uploads/documentos/";

            if(!file_exists($carpeta_docs)){
                mkdir($carpeta_docs, 0777, true);
            }

            $nombre_pdf = time() . "_" . $_FILES['documento_pdf']['name'];

            $ruta_pdf = $carpeta_docs . $nombre_pdf;

            move_uploaded_file(
                $_FILES['documento_pdf']['tmp_name'],
                $ruta_pdf
            );

            $documento_pdf = $nombre_pdf;

        }

    }

    // ✅ INSERTAR
    if(empty($mensaje_error)){

        $stm = $conexion->prepare("
        INSERT INTO deportista(
            tipo_documento,
            documento,
            telefono,
            nombre,
            fecha_nacimiento,
            categoria_id,
            foto,
            documento_pdf,
            estado
        )
        VALUES(
            :tipo_documento,
            :documento,
            :telefono,
            :nombre,
            :fecha_nacimiento,
            :categoria_id,
            :foto,
            :documento_pdf,
            'activo'
        )
        ");

        $stm->execute([
            ":tipo_documento"=>$tipo_documento,
            ":documento"=>$documento,
            ":telefono"=>$telefono,
            ":nombre"=>$nombre,
            ":fecha_nacimiento"=>$fecha_nacimiento,
            ":categoria_id"=>$categoria_id,
            ":foto"=>$foto,
            ":documento_pdf"=>$documento_pdf
        ]);

        // ✅ ID DEPORTISTA
        $deportista_id = $conexion->lastInsertId();

        // ✅ GUARDAR ACUDIENTE
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

<!-- ✅ MODAL CREAR -->
<div 
    class="modal fade <?php if(!empty($mensaje_error)){ echo 'show'; } ?>" 
    id="create" 
    tabindex="-1"
    style="<?php if(!empty($mensaje_error)){ echo 'display:block; background:rgba(0,0,0,0.5);'; } ?>"
>

    <div class="modal-dialog modal-xl">

        <div class="modal-content shadow">

            <!-- HEADER -->
            <div class="modal-header bg-primary text-white">

                <h5 class="modal-title">
                    Crear Deportista
                </h5>

                <button 
                    type="button" 
                    class="btn-close btn-close-white" 
                    data-bs-dismiss="modal"
                ></button>

            </div>

            <form action="" method="post" enctype="multipart/form-data">

                <div class="modal-body">

                    <!-- ERROR -->
                    <?php if(!empty($mensaje_error)){ ?>

                        <div class="alert alert-danger">

                            <?php echo $mensaje_error; ?>

                        </div>

                    <?php } ?>

                    <div class="row">

                        <!-- FOTO PERFIL -->
                        <div class="col-md-4 mb-3">

                            <div class="card p-3 text-center shadow-sm">

                                <h6 class="mb-3">
                                    Foto de Perfil
                                </h6>

                                <input 
                                    type="file"
                                    name="foto"
                                    class="form-control"
                                    accept="image/*"
                                >

                                <small class="text-muted mt-2">
                                    JPG, PNG o WEBP
                                </small>

                            </div>

                        </div>

                        <!-- DOCUMENTOS -->
                        <div class="col-md-8">

                            <div class="row">

                                <!-- TIPO DOCUMENTO -->
                                <div class="col-md-6 mb-3">

                                    <label class="form-label">
                                        Tipo Documento
                                    </label>

                                    <input 
                                        type="text" 
                                        name="tipo_documento" 
                                        class="form-control"
                                        value="<?php echo $_POST['tipo_documento'] ?? ''; ?>"
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
                                        value="<?php echo $_POST['documento'] ?? ''; ?>"
                                    >

                                </div>

                                <!-- TELÉFONO -->
                                <div class="col-md-6 mb-3">

                                    <label class="form-label">
                                        Teléfono
                                    </label>

                                    <input 
                                        type="text" 
                                        name="telefono" 
                                        class="form-control"
                                        value="<?php echo $_POST['telefono'] ?? ''; ?>"
                                    >

                                </div>

                                <!-- NOMBRE -->
                                <div class="col-md-6 mb-3">

                                    <label class="form-label">
                                        Nombre
                                    </label>

                                    <input 
                                        type="text" 
                                        name="nombre" 
                                        class="form-control <?php if($error_nombre){ echo 'border border-danger'; } ?>"
                                        value="<?php echo $_POST['nombre'] ?? ''; ?>"
                                    >

                                </div>

                                <!-- FECHA -->
                                <div class="col-md-6 mb-3">

                                    <label class="form-label">
                                        Fecha de nacimiento
                                    </label>

                                    <input 
                                        type="date" 
                                        class="form-control" 
                                        name="fecha_nacimiento"
                                        value="<?php echo $_POST['fecha_nacimiento'] ?? ''; ?>"
                                    >

                                </div>

                                <!-- CATEGORIA -->
                                <div class="col-md-6 mb-3">

                                    <label class="form-label">
                                        Categoría
                                    </label>

                                    <select 
                                        name="categoria_id" 
                                        class="form-control"
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

                            </div>

                        </div>

                    </div>

                    <hr>

                    <div class="row">

                        <!-- ACUDIENTE -->
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Acudiente
                            </label>

                            <input 
                                type="text" 
                                name="acudiente" 
                                class="form-control"
                                value="<?php echo $_POST['acudiente'] ?? ''; ?>"
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
                            >

                                <option value="">
                                    Seleccione parentesco
                                </option>

                                <option value="Papá">Papá</option>
                                <option value="Mamá">Mamá</option>
                                <option value="Acudiente">Acudiente</option>

                            </select>

                        </div>

                        <!-- DOCUMENTOS PDF -->
                        <div class="col-md-12 mb-3">

                            <label class="form-label">
                                Adjuntar Documentos
                            </label>

                            <input 
                                type="file"
                                name="documento_pdf"
                                class="form-control"
                                accept=".pdf,.jpg,.png"
                            >

                            <small class="text-muted">
                                Puedes adjuntar PDF, JPG o PNG
                            </small>

                        </div>

                    </div>

                </div>

                <!-- FOOTER -->
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
                        Guardar Deportista
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>