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
FUNCIÓN PARA CONVERTIR URL DE YOUTUBE
A URL EMBED
=================================================
*/

function obtenerYoutubeEmbed($url)
{

    $url = trim($url);


    /*
    ==============================================
    YOUTUBE NORMAL
    https://www.youtube.com/watch?v=XXXXXXXX
    ==============================================
    */

    if(
        preg_match(
            '/youtube\.com\/watch\?v=([^&]+)/',
            $url,
            $coincidencia
        )
    ){

        return "https://www.youtube.com/embed/" . $coincidencia[1];

    }


    /*
    ==============================================
    YOUTUBE CORTO
    https://youtu.be/XXXXXXXX
    ==============================================
    */

    if(
        preg_match(
            '/youtu\.be\/([^?&]+)/',
            $url,
            $coincidencia
        )
    ){

        return "https://www.youtube.com/embed/" . $coincidencia[1];

    }


    /*
    ==============================================
    YOUTUBE SHORTS
    https://www.youtube.com/shorts/XXXXXXXX
    ==============================================
    */

    if(
        preg_match(
            '/youtube\.com\/shorts\/([^?&]+)/',
            $url,
            $coincidencia
        )
    ){

        return "https://www.youtube.com/embed/" . $coincidencia[1];

    }


    return null;

}


/*
=================================================
CONSULTAR COMPETENCIAS
=================================================
*/

$stmt = $conexion->prepare("
    SELECT *
    FROM cms_competencias
    WHERE activo = 1
    ORDER BY orden ASC, id ASC
");

$stmt->execute();

$competencias = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
=================================================
TÍTULO DE LA PÁGINA
=================================================
*/

$titulo_pagina = "Competencias";

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
        <?= htmlspecialchars($titulo_pagina); ?>
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


</head>


<body>


<?php include(__DIR__ . "/../includes/header.php"); ?>


<!-- ===================================== -->
<!-- BANNER -->
<!-- ===================================== -->

<section class="banner-contacto">

    <h1>

        <?= htmlspecialchars($titulo_pagina); ?>

    </h1>

</section>


<!-- ===================================== -->
<!-- CONTENIDO -->
<!-- ===================================== -->

<section class="contenedor-contacto">

    <div class="card-contacto">


        <?php if(empty($competencias)): ?>


            <!-- ================================= -->
            <!-- SIN COMPETENCIAS -->
            <!-- ================================= -->

            <h2>

                <i class="fa-solid fa-trophy"></i>

                Competencias

            </h2>


            <p>

                Actualmente no hay competencias registradas.

            </p>


        <?php else: ?>


            <?php foreach($competencias as $competencia): ?>


                <!-- ================================= -->
                <!-- COMPETENCIA -->
                <!-- ================================= -->

                <div class="info-contacto">


                    <!-- ================================= -->
                    <!-- NOMBRE -->
                    <!-- ================================= -->

                    <h2>

                        <i class="fa-solid fa-trophy"></i>

                        <?php

                        echo htmlspecialchars(
                            $competencia['nombre']
                        );

                        ?>

                    </h2>


                    <!-- ================================= -->
                    <!-- DESCRIPCIÓN -->
                    <!-- ================================= -->

                    <?php if(!empty($competencia['descripcion'])): ?>

                        <p>

                            <?php

                            echo nl2br(
                                htmlspecialchars(
                                    $competencia['descripcion']
                                )
                            );

                            ?>

                        </p>

                    <?php endif; ?>


                    <!-- ================================= -->
                    <!-- FOTOS -->
                    <!-- ================================= -->

                    <?php

                    $stmtFotos = $conexion->prepare("
                        SELECT *
                        FROM cms_competencias_fotos
                        WHERE competencia_id = :competencia_id
                        AND activo = 1
                        ORDER BY orden ASC, id ASC
                    ");

                    $stmtFotos->execute([

                        ":competencia_id" =>
                            $competencia['id']

                    ]);

                    $fotos = $stmtFotos->fetchAll(
                        PDO::FETCH_ASSOC
                    );

                    ?>


                    <?php if(!empty($fotos)): ?>


                        <!-- ================================= -->
                        <!-- TÍTULO FOTOS -->
                        <!-- ================================= -->

                        <h3>

                            <i class="fa-solid fa-images"></i>

                            Fotos

                        </h3>


                        <?php foreach($fotos as $foto): ?>


                            <!-- ================================= -->
                            <!-- FOTO -->
                            <!-- ================================= -->

                            <div>


                                <img
                                    class="media-full-width"
                                    src="<?= $url_base ?>/uploads/competencias/<?= htmlspecialchars($foto['archivo']); ?>"
                                    alt="<?= htmlspecialchars($foto['descripcion'] ?? $competencia['nombre']); ?>"
                                >


                                <?php if(!empty($foto['descripcion'])): ?>

                                    <p>

                                        <?= htmlspecialchars(
                                            $foto['descripcion']
                                        ); ?>

                                    </p>

                                <?php endif; ?>


                            </div>


                        <?php endforeach; ?>


                    <?php endif; ?>


                    <!-- ================================= -->
                    <!-- VIDEOS -->
                    <!-- ================================= -->

                    <?php

                    $stmtVideos = $conexion->prepare("
                        SELECT *
                        FROM cms_competencias_videos
                        WHERE competencia_id = :competencia_id
                        AND activo = 1
                        ORDER BY orden ASC, id ASC
                    ");

                    $stmtVideos->execute([

                        ":competencia_id" =>
                            $competencia['id']

                    ]);

                    $videos = $stmtVideos->fetchAll(
                        PDO::FETCH_ASSOC
                    );

                    ?>


                    <?php if(!empty($videos)): ?>


                        <!-- ================================= -->
                        <!-- TÍTULO VIDEOS -->
                        <!-- ================================= -->

                        <h3>

                            <i class="fa-solid fa-video"></i>

                            Videos

                        </h3>


                        <?php foreach($videos as $video): ?>


                            <?php

                            $youtubeEmbed =
                                obtenerYoutubeEmbed(
                                    $video['url']
                                );

                            ?>


                            <!-- ================================= -->
                            <!-- TÍTULO DEL VIDEO -->
                            <!-- ================================= -->

                            <?php if(!empty($video['titulo'])): ?>

                                <h4>

                                    <?= htmlspecialchars(
                                        $video['titulo']
                                    ); ?>

                                </h4>

                            <?php endif; ?>


<?php if($youtubeEmbed): ?>

    <!-- ================================= -->
    <!-- VIDEO YOUTUBE -->
    <!-- ================================= -->

    <div class="video-container">

        <iframe
            src="<?= htmlspecialchars($youtubeEmbed); ?>"
            title="<?= htmlspecialchars($video['titulo'] ?? 'Video de Bellavista FC'); ?>"
            allowfullscreen
        ></iframe>

    </div>

<?php else: ?>


                                <!-- ================================= -->
                                <!-- VIDEO EXTERNO -->
                                <!-- ================================= -->

                                <p>

                                    <a
                                        href="<?= htmlspecialchars($video['url']); ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >

                                        <i class="fa-solid fa-play"></i>

                                        Ver video

                                    </a>

                                </p>


                            <?php endif; ?>


                            <!-- ================================= -->
                            <!-- DESCRIPCIÓN VIDEO -->
                            <!-- ================================= -->

                            <?php if(!empty($video['descripcion'])): ?>

                                <p>

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $video['descripcion']
                                        )
                                    ); ?>

                                </p>

                            <?php endif; ?>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </div>


                <!-- ================================= -->
                <!-- SEPARACIÓN ENTRE COMPETENCIAS -->
                <!-- ================================= -->

                <?php if($competencia !== end($competencias)): ?>

                    <hr>

                <?php endif; ?>


            <?php endforeach; ?>


        <?php endif; ?>


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