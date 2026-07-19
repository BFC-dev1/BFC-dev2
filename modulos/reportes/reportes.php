<?php
session_start();

if(!isset($_SESSION['usuario'])){
    header("Location: /BFC-dev2/auth/login.php");
    exit;
}

include(__DIR__ . "/../../modulos/conexion_modulos.php");
?>

<?php include("../../includes/header_dashboard.php"); ?>
<?php include("../Dashboard/sidebar.php"); ?>

<div class="container py-4">

    <!-- BOTON VOLVER -->
    <div class="d-flex align-items-center mb-3">

        <a
            href="http://localhost/BFC-dev2/modulos/dashboard/index.php"
            class="btn btn-outline-dark"
        >
            <i class="fa-solid fa-arrow-left me-2"></i>
            Volver al Dashboard
        </a>

    </div>

    <!-- TITULO -->
    <div class="mb-4">

        <h3 class="fw-bold mb-1">
            <i class="fa-solid fa-chart-column me-2 text-primary"></i>
            Centro de Reportes
        </h3>

        <p class="text-muted">
            Exportación y gestión de información del sistema.
        </p>

    </div>

    <!-- ================= USUARIOS ================= -->
    <div class="card border-0 rounded-4 p-4 mb-4 shadow-sm">

        <h5 class="fw-bold mb-3">
            <i class="fa-solid fa-users me-2 text-dark"></i>
            Usuarios
        </h5>

        <p class="text-muted mb-3">
            Descarga el listado completo de usuarios registrados.
        </p>

        <a href="export_usuarios.php" class="btn btn-dark rounded-pill">

            <i class="fa-solid fa-file-export me-2"></i>
            Exportar usuarios

        </a>

    </div>

    <!-- ================= DEPORTISTAS ================= -->
    <div class="card border-0 rounded-4 p-4 mb-4 shadow-sm">

        <h5 class="fw-bold mb-3">
            <i class="fa-solid fa-person-running me-2 text-success"></i>
            Deportistas
        </h5>

        <p class="text-muted mb-3">
            Exporta la información de todos los deportistas registrados.
        </p>

        <a href="export_deportistas.php" class="btn btn-dark rounded-pill">

            <i class="fa-solid fa-file-export me-2"></i>
            Exportar deportistas

        </a>

    </div>

    <!-- ================= ASISTENCIAS ================= -->
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

<?php include("../../includes/footer_dashboard.php"); ?>