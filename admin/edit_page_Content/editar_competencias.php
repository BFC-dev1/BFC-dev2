<?php

require_once("../../includes/verificar_roles.php");

permitirRoles([
    "admin"
]);

require_once("../../includes/config.php");

include(__DIR__ . "/../../includes/conexion_BDcms.php");


/*
=================================================
CONSULTAR COMPETENCIAS
=================================================
*/

$stmt = $conexion->prepare("
    SELECT *
    FROM cms_competencias
    ORDER BY orden ASC, id ASC
");

$stmt->execute();

$competencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Editar Competencias</title>


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

            Editar Competencias

        </h2>


        <?php if(empty($competencias)){ ?>

            <div class="alert alert-info">

                No hay competencias registradas.

            </div>

        <?php } ?>


        <?php foreach($competencias as $competencia){ ?>


            <div class="card mb-3 border">


                <div class="card-body">


                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">


                        <div>

                            <h4 class="mb-1">

                                <i class="fa-solid fa-trophy text-warning"></i>

                                <?php echo htmlspecialchars(
                                    $competencia['nombre'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </h4>


                            <p class="text-muted mb-2">

                                <?php
                                echo htmlspecialchars(
                                    $competencia['descripcion'] ?? "",
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                            </p>


                            <span
                                class="badge <?php echo $competencia['activo']
                                    ? 'bg-success'
                                    : 'bg-secondary'; ?>"
                            >

                                <?php echo $competencia['activo']
                                    ? 'Activa'
                                    : 'Inactiva'; ?>

                            </span>

                        </div>


                        <div class="d-flex gap-2 flex-wrap">


                            <!-- EDITAR -->

                            <a
                                href="editar_competencia.php?id=<?php echo $competencia['id']; ?>"
                                class="btn btn-primary"
                            >

                                <i class="fa-solid fa-pen-to-square me-1"></i>

                                Editar

                            </a>


                            <!-- FOTOS -->

                            <a
                                href="competencia_fotos.php?id=<?php echo $competencia['id']; ?>"
                                class="btn btn-success"
                            >

                                <i class="fa-solid fa-images me-1"></i>

                                Fotos

                            </a>


                            <!-- VIDEOS -->

                            <a
                                href="competencia_videos.php?id=<?php echo $competencia['id']; ?>"
                                class="btn btn-danger"
                            >

                                <i class="fa-solid fa-video me-1"></i>

                                Videos

                            </a>


                        </div>

                    </div>

                </div>

            </div>


        <?php } ?>


        <!-- VOLVER -->

        <div class="mt-4">

            <a
                href="<?= $url_base ?>/modulos/dashboard/index.php"
                class="btn btn-outline-secondary"
            >

                <i class="fa-solid fa-arrow-left me-2"></i>

                Volver

            </a>

        </div>


    </div>

</div>


</body>

</html>