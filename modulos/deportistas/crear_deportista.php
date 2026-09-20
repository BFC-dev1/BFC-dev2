<?php 

// ✅ VARIABLES DE ERROR
$error_documento = false;
$error_nombre = false;

$mensaje_error = "";

/* =================================================
   OBTENER AUTOMÁTICAMENTE EL PRIMER DORSAL DISPONIBLE (1 a 99)
================================================= */
$stmtOcupados = $conexion->query("
    SELECT dorsal 
    FROM deportista 
    WHERE estado = 'activo' 
      AND dorsal IS NOT NULL
");
$ocupados = $stmtOcupados->fetchAll(PDO::FETCH_COLUMN);
$mapaOcupados = array_flip($ocupados);

$dorsalAutollenado = null;
for ($i = 1; $i <= 99; $i++) {
    if (!isset($mapaOcupados[$i])) {
        $dorsalAutollenado = $i;
        break;
    }
}

if($_POST){

    $tipo_documento = trim($_POST['tipo_documento'] ?? "");
    $documento = trim($_POST['documento'] ?? "");
    $dorsal = !empty($_POST['dorsal']) ? (int)$_POST['dorsal'] : null;
    $telefono = trim($_POST['telefono'] ?? "");
    $nombre = trim($_POST['nombre'] ?? "");
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? "";
    $categoria_id = $_POST['categoria_id'] ?? "";

    // =================================================
    // ENTRENADOR
    // =================================================
    $entrenador_id = !empty($_POST['entrenador_id'])
        ? (int) $_POST['entrenador_id']
        : null;

    // ✅ ACUDIENTE MANUAL
    $acudiente = trim($_POST['acudiente'] ?? "");
    $parentesco = trim($_POST['parentesco'] ?? "");

    // ✅ FOTO
    $foto = "";

    // 🔒 VALIDACIONES

    if(empty($acudiente)){
        $mensaje_error = "Debe ingresar un acudiente";
    }

    if(empty($categoria_id)){
        $mensaje_error = "Debe seleccionar una categoría";
    }

    // 🔍 VALIDAR CATEGORÍA
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
            $mensaje_error = "El documento ya está registrado.";
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

    // 🔍 VALIDAR DORSAL REPETIDO EN JUGADORES ACTIVOS
    if(empty($mensaje_error) && !empty($dorsal)){
        $stmt_dorsal = $conexion->prepare("
        SELECT id, nombre 
        FROM deportista 
        WHERE dorsal = :dorsal 
          AND estado = 'activo'
        ");

        $stmt_dorsal->execute([
            ":dorsal"=>$dorsal
        ]);

        $jugadorExistente = $stmt_dorsal->fetch(PDO::FETCH_ASSOC);

        if($jugadorExistente){
            $mensaje_error = "El dorsal #" . $dorsal . " ya está asignado al deportista activo: " . htmlspecialchars($jugadorExistente['nombre']) . ".";
        }
    }

    // =================================================
    // VALIDAR ENTRENADOR
    // =================================================
    if(empty($mensaje_error)){
        if(empty($entrenador_id)){
            $mensaje_error = "Debe seleccionar un entrenador.";
        }else{
            $stmt_ent = $conexion->prepare("
                SELECT id
                FROM usuario
                WHERE id = :id
                AND rol_id = 3
                AND estado = 'activo'
            ");

            $stmt_ent->execute([
                ":id" => $entrenador_id
            ]);

            if(!$stmt_ent->fetch()){
                $mensaje_error = "El entrenador seleccionado no existe o no está activo.";
            }
        }
    }

    // =========================
    // SUBIR FOTO
    // =========================

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

    // =========================
    // INSERTAR DEPORTISTA
    // =========================

    if(empty($mensaje_error)){

        $stm = $conexion->prepare("
        INSERT INTO deportista(
            tipo_documento,
            documento,
            dorsal,
            telefono,
            nombre,
            fecha_nacimiento,
            categoria_id,
            foto,
            estado
        )
        VALUES(
            :tipo_documento,
            :documento,
            :dorsal,
            :telefono,
            :nombre,
            :fecha_nacimiento,
            :categoria_id,
            :foto,
            'activo'
        )
        ");

        $stm->bindValue(":tipo_documento", $tipo_documento);
        $stm->bindValue(":documento", $documento);
        $stm->bindValue(":dorsal", $dorsal, $dorsal === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stm->bindValue(":telefono", $telefono);
        $stm->bindValue(":nombre", $nombre);
        $stm->bindValue(":fecha_nacimiento", $fecha_nacimiento);
        $stm->bindValue(":categoria_id", $categoria_id);
        $stm->bindValue(":foto", $foto);
        $stm->execute();

        // ✅ ID DEPORTISTA
        $deportista_id = $conexion->lastInsertId();

        $operacion_id = uniqid("", true);
        $usuario_id = $_SESSION['usuario_id'] ?? null;

        // =========================
        // GUARDAR ACUDIENTE + ENTRENADOR
        // =========================

        $stmt_rel = $conexion->prepare("
        INSERT INTO usuario_deportista(
            deportista_id,
            acudiente,
            parentesco,
            entrenador_id
        )
        VALUES(
            :deportista_id,
            :acudiente,
            :parentesco,
            :entrenador_id
        )
        ");

        $stmt_rel->execute([
            ":deportista_id"=>$deportista_id,
            ":acudiente"=>$acudiente,
            ":parentesco"=>$parentesco,
            ":entrenador_id"=>$entrenador_id
        ]);

        // =========================
        // REGISTRAR AUDITORÍA DE CREACIÓN
        // =========================

        $cambios = [
            "tipo_documento" => [
                "antes"   => null,
                "despues" => $tipo_documento
            ],
            "documento" => [
                "antes"   => null,
                "despues" => $documento
            ],
            "dorsal" => [
                "antes"   => null,
                "despues" => $dorsal
            ],
            "telefono" => [
                "antes"   => null,
                "despues" => $telefono
            ],
            "nombre" => [
                "antes"   => null,
                "despues" => $nombre
            ],
            "fecha_nacimiento" => [
                "antes"   => null,
                "despues" => $fecha_nacimiento
            ],
            "categoria_id" => [
                "antes"   => null,
                "despues" => $categoria_id
            ],
            "entrenador_id" => [
                "antes"   => null,
                "despues" => $entrenador_id
            ],
            "acudiente" => [
                "antes"   => null,
                "despues" => $acudiente
            ],
            "parentesco" => [
                "antes"   => null,
                "despues" => $parentesco
            ]
        ];

        registrarAuditoria(
            $conexion,
            "deportista",
            $deportista_id,
            "CREAR",
            $cambios,
            "Se creó un deportista"
        );

        // =========================
        // SUBIR MULTIPLES DOCUMENTOS
        // =========================

        if(isset($_FILES['documentos'])){
            $carpeta_docs = "../../uploads/documentos/";

            if(!file_exists($carpeta_docs)){
                mkdir($carpeta_docs, 0777, true);
            }

            foreach($_FILES['documentos']['tmp_name'] as $key => $tmp_name){
                if($_FILES['documentos']['error'][$key] == 0){
                    $archivoOriginal = $_FILES['documentos']['name'][$key];
                    $extension = strtolower(pathinfo($archivoOriginal, PATHINFO_EXTENSION));

                    if(
                        $extension == "pdf" ||
                        $extension == "jpg" ||
                        $extension == "jpeg" ||
                        $extension == "png"
                    ){
                        $archivoOriginal = preg_replace(
                            '/[^A-Za-z0-9_\-.]/',
                            '_',
                            $archivoOriginal
                        );

                        $nombreBase = pathinfo(
                            $archivoOriginal,
                            PATHINFO_FILENAME
                        );

                        $nuevoNombre = $archivoOriginal;
                        $rutaFinal = $carpeta_docs . $nuevoNombre;
                        $contador = 1;

                        while(file_exists($rutaFinal)){
                            $nuevoNombre = $nombreBase . "_" . $contador . "." . $extension;
                            $rutaFinal = $carpeta_docs . $nuevoNombre;
                            $contador++;
                        }

                        move_uploaded_file(
                            $tmp_name,
                            $rutaFinal
                        );

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
                            ":deportista_id"=>$deportista_id,
                            ":archivo"=>$nuevoNombre
                        ]);
                    }
                }
            }
        }

        header("Location: index.php?success=1");
        exit;
    }
}
?>

<!-- ✅ MODAL CREAR -->
<div 
    class="modal fade" 
    id="create" 
    tabindex="-1"
    aria-labelledby="createModalLabel"
    aria-hidden="true"
>

    <div class="modal-dialog modal-xl">

        <div class="modal-content shadow">

            <!-- HEADER -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createModalLabel">
                    Crear Deportista
                </h5>
                <button 
                    type="button" 
                    class="btn-close btn-close-white" 
                    data-bs-dismiss="modal"
                    aria-label="Close"
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

                        <!-- DATOS -->
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

                                <!-- DORSAL AUTOLLENADO AUTOMÁTICAMENTE -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        Dorsal / Número de Jugador
                                    </label>
                                    <input 
                                        type="number" 
                                        name="dorsal" 
                                        min="1" 
                                        max="99" 
                                        class="form-control"
                                        value="<?php echo $_POST['dorsal'] ?? $dorsalAutollenado; ?>"
                                    >
                                    <small class="text-muted">
                                        Autollenado con el primer número libre disponible.
                                    </small>
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
                                        Nombres y Apellidos
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
                                        id="fecha_nacimiento"
                                        name="fecha_nacimiento"
                                        class="form-control"
                                        value="<?php echo $_POST['fecha_nacimiento'] ?? ''; ?>"
                                        required
                                    >
                                </div>

                                <!-- CATEGORIA -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        Categoría
                                    </label>
                                    <select
                                        id="categoria_id"
                                        name="categoria_id"
                                        class="form-control"
                                        required
                                    >
                                        <option value="">
                                            Seleccionar categoría
                                        </option>
                                        <?php
                                        $stmt =$conexion->query("
                                        SELECT id, nombre, anio_desde, anio_hasta
                                        FROM categoria
                                        ORDER BY anio_desde DESC
                                        ");

                                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){$selected = "";
                                            if(isset($_POST['categoria_id']) &&$_POST['categoria_id'] == $row['id']){$selected = "selected";
                                            }

                                        echo "
                                        <option 
                                            value='".$row['id']."' 
                                            data-desde='".$row['anio_desde']."'
                                            data-hasta='".$row['anio_hasta']."'
                                            $selected
                                        >
                                            ".$row['nombre']."
                                        </option>
                                        ";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- ENTRENADOR -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        Entrenador
                                    </label>
                                    <select 
                                        id="entrenador_id"
                                        name="entrenador_id"
                                        class="form-control"
                                        required
                                    >
                                        <option value="">
                                            Seleccione entrenador
                                        </option>
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
                                <option value="Papá">Papá</option>
                                <option value="Mamá">Mamá</option>
                                <option value="Acudiente">Acudiente</option>
                            </select>
                        </div>

                        <!-- DOCUMENTOS -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">
                                Adjuntar Documentos
                            </label>
                            <input 
                                type="file"
                                name="documentos[]"
                                class="form-control"
                                accept=".pdf,.jpg,.jpeg,.png"
                                multiple
                            >
                            <small class="text-muted">
                                Puedes subir múltiples archivos PDF, JPG o PNG
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

<script>
document.addEventListener("DOMContentLoaded", function(){

    const modalElement = document.getElementById('create');
    
    // Si hubo un error en la validación POST, abrir el modal mediante el API nativo de Bootstrap
    <?php if(!empty($mensaje_error)): ?>
        if (modalElement && typeof bootstrap !== 'undefined') {
            const modalInstance = new bootstrap.Modal(modalElement);
            modalInstance.show();
        }
    <?php endif; ?>

    const categoria = document.getElementById("categoria_id");
    const entrenador = document.getElementById("entrenador_id");
    const fecha = document.getElementById("fecha_nacimiento");

    function cargarEntrenadores(){
        let categoria_id = categoria.value;

        entrenador.innerHTML = `
            <option value="">
                Seleccione entrenador
            </option>
        `;

        if(categoria_id == ""){
            return;
        }

        fetch("buscar_entrenadores.php?categoria_id=" + categoria_id)
        .then(response => response.json())
        .then(data => {

            if(data.length === 0){
                entrenador.innerHTML = `
                    <option value="">
                        No hay entrenadores asignados
                    </option>
                `;
                return;
            }

            data.forEach(function(ent){
                let option = document.createElement("option");
                option.value = ent.id;
                option.textContent = ent.nombre;
                entrenador.appendChild(option);
            });

            if(data.length === 1){
                entrenador.value = data[0].id;
            }

        })
        .catch(error => {
            console.log("Error cargando entrenadores:", error);
        });
    }

    // Cambio de categoría manual
    categoria.addEventListener("change", cargarEntrenadores);

    // Cambio de fecha nacimiento
    fecha.addEventListener("change", function(){
        let añoNacimiento = new Date(this.value).getFullYear();
        let opciones = categoria.querySelectorAll("option");

        opciones.forEach(function(opcion){
            let desde = parseInt(opcion.dataset.desde);
            let hasta = parseInt(opcion.dataset.hasta);

            if(
                añoNacimiento >= desde &&
                añoNacimiento <= hasta
            ){
                categoria.value = opcion.value;
                cargarEntrenadores();
            }
        });
    });

});
</script>