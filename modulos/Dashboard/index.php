<?php
/*
=================================================
VERIFICAR SESIÓN DEL USUARIO
=================================================
*/
require_once(__DIR__ . "/../../includes/verificar_roles.php");

/*
=================================================
CONEXIÓN A BASE DE DATOS
=================================================
*/
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
ENTRENADORES
=========================
*/
$stmt = $conexion->prepare("
    SELECT COUNT(*) AS total 
    FROM usuario u 
    INNER JOIN rol r ON u.rol_id = r.id 
    WHERE LOWER(r.nombre) = 'entrenador'
");
$stmt->execute();
$totalEntrenadores = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conexion->prepare("
    SELECT COUNT(*) AS total 
    FROM usuario u 
    INNER JOIN rol r ON u.rol_id = r.id 
    WHERE LOWER(r.nombre) = 'entrenador' AND u.estado = 'activo'
");
$stmt->execute();
$entrenadoresActivos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

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
<?php include(__DIR__ . "/sidebar.php"); ?>

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

        <div class="row g-4 mb-4">
            <!-- TOTAL -->
            <div class="col-12 col-sm-6 col-md-4">
                <div class="metric-card">
                    <div class="metric-icon bg-soft-primary text-primary">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="metric-title">Total usuarios</div>
                    <div class="metric-value"><?php echo $totalUsuarios; ?></div>
                </div>
            </div>

            <!-- ACTIVOS -->
            <div class="col-12 col-sm-6 col-md-4">
                <div class="metric-card">
                    <div class="metric-icon bg-soft-success text-success">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div class="metric-title">Usuarios activos</div>
                    <div class="metric-value text-success"><?php echo $usuariosActivos; ?></div>
                </div>
            </div>

            <!-- INACTIVOS -->
            <div class="col-12 col-sm-6 col-md-4">
                <div class="metric-card">
                    <div class="metric-icon bg-soft-danger text-danger">
                        <i class="fa-solid fa-user-xmark"></i>
                    </div>
                    <div class="metric-title">Usuarios inactivos</div>
                    <div class="metric-value text-danger"><?php echo $usuariosInactivos; ?></div>
                </div>
            </div>
        </div>

        <!-- =========================
        ENTRENADORES
        ========================= -->
        <div class="section-title">
            <i class="fa-solid fa-stopwatch text-warning"></i>
            Entrenadores
        </div>

        <div class="row g-4 mb-4">
            <!-- TOTAL -->
            <div class="col-12 col-sm-6 col-md-6">
                <div class="metric-card">
                    <div class="metric-icon bg-soft-warning text-warning">
                        <i class="fa-solid fa-user-ninja"></i>
                    </div>
                    <div class="metric-title">Total entrenadores</div>
                    <div class="metric-value"><?php echo $totalEntrenadores; ?></div>
                </div>
            </div>

            <!-- ACTIVOS -->
            <div class="col-12 col-sm-6 col-md-6">
                <div class="metric-card">
                    <div class="metric-icon bg-soft-success text-success">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="metric-title">Entrenadores activos</div>
                    <div class="metric-value text-success"><?php echo $entrenadoresActivos; ?></div>
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
            <div class="col-12 col-sm-6 col-md-4">
                <div class="metric-card">
                    <div class="metric-icon bg-soft-dark text-dark">
                        <i class="fa-solid fa-person-running"></i>
                    </div>
                    <div class="metric-title">Total deportistas</div>
                    <div class="metric-value"><?php echo $totalDeportistas; ?></div>
                </div>
            </div>

            <!-- ACTIVOS -->
            <div class="col-12 col-sm-6 col-md-4">
                <div class="metric-card">
                    <div class="metric-icon bg-soft-success text-success">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="metric-title">Deportistas activos</div>
                    <div class="metric-value text-success"><?php echo $deportistasActivos; ?></div>
                </div>
            </div>

            <!-- INACTIVOS -->
            <div class="col-12 col-sm-6 col-md-4">
                <div class="metric-card">
                    <div class="metric-icon bg-soft-danger text-danger">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>
                    <div class="metric-title">Deportistas inactivos</div>
                    <div class="metric-value text-danger"><?php echo $deportistasInactivos; ?></div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include(__DIR__ . "/../../includes/footer_dashboard.php"); ?>