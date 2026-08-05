<?php

// ======================================================
// FUNCIÓN DE AUDITORÍA
// ======================================================
include("../auditoria/funciones/registrar_auditoria.php");

// ✅ VARIABLES DE ERROR
$error_documento = false;
$error_nombre = false;

// ======================================================
// INICIAR SESIÓN
// Necesario para identificar el usuario que realiza acciones.
// ======================================================
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

include("../../modulos/conexion_modulos.php");

include("crear_deportista.php");

$buscar = trim($_GET["buscar"] ?? "");

// ✅ CONSULTAR DEPORTISTAS
if($buscar != ""){

    $stm = $conexion->prepare("
        SELECT
            d.*,
            c.nombre AS categoria_nombre,
            u.nombre AS entrenador_nombre,
            ud.acudiente AS acudiente_nombre

        FROM deportista d

        LEFT JOIN categoria c
            ON d.categoria_id = c.id

        LEFT JOIN usuario_deportista ud
            ON ud.deportista_id = d.id

        LEFT JOIN usuario u
            ON u.id = ud.entrenador_id

        WHERE
            d.tipo_documento LIKE :buscar
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

    $stm->execute([
        ":buscar" => "%".$buscar."%"
    ]);

}else{

    $stm = $conexion->prepare("
        SELECT
            d.*,
            c.nombre AS categoria_nombre,
            u.nombre AS entrenador_nombre,
            ud.acudiente AS acudiente_nombre

        FROM deportista d

        LEFT JOIN categoria c
            ON d.categoria_id = c.id

        LEFT JOIN usuario_deportista ud
            ON ud.deportista_id = d.id

        LEFT JOIN usuario u
            ON u.id = ud.entrenador_id

        ORDER BY d.id DESC
    ");

    $stm->execute();

}


$deportista = $stm->fetchAll(PDO::FETCH_ASSOC);


/*
=================================================
ELIMINAR DEPORTISTA CON AUDITORÍA
=================================================
*/

if(isset($_GET['id'])){

    $txtid = $_GET['id'];

    /*
    =============================================
    OBTENER DATOS DEL DEPORTISTA
    =============================================
    */

    $stmt = $conexion->prepare("
    SELECT
        d.*,
        ud.acudiente,
        ud.parentesco,
        ud.entrenador_id
    FROM deportista d
    LEFT JOIN usuario_deportista ud
        ON d.id = ud.deportista_id
    WHERE d.id = :id
    ");

    $stmt->execute([
        ":id"=>$txtid
    ]);

    $deportistaEliminar = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
    =============================================
    REGISTRAR AUDITORÍA
    =============================================
    */

    if($deportistaEliminar){

        $cambios = [

            "tipo_documento"=>[
                "antes"=>$deportistaEliminar["tipo_documento"],
                "despues"=>null
            ],

            "documento"=>[
                "antes"=>$deportistaEliminar["documento"],
                "despues"=>null
            ],

            "telefono"=>[
                "antes"=>$deportistaEliminar["telefono"],
                "despues"=>null
            ],

            "nombre"=>[
                "antes"=>$deportistaEliminar["nombre"],
                "despues"=>null
            ],

            "fecha_nacimiento"=>[
                "antes"=>$deportistaEliminar["fecha_nacimiento"],
                "despues"=>null
            ],

            "categoria_id"=>[
                "antes"=>$deportistaEliminar["categoria_id"],
                "despues"=>null
            ],

            "estado"=>[
                "antes"=>$deportistaEliminar["estado"],
                "despues"=>null
            ],

            "acudiente"=>[
                "antes"=>$deportistaEliminar["acudiente"],
                "despues"=>null
            ],

            "parentesco"=>[
                "antes"=>$deportistaEliminar["parentesco"],
                "despues"=>null
            ],

            "entrenador_id"=>[
                "antes"=>$deportistaEliminar["entrenador_id"],
                "despues"=>null
            ]

        ];

        registrarAuditoria(

            $conexion,
            "deportista",
            $txtid,
            "ELIMINAR",
            $cambios,
            "Eliminación de deportista"

        );

    }

/*
=================================================
ELIMINAR DOCUMENTOS DEL DEPORTISTA
=================================================
*/

/*
=============================================
OBTENER DOCUMENTOS
=============================================
*/

$stmtDocs = $conexion->prepare("
SELECT archivo
FROM deportista_documentos
WHERE deportista_id = :id
");

$stmtDocs->execute([
    ":id"=>$txtid
]);

$documentos = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

/*
=============================================
ELIMINAR ARCHIVOS FÍSICOS
=============================================
*/

foreach($documentos as $doc){

    $ruta = "../../uploads/documentos/" . $doc["archivo"];

    if(file_exists($ruta)){

        unlink($ruta);

    }

}

/*
=============================================
ELIMINAR REGISTROS DE LA BD
=============================================
*/

$stmtDocs = $conexion->prepare("
DELETE FROM deportista_documentos
WHERE deportista_id = :id
");

$stmtDocs->execute([
    ":id"=>$txtid
]);

    /*
    =============================================
    ELIMINAR RELACIÓN
    =============================================
    */

    $stmt = $conexion->prepare("
    DELETE FROM usuario_deportista
    WHERE deportista_id = :id
    ");

    $stmt->execute([
        ":id"=>$txtid
    ]);

    /*
    =============================================
    ELIMINAR DEPORTISTA
    =============================================
    */

    $stmt = $conexion->prepare("
    DELETE FROM deportista
    WHERE id = :id
    ");

    $stmt->execute([
        ":id"=>$txtid
    ]);

    echo "
    <script>
        window.location='index.php';
    </script>
    ";

    exit;

}



?>


<?php include("../../template/header_modulos.php") ?>


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

        <a
            href="http://localhost/BFC-dev2/modulos/dashboard/index.php"
            class="btn btn-outline-dark"
        >
            ← Volver al Dashboard
        </a>

        <button
            type="button"
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#create"
        >
            Crear Deportista
        </button>

        <a
            href="importar_deportista.php"
            class="btn btn-success"
        >
            Importar Deportistas
        </a>

    </div>

    <form method="GET" class="d-flex gap-2">

        <input
            type="text"
            name="buscar"
            class="form-control"
            style="width:300px;"
            placeholder="Buscar deportista, documento..."
            value="<?php echo htmlspecialchars($buscar); ?>"
        >

        <button class="btn btn-primary">
            Buscar
        </button>

        <a href="index.php" class="btn btn-secondary">
            Limpiar
        </a>

    </form>

</div>


<table class="table table-bordered table-hover text-center align-middle">

    <thead class="table-dark">

        <tr>

            <th>Tipo de Documento</th>
            <th>Documento</th>
            <th>Teléfono</th>
            <th>Nombre</th>
            <th>Fecha</th>
            <th>Categoría</th>

            <!-- ✅ NUEVA COLUMNA -->
            <th>Entrenador</th>

            <th>Acudiente</th>
            <th>Estado</th>
            <th>Acciones</th>

        </tr>

    </thead>

    <tbody>

    <?php foreach($deportista as $deportista) { ?>

        <tr>

            <td>
                <?php echo $deportista['tipo_documento']; ?>
            </td>

            <td>
                <?php echo $deportista['documento']; ?>
            </td>

            <td>
                <?php echo $deportista['telefono']; ?>
            </td>

            <td>
                <?php echo $deportista['nombre']; ?>
            </td>

            <td>
                <?php echo $deportista['fecha_nacimiento']; ?>
            </td>

            <td>
                <?php echo $deportista['categoria_nombre']; ?>
            </td>

            <!-- ✅ MOSTRAR ENTRENADOR -->
            <td>
                <?php echo $deportista['entrenador_nombre']; ?>
            </td>

            <!-- ✅ ACUDIENTE -->
            <td>
                <?php echo $deportista['acudiente_nombre']; ?>
            </td>

            <!-- ✅ SWITCH ESTADO -->
            <td>

                <div class="form-check form-switch d-flex justify-content-center">

                    <input 
                        class="form-check-input"
                        type="checkbox"

                        <?php 
                        if($deportista['estado'] == 'activo'){
                            echo 'checked';
                        }
                        ?>

                        onclick="cambiarEstado(<?php echo $deportista['id']; ?>)"
                    >

                </div>

            </td>

            <td>

                <div class="d-flex justify-content-center gap-2">

                    <a 
                        href="editar.php?id=<?php echo $deportista['id']; ?>" 
                        class="btn btn-success btn-sm"
                    >
                        Editar
                    </a>

                    <!-- ✅ ELIMINAR -->
                    <a 
                        href="javascript:void(0)"
                        onclick="confirmarEliminacion(<?php echo $deportista['id']; ?>)"
                        class="btn btn-danger btn-sm"
                    >
                        Eliminar
                    </a>

                </div>

            </td>

        </tr>

    <?php } ?>

    </tbody>

</table>


<!-- ✅ SCRIPT ELIMINAR -->

<script>



// =========================
// CAMBIAR ESTADO
// =========================
function cambiarEstado(id){

    fetch("cambiar_estado_deportista.php?id=" + id)
    .then(response => response.text())
    .then(data => {
        console.log("Respuesta:", data);
    })
    .catch(error => {
        console.error("Error:", error);
    });

}


/*
=================================================
CONFIRMAR ELIMINACIÓN
=================================================
*/

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


<?php include("../../template/footer_modulos.php") ?>