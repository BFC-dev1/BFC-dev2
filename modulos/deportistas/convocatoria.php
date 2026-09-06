<?php

/*
=================================================
BELLAVISTA FC
MÓDULO DE CONVOCATORIA

Archivo:

    /modulos/deportistas/convocatoria.php

ESTRUCTURA BASADA EN EL ARCHIVO ORIGINAL.

FUNCIONES:

1. Mostrar categorías.
2. Mostrar deportistas activos.
3. Seleccionar jugadores.
4. Mantener convocados en SESSION.
5. Mantener convocados al cambiar:
       - categoría
       - fecha
       - hora
       - lugar
       - notas
6. Limpiar convocados únicamente al cambiar:
       - rival
7. Mostrar tabla de convocados.
8. Enviar información al reporte PDF.

IMPORTANTE:

- NO genera PDF aquí.
- NO envía WhatsApp aquí.
- NO modifica enviar_plantilla_whatsapp.php.
- Mantiene header_dashboard.php.
- Mantiene sidebar.php.
- Mantiene footer_dashboard.php.
- Mantiene convocatoria.css externo.
=================================================
*/


/*
=================================================
INICIAR SESIÓN
=================================================
*/

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}


/*
=================================================
CONFIGURACIÓN GENERAL
=================================================

Se carga antes de los archivos del dashboard
para disponer de:

    $url_base
    $css_url
    $img_url

Esto permite trabajar en:

    LOCAL
    WEB
=================================================
*/

require_once(
    __DIR__ . "/../../includes/config.php"
);


/*
=================================================
CONEXIÓN BASE DE DATOS
=================================================
*/

include(
    __DIR__ . "/../conexion_modulos.php"
);


/*
=================================================
INICIALIZAR VARIABLES DE SESIÓN
=================================================
*/


/*
-------------------------------------------------
CONVOCADOS
-------------------------------------------------
*/

if (!isset($_SESSION['convocados'])) {

    $_SESSION['convocados'] = [];

}


/*
-------------------------------------------------
FECHA
-------------------------------------------------
*/

if (!isset($_SESSION['fecha_convocatoria'])) {

    $_SESSION['fecha_convocatoria'] = "";

}


/*
-------------------------------------------------
RIVAL
-------------------------------------------------
*/

if (!isset($_SESSION['rival_convocatoria'])) {

    $_SESSION['rival_convocatoria'] = "";

}


/*
-------------------------------------------------
CATEGORÍA
-------------------------------------------------
*/

if (!isset($_SESSION['categoria_convocatoria'])) {

    $_SESSION['categoria_convocatoria'] = "";

}


/*
-------------------------------------------------
HORA
-------------------------------------------------
*/

if (!isset($_SESSION['hora_convocatoria'])) {

    $_SESSION['hora_convocatoria'] = "";

}


/*
-------------------------------------------------
LUGAR
-------------------------------------------------
*/

if (!isset($_SESSION['lugar_convocatoria'])) {

    $_SESSION['lugar_convocatoria'] = "";

}


/*
-------------------------------------------------
NOTAS
-------------------------------------------------
*/

if (!isset($_SESSION['notas_convocatoria'])) {

    $_SESSION['notas_convocatoria'] = "";

}


/*
=================================================
FILTROS

Se toman primero los valores enviados por GET.

Si no existen, se toman de SESSION.
=================================================
*/

$categoria_id =
    $_GET['categoria_id']
    ?? $_SESSION['categoria_convocatoria']
    ?? "";


$fecha =
    $_GET['fecha']
    ?? $_SESSION['fecha_convocatoria']
    ?? date("Y-m-d");


$rival =
    trim(
        $_GET['rival']
        ?? $_SESSION['rival_convocatoria']
        ?? ""
    );


$hora =
    $_GET['hora']
    ?? $_SESSION['hora_convocatoria']
    ?? "";


$lugar =
    trim(
        $_GET['lugar']
        ?? $_SESSION['lugar_convocatoria']
        ?? ""
    );


$notas =
    trim(
        $_GET['notas']
        ?? $_SESSION['notas_convocatoria']
        ?? ""
    );


/*
=================================================
DETECTAR CAMBIO DE RIVAL
=================================================

REGLA:

Cambiar rival significa comenzar una nueva
convocatoria.

Por eso:

    rival anterior != rival nuevo
            ↓
    limpiar convocados

IMPORTANTE:

Cambiar fecha NO limpia.
Cambiar categoría NO limpia.
Cambiar hora NO limpia.
Cambiar lugar NO limpia.
Cambiar notas NO limpia.
=================================================
*/

$rival_anterior =
    $_SESSION['rival_convocatoria']
    ?? "";


if (
    !empty($rival_anterior) &&
    !empty($rival) &&
    $rival_anterior !== $rival
) {

    $_SESSION['convocados'] = [];

}


/*
=================================================
GUARDAR DATOS ACTUALES EN SESSION
=================================================
*/

$_SESSION['fecha_convocatoria'] =
    $fecha;


$_SESSION['rival_convocatoria'] =
    $rival;


$_SESSION['categoria_convocatoria'] =
    $categoria_id;


$_SESSION['hora_convocatoria'] =
    $hora;


$_SESSION['lugar_convocatoria'] =
    $lugar;


$_SESSION['notas_convocatoria'] =
    $notas;


/*
=================================================
AGREGAR / QUITAR CONVOCADOS
=================================================

Este bloque es utilizado por JavaScript mediante
FETCH.

Acciones:

    agregar
    quitar

La respuesta se mantiene en JSON como en el
código original.
=================================================
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['accion'])
) {

    $accion =
        $_POST['accion']
        ?? "";


    $id =
        $_POST['id']
        ?? "";


    /*
    =============================================
    AGREGAR
    =============================================
    */

    if (
        $accion === 'agregar'
    ) {

        $_SESSION['convocados'][$id] = [

            'nombre' =>
                $_POST['nombre']
                ?? '',

            'documento' =>
                $_POST['documento']
                ?? '',

            'telefono' =>
                $_POST['telefono']
                ?? '',

            'entrenador' =>
                $_POST['entrenador']
                ?? '',

            'acudiente' =>
                $_POST['acudiente']
                ?? '',

            'parentesco' =>
                $_POST['parentesco']
                ?? '',

            'fecha_nacimiento' =>
                $_POST['fecha_nacimiento']
                ?? '',

            'categoria' =>
                $_POST['categoria']
                ?? ''

        ];

    }


    /*
    =============================================
    QUITAR
    =============================================
    */

    if (
        $accion === 'quitar'
    ) {

        unset(
            $_SESSION['convocados'][$id]
        );

    }


    /*
    =============================================
    RESPUESTA AJAX
    =============================================
    */

    header(
        'Content-Type: application/json; charset=utf-8'
    );


    echo json_encode([

        "success" => true

    ]);


    exit;

}


/*
=================================================
CATEGORÍAS

TABLA REAL:

    categoria
=================================================
*/

$stmtCat =
    $conexion->prepare("

        SELECT *

        FROM categoria

        ORDER BY nombre ASC

    ");


$stmtCat->execute();


/*
=================================================
DEPORTISTAS
=================================================
*/

$deportistas = [];


if (
    !empty($categoria_id)
) {

    $stmtDep =
        $conexion->prepare("

        SELECT

            d.id,

            d.nombre,

            d.documento,

            d.telefono,

            d.fecha_nacimiento,

            ud.acudiente,

            ud.parentesco,

            u.nombre AS entrenador,

            c.nombre AS categoria

        FROM deportista d

        LEFT JOIN categoria c
            ON c.id = d.categoria_id

        LEFT JOIN usuario_deportista ud
            ON ud.deportista_id = d.id

        LEFT JOIN usuario u
            ON u.id = ud.entrenador_id

        WHERE d.estado = 'activo'

        AND d.categoria_id = :cat

        ORDER BY d.nombre ASC

    ");


    $stmtDep->execute([

        ":cat" =>
            $categoria_id

    ]);


    $deportistas =
        $stmtDep->fetchAll(
            PDO::FETCH_ASSOC
        );

}


/*
=================================================
URL DEL REPORTE

Archivo:

    /modulos/reportes/reporte_convocatoria.php
=================================================
*/

$url_reporte =
    $url_base .
    "/modulos/reportes/reporte_convocatoria.php";

?>


<?php

/*
=================================================
HEADER PRINCIPAL DEL DASHBOARD
=================================================
*/

include(
    __DIR__ . "/../../includes/header_dashboard.php"
);

?>


<!--
=================================================
CSS DEL MÓDULO

Se utiliza $css_url para funcionar en:

    LOCAL
    WEB
=================================================
-->

<link
    rel="stylesheet"
    href="<?= htmlspecialchars(
        $css_url,
        ENT_QUOTES,
        'UTF-8'
    ) ?>/convocatoria.css"
>


<?php

/*
=================================================
SIDEBAR
=================================================

IMPORTANTE:

Se utiliza __DIR__ para evitar problemas de
rutas entre:

    WINDOWS / XAMPP
    LINUX / SERVIDOR WEB
=================================================
*/

include(
    __DIR__ . "/../Dashboard/sidebar.php"
);

?>


<div class="main-content">

    <div class="container-fluid py-4">

        <!-- =====================================
             BOTÓN VOLVER AL DASHBOARD
             ===================================== -->
        <div class="mb-3">
            <a
                href="<?= htmlspecialchars($url_base, ENT_QUOTES, 'UTF-8') ?>/modulos/dashboard/index.php"
                class="btn btn-outline-dark"
            >
                ← Volver al Dashboard
            </a>
        </div>

        <div class="card shadow p-4">


            <!--
            =====================================
            TÍTULO
            =====================================
            -->

            <h2 class="mb-4">

                <i class="fa-solid fa-clipboard-list"></i>

                Generar Convocatoria

            </h2>


            <!--
            =====================================
            FORMULARIO FILTRO
            =====================================
            -->

            <form
                method="GET"
                id="formFiltro"
            >


                <div class="row">


                    <!--
                    =================================
                    FECHA
                    =================================
                    -->

                    <div class="col-md-4 mb-3">


                        <label
                            class="form-label fw-bold"
                        >

                            Fecha del partido

                        </label>


                        <input
                            type="date"
                            name="fecha"
                            id="fechaInput"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $fecha,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            required
                        >


                    </div>


                    <!--
                    =================================
                    RIVAL
                    =================================
                    -->

                    <div class="col-md-4 mb-3">


                        <label
                            class="form-label fw-bold"
                        >

                            Rival

                        </label>


                        <input
                            type="text"
                            name="rival"
                            id="rivalInput"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $rival,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="Nombre del rival"
                            required
                        >


                    </div>


                    <!--
                    =================================
                    CATEGORÍA
                    =================================
                    -->

                    <div class="col-md-4 mb-3">


                        <label
                            class="form-label fw-bold"
                        >

                            Categoría

                        </label>


                        <select
                            name="categoria_id"
                            id="categoriaSelect"
                            class="form-select"
                            required
                        >


                            <option value="">

                                Seleccionar categoría

                            </option>


                            <?php while (
                                $cat =
                                $stmtCat->fetch(
                                    PDO::FETCH_ASSOC
                                )
                            ) { ?>


                                <option
                                    value="<?= (int) $cat['id'] ?>"
                                    <?= (
                                        $categoria_id ==
                                        $cat['id']
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        $cat['nombre'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </option>


                            <?php } ?>


                        </select>


                    </div>


                    <!--
                    =================================
                    HORA
                    =================================
                    -->

                    <div class="col-md-4 mb-3">


                        <label
                            class="form-label fw-bold"
                        >

                            Hora del partido

                        </label>


                        <input
                            type="time"
                            name="hora"
                            id="horaInput"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $hora,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >


                    </div>


                    <!--
                    =================================
                    LUGAR
                    =================================
                    -->

                    <div class="col-md-4 mb-3">


                        <label
                            class="form-label fw-bold"
                        >

                            Lugar

                        </label>


                        <input
                            type="text"
                            name="lugar"
                            id="lugarInput"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $lugar,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="Lugar del partido"
                        >


                    </div>


                    <!--
                    =================================
                    NOTAS
                    =================================
                    -->

                    <div class="col-md-4 mb-3">


                        <label
                            class="form-label fw-bold"
                        >

                            Notas

                        </label>


                        <input
                            type="text"
                            name="notas"
                            id="notasInput"
                            class="form-control"
                            value="<?= htmlspecialchars(
                                $notas,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            placeholder="Observaciones"
                        >


                    </div>


                </div>


            </form>


            <!--
            =====================================
            TABLA JUGADORES
            =====================================
            -->

            <?php if (
                !empty($deportistas)
            ) { ?>


                <h4 class="mb-3 mt-4">

                    <i class="fa-solid fa-users"></i>

                    Seleccionar Convocados

                </h4>


                <div class="table-responsive">


                    <table
                        class="table table-bordered table-hover align-middle"
                    >


                        <thead class="table-dark">


                            <tr>

                                <th>
                                    Jugador
                                </th>

                                <th>
                                    Documento
                                </th>

                                <th>
                                    Teléfono
                                </th>

                                <th>
                                    Entrenador
                                </th>

                                <th>
                                    Acudiente
                                </th>

                                <th>
                                    Parentesco
                                </th>

                                <th>
                                    Nacimiento
                                </th>

                                <th>
                                    Categoría
                                </th>

                                <th>
                                    Convocar
                                </th>

                            </tr>


                        </thead>


                        <tbody>


                            <?php foreach (
                                $deportistas
                                as $dep
                            ):


                                $checked =
                                    isset(
                                        $_SESSION[
                                            'convocados'
                                        ][
                                            $dep['id']
                                        ]
                                    )
                                        ? "checked"
                                        : "";

                            ?>


                                <tr>


                                    <td>

                                        <?= htmlspecialchars(
                                            $dep['nombre'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $dep['documento'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $dep['telefono'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $dep['entrenador'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $dep['acudiente'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $dep['parentesco'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $dep['fecha_nacimiento'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $dep['categoria'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </td>


                                    <td class="text-center">


                                        <input
                                            type="checkbox"
                                            class="form-check-input jugador-check"

                                            data-id="<?= (int) $dep['id'] ?>"

                                            data-nombre="<?= htmlspecialchars(
                                                $dep['nombre'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"

                                            data-documento="<?= htmlspecialchars(
                                                $dep['documento'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"

                                            data-telefono="<?= htmlspecialchars(
                                                $dep['telefono'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"

                                            data-entrenador="<?= htmlspecialchars(
                                                $dep['entrenador'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"

                                            data-acudiente="<?= htmlspecialchars(
                                                $dep['acudiente'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"

                                            data-parentesco="<?= htmlspecialchars(
                                                $dep['parentesco'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"

                                            data-fecha="<?= htmlspecialchars(
                                                $dep['fecha_nacimiento'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"

                                            data-categoria="<?= htmlspecialchars(
                                                $dep['categoria'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"

                                            <?= $checked ?>
                                        >


                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php } ?>


            <hr class="my-4">


            <!--
            =====================================
            TABLA CONVOCADOS
            =====================================
            -->

            <h4 class="mb-3">

                <i class="fa-solid fa-list"></i>

                Convocados

            </h4>


            <div class="table-responsive">


                <table
                    class="table table-striped table-bordered"
                >


                    <thead class="table-dark">


                        <tr>

                            <th>
                                Jugador
                            </th>

                            <th>
                                Documento
                            </th>

                            <th>
                                Teléfono
                            </th>

                            <th>
                                Entrenador
                            </th>

                            <th>
                                Acudiente
                            </th>

                            <th>
                                Parentesco
                            </th>

                            <th>
                                Nacimiento
                            </th>

                            <th>
                                Categoría
                            </th>

                        </tr>


                    </thead>


                    <tbody>


                        <?php if (
                            empty(
                                $_SESSION['convocados']
                            )
                        ) { ?>


                            <tr>

                                <td
                                    colspan="8"
                                    class="text-center"
                                >

                                    No hay jugadores
                                    convocados.

                                </td>

                            </tr>


                        <?php } else { ?>


                            <?php foreach (
                                $_SESSION['convocados']
                                as $convocado
                            ): ?>


                                <tr>


                                    <td>

                                        <?= htmlspecialchars(
                                            $convocado['nombre'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $convocado['documento'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $convocado['telefono'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $convocado['entrenador'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $convocado['acudiente'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $convocado['parentesco'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $convocado['fecha_nacimiento'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $convocado['categoria'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php } ?>


                    </tbody>


                </table>


            </div>


            <!--
            =====================================
            BOTÓN DESCARGAR
            =====================================
            -->

            <div class="mt-4 text-end">


                <form
                    method="POST"
                    action="<?= htmlspecialchars(
                        $url_reporte,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    target="_blank"
                >


                    <input
                        type="hidden"
                        name="fecha"
                        value="<?= htmlspecialchars(
                            $fecha,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >


                    <input
                        type="hidden"
                        name="rival"
                        value="<?= htmlspecialchars(
                            $rival,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >


                    <input
                        type="hidden"
                        name="categoria_id"
                        value="<?= htmlspecialchars(
                            $categoria_id,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >


                    <input
                        type="hidden"
                        name="hora"
                        value="<?= htmlspecialchars(
                            $hora,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >


                    <input
                        type="hidden"
                        name="lugar"
                        value="<?= htmlspecialchars(
                            $lugar,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >


                    <input
                        type="hidden"
                        name="notas"
                        value="<?= htmlspecialchars(
                            $notas,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >


                    <button
                        type="submit"
                        class="btn btn-success"
                        <?= empty(
                            $_SESSION['convocados']
                        )
                            ? 'disabled'
                            : ''
                        ?>
                    >

                        <i class="fa-solid fa-download"></i>

                        Descargar Convocatoria

                    </button>


                </form>


            </div>


        </div>


    </div>


</div>


<!--
=================================================
JAVASCRIPT
=================================================
-->

<script>

document.addEventListener(
    "DOMContentLoaded",
    () => {


        /*
        =============================================
        CHECKBOXES
        =============================================
        */

        const checks =
            document.querySelectorAll(
                ".jugador-check"
            );


        /*
        =============================================
        FORMULARIO FILTRO
        =============================================
        */

        const formFiltro =
            document.getElementById(
                "formFiltro"
            );


        /*
        =============================================
        CATEGORÍA
        =============================================
        */

        const categoriaSelect =
            document.getElementById(
                "categoriaSelect"
            );


        /*
        =============================================
        FECHA
        =============================================
        */

        const fechaInput =
            document.getElementById(
                "fechaInput"
            );


        /*
        =============================================
        HORA
        =============================================
        */

        const horaInput =
            document.getElementById(
                "horaInput"
            );


        /*
        =============================================
        LUGAR
        =============================================
        */

        const lugarInput =
            document.getElementById(
                "lugarInput"
            );


        /*
        =============================================
        NOTAS
        =============================================
        */

        const notasInput =
            document.getElementById(
                "notasInput"
            );


        /*
        =============================================
        CATEGORÍA

        Cambiar categoría NO elimina convocados.
        =============================================
        */

        if (
            categoriaSelect
        ) {

            categoriaSelect.addEventListener(
                "change",
                () => {

                    formFiltro.submit();

                }
            );

        }


        /*
        =============================================
        FECHA

        Cambiar fecha NO elimina convocados.
        =============================================
        */

        if (
            fechaInput
        ) {

            fechaInput.addEventListener(
                "change",
                () => {

                    formFiltro.submit();

                }
            );

        }


        /*
        =============================================
        HORA

        Cambiar hora NO elimina convocados.
        =============================================
        */

        if (
            horaInput
        ) {

            horaInput.addEventListener(
                "change",
                () => {

                    formFiltro.submit();

                }
            );

        }


        /*
        =============================================
        LUGAR

        Cambiar lugar NO elimina convocados.
        =============================================
        */

        if (
            lugarInput
        ) {

            lugarInput.addEventListener(
                "change",
                () => {

                    formFiltro.submit();

                }
            );

        }


        /*
        =============================================
        NOTAS

        Cambiar notas NO elimina convocados.
        =============================================
        */

        if (
            notasInput
        ) {

            notasInput.addEventListener(
                "change",
                () => {

                    formFiltro.submit();

                }
            );

        }


        /*
        =============================================
        RIVAL

        El rival se procesa mediante GET.

        Cuando cambia:

            rival anterior
                    ↓
            rival nuevo

        PHP limpia los convocados.

        El submit se realiza al cambiar el campo.
        =============================================
        */

        const rivalInput =
            document.getElementById(
                "rivalInput"
            );


        if (
            rivalInput
        ) {

            rivalInput.addEventListener(
                "change",
                () => {

                    formFiltro.submit();

                }
            );

        }


        /*
        =============================================
        GUARDAR CONVOCADOS EN SESSION
        =============================================
        */

        checks.forEach(
            c => {


                c.addEventListener(
                    "change",
                    async () => {


                        /*
                        ---------------------------------
                        DETERMINAR ACCIÓN
                        ---------------------------------
                        */

                        let accion =
                            c.checked
                                ? "agregar"
                                : "quitar";


                        /*
                        ---------------------------------
                        DATOS
                        ---------------------------------
                        */

                        const body =
                            new URLSearchParams({

                                accion:
                                    accion,

                                id:
                                    c.dataset.id,

                                nombre:
                                    c.dataset.nombre,

                                documento:
                                    c.dataset.documento,

                                telefono:
                                    c.dataset.telefono,

                                entrenador:
                                    c.dataset.entrenador,

                                acudiente:
                                    c.dataset.acudiente,

                                parentesco:
                                    c.dataset.parentesco,

                                fecha_nacimiento:
                                    c.dataset.fecha,

                                categoria:
                                    c.dataset.categoria

                            });


                        /*
                        ---------------------------------
                        ENVIAR AL SERVIDOR
                        ---------------------------------
                        */

                        try {


                            const respuesta =
                                await fetch(
                                    window.location.href,
                                    {

                                        method:
                                            "POST",

                                        headers: {

                                            "Content-Type":
                                                "application/x-www-form-urlencoded"

                                        },

                                        body:
                                            body.toString()

                                    }
                                );


                            /*
                            ---------------------------------
                            LEER RESPUESTA
                            ---------------------------------
                            */

                            const resultado =
                                await respuesta.json();


                            /*
                            ---------------------------------
                            COMPROBAR RESULTADO
                            ---------------------------------
                            */

                            if (
                                !resultado.success
                            ) {

                                throw new Error(
                                    "No se pudo guardar."
                                );

                            }


                            /*
                            ---------------------------------
                            RECARGAR
                            ---------------------------------
                            */

                            location.reload();


                        } catch (error) {


                            /*
                            ---------------------------------
                            DESHACER CHECKBOX
                            ---------------------------------
                            */

                            c.checked =
                                !c.checked;


                            alert(
                                "No fue posible actualizar el convocado."
                            );

                        }

                    }
                );

            }
        );


    }
);

</script>


<?php

/*
=================================================
FOOTER DEL DASHBOARD
=================================================

IMPORTANTE:

El footer se encarga de cerrar:

    .d-flex
    </body>
    </html>

Por eso NO se colocan esas etiquetas antes
del include.
=================================================
*/

include(
    __DIR__ . "/../../includes/footer_dashboard.php"
);

?>

