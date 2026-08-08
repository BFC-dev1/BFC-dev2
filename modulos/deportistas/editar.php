<?php  

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* =================================================
   INICIAR SESIÓN Y CONEXIÓN
================================================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../../modulos/conexion_modulos.php");
require_once("../../modulos/auditoria/funciones/registrar_auditoria.php");

$mensajeError = "";
$txtid = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

/* =================================================
   ELIMINAR DOCUMENTO
================================================= */
if (isset($_GET['eliminar_doc'])) {
    $doc_id = (int)$_GET['eliminar_doc'];

    $stmtDoc = $conexion->prepare("SELECT archivo FROM deportista_documentos WHERE id = :id");
    $stmtDoc->execute([":id" => $doc_id]);
    $doc = $stmtDoc->fetch(PDO::FETCH_ASSOC);

    if ($doc) {
        $ruta = "../../uploads/documentos/" . $doc['archivo'];
        if (file_exists($ruta)) {
            unlink($ruta);
        }

        $stmtDelete = $conexion->prepare("DELETE FROM deportista_documentos WHERE id = :id");
        $stmtDelete->execute([":id" => $doc_id]);
    }

    header("Location: editar.php?id=" . $txtid);
    exit;
}

/* =================================================
   PROCESAR FORMULARIO (POST)
================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tipo_documento   = $_POST['tipo_documento'] ?? "";
    $documento        = trim($_POST['documento'] ?? "");
    $telefono         = $_POST['telefono'] ?? "";
    $nombre           = trim($_POST['nombre'] ?? "");
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? "";
    $categoria_id     = $_POST['categoria_id'] ?? "";
    $estado           = $_POST['estado'] ?? "activo";
    $acudiente        = trim($_POST['acudiente'] ?? "");
    $parentesco       = $_POST['parentesco'] ?? "";
    $entrenador_id    = !empty($_POST['entrenador_id']) ? $_POST['entrenador_id'] : null;

    // VALIDACIONES BASICAS
    if (empty($acudiente)) {
        $mensajeError = "Ingresa un acudiente.";
    } elseif (empty($categoria_id)) {
        $mensajeError = "Selecciona una categoría.";
    } else {
        // VALIDAR SI EL DOCUMENTO YA EXISTE EN OTRO DEPORTISTA
        $stmt_check = $conexion->prepare("SELECT id FROM deportista WHERE documento = :documento AND id != :id");
        $stmt_check->execute([":documento" => $documento, ":id" => $txtid]);

        if ($stmt_check->fetch()) {
            $mensajeError = "Este número de documento ya está registrado en otro deportista.";
        }
    }

    if (empty($mensajeError)) {

        // OBTENER FOTO ACTUAL
        $stmt_actual = $conexion->prepare("SELECT foto FROM deportista WHERE id = :id");
        $stmt_actual->execute([":id" => $txtid]);
        $actual = $stmt_actual->fetch(PDO::FETCH_ASSOC);
        $foto = $actual['foto'] ?? "";

        // SUBIR NUEVA FOTO (CON SANITIZACIÓN DE NOMBRE)
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $extFoto = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $extPermitidasFoto = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($extFoto, $extPermitidasFoto)) {
                $nombreFoto = time() . "_" . uniqid() . "." . $extFoto;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], "../../uploads/fotos/" . $nombreFoto)) {
                    // Borrar foto anterior si no era por defecto
                    if (!empty($foto) && file_exists("../../uploads/fotos/" . $foto)) {
                        unlink("../../uploads/fotos/" . $foto);
                    }
                    $foto = $nombreFoto;
                }
            }
        }

        // DATOS ANTERIORES PARA AUDITORÍA
        $stmtAnterior = $conexion->prepare("
            SELECT d.*, ud.acudiente, ud.parentesco, ud.entrenador_id
            FROM deportista d
            LEFT JOIN usuario_deportista ud ON d.id = ud.deportista_id
            WHERE d.id = :id
        ");
        $stmtAnterior->execute([":id" => $txtid]);
        $anterior = $stmtAnterior->fetch(PDO::FETCH_ASSOC);

        // ACTUALIZAR TABLA DEPORTISTA
        $stm = $conexion->prepare("
            UPDATE deportista SET 
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
            ":tipo_documento"   => $tipo_documento,
            ":documento"        => $documento,
            ":telefono"         => $telefono,
            ":nombre"           => $nombre,
            ":fecha_nacimiento" => $fecha_nacimiento,
            ":categoria_id"     => $categoria_id,
            ":estado"           => $estado,
            ":foto"             => $foto,
            ":id"               => $txtid
        ]);

        // ACTUALIZAR TABLA USUARIO_DEPORTISTA
        $stmt_rel = $conexion->prepare("
            UPDATE usuario_deportista SET 
                acudiente = :acudiente,
                parentesco = :parentesco,
                entrenador_id = :entrenador_id
            WHERE deportista_id = :deportista_id
        ");
        $stmt_rel->bindValue(":acudiente", $acudiente);
        $stmt_rel->bindValue(":parentesco", $parentesco);
        $stmt_rel->bindValue(":entrenador_id", $entrenador_id, $entrenador_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt_rel->bindValue(":deportista_id", $txtid, PDO::PARAM_INT);
        $stmt_rel->execute();

        // AUDITORÍA DE CAMBIOS
        $campos = [
            "tipo_documento"   => $tipo_documento,
            "documento"        => $documento,
            "telefono"         => $telefono,
            "nombre"           => $nombre,
            "fecha_nacimiento" => $fecha_nacimiento,
            "categoria_id"     => $categoria_id,
            "estado"           => $estado,
            "acudiente"        => $acudiente,
            "parentesco"       => $parentesco,
            "entrenador_id"    => $entrenador_id
        ];

        $cambios = [];
        foreach ($campos as $campo => $nuevo) {
            $anteriorValor = $anterior[$campo] ?? null;
            if ((string)$anteriorValor !== (string)$nuevo) {
                $cambios[$campo] = [
                    "antes"   => $anteriorValor,
                    "despues" => $nuevo
                ];
            }
        }

        if (!empty($cambios)) {
            registrarAuditoria($conexion, "deportista", $txtid, "EDITAR", $cambios, "Edición de deportista");
        }

        // SUBIR MÚLTIPLES ARCHIVOS
        if (isset($_FILES['documentos']) && !empty($_FILES['documentos']['name'][0])) {
            $carpetaDocs = "../../uploads/documentos/";
            if (!file_exists($carpetaDocs)) {
                mkdir($carpetaDocs, 0755, true);
            }

            foreach ($_FILES['documentos']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['documentos']['error'][$key] == 0) {
                    $archivoOriginal = $_FILES['documentos']['name'][$key];
                    $extension = strtolower(pathinfo($archivoOriginal, PATHINFO_EXTENSION));

                    if (in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'])) {
                        $nombreBase = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($archivoOriginal, PATHINFO_FILENAME));
                        $nuevoNombre = $nombreBase . "." . $extension;
                        $rutaFinal = $carpetaDocs . $nuevoNombre;

                        $contador = 1;
                        while (file_exists($rutaFinal)) {
                            $nuevoNombre = $nombreBase . "_" . $contador . "." . $extension;
                            $rutaFinal = $carpetaDocs . $nuevoNombre;
                            $contador++;
                        }

                        if (move_uploaded_file($tmp_name, $rutaFinal)) {
                            $stmtInsert = $conexion->prepare("
                                INSERT INTO deportista_documentos (deportista_id, archivo) 
                                VALUES (:deportista_id, :archivo)
                            ");
                            $stmtInsert->execute([
                                ":deportista_id" => $txtid,
                                ":archivo"       => $nuevoNombre
                            ]);
                        }
                    }
                }
            }
        }

        header("Location: index.php?actualizado=1");
        exit;
    }
}

/* =================================================
   OBTENER DATOS PARA FORMULARIO (GET)
================================================= */
if ($txtid > 0) {
    $stm = $conexion->prepare("
        SELECT d.*, ud.acudiente, ud.parentesco, ud.entrenador_id
        FROM deportista d
        LEFT JOIN usuario_deportista ud ON d.id = ud.deportista_id
        WHERE d.id = :id
    ");
    $stm->execute([":id" => $txtid]);
    $registro = $stm->fetch(PDO::FETCH_ASSOC);

    if ($registro) {
        $tipo_documento   = $registro['tipo_documento'];
        $documento        = $registro['documento'];
        $telefono         = $registro['telefono'];
        $nombre           = $registro['nombre'];
        $fecha_nacimiento = $registro['fecha_nacimiento'];
        $categoria_id     = $registro['categoria_id'];
        $estado           = $registro['estado'];
        $foto             = $registro['foto'] ?? "";
        $acudiente        = $registro['acudiente'] ?? "";
        $parentesco       = $registro['parentesco'] ?? "";
        $entrenador_id    = $registro['entrenador_id'] ?? "";
    }
}
?>

<?php include("../../template/header_modulos.php") ?>

<div class="container mt-4 mb-5">
    <div class="card shadow border-0 rounded-4">
        <div class="card-header bg-primary text-white rounded-top-4">
            <h4 class="mb-0">⚽ Editar Deportista</h4>
        </div>

        <div class="card-body p-4">

            <?php if (!empty($mensajeError)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error:</strong> <?php echo htmlspecialchars($mensajeError); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($txtid); ?>">

                <div class="row">
                    <!-- FOTO -->
                    <div class="col-md-12 text-center mb-4">
                        <label for="fotoInput" style="cursor:pointer; color:#0d6efd; font-weight:bold; display:block; margin-bottom:10px;">
                            Editar Foto
                        </label>
                        
                        <img src="<?php echo !empty($foto) ? '../../uploads/fotos/' . htmlspecialchars($foto) : 'https://via.placeholder.com/150'; ?>" 
                             id="imgPreview"
                             width="150" height="150" 
                             class="rounded-circle shadow" 
                             style="object-fit:cover;">

                        <input type="file" id="fotoInput" name="foto" accept="image/*" style="display:none;" onchange="previewImage(event)">
                    </div>

                    <!-- ADJUNTAR DOCUMENTOS -->
                    <div class="col-md-12 mb-4">
                        <label class="form-label fw-bold">Adjuntar Documentos</label>
                        <input type="file" name="documentos[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png" multiple>
                    </div>

                    <!-- LISTA DE DOCUMENTOS ADJUNTOS -->
                    <div class="col-md-12 mb-4">
                        <div class="border rounded p-3 bg-light">
                            <h5 class="fw-bold mb-3">📁 Documentos Adjuntos</h5>
                            <?php
                            $stmtDocs = $conexion->prepare("SELECT * FROM deportista_documentos WHERE deportista_id = :id ORDER BY id DESC");
                            $stmtDocs->execute([":id" => $txtid]);
                            $documentos = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);
                            ?>

                            <?php if (count($documentos) > 0): ?>
                                <?php foreach ($documentos as $doc): ?>
                                    <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2 bg-white">
                                        <div>
                                            <span class="fw-bold text-danger">📄 Archivo</span><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($doc['archivo']); ?></small>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="../../uploads/documentos/<?php echo htmlspecialchars($doc['archivo']); ?>" target="_blank" class="btn btn-primary btn-sm">👁 Ver</a>
                                            <a href="../../uploads/documentos/<?php echo htmlspecialchars($doc['archivo']); ?>" download class="btn btn-success btn-sm">⬇ Descargar</a>
                                            <a href="?id=<?php echo $txtid; ?>&eliminar_doc=<?php echo $doc['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar documento?')">🗑 Eliminar</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">No hay documentos adjuntos.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- CAMPOS DEL FORMULARIO -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tipo Documento</label>
                        <input type="text" class="form-control" name="tipo_documento" value="<?php echo htmlspecialchars($tipo_documento ?? ''); ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Documento</label>
                        <input type="text" class="form-control" name="documento" value="<?php echo htmlspecialchars($documento ?? ''); ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" class="form-control" name="telefono" value="<?php echo htmlspecialchars($telefono ?? ''); ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($nombre ?? ''); ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fecha de nacimiento</label>
                        <input type="date" id="fecha_nacimiento" class="form-control" name="fecha_nacimiento" value="<?php echo htmlspecialchars($fecha_nacimiento ?? ''); ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Categoría</label>
                        <select id="categoria_id" name="categoria_id" class="form-control" required>
                            <option value="">Seleccionar categoría</option>
                            <?php
                            $stmtCat = $conexion->query("SELECT id, nombre, anio_desde, anio_hasta FROM categoria ORDER BY anio_desde DESC");
                            while ($row = $stmtCat->fetch(PDO::FETCH_ASSOC)) {
                                $selected = ($row['id'] == $categoria_id) ? "selected" : "";
                                echo "<option value='{$row['id']}' data-desde='{$row['anio_desde']}' data-hasta='{$row['anio_hasta']}' $selected>" . htmlspecialchars($row['nombre']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Entrenador</label>
                        <select id="entrenador_id" name="entrenador_id" class="form-control">
                            <option value="">Seleccione entrenador</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Acudiente</label>
                        <input type="text" name="acudiente" class="form-control" value="<?php echo htmlspecialchars($acudiente ?? ''); ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Parentesco</label>
                        <select name="parentesco" class="form-control" required>
                            <option value="">Seleccione parentesco</option>
                            <option value="Papá" <?php echo ($parentesco == "Papá") ? "selected" : ""; ?>>Papá</option>
                            <option value="Mamá" <?php echo ($parentesco == "Mamá") ? "selected" : ""; ?>>Mamá</option>
                            <option value="Acudiente" <?php echo ($parentesco == "Acudiente") ? "selected" : ""; ?>>Acudiente</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-control" required>
                            <option value="activo" <?php echo ($estado == "activo") ? "selected" : ""; ?>>Activo</option>
                            <option value="inactivo" <?php echo ($estado == "inactivo") ? "selected" : ""; ?>>Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <a href="index.php" class="btn btn-danger">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar Deportista</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Previsualizar la foto antes de subirla
function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function(){
        const output = document.getElementById('imgPreview');
        output.src = reader.result;
    };
    if(event.target.files[0]){
        reader.readAsDataURL(event.target.files[0]);
    }
}

document.addEventListener("DOMContentLoaded", function() {
    const categoria = document.getElementById("categoria_id");
    const entrenador = document.getElementById("entrenador_id");
    const fecha = document.getElementById("fecha_nacimiento");
    let entrenadorActual = "<?php echo $entrenador_id; ?>";

    function cargarEntrenadores() {
        let categoria_id = categoria.value;
        entrenador.innerHTML = '<option value="">Seleccione entrenador</option>';

        if (!categoria_id) return;

        fetch("buscar_entrenadores.php?categoria_id=" + categoria_id)
            .then(response => response.json())
            .then(data => {
                data.forEach(function(ent) {
                    let option = document.createElement("option");
                    option.value = ent.id;
                    option.textContent = ent.nombre;
                    if (ent.id == entrenadorActual) {
                        option.selected = true;
                    }
                    entrenador.appendChild(option);
                });

                if (data.length === 1) {
                    entrenador.value = data[0].id;
                }
            })
            .catch(err => console.error("Error al cargar entrenadores:", err));
    }

    categoria.addEventListener("change", function() {
        entrenadorActual = "";
        cargarEntrenadores();
    });

    fecha.addEventListener("change", function() {
        if (!this.value) return;

        // Extraer el año directamente del string 'YYYY-MM-DD' evita problemas de timezone en JS
        let anioNacimiento = parseInt(this.value.split('-')[0], 10);
        let opciones = categoria.querySelectorAll("option");

        opciones.forEach(function(opcion) {
            let desde = parseInt(opcion.dataset.desde, 10);
            let hasta = parseInt(opcion.dataset.hasta, 10);

            if (anioNacimiento >= desde && anioNacimiento <= hasta) {
                categoria.value = opcion.value;
                entrenadorActual = "";
                cargarEntrenadores();
            }
        });
    });

    // Cargar entrenadores al abrir
    cargarEntrenadores();
});
</script>

<?php include("../../template/footer_modulos.php") ?>