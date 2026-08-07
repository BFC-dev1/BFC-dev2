<?php

/*
=================================================
CARGAR CONFIGURACIÓN GENERAL

Importa la variable $url_base definida en:

includes/config.php

Con esto todos los recursos del Dashboard
(CSS, imágenes, JavaScript, etc.) funcionarán
automáticamente tanto en:

LOCAL:
http://localhost/BFC-dev2/

WEB:
https://bellavistafcdev.page.gd/

sin modificar el código.
=================================================
*/

require_once(__DIR__ . "/config.php");

?>

<!doctype html>
<html lang="es">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Dashboard | Bellavista FC</title>

    <!-- Bootstrap -->
    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" 
        rel="stylesheet"
    >

<!--
=================================================
HOJA DE ESTILOS DEL DASHBOARD

La carpeta de estilos se obtiene desde la
configuración general del sistema.

LOCAL:
assets/dashboard.css

WEB:
style/dashboard.css
=================================================
-->

<link
    rel="stylesheet"
    href="<?= $url_base ?>/<?= $css_base ?>/dashboard.css"
>

    <!-- Bootstrap Icons -->
    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" 
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link 
        rel="stylesheet" 
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    >

</head>

<body>

<!-- OVERLAY -->
<div class="overlay" id="overlay"></div>

<!-- CONTENEDOR GENERAL -->
<div class="d-flex">
