<?php
/*
=================================================
VERIFICAR PERMISOS DEL MÓDULO DEPORTISTAS
=================================================
*/


require_once("../../includes/config.php");
require_once("../../includes/verificar_roles.php");

// Permite la entrada si tiene permiso de gestión completa O de solo lectura
if (!tiene_permiso('deportistas') && !tiene_permiso('ver_deportistas')) {
    header("Location: " . $url_base . "/index.php");
    exit;
}

include("../auditoria/funciones/registrar_auditoria.php");

$error_documento = false;
$error_nombre = false;

include("../../modulos/conexion_modulos.php");


/*
=================================================
ELIMINAR DEPORTISTA CON AUDITORÍA
(Protección del Backend: Solo si tiene 'deportistas')
=================================================
*/
if(isset($_GET['id'])){

    if (!tiene_permiso('deportistas')) {
        header("Location: index.php");
        exit;
    }

    $txtid = (int) $_GET['id'];

    $stmt = $conexion->prepare("
        SELECT d.*, ud.acudiente, ud.parentesco, ud.entrenador_id
        FROM deportista d
        LEFT JOIN usuario_deportista ud ON d.id = ud.deportista_id
        WHERE d.id = :id
    ");
    $stmt->execute([":id" => $txtid]);
    $deportistaEliminar = $stmt->fetch(PDO::FETCH_ASSOC);

    if($deportistaEliminar){
        $cambios = [
            "tipo_documento"  => ["antes" => $deportistaEliminar["tipo_documento"], "despues" => null],
            "documento"       => ["antes" => $deportistaEliminar["documento"],      "despues" => null],
            "telefono"        => ["antes" => $deportistaEliminar["telefono"],       "despues" => null],
            "nombre"          => ["antes" => $deportistaEliminar["nombre"],         "despues" => null],
            "fecha_nacimiento"=> ["antes" => $deportistaEliminar["fecha_nacimiento"],"despues" => null],
            "categoria_id"    => ["antes" => $deportistaEliminar["categoria_id"],   "despues" => null],
            "estado"          => ["antes" => $deportistaEliminar["estado"],         "despues" => null],
            "acudiente"       => ["antes" => $deportistaEliminar["acudiente"],      "despues" => null],
            "parentesco"      => ["antes" => $deportistaEliminar["parentesco"],     "despues" => null],
            "entrenador_id"   => ["antes" => $deportistaEliminar["entrenador_id"],  "despues" => null]
        ];

        registrarAuditoria($conexion, "deportista", $txtid, "ELIMINAR", $cambios, "Eliminación de deportista");
    }

    // ELIMINAR ARCHIVOS FÍSICOS
    $stmtDocs = $conexion->prepare("SELECT archivo FROM deportista_documentos WHERE deportista_id = :id");
    $stmtDocs->execute([":id" => $txtid]);
    $documentos = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

    foreach($documentos as $doc){
        $ruta = "../../uploads/documentos/" . $doc["archivo"];
        if(file_exists($ruta)){ unlink($ruta); }
    }

    // ELIMINAR REGISTROS DE BD
    $conexion->prepare("DELETE FROM deportista_documentos WHERE deportista_id = :id")->execute([":id" => $txtid]);
    $conexion->prepare("DELETE FROM usuario_deportista WHERE deportista_id = :id")->execute([":id" => $txtid]);
    $conexion->prepare("DELETE FROM deportista WHERE id = :id")->execute([":id" => $txtid]);

    echo "<script>window.location='index.php';</script>";
    exit;
}



$buscar = trim($_GET["buscar"] ?? "");

// CONSULTAR DEPORTISTAS
if($buscar != ""){
    $stm = $conexion->prepare("
        SELECT d.*, c.nombre AS categoria_nombre, u.nombre AS entrenador_nombre, ud.acudiente AS acudiente_nombre
        FROM deportista d
        LEFT JOIN categoria c ON d.categoria_id = c.id
        LEFT JOIN usuario_deportista ud ON ud.deportista_id = d.id
        LEFT JOIN usuario u ON u.id = ud.entrenador_id
        WHERE d.tipo_documento LIKE :buscar
           OR d.documento LIKE :buscar
           OR d.telefono LIKE :buscar
           OR d.nombre LIKE :buscar
           OR d.fecha_nacimiento LIKE :buscar
           OR c.nombre LIKE :buscar
           OR u.nombre LIKE :buscar
           OR ud.acudiente LIKE :buscar
           OR d.estado LIKE :buscar
        ORDER BY d.id DESC
    ");
    $stm->execute([":buscar" => "%".$buscar."%"]);
} else {
    $stm = $conexion->prepare("
        SELECT d.*, c.nombre AS categoria_nombre, u.nombre AS entrenador_nombre, ud.acudiente AS acudiente_nombre
        FROM deportista d
        LEFT JOIN categoria c ON d.categoria_id = c.id
        LEFT JOIN usuario_deportista ud ON ud.deportista_id = d.id
        LEFT JOIN usuario u ON u.id = ud.entrenador_id
        ORDER BY d.id DESC
    ");
    $stm->execute();
}

$deportista = $stm->fetchAll(PDO::FETCH_ASSOC);



/*
=========================================================
CONFIGURACIÓN DEL MÓDULO DEPORTISTAS
=========================================================
*/

/*
Nombre principal que mostrará el header.
*/
$modulo_actual = 'Deportistas';

/*
Deportistas no tiene actualmente un submódulo.
*/
$submodulo_actual = '';

/*
No utilizamos menú adicional en el navbar por ahora.
*/
$menu_modulo = [];


/*
=================================================
CARGA DEL HEADER ANTES DEL CONTENIDO VISUAL
=================================================

IMPORTANTE:

header_modulos.php debe cargarse antes de cualquier
archivo que genere HTML visible.

Si crear_deportista.php se carga antes del header,
el navegador puede mostrar temporalmente el contenido
sin los estilos CSS, provocando un parpadeo visual
("flash") durante la carga de la página.

Por esta razón se mantiene el siguiente orden:

    header_modulos.php
            ↓
    crear_deportista.php
            ↓
    resto del contenido visual

Toda la lógica PHP y procesamiento de datos debe
realizarse antes de llegar a este punto.

=================================================
*/

include("../../template/header_modulos.php");


/*
=================================================
FORMULARIO DE CREACIÓN DE DEPORTISTA
=================================================
*/

if (tiene_permiso('deportistas')) {
    include("crear_deportista.php");
}

?>

<?php if(isset($_GET['actualizado'])){ ?>
<script>
Swal.fire({
    icon: "success",
    title: "Operación Exitosa",
    text: "Los datos fueron actualizados correctamente.",
    confirmButtonText: "Aceptar"
});
</script>
<?php } ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex gap-2">
        <a href="<?= $url_base ?>/modulos/dashboard/index.php" class="btn btn-outline-dark">
            ← Volver al Dashboard
        </a>

        <!-- BOTONES VISIBLES SOLO PARA 'deportistas' -->
        <?php if (tiene_permiso('deportistas')): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#create">
                Crear Deportista
            </button>
            <a href="importar_deportista.php" class="btn btn-success">
                Importar Deportistas
            </a>
        <?php endif; ?>
    </div>

    <form method="GET" class="d-flex gap-2">
        <input type="text" name="buscar" class="form-control" style="width:300px;" placeholder="Buscar deportista, documento..." value="<?php echo htmlspecialchars($buscar); ?>">
        <button class="btn btn-primary">Buscar</button>
        <a href="index.php" class="btn btn-secondary">Limpiar</a>
    </form>
</div>

<table class="table table-bordered table-hover text-center align-middle">
    <thead class="table-dark">
        <tr>
            <th>Tipo Doc.</th>
            <th>Documento</th>
            <th>Teléfono</th>
            <th>Nombre</th>
            <th>Fecha Nac.</th>
            <th>Categoría</th>
            <th>Entrenador</th>
            <th>Acudiente</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($deportista as $deportista_item) { ?>
        <tr>
            <td><?php echo $deportista_item['tipo_documento']; ?></td>
            <td><?php echo $deportista_item['documento']; ?></td>
            <td><?php echo $deportista_item['telefono']; ?></td>
            <td><?php echo $deportista_item['nombre']; ?></td>
            <td><?php echo $deportista_item['fecha_nacimiento']; ?></td>
            <td><?php echo $deportista_item['categoria_nombre']; ?></td>
            <td><?php echo $deportista_item['entrenador_nombre']; ?></td>
            <td><?php echo $deportista_item['acudiente_nombre']; ?></td>

            <!-- SWITCH DE ESTADO -->
            <td>
                <div class="form-check form-switch d-flex justify-content-center">
                    <input class="form-check-input" type="checkbox"
                        <?php echo ($deportista_item['estado'] == 'activo') ? 'checked' : ''; ?>
                        <?php if (tiene_permiso('deportistas')): ?>
                            onclick="cambiarEstado(<?php echo $deportista_item['id']; ?>)"
                        <?php else: ?>
                            disabled
                        <?php endif; ?>
                    >
                </div>
            </td>

            <!-- COLUMNA DE ACCIONES -->
            <td>
                <?php if (tiene_permiso('deportistas')): ?>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="editar.php?id=<?php echo $deportista_item['id']; ?>" class="btn btn-success btn-sm">Editar</a>
                        <a href="javascript:void(0)" onclick="confirmarEliminacion(<?php echo $deportista_item['id']; ?>)" class="btn btn-danger btn-sm">Eliminar</a>
                    </div>
                <?php else: ?>
                    <span class="badge bg-secondary">Solo lectura</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>

<script>
function cambiarEstado(id){
    fetch("cambiar_estado_deportista.php?id=" + id)
    .then(response => response.text())
    .then(data => console.log("Respuesta:", data))
    .catch(error => console.error("Error:", error));
}

function confirmarEliminacion(id){
    Swal.fire({
        title: "¿Eliminar deportista?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar"
    }).then((result)=>{
        if(result.isConfirmed){
            window.location = "index.php?id=" + id;
        }
    });
}
</script>

<?php include("../../template/footer_modulos.php"); ?>