<?php

require_once("../../includes/verificar_roles.php");

permitirRoles([
    "admin"
]);

require_once("../../includes/config.php");

include(__DIR__ . "/../../includes/conexion_BDcms.php");


/*
=================================================
OBTENER ID DE LA COMPETENCIA
=================================================
*/

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id) {

    header("Location: editar_competencias.php");

    exit;
}


/*
=================================================
CARGAR COMPETENCIA
=================================================
*/

$stmt = $conexion->prepare("
    SELECT *
    FROM cms_competencias
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ":id" => $id
]);

$competencia = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$competencia) {

    header("Location: editar_competencias.php");

    exit;
}


/*
=================================================
VARIABLES
=================================================
*/

$error = "";


/*
=================================================
AGREGAR VIDEO
=================================================
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['accion'])
    &&
    $_POST['accion'] === "agregar"
) {

    $titulo = trim(
        $_POST['titulo'] ?? ""
    );

    $url = trim(
        $_POST['url'] ?? ""
    );

    $descripcion = trim(
        $_POST['descripcion'] ?? ""
    );


    /*
    =============================================
    VALIDAR URL
    =============================================
    */

    if ($url === "") {

        $error = "La URL del video es obligatoria.";

    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {

        $error = "La URL del video no es válida.";

    } else {

        /*
        =========================================
        OBTENER ORDEN SIGUIENTE
        =========================================
        */

        $stmtOrden = $conexion->prepare("
            SELECT
                COALESCE(MAX(orden), 0) + 1
            FROM cms_competencias_videos
            WHERE competencia_id = :competencia_id
        ");

        $stmtOrden->execute([
            ":competencia_id" => $id
        ]);

        $orden = (int)
            $stmtOrden->fetchColumn();


        /*
        =========================================
        INSERTAR VIDEO
        =========================================
        */

        $stmtInsert = $conexion->prepare("
            INSERT INTO cms_competencias_videos
            (
                competencia_id,
                titulo,
                url,
                descripcion,
                orden,
                activo
            )
            VALUES
            (
                :competencia_id,
                :titulo,
                :url,
                :descripcion,
                :orden,
                1
            )
        ");

        $stmtInsert->execute([

            ":competencia_id" => $id,

            ":titulo" =>
                ($titulo === "")
                    ? null
                    : $titulo,

            ":url" => $url,

            ":descripcion" =>
                ($descripcion === "")
                    ? null
                    : $descripcion,

            ":orden" => $orden

        ]);


        /*
        =========================================
        REDIRECT
        =========================================
        */

        header(
            "Location: competencia_videos.php?id="
            . $id
            . "&agregado=1"
        );

        exit;
    }
}


/*
=================================================
ACTUALIZAR VIDEO
=================================================
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['accion'])
    &&
    $_POST['accion'] === "actualizar"
) {

    $videoId = filter_input(
        INPUT_POST,
        'video_id',
        FILTER_VALIDATE_INT
    );

    $titulo = trim(
        $_POST['titulo'] ?? ""
    );

    $url = trim(
        $_POST['url'] ?? ""
    );

    $descripcion = trim(
        $_POST['descripcion'] ?? ""
    );

    $orden = filter_input(
        INPUT_POST,
        'orden',
        FILTER_VALIDATE_INT
    );

    $activo = isset(
        $_POST['activo']
    ) ? 1 : 0;


    /*
    =============================================
    VALIDAR
    =============================================
    */

    if (!$videoId) {

        $error = "Video no válido.";

    } elseif ($url === "") {

        $error = "La URL del video es obligatoria.";

    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {

        $error = "La URL del video no es válida.";

    } else {

        if (
            $orden === false
            ||
            $orden === null
        ) {

            $orden = 0;
        }


        /*
        =========================================
        ACTUALIZAR
        =========================================
        */

        $stmtUpdate = $conexion->prepare("
            UPDATE cms_competencias_videos
            SET
                titulo = :titulo,
                url = :url,
                descripcion = :descripcion,
                orden = :orden,
                activo = :activo
            WHERE
                id = :id
                AND competencia_id = :competencia_id
        ");

        $stmtUpdate->execute([

            ":titulo" =>
                ($titulo === "")
                    ? null
                    : $titulo,

            ":url" => $url,

            ":descripcion" =>
                ($descripcion === "")
                    ? null
                    : $descripcion,

            ":orden" => $orden,

            ":activo" => $activo,

            ":id" => $videoId,

            ":competencia_id" => $id

        ]);


        /*
        =========================================
        REDIRECT
        =========================================
        */

        header(
            "Location: competencia_videos.php?id="
            . $id
            . "&actualizado=1"
        );

        exit;
    }
}


/*
=================================================
ELIMINAR VIDEO
=================================================
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['accion'])
    &&
    $_POST['accion'] === "eliminar"
) {

    $videoId = filter_input(
        INPUT_POST,
        'video_id',
        FILTER_VALIDATE_INT
    );


    if ($videoId) {

        $stmtDelete = $conexion->prepare("
            DELETE FROM cms_competencias_videos
            WHERE
                id = :id
                AND competencia_id = :competencia_id
        ");

        $stmtDelete->execute([

            ":id" => $videoId,

            ":competencia_id" => $id

        ]);


        header(
            "Location: competencia_videos.php?id="
            . $id
            . "&eliminado=1"
        );

        exit;
    }
}


/*
=================================================
CONSULTAR VIDEOS
=================================================
*/

$stmtVideos = $conexion->prepare("
    SELECT *
    FROM cms_competencias_videos
    WHERE competencia_id = :competencia_id
    ORDER BY orden ASC, id ASC
");

$stmtVideos->execute([
    ":competencia_id" => $id
]);

$videos = $stmtVideos->fetchAll(
    PDO::FETCH_ASSOC
);

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>

        Videos -
        <?php
        echo htmlspecialchars(
            $competencia['nombre'],
            ENT_QUOTES,
            'UTF-8'
        );
        ?>

    </title>


    <!-- BOOTSTRAP -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>


<body class="bg-light">


<div class="container py-5">


    <div class="card shadow p-4">


        <!-- TITULO -->

        <h2 class="mb-2">

            <i class="fa-solid fa-video"></i>

            Videos de la competencia

        </h2>


        <h4 class="text-muted mb-4">

            <?php

            echo htmlspecialchars(
                $competencia['nombre'],
                ENT_QUOTES,
                'UTF-8'
            );

            ?>

        </h4>


        <!-- MENSAJE AGREGADO -->

        <?php if (isset($_GET['agregado'])) { ?>

            <div class="alert alert-success">

                <i class="fa-solid fa-circle-check me-2"></i>

                Video agregado correctamente.

            </div>

        <?php } ?>


        <!-- MENSAJE ACTUALIZADO -->

        <?php if (isset($_GET['actualizado'])) { ?>

            <div class="alert alert-success">

                <i class="fa-solid fa-circle-check me-2"></i>

                Video actualizado correctamente.

            </div>

        <?php } ?>


        <!-- MENSAJE ELIMINADO -->

        <?php if (isset($_GET['eliminado'])) { ?>

            <div class="alert alert-success">

                <i class="fa-solid fa-circle-check me-2"></i>

                Video eliminado correctamente.

            </div>

        <?php } ?>


        <!-- ERROR -->

        <?php if ($error !== "") { ?>

            <div class="alert alert-danger">

                <i class="fa-solid fa-circle-exclamation me-2"></i>

                <?php

                echo htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                );

                ?>

            </div>

        <?php } ?>


        <!-- AGREGAR VIDEO -->

        <div class="card mb-4">

            <div class="card-body">


                <h5 class="card-title">

                    <i class="fa-solid fa-plus me-2"></i>

                    Agregar video

                </h5>


                <form method="POST">


                    <input
                        type="hidden"
                        name="accion"
                        value="agregar"
                    >


                    <!-- TITULO -->

                    <div class="mb-3">

                        <label
                            for="titulo"
                            class="form-label"
                        >

                            Título del video

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="titulo"
                            name="titulo"
                            maxlength="150"
                            placeholder="Ejemplo: Bellavista FC - Copa Bellanita"
                        >

                    </div>


                    <!-- URL -->

                    <div class="mb-3">

                        <label
                            for="url"
                            class="form-label"
                        >

                            URL del video

                        </label>

                        <input
                            type="url"
                            class="form-control"
                            id="url"
                            name="url"
                            maxlength="500"
                            placeholder="https://www.youtube.com/watch?v=..."
                            required
                        >

                        <div class="form-text">

                            Puedes utilizar una URL de YouTube u otra plataforma de videos.

                        </div>

                    </div>


                    <!-- DESCRIPCION -->

                    <div class="mb-3">

                        <label
                            for="descripcion"
                            class="form-label"
                        >

                            Descripción

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="descripcion"
                            name="descripcion"
                            maxlength="255"
                        >

                    </div>


                    <!-- BOTON -->

                    <button
                        type="submit"
                        class="btn btn-success"
                    >

                        <i class="fa-solid fa-plus me-2"></i>

                        Agregar Video

                    </button>


                </form>


            </div>

        </div>


        <!-- VIDEOS REGISTRADOS -->

        <h4 class="mb-3">

            <i class="fa-solid fa-film me-2"></i>

            Videos registrados

        </h4>


        <?php if (empty($videos)) { ?>

            <div class="alert alert-info">

                Esta competencia todavía no tiene videos.

            </div>

        <?php } else { ?>


            <?php foreach ($videos as $video) { ?>


                <div class="card mb-3 shadow-sm">


                    <div class="card-body">


                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">


                            <h5 class="mb-0">

                                <i class="fa-solid fa-video text-danger me-2"></i>

                                <?php

                                echo htmlspecialchars(
                                    $video['titulo']
                                        ?: 'Video sin título',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </h5>


                            <span
                                class="badge <?php
                                echo $video['activo']
                                    ? 'bg-success'
                                    : 'bg-secondary';
                                ?>"
                            >

                                <?php

                                echo $video['activo']
                                    ? 'Activo'
                                    : 'Inactivo';

                                ?>

                            </span>

                        </div>


                        <!-- FORMULARIO EDICIÓN -->

                        <form method="POST">


                            <input
                                type="hidden"
                                name="accion"
                                value="actualizar"
                            >


                            <input
                                type="hidden"
                                name="video_id"
                                value="<?php
                                    echo (int)
                                        $video['id'];
                                ?>"
                            >


                            <!-- TITULO -->

                            <div class="mb-3">

                                <label class="form-label">

                                    Título

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    name="titulo"
                                    maxlength="150"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $video['titulo']
                                            ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>"
                                >

                            </div>


                            <!-- URL -->

                            <div class="mb-3">

                                <label class="form-label">

                                    URL

                                </label>

                                <input
                                    type="url"
                                    class="form-control"
                                    name="url"
                                    maxlength="500"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $video['url'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>"
                                    required
                                >

                            </div>


                            <!-- DESCRIPCION -->

                            <div class="mb-3">

                                <label class="form-label">

                                    Descripción

                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    name="descripcion"
                                    maxlength="255"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $video['descripcion']
                                            ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>"
                                >

                            </div>


                            <!-- ORDEN -->

                            <div class="mb-3">

                                <label class="form-label">

                                    Orden

                                </label>

                                <input
                                    type="number"
                                    class="form-control"
                                    name="orden"
                                    min="0"
                                    value="<?php

                                    echo (int)
                                        $video['orden'];

                                    ?>"
                                >

                            </div>


                            <!-- ACTIVO -->

                            <div class="form-check mb-3">

                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    name="activo"
                                    value="1"
                                    id="activo_<?php
                                        echo (int)
                                            $video['id'];
                                    ?>"
                                    <?php

                                    echo $video['activo']
                                        ? 'checked'
                                        : '';

                                    ?>
                                >

                                <label
                                    class="form-check-label"
                                    for="activo_<?php
                                        echo (int)
                                            $video['id'];
                                    ?>"
                                >

                                    Video activo

                                </label>

                            </div>


                            <!-- GUARDAR -->

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i class="fa-solid fa-floppy-disk me-2"></i>

                                Guardar Cambios

                            </button>


                        </form>


                        <!-- ELIMINAR -->

                        <form
                            method="POST"
                            class="mt-2"
                            onsubmit="return confirm(
                                '¿Seguro que deseas eliminar este video?'
                            );"
                        >

                            <input
                                type="hidden"
                                name="accion"
                                value="eliminar"
                            >

                            <input
                                type="hidden"
                                name="video_id"
                                value="<?php
                                    echo (int)
                                        $video['id'];
                                ?>"
                            >


                            <button
                                type="submit"
                                class="btn btn-outline-danger"
                            >

                                <i class="fa-solid fa-trash me-2"></i>

                                Eliminar

                            </button>

                        </form>


                    </div>

                </div>


            <?php } ?>


        <?php } ?>


        <!-- VOLVER -->

        <div class="mt-4">

            <a
                href="editar_competencias.php"
                class="btn btn-outline-secondary"
            >

                <i class="fa-solid fa-arrow-left me-2"></i>

                Volver a Competencias

            </a>

        </div>


    </div>

</div>


</body>

</html>