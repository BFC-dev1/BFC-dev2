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

IMPORTANTE:

NO se crea una segunda tabla para celular.

Se utiliza UNA SOLA tabla HTML.

En computador:

    Tabla tradicional
    ┌──────┬──────────┬─────────┬──────────┐
    │ Tipo │ Documento│ Nombre  │ Categoría│
    ├──────┼──────────┼─────────┼──────────┤
    │ CC   │ 123456   │ Juan    │ Sub-10   │
    └──────┴──────────┴─────────┴──────────┘

En celular/APK:

    La misma tabla se transforma visualmente
    en tarjetas verticales.

    ┌─────────────────────────────┐
    │ Nombre                      │
    │ Juan Pérez                  │
    │                             │
    │ Documento: 123456           │
    │ Teléfono: 3001234567        │
    │ Categoría: Sub-10           │
    │ Entrenador: Carlos          │
    │ Acudiente: María            │
    │ Estado: ● Activo            │
    │                             │
    │ [ Editar ] [ Eliminar ]     │
    └─────────────────────────────┘

Esto evita:

    - Scroll horizontal.
    - Texto demasiado pequeño.
    - Tabla comprimida.
    - Botones difíciles de tocar.
    - Problemas visuales en Android WebView.

=========================================================
*/


/*
=========================================================
1. VERIFICAR PERMISOS DEL MÓDULO DEPORTISTAS
=========================================================
*/

require_once("../../includes/config.php");
require_once("../../includes/verificar_roles.php");


/*
---------------------------------------------------------
Permitir acceso si el usuario tiene:

    deportistas
        O
    ver_deportistas

Si no tiene ninguno de los dos permisos,
se devuelve al Dashboard.
---------------------------------------------------------
*/

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

La eliminación solamente está disponible para usuarios
que tengan el permiso:

    deportistas

Antes de eliminar:

    - Se obtiene la información.
    - Se registra la auditoría.
    - Se eliminan los documentos físicos.
    - Se eliminan registros relacionados.
    - Finalmente se elimina el deportista.

=========================================================
*/

if (isset($_GET['id'])) {


    /*
    -----------------------------------------------------
    Verificar permiso de eliminación
    -----------------------------------------------------
    */

    if (!tiene_permiso('deportistas')) {

        header("Location: index.php");

        exit;
    }


    /*
    -----------------------------------------------------
    Obtener ID del deportista
    -----------------------------------------------------
    */

    $txtid = (int) $_GET['id'];


    /*
    -----------------------------------------------------
    Obtener información del deportista
    -----------------------------------------------------
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
        ":id" => $txtid
    ]);

    $deportistaEliminar = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
    -----------------------------------------------------
    Registrar eliminación en auditoría
    -----------------------------------------------------
    */

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


    /*
    -----------------------------------------------------
    ELIMINAR DOCUMENTOS FÍSICOS
    -----------------------------------------------------
    */

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


    /*
    -----------------------------------------------------
    ELIMINAR REGISTROS RELACIONADOS
    -----------------------------------------------------
    */

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


    /*
    -----------------------------------------------------
    ELIMINAR DEPORTISTA
    -----------------------------------------------------
    */

    $conexion->prepare("
        DELETE FROM deportista
        WHERE id = :id
    ")->execute([
        ":id" => $txtid
    ]);


    /*
    -----------------------------------------------------
    Regresar al listado
    -----------------------------------------------------
    */

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

Se mantiene UNA SOLA consulta.

No existe una consulta diferente para celular.

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


/*
---------------------------------------------------------
Guardar todos los deportistas en un arreglo.
---------------------------------------------------------
*/

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

Solo se carga si el usuario tiene permiso completo
de gestión de deportistas.

=========================================================
*/

if (tiene_permiso('deportistas')) {

    include("crear_deportista.php");
}

?>


<!-- =====================================================
     MENSAJE DE ACTUALIZACIÓN EXITOSA
====================================================== -->

<?php if (isset($_GET['actualizado'])) { ?>

<script>

Swal.fire({

    icon: "success",

    title: "Operación Exitosa",

    text: "Los datos fueron actualizados correctamente.",

    confirmButtonText: "Aceptar"

});

</script>

<?php } ?>


<!-- =====================================================
     CONTENEDOR SUPERIOR DEL MÓDULO
======================================================

     PC:
     -----------------------------------------------------
     [Dashboard] [Crear] [Importar]      [Buscar] [Limpiar]

     CELULAR:
     -----------------------------------------------------
     [Dashboard]

     [Crear]
     [Importar]

     [Buscar...................]

     [Buscar] [Limpiar]

====================================================== -->

<div class="deportistas-toolbar">


    <!-- =================================================
         BOTONES DE ACCIONES
    ================================================== -->

    <div class="deportistas-acciones">


        <!-- Volver al Dashboard -->

        <a
            href="<?= $url_base ?>/modulos/dashboard/index.php"
            class="btn btn-outline-dark"
        >
            ← Volver al Dashboard
        </a>


        <?php if (tiene_permiso('deportistas')): ?>


            <!-- Crear deportista -->

            <button
                type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#create"
            >
                Crear Deportista
            </button>


            <!-- Importar deportistas -->

            <a
                href="importar_deportista.php"
                class="btn btn-success"
            >
                Importar Deportistas
            </a>


        <?php endif; ?>


    </div>


    <!-- =================================================
         FORMULARIO DE BÚSQUEDA
    ================================================== -->

    <form
        method="GET"
        class="deportistas-busqueda"
    >


        <!-- Campo de búsqueda -->

        <input
            type="text"
            name="buscar"
            class="form-control deportistas-input-busqueda"
            placeholder="Buscar deportista, documento..."
            value="<?php echo htmlspecialchars($buscar); ?>"
        >


        <!-- Botón Buscar -->

        <button
            type="submit"
            class="btn btn-primary"
        >
            Buscar
        </button>


        <!-- Limpiar búsqueda -->

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
======================================================

IMPORTANTE:

Esta es la ÚNICA tabla del módulo.

En PC:

    permanece como tabla.

En celular/APK:

    CSS transforma visualmente cada fila en una tarjeta.

====================================================== -->


<div class="tabla-deportistas-contenedor">


<table
    class="table table-bordered table-hover text-center align-middle tabla-deportistas"
>


    <!-- =================================================
         CABECERA DE LA TABLA
    ================================================== -->

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


    <!-- =================================================
         CUERPO DE LA TABLA
    ================================================== -->

    <tbody>


    <?php foreach ($deportista as $deportista_item) { ?>


        <tr>


            <!-- =========================================
                 TIPO DE DOCUMENTO
            ========================================== -->

            <td data-label="Tipo Doc.">

                <?php
                echo htmlspecialchars(
                    $deportista_item['tipo_documento'] ?? ''
                );
                ?>

            </td>


            <!-- =========================================
                 DOCUMENTO
            ========================================== -->

            <td data-label="Documento">

                <?php
                echo htmlspecialchars(
                    $deportista_item['documento'] ?? ''
                );
                ?>

            </td>


            <!-- =========================================
                 TELÉFONO
            ========================================== -->

            <td data-label="Teléfono">

                <?php
                echo htmlspecialchars(
                    $deportista_item['telefono'] ?? ''
                );
                ?>

            </td>


            <!-- =========================================
                 NOMBRE
            ========================================== -->

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


            <!-- =========================================
                 FECHA DE NACIMIENTO
            ========================================== -->

            <td data-label="Fecha Nac.">

                <?php
                echo htmlspecialchars(
                    $deportista_item['fecha_nacimiento'] ?? ''
                );
                ?>

            </td>


            <!-- =========================================
                 CATEGORÍA
            ========================================== -->

            <td data-label="Categoría">

                <?php
                echo htmlspecialchars(
                    $deportista_item['categoria_nombre'] ?? 'Sin categoría'
                );
                ?>

            </td>


            <!-- =========================================
                 ENTRENADOR
            ========================================== -->

            <td data-label="Entrenador">

                <?php
                echo htmlspecialchars(
                    $deportista_item['entrenador_nombre'] ?? 'Sin entrenador'
                );
                ?>

            </td>


            <!-- =========================================
                 ACUDIENTE
            ========================================== -->

            <td data-label="Acudiente">

                <?php
                echo htmlspecialchars(
                    $deportista_item['acudiente_nombre'] ?? 'Sin acudiente'
                );
                ?>

            </td>


            <!-- =========================================
                 ESTADO
            ========================================== -->

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
                                <?php
                                echo (int)$deportista_item['id'];
                                ?>
                            )"

                        <?php else: ?>

                            disabled

                        <?php endif; ?>

                    >


                </div>

            </td>


            <!-- =========================================
                 ACCIONES
            ========================================== -->

            <td
                data-label="Acciones"
                class="celda-acciones"
            >


                <?php if (tiene_permiso('deportistas')): ?>


                    <div class="deportista-botones">


                        <!-- Editar -->

                        <a
                            href="editar.php?id=<?php echo (int)$deportista_item['id']; ?>"
                            class="btn btn-success btn-sm"
                        >
                            Editar
                        </a>


                        <!-- Eliminar -->

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


/*
=========================================================
CAMBIAR ESTADO DEL DEPORTISTA
=========================================================

Activa o desactiva el estado del deportista.

=========================================================
*/

function cambiarEstado(id) {


    fetch(
        "cambiar_estado_deportista.php?id=" + id
    )


    .then(function(response) {

        return response.text();

    })


    .then(function(data) {

        console.log(
            "Respuesta:",
            data
        );

    })


    .catch(function(error) {

        console.error(
            "Error:",
            error
        );

    });

}



/*
=========================================================
CONFIRMAR ELIMINACIÓN
=========================================================
*/

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


            window.location =
                "index.php?id=" + id;


        }


    });

}


</script>


<!-- =====================================================
     APERTURA AUTOMÁTICA DEL FORMULARIO

======================================================

Cuando se accede mediante:

    index.php?crear=1

se abre automáticamente el botón:

    Crear Deportista

====================================================== -->


<?php if (
    isset($_GET['crear']) &&
    $_GET['crear'] === '1'
): ?>


<script>


document.addEventListener(
    "DOMContentLoaded",
    function () {


        /*
        -------------------------------------------------
        Buscar todos los botones y enlaces
        -------------------------------------------------
        */

        const botones =
            document.querySelectorAll(
                "button, a"
            );


        /*
        -------------------------------------------------
        Buscar "Crear Deportista"
        -------------------------------------------------
        */

        botones.forEach(
            function (boton) {


                const texto =
                    boton.textContent
                    .trim()
                    .toLowerCase();


                /*
                -----------------------------------------
                Abrir modal
                -----------------------------------------
                */

                if (
                    texto.includes(
                        "crear deportista"
                    )
                ) {

                    boton.click();

                }


            }
        );


    }
);


</script>


<?php endif; ?>


<!-- =====================================================
     CSS RESPONSIVE DEL MÓDULO DEPORTISTAS
======================================================

IMPORTANTE:

Este bloque NO reemplaza tu CSS general.

Su función es específica para este listado.

BREAKPOINT:

    768px

Por encima de 768px:

    Se mantiene la tabla.

Por debajo de 768px:

    La tabla se convierte visualmente
    en tarjetas.

====================================================== -->


<style>


/*
=========================================================
1. BARRA SUPERIOR
=========================================================
*/

.deportistas-toolbar {

    width: 100%;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    margin-bottom: 20px;

}


/*
=========================================================
2. BOTONES DE ACCIONES
=========================================================
*/

.deportistas-acciones {

    display: flex;

    align-items: center;

    gap: 8px;

    flex-wrap: wrap;

}


/*
=========================================================
3. FORMULARIO DE BÚSQUEDA
=========================================================
*/

.deportistas-busqueda {

    display: flex;

    align-items: center;

    gap: 8px;

}


/*
=========================================================
4. CAMPO DE BÚSQUEDA EN PC
=========================================================
*/

.deportistas-input-busqueda {

    width: 300px;

}


/*
=========================================================
5. CONTENEDOR DE TABLA
=========================================================

En PC puede ocupar todo el ancho disponible.

NO usamos overflow-x:auto porque precisamente
queremos evitar el scroll horizontal en celular.

=========================================================
*/

.tabla-deportistas-contenedor {

    width: 100%;

}


/*
=========================================================
6. NOMBRE DEL DEPORTISTA
=========================================================
*/

.tabla-deportistas .deportista-nombre {

    font-weight: 600;

}


/*
=========================================================
7. BOTONES DE ACCIONES
=========================================================
*/

.deportista-botones {

    display: flex;

    justify-content: center;

    align-items: center;

    gap: 6px;

}


/*
=========================================================
8. CELDA DE ESTADO
=========================================================
*/

.tabla-deportistas .celda-estado {

    min-width: 90px;

}


/*
=========================================================
9. RESPONSIVE - CELULARES Y APK
=========================================================

Aquí ocurre la transformación.

=========================================================
*/

@media (max-width: 768px) {


    /*
    =====================================================
    BARRA SUPERIOR
    =====================================================
    */

    .deportistas-toolbar {

        display: flex;

        flex-direction: column;

        align-items: stretch;

        width: 100%;

        gap: 12px;

    }


    /*
    =====================================================
    BOTONES SUPERIORES
    =====================================================
    */

    .deportistas-acciones {

        display: flex;

        flex-direction: column;

        align-items: stretch;

        width: 100%;

        gap: 8px;

    }


    /*
    -----------------------------------------------------
    Todos los botones superiores ocupan el ancho.
    -----------------------------------------------------
    */

    .deportistas-acciones .btn {

        width: 100%;

        min-height: 44px;

        display: flex;

        align-items: center;

        justify-content: center;

    }


    /*
    =====================================================
    BUSCADOR
    =====================================================
    */

    .deportistas-busqueda {

        display: flex;

        flex-direction: column;

        align-items: stretch;

        width: 100%;

        gap: 8px;

    }


    /*
    =====================================================
    CAMPO DE BÚSQUEDA
    =====================================================
    */

    .deportistas-input-busqueda {

        width: 100% !important;

        min-height: 44px;

        font-size: 16px;

    }


    /*
    =====================================================
    BOTONES DEL BUSCADOR
    =====================================================
    */

    .deportistas-busqueda .btn {

        width: 100%;

        min-height: 44px;

    }


    /*
    =====================================================
    OCULTAR CABECERA DE TABLA
    =====================================================

    En móvil ya no necesitamos:

        Tipo | Documento | Nombre | ...

    =====================================================
    */

    .tabla-deportistas thead {

        display: none;

    }


    /*
    =====================================================
    QUITAR COMPORTAMIENTO DE TABLA
    =====================================================
    */

    .tabla-deportistas,
    .tabla-deportistas tbody,
    .tabla-deportistas tr,
    .tabla-deportistas td {

        display: block;

        width: 100%;

    }


    /*
    =====================================================
    CADA FILA SE CONVIERTE EN TARJETA
    =====================================================
    */

    .tabla-deportistas tbody tr {

        display: block;

        width: 100%;

        margin-bottom: 18px;

        padding: 14px;

        background: #ffffff;

        border: 1px solid #dee2e6;

        border-radius: 12px;

        box-shadow:
            0 2px 8px rgba(0, 0, 0, 0.08);

    }


    /*
    =====================================================
    CELDAS DE LAS TARJETAS
    =====================================================

    Cada dato tendrá:

        Documento: 123456
        Teléfono: 3001234567
        Categoría: Sub-10

    =====================================================
    */

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


    /*
    =====================================================
    ETIQUETA DEL CAMPO
    =====================================================

    El contenido de:

        data-label="Documento"

    aparecerá automáticamente.

    =====================================================
    */

    .tabla-deportistas tbody td::before {

        content: attr(data-label);

        flex: 0 0 42%;

        padding-right: 10px;

        font-weight: 700;

        text-align: left;

    }


    /*
    =====================================================
    ÚLTIMA CELDA SIN LÍNEA INFERIOR
    =====================================================
    */

    .tabla-deportistas tbody td:last-child {

        border-bottom: none;

    }


    /*
    =====================================================
    NOMBRE DEL DEPORTISTA
    =====================================================

    Lo destacamos para que sea lo primero
    que identifique el usuario.

    =====================================================
    */

    .tabla-deportistas tbody td.deportista-nombre {

        display: block;

        padding: 4px 0 14px;

        text-align: left !important;

        font-size: 18px;

        font-weight: 700;

        border-bottom: 2px solid #0A4FA3;

    }


    /*
    -----------------------------------------------------
    No mostrar "Nombre:" delante del nombre principal.
    -----------------------------------------------------
    */

    .tabla-deportistas tbody td.deportista-nombre::before {

        display: none;

    }


    /*
    =====================================================
    ESTADO
    =====================================================
    */

    .tabla-deportistas tbody td.celda-estado {

        align-items: center;

    }


    /*
    -----------------------------------------------------
    El switch queda a la derecha.
    -----------------------------------------------------
    */

    .tabla-deportistas tbody td.celda-estado
    .form-check {

        margin-left: auto;

        margin-right: 0;

    }


    /*
    =====================================================
    ACCIONES
    =====================================================
    */

    .tabla-deportistas tbody td.celda-acciones {

        display: block;

        padding-top: 14px;

    }


    /*
    -----------------------------------------------------
    Ocultar etiqueta "Acciones".
    -----------------------------------------------------
    */

    .tabla-deportistas tbody td.celda-acciones::before {

        display: none;

    }


    /*
    =====================================================
    BOTONES EDITAR / ELIMINAR
    =====================================================
    */

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


    /*
    =====================================================
    SWITCH MÁS FÁCIL DE TOCAR EN CELULAR
    =====================================================
    */

    .tabla-deportistas
    .form-check-input {

        width: 2.8em;

        height: 1.5em;

        cursor: pointer;

    }


    /*
    =====================================================
    EVITAR TEXTO DEMASIADO PEQUEÑO
    =====================================================
    */

    .tabla-deportistas tbody td {

        font-size: 14px;

    }


    /*
    =====================================================
    EVITAR QUE LA TABLA GENERE SCROLL HORIZONTAL
    =====================================================
    */

    .tabla-deportistas {

        width: 100% !important;

        max-width: 100% !important;

        table-layout: auto;

    }


    /*
    =====================================================
    CONTENEDOR
    =====================================================
    */

    .tabla-deportistas-contenedor {

        width: 100%;

        max-width: 100%;

        overflow: visible;

    }


}


/*
=========================================================
10. CELULARES MUY PEQUEÑOS
=========================================================

Ejemplo:

    320px
    360px

Reducimos ligeramente espacios.

=========================================================
*/

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
