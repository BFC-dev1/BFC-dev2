<?php
/*
=========================================================
MÓDULO DEPORTISTAS - BELLAVISTA FC
=========================================================

Este archivo:

    1. Verifica permisos del usuario.
    2. Consulta los deportistas.
    3. Permite eliminar deportistas.
    4. Registra eliminaciones en auditoría.
    5. Muestra el listado de deportistas.
    6. Permite buscar deportistas.
    7. Permite cambiar el estado activo/inactivo.
    8. Permite editar y eliminar deportistas.
    9. Permite crear e importar deportistas.
   10. Adapta la tabla automáticamente para celulares/APK.

=========================================================
DISEÑO RESPONSIVE
=========================================================
*/


/*
=========================================================
1. VERIFICAR PERMISOS DEL MÓDULO DEPORTISTAS
=========================================================
*/

require_once("../../includes/config.php");
require_once("../../includes/verificar_roles.php");

if (!tiene_permiso('deportistas') && !tiene_permiso('ver_deportistas')) {
    header("Location: " . $url_base . "/index.php");
    exit;
}


/*
=========================================================
2. CARGAR FUNCIONES DE AUDITORÍA
=========================================================
*/

include("../auditoria/funciones/registrar_auditoria.php");


/*
=========================================================
3. VARIABLES DE CONTROL
=========================================================
*/

$error_documento = false;
$error_nombre = false;


/*
=========================================================
4. CONEXIÓN A LA BASE DE DATOS
=========================================================
*/

include("../../modulos/conexion_modulos.php");


/*
=========================================================
5. ELIMINAR DEPORTISTA
=========================================================
*/

if (isset($_GET['id'])) {

    if (!tiene_permiso('deportistas')) {
        header("Location: index.php");
        exit;
    }

    $txtid = (int) $_GET['id'];

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
        ":id" => $txtid
    ]);

    $deportistaEliminar = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($deportistaEliminar) {

        $cambios = [
            "tipo_documento" => [
                "antes" => $deportistaEliminar["tipo_documento"],
                "despues" => null
            ],
            "documento" => [
                "antes" => $deportistaEliminar["documento"],
                "despues" => null
            ],
            "dorsal" => [
                "antes" => $deportistaEliminar["dorsal"] ?? null,
                "despues" => null
            ],
            "telefono" => [
                "antes" => $deportistaEliminar["telefono"],
                "despues" => null
            ],
            "nombre" => [
                "antes" => $deportistaEliminar["nombre"],
                "despues" => null
            ],
            "fecha_nacimiento" => [
                "antes" => $deportistaEliminar["fecha_nacimiento"],
                "despues" => null
            ],
            "categoria_id" => [
                "antes" => $deportistaEliminar["categoria_id"],
                "despues" => null
            ],
            "estado" => [
                "antes" => $deportistaEliminar["estado"],
                "despues" => null
            ],
            "acudiente" => [
                "antes" => $deportistaEliminar["acudiente"],
                "despues" => null
            ],
            "parentesco" => [
                "antes" => $deportistaEliminar["parentesco"],
                "despues" => null
            ],
            "entrenador_id" => [
                "antes" => $deportistaEliminar["entrenador_id"],
                "despues" => null
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

    $stmtDocs = $conexion->prepare("
        SELECT archivo
        FROM deportista_documentos
        WHERE deportista_id = :id
    ");

    $stmtDocs->execute([
        ":id" => $txtid
    ]);

    $documentos = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

    foreach ($documentos as $doc) {
        $ruta = "../../uploads/documentos/" . $doc["archivo"];
        if (file_exists($ruta)) {
            unlink($ruta);
        }
    }

    $conexion->prepare("
        DELETE FROM deportista_documentos
        WHERE deportista_id = :id
    ")->execute([
        ":id" => $txtid
    ]);

    $conexion->prepare("
        DELETE FROM usuario_deportista
        WHERE deportista_id = :id
    ")->execute([
        ":id" => $txtid
    ]);

    $conexion->prepare("
        DELETE FROM deportista
        WHERE id = :id
    ")->execute([
        ":id" => $txtid
    ]);

    echo "<script>window.location='index.php';</script>";
    exit;
}


/*
=========================================================
6. BUSCADOR
=========================================================
*/

$buscar = trim($_GET["buscar"] ?? "");


/*
=========================================================
7. CONSULTAR DEPORTISTAS
=========================================================
*/

if ($buscar != "") {

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
        WHERE d.tipo_documento LIKE :buscar
           OR d.documento LIKE :buscar
           OR d.dorsal LIKE :buscar
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
        ":buscar" => "%" . $buscar . "%"
    ]);

} else {

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
=========================================================
8. CONFIGURACIÓN DEL MÓDULO
=========================================================
*/

$modulo_actual = 'Deportistas';
$submodulo_actual = '';
$menu_modulo = [];


/*
=========================================================
9. CARGAR HEADER GENERAL
=========================================================
*/

include("../../template/header_modulos.php");


/*
=========================================================
10. FORMULARIO DE CREACIÓN
=========================================================
*/

if (tiene_permiso('deportistas')) {
    include("crear_deportista.php");
}

?>


<!-- =====================================================
     MENSAJE DE CREACIÓN / OPERACIÓN EXITOSA
====================================================== -->

<?php if ((isset($_GET['creado']) || isset($_GET['success'])) && empty($mensaje_error) && !$_POST) { ?>

<script>
Swal.fire({
    icon: "success",
    title: "Operación Exitosa",
    text: "El deportista fue registrado correctamente.",
    confirmButtonText: "Aceptar"
});

// Limpiar la URL para evitar relanzar el modal en futuras acciones
if (window.history.replaceState) {
    const urlLimpia = window.location.protocol + "//" + window.location.host + window.location.pathname;
    window.history.replaceState({path: urlLimpia}, '', urlLimpia);
}
</script>

<?php } ?>


<!-- =====================================================
     MENSAJE DE ACTUALIZACIÓN EXITOSA
====================================================== -->

<?php if (isset($_GET['actualizado']) && empty($mensaje_error) && !$_POST) { ?>

<script>
Swal.fire({
    icon: "success",
    title: "Operación Exitosa",
    text: "Los datos fueron actualizados correctamente.",
    confirmButtonText: "Aceptar"
});

// Limpiar la URL para evitar relanzar el modal en futuras acciones
if (window.history.replaceState) {
    const urlLimpia = window.location.protocol + "//" + window.location.host + window.location.pathname;
    window.history.replaceState({path: urlLimpia}, '', urlLimpia);
}
</script>

<?php } ?>


<!-- =====================================================
     CONTENEDOR SUPERIOR DEL MÓDULO
====================================================== -->

<div class="deportistas-toolbar">

    <div class="deportistas-acciones">

        <a
            href="<?= $url_base ?>/modulos/dashboard/index.php"
            class="btn btn-outline-dark"
        >
            ← Volver al Dashboard
        </a>

        <?php if (tiene_permiso('deportistas')): ?>

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

        <?php endif; ?>

    </div>

    <form
        method="GET"
        class="deportistas-busqueda"
    >

        <input
            type="text"
            name="buscar"
            class="form-control deportistas-input-busqueda"
            placeholder="Buscar por dorsal, deportista, doc..."
            value="<?php echo htmlspecialchars($buscar); ?>"
        >

        <button
            type="submit"
            class="btn btn-primary"
        >
            Buscar
        </button>

        <a
            href="index.php"
            class="btn btn-secondary"
        >
            Limpiar
        </a>

    </form>

</div>


<!-- =====================================================
     LISTADO DE DEPORTISTAS
====================================================== -->

<div class="tabla-deportistas-contenedor">

<table
    class="table table-bordered table-hover text-center align-middle tabla-deportistas"
>

    <!-- =================================================
         CABECERA DE LA TABLA CON DORSAL
    ================================================== -->

    <thead class="table-dark">
        <tr>
            <th>Dorsal</th>
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

    <!-- =================================================
         CUERPO DE LA TABLA
    ================================================== -->

    <tbody>

    <?php foreach ($deportista as $deportista_item) { ?>

        <tr>

            <!-- DORSAL / NÚMERO DE JUGADOR -->
            <td data-label="Dorsal">
                <span class="dorsal-badge">
                    #<?php echo htmlspecialchars($deportista_item['dorsal'] ?? 'S/N'); ?>
                </span>
            </td>

            <td data-label="Tipo Doc.">
                <?php
                echo htmlspecialchars(
                    $deportista_item['tipo_documento'] ?? ''
                );
                ?>
            </td>

            <td data-label="Documento">
                <?php
                echo htmlspecialchars(
                    $deportista_item['documento'] ?? ''
                );
                ?>
            </td>

            <td data-label="Teléfono">
                <?php
                echo htmlspecialchars(
                    $deportista_item['telefono'] ?? ''
                );
                ?>
            </td>

            <td
                data-label="Nombre"
                class="deportista-nombre"
            >
                <?php
                echo htmlspecialchars(
                    $deportista_item['nombre'] ?? ''
                );
                ?>
            </td>

            <td data-label="Fecha Nac.">
                <?php
                echo htmlspecialchars(
                    $deportista_item['fecha_nacimiento'] ?? ''
                );
                ?>
            </td>

            <td data-label="Categoría">
                <?php
                echo htmlspecialchars(
                    $deportista_item['categoria_nombre'] ?? 'Sin categoría'
                );
                ?>
            </td>

            <td data-label="Entrenador">
                <?php
                echo htmlspecialchars(
                    $deportista_item['entrenador_nombre'] ?? 'Sin entrenador'
                );
                ?>
            </td>

            <td data-label="Acudiente">
                <?php
                echo htmlspecialchars(
                    $deportista_item['acudiente_nombre'] ?? 'Sin acudiente'
                );
                ?>
            </td>

            <td
                data-label="Estado"
                class="celda-estado"
            >
                <div
                    class="form-check form-switch d-flex justify-content-center"
                >
                    <input
                        class="form-check-input"
                        type="checkbox"
                        <?php
                        echo (
                            $deportista_item['estado'] == 'activo'
                        )
                        ? 'checked'
                        : '';
                        ?>
                        <?php if (tiene_permiso('deportistas')): ?>
                            onclick="cambiarEstado(
                                <?php echo (int)$deportista_item['id']; ?>,
                                this
                            )"
                        <?php else: ?>
                            disabled
                        <?php endif; ?>
                    >
                </div>
            </td>

            <td
                data-label="Acciones"
                class="celda-acciones"
            >
                <?php if (tiene_permiso('deportistas')): ?>

                    <div class="deportista-botones">

                        <a
                            href="editar.php?id=<?php echo (int)$deportista_item['id']; ?>"
                            class="btn btn-success btn-sm"
                        >
                            Editar
                        </a>

                        <a
                            href="javascript:void(0)"
                            onclick="confirmarEliminacion(
                                <?php
                                echo (int)$deportista_item['id'];
                                ?>
                            )"
                            class="btn btn-danger btn-sm"
                        >
                            Eliminar
                        </a>

                    </div>

                <?php else: ?>

                    <span class="badge bg-secondary">
                        Solo lectura
                    </span>

                <?php endif; ?>
            </td>

        </tr>

    <?php } ?>

    </tbody>

</table>

</div>


<!-- =====================================================
     JAVASCRIPT DEL MÓDULO
====================================================== -->

<script>
function cambiarEstado(id, checkbox) {
    fetch("cambiar_estado_deportista.php?id=" + id)
    .then(response => response.json())
    .then(data => {
        if (data.status === "error") {
            // Revertir el switch a su estado anterior sin recargar la página
            checkbox.checked = !checkbox.checked;

            Swal.fire({
                icon: "error",
                title: "Conflicto de Dorsal",
                text: data.mensaje,
                confirmButtonText: "Entendido"
            });
        } else {
            console.log("Estado cambiado correctamente a:", data.nuevo_estado);
        }
    })
    .catch(error => {
        // Revertir en caso de error de conexión
        checkbox.checked = !checkbox.checked;
        console.error("Error cambiando el estado:", error);
    });
}

function confirmarEliminacion(id) {
    Swal.fire({
        title: "¿Eliminar deportista?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar"
    })
    .then(function(result) {
        if (result.isConfirmed) {
            window.location = "index.php?id=" + id;
        }
    });
}
</script>


<?php if (
    isset($_GET['crear']) &&
    $_GET['crear'] === '1'
): ?>

<script>
document.addEventListener(
    "DOMContentLoaded",
    function () {
        const botones = document.querySelectorAll("button, a");
        botones.forEach(function (boton) {
            const texto = boton.textContent.trim().toLowerCase();
            if (texto.includes("crear deportista")) {
                boton.click();
            }
        });
    }
);
</script>

<?php endif; ?>


<!-- =====================================================
     CSS RESPONSIVE DEL MÓDULO DEPORTISTAS
====================================================== -->

<style>

/* BADGE ESTILO DORSAL DE CAMISETA */
.dorsal-badge {
    display: inline-block;
    background-color: #0A4FA3;
    color: #ffffff;
    font-weight: 800;
    font-size: 15px;
    padding: 4px 10px;
    border-radius: 6px;
    border: 1px solid #083c7d;
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
}

.deportistas-toolbar {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.deportistas-acciones {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.deportistas-busqueda {
    display: flex;
    align-items: center;
    gap: 8px;
}

.deportistas-input-busqueda {
    width: 300px;
}

.tabla-deportistas-contenedor {
    width: 100%;
}

.tabla-deportistas .deportista-nombre {
    font-weight: 600;
}

.deportista-botones {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
}

.tabla-deportistas .celda-estado {
    min-width: 90px;
}

@media (max-width: 768px) {

    .deportistas-toolbar {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        width: 100%;
        gap: 12px;
    }

    .deportistas-acciones {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        width: 100%;
        gap: 8px;
    }

    .deportistas-acciones .btn {
        width: 100%;
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .deportistas-busqueda {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        width: 100%;
        gap: 8px;
    }

    .deportistas-input-busqueda {
        width: 100% !important;
        min-height: 44px;
        font-size: 16px;
    }

    .deportistas-busqueda .btn {
        width: 100%;
        min-height: 44px;
    }

    .tabla-deportistas thead {
        display: none;
    }

    .tabla-deportistas,
    .tabla-deportistas tbody,
    .tabla-deportistas tr,
    .tabla-deportistas td {
        display: block;
        width: 100%;
    }

    .tabla-deportistas tbody tr {
        display: block;
        width: 100%;
        margin-bottom: 18px;
        padding: 14px;
        background: #ffffff;
        border: 1px solid #dee2e6;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .tabla-deportistas tbody td {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        width: 100%;
        min-height: 40px;
        padding: 9px 0;
        margin: 0;
        border: none;
        border-bottom: 1px solid #eeeeee;
        text-align: right !important;
        white-space: normal;
        word-break: break-word;
    }

    .tabla-deportistas tbody td::before {
        content: attr(data-label);
        flex: 0 0 42%;
        padding-right: 10px;
        font-weight: 700;
        text-align: left;
    }

    .tabla-deportistas tbody td:last-child {
        border-bottom: none;
    }

    .tabla-deportistas tbody td.deportista-nombre {
        display: block;
        padding: 4px 0 14px;
        text-align: left !important;
        font-size: 18px;
        font-weight: 700;
        border-bottom: 2px solid #0A4FA3;
    }

    .tabla-deportistas tbody td.deportista-nombre::before {
        display: none;
    }

    .tabla-deportistas tbody td.celda-estado {
        align-items: center;
    }

    .tabla-deportistas tbody td.celda-estado .form-check {
        margin-left: auto;
        margin-right: 0;
    }

    .tabla-deportistas tbody td.celda-acciones {
        display: block;
        padding-top: 14px;
    }

    .tabla-deportistas tbody td.celda-acciones::before {
        display: none;
    }

    .deportista-botones {
        display: flex;
        width: 100%;
        gap: 8px;
    }

    .deportista-botones .btn {
        flex: 1;
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .tabla-deportistas .form-check-input {
        width: 2.8em;
        height: 1.5em;
        cursor: pointer;
    }

    .tabla-deportistas tbody td {
        font-size: 14px;
    }

    .tabla-deportistas {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: auto;
    }

    .tabla-deportistas-contenedor {
        width: 100%;
        max-width: 100%;
        overflow: visible;
    }

}

@media (max-width: 380px) {

    .tabla-deportistas tbody tr {
        padding: 11px;
        border-radius: 10px;
    }

    .tabla-deportistas tbody td {
        font-size: 13px;
    }

    .tabla-deportistas tbody td::before {
        flex-basis: 40%;
    }

    .tabla-deportistas tbody td.deportista-nombre {
        font-size: 17px;
    }

    .deportista-botones .btn {
        font-size: 13px;
        padding-left: 6px;
        padding-right: 6px;
    }

}

</style>


<?php

/*
=========================================================
11. FOOTER GENERAL
=========================================================
*/

include("../../template/footer_modulos.php");

?>