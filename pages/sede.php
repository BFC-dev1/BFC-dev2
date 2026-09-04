<?php

/*
=================================================
CONFIGURACIÓN GENERAL
=================================================
*/

require_once(__DIR__ . "/../includes/config.php");


/*
=================================================
CONEXIÓN BASE DE DATOS CMS
=================================================
*/

include(__DIR__ . "/../includes/conexion_BDcms.php");


/*
=================================================
CONSULTAR SEDE
=================================================
*/

$stmt = $conexion->prepare("
    SELECT *
    FROM cms_sede
    WHERE activo = 1
    LIMIT 1
");

$stmt->execute();

$sede = $stmt->fetch(PDO::FETCH_ASSOC);


/*
=================================================
EVITAR ERRORES
SI NO EXISTE REGISTRO
=================================================
*/

if(!$sede){

    $sede = [

        "titulo" => "Nuestra Sede",

        "nombre" => "Bellavista FC",

        "descripcion" => "",

        "direccion" => "",

        "lugar_entrenamiento" => "",

        "horario" => "",

        "latitud" => "",

        "longitud" => "",

        "mapa_url" => ""

    ];

}

?>

<!DOCTYPE html>

<html lang="es">

<head>


    <!-- ===================================== -->
    <!-- CONFIGURACIÓN -->
    <!-- ===================================== -->

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >


    <!-- ===================================== -->
    <!-- TÍTULO -->
    <!-- ===================================== -->

    <title>
        <?php echo htmlspecialchars($sede['titulo']); ?>
        - Bellavista FC
    </title>


    <!-- ===================================== -->
    <!-- FAVICON -->
    <!-- ===================================== -->

    <link
        rel="icon"
        type="image/x-icon"
        href="<?= $favicon_url ?>"
    >


    <!-- ===================================== -->
    <!-- CSS PRINCIPAL -->
    <!-- ===================================== -->

    <link
        rel="stylesheet"
        href="<?= $css_url ?>/estilo.css"
    >


    <!-- ===================================== -->
    <!-- FONT AWESOME -->
    <!-- ===================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    >


    <!-- ===================================== -->
    <!-- LEAFLET -->
    <!-- ===================================== -->

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >


</head>


<body>


<?php include(__DIR__ . "/../includes/header.php"); ?>


<!-- ===================================== -->
<!-- BANNER -->
<!-- ===================================== -->

<section class="banner-contacto">

    <h1>

        <?php
        echo htmlspecialchars($sede['titulo']);
        ?>

    </h1>

</section>


<!-- ===================================== -->
<!-- CONTENIDO -->
<!-- ===================================== -->

<section class="contenedor-contacto">

    <div class="card-contacto">


        <!-- ================================= -->
        <!-- NOMBRE -->
        <!-- ================================= -->

        <h2>

            <?php
            echo htmlspecialchars($sede['nombre']);
            ?>

        </h2>


        <!-- ================================= -->
        <!-- DESCRIPCIÓN -->
        <!-- ================================= -->

        <p>

            <?php

            echo nl2br(
                htmlspecialchars(
                    $sede['descripcion']
                )
            );

            ?>

        </p>


        <div class="info-contacto">


            <!-- ================================= -->
            <!-- DIRECCIÓN -->
            <!-- ================================= -->

            <h3>

                <i class="fa-solid fa-location-dot"></i>

                Dirección

            </h3>


            <p>

                <?php

                echo htmlspecialchars(
                    $sede['direccion']
                );

                ?>

            </p>



            <!-- ================================= -->
            <!-- LUGAR DE ENTRENAMIENTO -->
            <!-- ================================= -->

            <h3>

                <i class="fa-solid fa-futbol"></i>

                Lugar de entrenamiento

            </h3>


            <p>

                <?php

                echo htmlspecialchars(
                    $sede['lugar_entrenamiento']
                );

                ?>

            </p>



            <!-- ================================= -->
            <!-- HORARIOS -->
            <!-- ================================= -->

            <h3>

                <i class="fa-regular fa-clock"></i>

                Horarios de entrenamiento

            </h3>


            <p>

                <?php

                echo nl2br(
                    htmlspecialchars(
                        $sede['horario']
                    )
                );

                ?>

            </p>



            <!-- ================================= -->
            <!-- MAPA -->
            <!-- ================================= -->

            <h3>

                <i class="fa-solid fa-map-location-dot"></i>

                Ubicación

            </h3>


            <?php

            if(
                !empty($sede['latitud']) &&
                !empty($sede['longitud'])
            ):

            ?>


                <!-- ================================= -->
                <!-- CONTENEDOR MAPA -->
                <!-- ================================= -->

                <div
                    id="mapa-sede"
                    style="
                        width: 100%;
                        height: 400px;
                        border-radius: 12px;
                        margin-top: 15px;
                        overflow: hidden;
                    "
                ></div>


                <!-- ================================= -->
                <!-- LEAFLET JAVASCRIPT -->
                <!-- ================================= -->

                <script
                    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">
                </script>


                <script>


                    /*
                    =================================
                    COORDENADAS
                    =================================
                    */

                    const latitud =
                        <?php echo (float)$sede['latitud']; ?>;


                    const longitud =
                        <?php echo (float)$sede['longitud']; ?>;



                    /*
                    =================================
                    CREAR MAPA
                    =================================
                    */

                    const mapa = L.map(
                        'mapa-sede'
                    ).setView(
                        [
                            latitud,
                            longitud
                        ],
                        16
                    );



                    /*
                    =================================
                    MAPA OPENSTREETMAP
                    =================================
                    */

                    L.tileLayer(
                        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                        {

                            attribution:
                                '&copy; OpenStreetMap contributors'

                        }
                    ).addTo(mapa);



                    /*
                    =================================
                    MARCADOR
                    =================================
                    */

                    const marcador = L.marker([

                        latitud,
                        longitud

                    ]).addTo(mapa);



/*
=================================
INFORMACIÓN DEL MARCADOR
=================================
*/

marcador.bindPopup(

    "<strong>" +
    <?= json_encode($sede['nombre'], JSON_UNESCAPED_UNICODE); ?> +
    "</strong><br>" +
    <?= json_encode($sede['lugar_entrenamiento'], JSON_UNESCAPED_UNICODE); ?>

).openPopup();


                </script>


            <?php else: ?>


                <p>

                    <i class="fa-solid fa-map"></i>

                    La ubicación en el mapa está pendiente de configurar.

                </p>


            <?php endif; ?>


        </div>

    </div>

</section>



<!-- ===================================== -->
<!-- REDES SOCIALES -->
<!-- ===================================== -->

<section class="redes">

    <h3>

        Síguenos

    </h3>


    <div class="iconos">


        <!-- FACEBOOK -->

        <a
            href="https://www.facebook.com/BellavistaFC"
            target="_blank"
            title="Facebook"
        >

            <i class="fa-brands fa-facebook-f"></i>

        </a>



        <!-- WHATSAPP -->

        <a
            href="https://wa.me/573001234567"
            target="_blank"
            title="WhatsApp"
        >

            <i class="fa-brands fa-whatsapp"></i>

        </a>



        <!-- YOUTUBE -->

        <a
            href="#"
            title="YouTube"
        >

            <i class="fa-brands fa-youtube"></i>

        </a>


    </div>

</section>



<!-- ===================================== -->
<!-- FOOTER -->
<!-- ===================================== -->

<footer class="footer">

    <?php include(__DIR__ . "/../includes/footer.php"); ?>

</footer>


</body>

</html>