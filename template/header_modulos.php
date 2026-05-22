<?php $url_base="http://localhost/BFC-dev2/"; ?>

<!doctype html>
<html lang="en">

<head>

    <title>Title</title>

    <!-- META -->
    <meta charset="utf-8" />

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, shrink-to-fit=no"
    />

    <!-- BOOTSTRAP -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
        crossorigin="anonymous"
    />

    <!-- CSS HEADER -->
    <link 
        rel="stylesheet" 
        href="<?php echo $url_base; ?>assets/header_deportistas.css"
    >

</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand navbar-dark bg-dark">

        <ul class="nav navbar-nav">

            <li class="nav-item">

                <a 
                    class="nav-link active" 
                    href="#" 
                    aria-current="page"
                >
                    BELLAVISTAFC

                    <span class="visually-hidden">
                        (current)
                    </span>

                </a>

            </li>

            <li class="nav-item">

                <a 
                    class="nav-link" 
                    href="<?php echo $url_base;?>modulos/deportistas"
                >
                    Deportistas
                </a>

            </li>

        </ul>

    </nav>

    <!-- CONTENIDO -->
    <main class="container">

        <br><br>