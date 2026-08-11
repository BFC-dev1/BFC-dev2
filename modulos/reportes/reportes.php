<?php
session_start();

if(!isset($_SESSION['usuario'])){
    header("Location: /BFC-dev2/auth/login.php");
    exit;
}

include(__DIR__ . "/../../modulos/conexion_modulos.php");


/* =========================================================
   TIPO DE REPORTE

   Recibe el tipo enviado desde el submenú de Reportes:

   ?tipo=usuarios
   ?tipo=deportistas
   ?tipo=asistencias

   Si no se recibe ningún tipo, se muestran los usuarios
   como opción predeterminada.
========================================================= */

$tipo = $_GET['tipo'] ?? 'usuarios';


/* =========================================================
   VALIDACIÓN

   Solo permitimos los tres tipos de reporte definidos.
   Esto evita recibir valores inesperados por la URL.
========================================================= */

$tiposPermitidos = [
    'usuarios',
    'deportistas',
    'asistencias'
];


if (!in_array($tipo, $tiposPermitidos, true)) {

    $tipo = 'usuarios';

}
?>

<?php include("../../includes/header_dashboard.php"); ?>
<?php include("../Dashboard/sidebar.php"); ?>

<div class="container py-4">

<!-- BOTON VOLVER -->
<div class="d-flex align-items-center mb-3">

    <a
        href="../dashboard/index.php"
        class="btn btn-outline-dark"
    >
        <i class="fa-solid fa-arrow-left me-2"></i>
        Volver al Dashboard
    </a>

</div>

<!-- =========================================================
     REPORTE DE DEPORTISTAS

     Esta sección solamente aparece cuando:

     reportes.php?tipo=deportistas
========================================================= -->

<?php if ($tipo === 'deportistas'): ?>

<div class="card border-0 rounded-4 p-4 mb-4 shadow-sm">

    <h5 class="fw-bold mb-3">

        <i class="fa-solid fa-person-running me-2 text-success"></i>

        Reporte de Deportistas

    </h5>


    <p class="text-muted mb-4">

        Exporta la información de los deportistas registrados.

    </p>


    <div class="row g-3">


        <!-- =================================================
             FILTRO DE ESTADO
        ================================================== -->

        <div class="col-md-4">

            <label class="fw-semibold mb-2">

                Estado

            </label>


            <select
                name="estado"
                class="form-select rounded-3"
            >

                <option value="">
                    Todos
                </option>

                <option value="1">
                    Activos
                </option>

                <option value="0">
                    Inactivos
                </option>

            </select>

        </div>


        <!-- =================================================
             FILTRO DE CATEGORÍA
        ================================================== -->

        <div class="col-md-4">

            <label class="fw-semibold mb-2">

                Categoría

            </label>


            <select
                name="categoria_id"
                class="form-select rounded-3"
            >

                <option value="">
                    Todas
                </option>

                <?php

                $cats = $conexion->query(
                    "SELECT id, nombre FROM categoria"
                );


                while (
                    $c = $cats->fetch(PDO::FETCH_ASSOC)
                ):

                ?>

                    <option value="<?= $c['id'] ?>">

                        <?= htmlspecialchars($c['nombre']) ?>

                    </option>

                <?php endwhile; ?>

            </select>

        </div>


        <!-- =================================================
             BOTÓN EXPORTAR
        ================================================== -->

        <div class="col-md-4 d-flex align-items-end">

            <a
                href="export_deportistas.php"
                class="btn btn-dark rounded-pill w-100"
            >

                <i class="fa-solid fa-file-export me-2"></i>

                Exportar deportistas

            </a>

        </div>


    </div>

</div>

<?php endif; ?>

<!-- =========================================================
     REPORTE DE USUARIOS

     Esta sección solamente aparece cuando:

     reportes.php?tipo=usuarios
========================================================= -->

<?php if ($tipo === 'usuarios'): ?>

<div class="card border-0 rounded-4 p-4 mb-4 shadow-sm">

    <h5 class="fw-bold mb-3">

        <i class="fa-solid fa-users me-2 text-dark"></i>

        Reporte de Usuarios

    </h5>


    <p class="text-muted mb-4">

        Descarga el listado de usuarios registrados.

    </p>


    <!-- =====================================================
         FILTRO DE ESTADO

         Por ahora dejamos:
         - Todos
         - Activos
         - Inactivos

         Posteriormente conectamos este filtro
         con la consulta de exportación.
    ====================================================== -->

    <div class="row g-3">


        <div class="col-md-6">

            <label class="fw-semibold mb-2">

                Estado

            </label>


            <select
                name="estado"
                class="form-select rounded-3"
            >

                <option value="">Todos</option>

                <option value="1">
                    Activos
                </option>

                <option value="0">
                    Inactivos
                </option>

            </select>

        </div>


        <!-- =================================================
             BOTÓN EXPORTAR
        ================================================== -->

        <div class="col-md-6 d-flex align-items-end">

            <a
                href="export_usuarios.php"
                class="btn btn-dark rounded-pill w-100"
            >

                <i class="fa-solid fa-file-export me-2"></i>

                Exportar usuarios

            </a>

        </div>


    </div>

</div>

<?php endif; ?>


<!-- =========================================================
     REPORTE DE ASISTENCIAS

     Esta sección solamente aparece cuando:

     reportes.php?tipo=asistencias
========================================================= -->

<?php if ($tipo === 'asistencias'): ?>

<div class="card border-0 rounded-4 p-4 shadow-sm">

        <h5 class="fw-bold mb-3">
            <i class="fa-solid fa-calendar-check me-2 text-warning"></i>
            Asistencias
        </h5>

        <p class="text-muted mb-4">
            Genera reportes filtrados por fecha y categoría.
        </p>

        <form method="GET" action="export_asistencias.php" class="row g-3">

            <!-- FECHA -->
            <div class="col-md-4">

                <label class="fw-semibold mb-2">
                    Fecha
                </label>

                <input 
                    type="date" 
                    name="fecha" 
                    class="form-control rounded-3" 
                    required
                >

            </div>

            <!-- CATEGORIA -->
            <div class="col-md-4">

                <label class="fw-semibold mb-2">
                    Categoría
                </label>

                <select name="categoria_id" class="form-control rounded-3">

                    <option value="">Todas</option>

                    <?php
                    $cats = $conexion->query("SELECT id, nombre FROM categoria");

                    while($c = $cats->fetch(PDO::FETCH_ASSOC)){

                        echo "
                            <option value='{$c['id']}'>
                                {$c['nombre']}
                            </option>
                        ";
                    }
                    ?>

                </select>

            </div>

            <!-- BOTON -->
            <div class="col-md-4 d-flex align-items-end">

                <button class="btn btn-success w-100 rounded-pill">

                    <i class="fa-solid fa-file-export me-2"></i>
                    Exportar asistencias

                </button>

            </div>

        </form>

    </div>

</div>

<?php endif; ?>

<?php include("../../includes/footer_dashboard.php"); ?>