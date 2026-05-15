<?php
session_start();

if(!isset($_SESSION['usuario'])){
    header("Location: /BFC-dev2/auth/login.php");
    exit;
}
?>

<?php include("../../includes/header_dashboard.php") ?>
<?php include("../../modulos/Dashboard/sidebar.php") ?>

<div class="mb-4">
    <h3 class="fw-bold">Panel de Administración</h3>
    <p class="text-muted">Resumen general del sistema</p>
</div>

<div class="row g-3">

    <!-- USUARIOS -->
    <div class="col-12 col-sm-6 col-md-3">
        <a href="usuarios.php" class="text-decoration-none text-dark">
            <div class="card shadow-sm border-0 hover-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Usuarios</h6>
                            <h4 class="fw-bold">120</h4>
                        </div>
                        <div class="bg-primary text-white p-3 rounded">
                            👤
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- DEPORTISTAS -->
    <div class="col-12 col-sm-6 col-md-3">
        <a href="<?php echo $url_base; ?>modulos/deportistas/" class="text-decoration-none text-dark">
            <div class="card shadow-sm border-0 hover-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Deportistas</h6>
                            <h4 class="fw-bold">85</h4>
                        </div>
                        <div class="bg-success text-white p-3 rounded">
                            🏃
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- PAGOS -->
    <div class="col-12 col-sm-6 col-md-3">
        <a href="pagos.php" class="text-decoration-none text-dark">
            <div class="card shadow-sm border-0 hover-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Pagos</h6>
                            <h4 class="fw-bold">$2.500.000</h4>
                        </div>
                        <div class="bg-warning text-white p-3 rounded">
                            💰
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- ASISTENCIA -->
    <div class="col-12 col-sm-6 col-md-3">
        <a href="<?php echo $url_base; ?>modulos/asistencia/" class="text-decoration-none text-dark">
            <div class="card shadow-sm border-0 hover-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Asistencia</h6>
                            <h4 class="fw-bold">Control</h4>
                        </div>
                        <div class="bg-info text-white p-3 rounded">
                            📅
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- REPORTES -->
    <div class="col-12 col-sm-6 col-md-3">
        <a href="reportes.php" class="text-decoration-none text-dark">
            <div class="card shadow-sm border-0 hover-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Reportes</h6>
                            <h4 class="fw-bold">Ver</h4>
                        </div>
                        <div class="bg-dark text-white p-3 rounded">
                            📊
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

</div>

<?php include("../../includes/footer_dashboard.php") ?>
