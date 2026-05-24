<?php 
$url_base="http://localhost/BFC-dev2/"; 
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

    <!-- CSS Dashboard -->
    <link 
        rel="stylesheet" 
        href="<?php echo $url_base; ?>assets/dashboard.css"
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

<script>

document.addEventListener("DOMContentLoaded", function(){

    const toggleBtn = document.getElementById("menuToggle");
    const sidebar = document.querySelector(".sidebar");
    const overlay = document.getElementById("overlay");
    const content = document.querySelector(".main-content");

    // VALIDAR SI EXISTE EL BOTON
    if(toggleBtn){

        toggleBtn.addEventListener("click", function(){

            sidebar.classList.toggle("active");
            sidebar.classList.toggle("oculto");

            if(content){
                content.classList.toggle("expandido");
            }

            overlay.classList.toggle("active");

        });

    }

    // CERRAR SIDEBAR
    overlay.addEventListener("click", function(){

        sidebar.classList.remove("active");
        sidebar.classList.remove("oculto");

        if(content){
            content.classList.remove("expandido");
        }

        overlay.classList.remove("active");

    });

});

</script>