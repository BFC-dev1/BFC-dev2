<?php

require_once("../../includes/verificar_roles.php");

permitirRoles([
    "admin"
]);

/*
=================================================
CARGAR CONFIGURACIÓN GENERAL

Importa:

$url_base

para que funcione en:

LOCAL:
http://localhost/BFC-dev2/

WEB:
https://bellavistafcdev.page.gd/
=================================================
*/

require_once("../../includes/config.php");

include(__DIR__ . "/../../includes/conexion_BDcms.php");


/*
=========================
CONSULTAR SEDE
=========================
*/

$stmt = $conexion->prepare("
    SELECT *
    FROM cms_sede
    WHERE id = 1
    LIMIT 1
");

$stmt->execute();

$sede = $stmt->fetch(PDO::FETCH_ASSOC);


/*
=========================
EVITAR ERRORES
SI NO EXISTE REGISTRO
=========================
*/

if(!$sede){

    $sede = [

        "id" => 1,

        "titulo" => "Nuestra Sede",

        "nombre" => "Bellavista FC",

        "descripcion" => "",

        "direccion" => "",

        "lugar_entrenamiento" => "",

        "horario" => "",

        "latitud" => "",

        "longitud" => "",

        "activo" => 1

    ];

}


/*
=========================
GUARDAR CAMBIOS
=========================
*/

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $titulo = trim($_POST['titulo'] ?? "");

    $nombre = trim($_POST['nombre'] ?? "");

    $descripcion = trim(
        $_POST['descripcion'] ?? ""
    );

    $direccion = trim(
        $_POST['direccion'] ?? ""
    );

    $lugar_entrenamiento = trim(
        $_POST['lugar_entrenamiento'] ?? ""
    );

    $horario = trim(
        $_POST['horario'] ?? ""
    );

    $latitud = trim(
        $_POST['latitud'] ?? ""
    );

    $longitud = trim(
        $_POST['longitud'] ?? ""
    );


    /*
    =========================================
    CONVERTIR CAMPOS VACÍOS A NULL
    =========================================
    */

    $latitud = ($latitud === "")
        ? null
        : $latitud;

    $longitud = ($longitud === "")
        ? null
        : $longitud;


    /*
    =========================================
    ACTUALIZAR SEDE
    =========================================
    */

    $stmtUpdate = $conexion->prepare("

        UPDATE cms_sede SET

            titulo = :titulo,

            nombre = :nombre,

            descripcion = :descripcion,

            direccion = :direccion,

            lugar_entrenamiento = :lugar_entrenamiento,

            horario = :horario,

            latitud = :latitud,

            longitud = :longitud

        WHERE id = 1

    ");


    $stmtUpdate->execute([

        ":titulo" => $titulo,

        ":nombre" => $nombre,

        ":descripcion" => $descripcion,

        ":direccion" => $direccion,

        ":lugar_entrenamiento" => $lugar_entrenamiento,

        ":horario" => $horario,

        ":latitud" => $latitud,

        ":longitud" => $longitud

    ]);


    /*
    =========================================
    VOLVER CON MENSAJE DE ÉXITO
    =========================================
    */

    header(
        "Location: editar_sede.php?ok=1"
    );

    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Editar Nuestra Sede</title>


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

            <i class="fa-solid fa-location-dot"></i>

            Editar Nuestra Sede

        </h2>


        <!-- ALERTA -->

        <?php if(isset($_GET['ok'])){ ?>

            <div class="alert alert-success">

                <i class="fa-solid fa-circle-check"></i>

                Información de la sede actualizada correctamente

            </div>

        <?php } ?>


        <form method="POST">


            <!-- TITULO -->

            <div class="mb-3">

                <label class="form-label fw-bold">

                    <i class="fa-solid fa-heading"></i>

                    Título

                </label>

                <input
                    type="text"
                    name="titulo"
                    class="form-control"
                    value="<?php echo htmlspecialchars($sede['titulo']); ?>"
                    required
                >

            </div>


            <!-- NOMBRE -->

            <div class="mb-3">

                <label class="form-label fw-bold">

                    <i class="fa-solid fa-building"></i>

                    Nombre

                </label>

                <input
                    type="text"
                    name="nombre"
                    class="form-control"
                    value="<?php echo htmlspecialchars($sede['nombre']); ?>"
                    placeholder="Ejemplo: Bellavista FC"
                    required
                >

            </div>


            <!-- DESCRIPCION -->

            <div class="mb-4">

                <label class="form-label fw-bold">

                    <i class="fa-solid fa-align-left"></i>

                    Descripción

                </label>

                <textarea
                    name="descripcion"
                    class="form-control"
                    rows="4"
                ><?php echo htmlspecialchars($sede['descripcion']); ?></textarea>

            </div>


            <hr>


            <!-- UBICACION -->

            <h5 class="mb-3">

                <i class="fa-solid fa-map-location-dot"></i>

                Información de ubicación

            </h5>


            <!-- DIRECCION -->

            <div class="input-group mb-3">

                <span class="input-group-text">

                    <i class="fa-solid fa-location-dot"></i>

                </span>

                <input
                    type="text"
                    name="direccion"
                    class="form-control"
                    value="<?php echo htmlspecialchars($sede['direccion']); ?>"
                    placeholder="Dirección de la sede"
                >

            </div>


            <!-- LUGAR DE ENTRENAMIENTO -->

            <div class="input-group mb-3">

                <span class="input-group-text">

                    <i class="fa-solid fa-futbol"></i>

                </span>

                <input
                    type="text"
                    name="lugar_entrenamiento"
                    class="form-control"
                    value="<?php echo htmlspecialchars($sede['lugar_entrenamiento']); ?>"
                    placeholder="Ejemplo: Cancha Municipal"
                >

            </div>


            <!-- HORARIOS -->

            <div class="mb-4">

                <label class="form-label fw-bold">

                    <i class="fa-regular fa-clock"></i>

                    Horarios de entrenamiento

                </label>

                <textarea
                    name="horario"
                    class="form-control"
                    rows="4"
                    placeholder="Ejemplo:
Lunes y miércoles: 4:00 PM - 6:00 PM
Martes y jueves: 4:00 PM - 6:00 PM"
                ><?php echo htmlspecialchars($sede['horario']); ?></textarea>

            </div>


            <hr>


            <!-- MAPA -->

            <h5 class="mb-3">

                <i class="fa-solid fa-map"></i>

                Coordenadas del mapa

            </h5>


            <p class="text-muted">

                Ingresa las coordenadas exactas de la sede.
                Estas se utilizarán para mostrar el marcador
                en el mapa de la página pública.

            </p>


            <!-- LATITUD -->

            <div class="input-group mb-3">

                <span class="input-group-text">

                    <i class="fa-solid fa-arrows-up-down"></i>

                </span>

                <input
                    type="text"
                    name="latitud"
                    class="form-control"
                    value="<?php echo htmlspecialchars($sede['latitud'] ?? ''); ?>"
                    placeholder="Ejemplo: 6.251840"
                >

            </div>


            <!-- LONGITUD -->

            <div class="input-group mb-4">

                <span class="input-group-text">

                    <i class="fa-solid fa-arrows-left-right"></i>

                </span>

                <input
                    type="text"
                    name="longitud"
                    class="form-control"
                    value="<?php echo htmlspecialchars($sede['longitud'] ?? ''); ?>"
                    placeholder="Ejemplo: -75.563590"
                >

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
                href="<?= $url_base ?>/modulos/dashboard/index.php"
                class="btn btn-outline-secondary ms-2"
            >

                <i class="fa-solid fa-arrow-left me-2"></i>

                Cancelar

            </a>


        </form>

    </div>

</div>


</body>

</html>