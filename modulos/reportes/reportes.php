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

<div class="container">

    <h3 class="fw-bold mb-4">📊 Centro de Reportes</h3>

    <!-- ================= USUARIOS ================= -->
    <div class="card p-3 mb-3 shadow-sm">
        <h5>👤 Usuarios</h5>
        <a href="export_usuarios.php" class="btn btn-dark mt-2">
            📤 Exportar usuarios
        </a>
    </div>

    <!-- ================= DEPORTISTAS ================= -->
    <div class="card p-3 mb-3 shadow-sm">
        <h5>🏃 Deportistas</h5>
        <a href="export_deportistas.php" class="btn btn-dark mt-2">
            📤 Exportar deportistas
        </a>
    </div>

    <!-- ================= ASISTENCIAS ================= -->
    <div class="card p-3 shadow-sm">
        <h5>📅 Asistencias</h5>

        <form method="GET" action="export_asistencias.php" class="row g-2 mt-2">

            <div class="col-md-4">
                <label>Fecha</label>
                <input type="date" name="fecha" class="form-control" required>
            </div>

            <div class="col-md-4">
                <label>Categoría</label>
                <select name="categoria_id" class="form-control">
                    <option value="">Todas</option>

                    <?php
                    $cats = $conexion->query("SELECT id, nombre FROM categoria");
                    while($c = $cats->fetch(PDO::FETCH_ASSOC)){
                        echo "<option value='{$c['id']}'>{$c['nombre']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-success w-100">
                    📤 Exportar asistencias
                </button>
            </div>

        </form>

    </div>

</div>

<?php include("../../includes/footer_dashboard.php"); ?>