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

<style>
.metric-card{
    background:#fff;
    border-radius:14px;
    padding:18px;
    box-shadow:0 6px 20px rgba(0,0,0,0.05);
    transition:0.2s;
    border-left:6px solid transparent;
}

.metric-card:hover{
    transform:translateY(-3px);
    box-shadow:0 10px 25px rgba(0,0,0,0.08);
}

.metric-title{
    font-size:13px;
    color:#6c757d;
}

.metric-value{
    font-size:28px;
    font-weight:700;
}

.section-title{
    font-weight:700;
    margin:25px 0 15px;
    font-size:18px;
}
</style>

<div class="mb-4">
    <h3 class="fw-bold">Panel de Administración</h3>
    <p class="text-muted">Resumen general del sistema</p>
</div>

<!-- =========================
USUARIOS
========================= -->
<div class="section-title">👤 Usuarios</div>

<div class="row g-3">

    <div class="col-md-3">
        <div class="metric-card border-primary">
            <div class="metric-title">Total usuarios</div>
            <div class="metric-value"><?php echo $totalUsuarios; ?></div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="metric-card border-success">
            <div class="metric-title">Activos</div>
            <div class="metric-value text-success"><?php echo $usuariosActivos; ?></div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="metric-card border-danger">
            <div class="metric-title">Inactivos</div>
            <div class="metric-value text-danger"><?php echo $usuariosInactivos; ?></div>
        </div>
    </div>

</div>

<!-- =========================
DEPORTISTAS
========================= -->
<div class="section-title">🏃 Deportistas</div>

<div class="row g-3">

    <div class="col-md-3">
        <div class="metric-card border-dark">
            <div class="metric-title">Total deportistas</div>
            <div class="metric-value"><?php echo $totalDeportistas; ?></div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="metric-card border-success">
            <div class="metric-title">Activos</div>
            <div class="metric-value text-success"><?php echo $deportistasActivos; ?></div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="metric-card border-danger">
            <div class="metric-title">Inactivos</div>
            <div class="metric-value text-danger"><?php echo $deportistasInactivos; ?></div>
        </div>
    </div>

</div>

<?php include("../../includes/footer_dashboard.php") ?>