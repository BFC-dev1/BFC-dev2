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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS Dashboard -->
    <link rel="stylesheet" href="<?php echo $url_base; ?>assets/css/dashboard.css">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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

    toggleBtn.addEventListener("click", function(){

        sidebar.classList.toggle("active");
        sidebar.classList.toggle("oculto");

        if(content){
            content.classList.toggle("expandido");
        }

        overlay.classList.toggle("active");
    });

    overlay.addEventListener("click", function(){
        sidebar.classList.remove("active");
        sidebar.classList.remove("oculto");

        if(content){
            content.classList.remove("expandido");
        }

        overlay.classList.remove("active");
    });

});
<<<<<<< HEAD
</script>
<<<<<<< HEAD
=======
</script>
>>>>>>> 1db6aaf8a5476ce21e7c33f0213aaf15de3f5d62
=======
.
>>>>>>> parent of 30e9da1 (test)
