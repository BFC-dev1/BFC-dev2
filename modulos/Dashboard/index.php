<?php
session_start();

if(!isset($_SESSION['usuario'])){
    header("Location: /BFC-dev2/auth/login.php");
    exit;
}

include(__DIR__ . "/../../modulos/conexion_modulos.php");

/*
=========================
USUARIOS
=========================
*/
$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM usuario");
$stmt->execute();
$totalUsuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM usuario WHERE estado = 'activo'");
$stmt->execute();
$usuariosActivos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM usuario WHERE estado = 'inactivo'");
$stmt->execute();
$usuariosInactivos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

/*
=========================
DEPORTISTAS
=========================
*/
$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM deportista");
$stmt->execute();
$totalDeportistas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM deportista WHERE estado = 'activo'");
$stmt->execute();
$deportistasActivos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM deportista WHERE estado = 'inactivo'");
$stmt->execute();
$deportistasInactivos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>

<?php include("../../includes/header_dashboard.php") ?>
<?php include("../../modulos/Dashboard/sidebar.php") ?>

<div class="main-content">

    <div class="container py-4">

        <!-- HEADER -->
        <div class="mb-5">

            <h3 class="fw-bold mb-1">

                <i class="fa-solid fa-shield-halved me-2 text-primary"></i>
                Panel de Administración

            </h3>

            <p class="text-muted">
                Resumen general y métricas del sistema Bellavista FC.
            </p>

        </div>

        <!-- =========================
        USUARIOS
        ========================= -->
        <div class="section-title">

            <i class="fa-solid fa-users text-primary"></i>
            Usuarios

        </div>

        <div class="row g-4">

            <!-- TOTAL -->
            <div class="col-md-4">

                <div class="metric-card">

                    <div class="metric-icon bg-soft-primary text-primary">

                        <i class="fa-solid fa-users"></i>

                    </div>

                    <div class="metric-title">
                        Total usuarios
                    </div>

                    <div class="metric-value">
                        <?php echo $totalUsuarios; ?>
                    </div>

                </div>

            </div>

            <!-- ACTIVOS -->
            <div class="col-md-4">

                <div class="metric-card">

                    <div class="metric-icon bg-soft-success text-success">

                        <i class="fa-solid fa-user-check"></i>

                    </div>

                    <div class="metric-title">
                        Usuarios activos
                    </div>

                    <div class="metric-value text-success">
                        <?php echo $usuariosActivos; ?>
                    </div>

                </div>

            </div>

            <!-- INACTIVOS -->
            <div class="col-md-4">

                <div class="metric-card">

                    <div class="metric-icon bg-soft-danger text-danger">

                        <i class="fa-solid fa-user-xmark"></i>

                    </div>

                    <div class="metric-title">
                        Usuarios inactivos
                    </div>

                    <div class="metric-value text-danger">
                        <?php echo $usuariosInactivos; ?>
                    </div>

                </div>

            </div>

        </div>

        <!-- =========================
        DEPORTISTAS
        ========================= -->
        <div class="section-title">

            <i class="fa-solid fa-person-running text-success"></i>
            Deportistas

        </div>

        <div class="row g-4">

            <!-- TOTAL -->
            <div class="col-md-4">

                <div class="metric-card">

                    <div class="metric-icon bg-soft-dark text-dark">

                        <i class="fa-solid fa-person-running"></i>

                    </div>

                    <div class="metric-title">
                        Total deportistas
                    </div>

                    <div class="metric-value">
                        <?php echo $totalDeportistas; ?>
                    </div>

                </div>

            </div>

            <!-- ACTIVOS -->
            <div class="col-md-4">

                <div class="metric-card">

                    <div class="metric-icon bg-soft-success text-success">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>

                    <div class="metric-title">
                        Deportistas activos
                    </div>

                    <div class="metric-value text-success">
                        <?php echo $deportistasActivos; ?>
                    </div>

                </div>

            </div>

            <!-- INACTIVOS -->
            <div class="col-md-4">

                <div class="metric-card">

                    <div class="metric-icon bg-soft-danger text-danger">

                        <i class="fa-solid fa-circle-xmark"></i>

                    </div>

                    <div class="metric-title">
                        Deportistas inactivos
                    </div>

                    <div class="metric-value text-danger">
                        <?php echo $deportistasInactivos; ?>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?php include("../../includes/footer_dashboard.php") ?>