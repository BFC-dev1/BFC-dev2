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

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

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


/*
=================================================
VERIFICAR QUE EXISTA
=================================================
*/

if (!$competencia) {
    header("Location: editar_competencias.php");
    exit;
}


/*
=================================================
ACTUALIZAR COMPETENCIA
=================================================
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST['nombre'] ?? "");

    $descripcion = trim(
        $_POST['descripcion'] ?? ""
    );

    $orden = filter_input(
        INPUT_POST,
        'orden',
        FILTER_VALIDATE_INT
    );

    $activo = isset($_POST['activo']) ? 1 : 0;


    /*
    =============================================
    VALIDAR NOMBRE
    =============================================
    */

    if ($nombre === "") {

        $error = "El nombre de la competencia es obligatorio.";

    } else {

        /*
        =========================================
        SI NO SE ENVÍA ORDEN
        =========================================
        */

        if ($orden === false || $orden === null) {
            $orden = 0;
        }


        /*
        =========================================
        ACTUALIZAR
        =========================================
        */

        $stmtUpdate = $conexion->prepare("
            UPDATE cms_competencias
            SET
                nombre = :nombre,
                descripcion = :descripcion,
                activo = :activo,
                orden = :orden
            WHERE id = :id
        ");

        $stmtUpdate->execute([

            ":nombre" => $nombre,

            ":descripcion" => $descripcion,

            ":activo" => $activo,

            ":orden" => $orden,

            ":id" => $id

        ]);


        /*
        =========================================
        VOLVER CON MENSAJE
        =========================================
        */

        header(
            "Location: editar_competencia.php?id="
            . $id
            . "&ok=1"
        );

        exit;
    }
}


/*
=================================================
RECARGAR DATOS ACTUALIZADOS
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
        Editar Competencia
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

        <h2 class="mb-4">

            <i class="fa-solid fa-trophy"></i>

            Editar Competencia

        </h2>


        <!-- MENSAJE EXITOSO -->

        <?php if (isset($_GET['ok'])) { ?>

            <div class="alert alert-success">

                <i class="fa-solid fa-circle-check me-2"></i>

                Competencia actualizada correctamente.

            </div>

        <?php } ?>


        <!-- MENSAJE DE ERROR -->

        <?php if (isset($error)) { ?>

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


        <!-- FORMULARIO -->

        <form
            method="POST"
            action=""
        >


            <!-- NOMBRE -->

            <div class="mb-3">

                <label
                    for="nombre"
                    class="form-label"
                >

                    <strong>
                        Nombre de la competencia
                    </strong>

                </label>

                <input
                    type="text"
                    class="form-control"
                    id="nombre"
                    name="nombre"
                    value="<?php
                        echo htmlspecialchars(
                            $competencia['nombre'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    ?>"
                    maxlength="150"
                    required
                >

            </div>


            <!-- DESCRIPCION -->

            <div class="mb-3">

                <label
                    for="descripcion"
                    class="form-label"
                >

                    <strong>
                        Descripción
                    </strong>

                </label>

                <textarea
                    class="form-control"
                    id="descripcion"
                    name="descripcion"
                    rows="5"
                    maxlength="5000"
                ><?php
                    echo htmlspecialchars(
                        $competencia['descripcion'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    );
                ?></textarea>

            </div>


            <!-- ORDEN -->

            <div class="mb-3">

                <label
                    for="orden"
                    class="form-label"
                >

                    <strong>
                        Orden de aparición
                    </strong>

                </label>

                <input
                    type="number"
                    class="form-control"
                    id="orden"
                    name="orden"
                    value="<?php
                        echo htmlspecialchars(
                            $competencia['orden'] ?? 0,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    ?>"
                    min="0"
                >

                <div class="form-text">

                    Un número menor aparecerá primero.

                </div>

            </div>


            <!-- ESTADO -->

            <div class="mb-4 form-check">

                <input
                    type="checkbox"
                    class="form-check-input"
                    id="activo"
                    name="activo"
                    value="1"
                    <?php
                    echo !empty($competencia['activo'])
                        ? 'checked'
                        : '';
                    ?>
                >

                <label
                    class="form-check-label"
                    for="activo"
                >

                    <strong>
                        Competencia activa
                    </strong>

                </label>

            </div>


            <!-- BOTONES -->

            <button
                type="submit"
                class="btn btn-primary"
            >

                <i class="fa-solid fa-floppy-disk me-2"></i>

                Guardar Cambios

            </button>


            <a
                href="editar_competencias.php"
                class="btn btn-outline-secondary ms-2"
            >

                <i class="fa-solid fa-arrow-left me-2"></i>

                Volver

            </a>


        </form>


    </div>


</div>


</body>

</html>