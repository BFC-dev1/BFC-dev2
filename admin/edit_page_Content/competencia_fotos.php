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
CONFIGURACIÓN DE FOTOS
=================================================
*/

/*
Ruta física donde se guardarán las fotos
*/

$directorioFotos = __DIR__ . "/../../uploads/competencias/";


/*
Ruta pública para mostrar las fotos
*/

$urlFotos = $url_base . "/uploads/competencias/";


/*
Crear carpeta si no existe
*/

if (!is_dir($directorioFotos)) {

    mkdir(
        $directorioFotos,
        0755,
        true
    );
}


/*
=================================================
VARIABLES
=================================================
*/

$mensaje = "";
$error = "";


/*
=================================================
SUBIR FOTOS
=================================================
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['accion'])
    &&
    $_POST['accion'] === "subir"
) {

    if (
        !isset($_FILES['fotos'])
        ||
        empty($_FILES['fotos']['name'][0])
    ) {

        $error = "Selecciona al menos una foto.";

    } else {

        $permitidos = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        $cantidad = count(
            $_FILES['fotos']['name']
        );

        $subidas = 0;

        for ($i = 0; $i < $cantidad; $i++) {

            /*
            =====================================
            VERIFICAR ERROR DE SUBIDA
            =====================================
            */

            if (
                $_FILES['fotos']['error'][$i]
                !== UPLOAD_ERR_OK
            ) {

                continue;
            }


            /*
            =====================================
            TAMAÑO MÁXIMO: 5 MB
            =====================================
            */

            if (
                $_FILES['fotos']['size'][$i]
                > 5 * 1024 * 1024
            ) {

                continue;
            }


            /*
            =====================================
            DETECTAR TIPO REAL
            =====================================
            */

            $tmpName = $_FILES['fotos']['tmp_name'][$i];

            $finfo = finfo_open(
                FILEINFO_MIME_TYPE
            );

            $tipoMime = finfo_file(
                $finfo,
                $tmpName
            );

            finfo_close($finfo);


            if (
                !isset(
                    $permitidos[$tipoMime]
                )
            ) {

                continue;
            }


            /*
            =====================================
            GENERAR NOMBRE ÚNICO
            =====================================
            */

            $extension =
                $permitidos[$tipoMime];

            $nombreArchivo =
                'competencia_'
                . $id
                . '_'
                . date('Ymd_His')
                . '_'
                . bin2hex(random_bytes(6))
                . '.'
                . $extension;


            $rutaDestino =
                $directorioFotos
                . $nombreArchivo;


            /*
            =====================================
            MOVER ARCHIVO
            =====================================
            */

            if (
                move_uploaded_file(
                    $tmpName,
                    $rutaDestino
                )
            ) {

                /*
                =================================
                OBTENER ORDEN SIGUIENTE
                =================================
                */

                $stmtOrden = $conexion->prepare("
                    SELECT
                        COALESCE(
                            MAX(orden),
                            0
                        ) + 1
                    FROM cms_competencias_fotos
                    WHERE competencia_id = :competencia_id
                ");

                $stmtOrden->execute([
                    ":competencia_id" => $id
                ]);

                $orden = (int)
                    $stmtOrden->fetchColumn();


                /*
                =================================
                REGISTRAR EN BD
                =================================
                */

                $stmtInsert = $conexion->prepare("
                    INSERT INTO cms_competencias_fotos
                    (
                        competencia_id,
                        archivo,
                        descripcion,
                        orden,
                        activo
                    )
                    VALUES
                    (
                        :competencia_id,
                        :archivo,
                        :descripcion,
                        :orden,
                        1
                    )
                ");

                $stmtInsert->execute([

                    ":competencia_id" => $id,

                    ":archivo" =>
                        $nombreArchivo,

                    ":descripcion" => null,

                    ":orden" => $orden

                ]);


                $subidas++;
            }
        }


        /*
        =========================================
        MENSAJE
        =========================================
        */

        if ($subidas > 0) {

            header(
                "Location: competencia_fotos.php?id="
                . $id
                . "&subidas="
                . $subidas
            );

            exit;

        } else {

            $error =
                "No se pudo subir ninguna foto. "
                . "Verifica que sean JPG, PNG o WEBP "
                . "y que no superen los 5 MB.";
        }
    }
}


/*
=================================================
ACTUALIZAR INFORMACIÓN DE UNA FOTO
=================================================
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['accion'])
    &&
    $_POST['accion'] === "actualizar"
) {

    $fotoId = filter_input(
        INPUT_POST,
        'foto_id',
        FILTER_VALIDATE_INT
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


    if ($fotoId) {

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
            UPDATE cms_competencias_fotos
            SET
                descripcion = :descripcion,
                orden = :orden,
                activo = :activo
            WHERE
                id = :id
                AND competencia_id = :competencia_id
        ");

        $stmtUpdate->execute([

            ":descripcion" => $descripcion,

            ":orden" => $orden,

            ":activo" => $activo,

            ":id" => $fotoId,

            ":competencia_id" => $id

        ]);


        header(
            "Location: competencia_fotos.php?id="
            . $id
            . "&actualizada=1"
        );

        exit;
    }
}


/*
=================================================
ELIMINAR FOTO
=================================================
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['accion'])
    &&
    $_POST['accion'] === "eliminar"
) {

    $fotoId = filter_input(
        INPUT_POST,
        'foto_id',
        FILTER_VALIDATE_INT
    );


    if ($fotoId) {

        /*
        =========================================
        OBTENER ARCHIVO
        =========================================
        */

        $stmtFoto = $conexion->prepare("
            SELECT archivo
            FROM cms_competencias_fotos
            WHERE
                id = :id
                AND competencia_id = :competencia_id
            LIMIT 1
        ");

        $stmtFoto->execute([

            ":id" => $fotoId,

            ":competencia_id" => $id

        ]);

        $foto = $stmtFoto->fetch(
            PDO::FETCH_ASSOC
        );


        if ($foto) {

            /*
            =====================================
            ELIMINAR ARCHIVO FÍSICO
            =====================================
            */

            $rutaArchivo =
                $directorioFotos
                . $foto['archivo'];


            if (
                is_file($rutaArchivo)
            ) {

                unlink($rutaArchivo);
            }


            /*
            =====================================
            ELIMINAR REGISTRO
            =====================================
            */

            $stmtDelete = $conexion->prepare("
                DELETE FROM cms_competencias_fotos
                WHERE
                    id = :id
                    AND competencia_id = :competencia_id
            ");

            $stmtDelete->execute([

                ":id" => $fotoId,

                ":competencia_id" => $id

            ]);
        }


        header(
            "Location: competencia_fotos.php?id="
            . $id
            . "&eliminada=1"
        );

        exit;
    }
}


/*
=================================================
CONSULTAR FOTOS
=================================================
*/

$stmtFotos = $conexion->prepare("
    SELECT *
    FROM cms_competencias_fotos
    WHERE competencia_id = :competencia_id
    ORDER BY orden ASC, id ASC
");

$stmtFotos->execute([
    ":competencia_id" => $id
]);

$fotos = $stmtFotos->fetchAll(
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
        Fotos - <?php
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


    <style>

        .foto-card img {

            width: 100%;

            height: 220px;

            object-fit: cover;

            border-radius: 8px;

        }

    </style>

</head>


<body class="bg-light">


<div class="container py-5">


    <div class="card shadow p-4">


        <!-- TITULO -->

        <h2 class="mb-2">

            <i class="fa-solid fa-images"></i>

            Fotos de la competencia

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


        <!-- MENSAJE SUBIDA -->

        <?php if (isset($_GET['subidas'])) { ?>

            <div class="alert alert-success">

                <i class="fa-solid fa-circle-check me-2"></i>

                Se subieron correctamente
                <?php echo (int) $_GET['subidas']; ?>
                foto(s).

            </div>

        <?php } ?>


        <!-- MENSAJE ACTUALIZADA -->

        <?php if (isset($_GET['actualizada'])) { ?>

            <div class="alert alert-success">

                <i class="fa-solid fa-circle-check me-2"></i>

                Información de la foto actualizada correctamente.

            </div>

        <?php } ?>


        <!-- MENSAJE ELIMINADA -->

        <?php if (isset($_GET['eliminada'])) { ?>

            <div class="alert alert-success">

                <i class="fa-solid fa-circle-check me-2"></i>

                Foto eliminada correctamente.

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


        <!-- SUBIR FOTOS -->

        <div class="card mb-4">

            <div class="card-body">

                <h5 class="card-title">

                    <i class="fa-solid fa-upload me-2"></i>

                    Subir fotos

                </h5>


                <form
                    method="POST"
                    enctype="multipart/form-data"
                >

                    <input
                        type="hidden"
                        name="accion"
                        value="subir"
                    >


                    <div class="mb-3">

                        <label
                            for="fotos"
                            class="form-label"
                        >

                            Seleccionar fotos

                        </label>

                        <input
                            type="file"
                            class="form-control"
                            id="fotos"
                            name="fotos[]"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            required
                        >

                        <div class="form-text">

                            Puedes seleccionar varias fotos.
                            Formatos permitidos: JPG, PNG y WEBP.
                            Máximo 5 MB por foto.

                        </div>

                    </div>


                    <button
                        type="submit"
                        class="btn btn-success"
                    >

                        <i class="fa-solid fa-cloud-arrow-up me-2"></i>

                        Subir Fotos

                    </button>

                </form>

            </div>

        </div>


        <!-- FOTOS EXISTENTES -->

        <h4 class="mb-3">

            <i class="fa-solid fa-photo-film me-2"></i>

            Fotos registradas

        </h4>


        <?php if (empty($fotos)) { ?>

            <div class="alert alert-info">

                Esta competencia todavía no tiene fotos.

            </div>

        <?php } else { ?>


            <div class="row g-4">


                <?php foreach ($fotos as $foto) { ?>


                    <div class="col-12 col-md-6 col-lg-4">


                        <div class="card shadow-sm foto-card h-100">


                            <!-- IMAGEN -->

                            <img
                                src="<?php
                                    echo htmlspecialchars(
                                        $urlFotos
                                        . $foto['archivo'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                ?>"
                                alt="Foto de competencia"
                            >


                            <div class="card-body">


                                <!-- ESTADO -->

                                <div class="mb-3">

                                    <span
                                        class="badge <?php
                                        echo $foto['activo']
                                            ? 'bg-success'
                                            : 'bg-secondary';
                                        ?>"
                                    >

                                        <?php
                                        echo $foto['activo']
                                            ? 'Activa'
                                            : 'Inactiva';
                                        ?>

                                    </span>

                                </div>


                                <!-- EDITAR -->

                                <form method="POST">

                                    <input
                                        type="hidden"
                                        name="accion"
                                        value="actualizar"
                                    >

                                    <input
                                        type="hidden"
                                        name="foto_id"
                                        value="<?php
                                            echo (int)
                                                $foto['id'];
                                        ?>"
                                    >


                                    <!-- DESCRIPCIÓN -->

                                    <div class="mb-3">

                                        <label
                                            class="form-label"
                                        >

                                            Descripción

                                        </label>

                                        <input
                                            type="text"
                                            class="form-control"
                                            name="descripcion"
                                            value="<?php
                                                echo htmlspecialchars(
                                                    $foto['descripcion']
                                                        ?? '',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                );
                                            ?>"
                                            maxlength="255"
                                        >

                                    </div>


                                    <!-- ORDEN -->

                                    <div class="mb-3">

                                        <label
                                            class="form-label"
                                        >

                                            Orden

                                        </label>

                                        <input
                                            type="number"
                                            class="form-control"
                                            name="orden"
                                            value="<?php
                                                echo (int)
                                                    $foto['orden'];
                                            ?>"
                                            min="0"
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
                                                    $foto['id'];
                                            ?>"
                                            <?php
                                            echo $foto['activo']
                                                ? 'checked'
                                                : '';
                                            ?>
                                        >

                                        <label
                                            class="form-check-label"
                                            for="activo_<?php
                                                echo (int)
                                                    $foto['id'];
                                            ?>"
                                        >

                                            Foto activa

                                        </label>

                                    </div>


                                    <!-- GUARDAR -->

                                    <button
                                        type="submit"
                                        class="btn btn-primary w-100 mb-2"
                                    >

                                        <i class="fa-solid fa-floppy-disk me-2"></i>

                                        Guardar

                                    </button>

                                </form>


                                <!-- ELIMINAR -->

                                <form
                                    method="POST"
                                    onsubmit="return confirm(
                                        '¿Seguro que deseas eliminar esta foto?'
                                    );"
                                >

                                    <input
                                        type="hidden"
                                        name="accion"
                                        value="eliminar"
                                    >

                                    <input
                                        type="hidden"
                                        name="foto_id"
                                        value="<?php
                                            echo (int)
                                                $foto['id'];
                                        ?>"
                                    >


                                    <button
                                        type="submit"
                                        class="btn btn-outline-danger w-100"
                                    >

                                        <i class="fa-solid fa-trash me-2"></i>

                                        Eliminar

                                    </button>

                                </form>


                            </div>

                        </div>

                    </div>


                <?php } ?>


            </div>


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