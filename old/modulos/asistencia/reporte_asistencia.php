<?php include("../../includes/header_dashboard.php"); ?>
<?php include("../Dashboard/sidebar.php"); ?>
<?php include("../conexion_modulos.php"); ?>

<?php
$fecha = $_GET['fecha'] ?? date('Y-m-d');
?>

<div class="container">

    <h4 class="fw-bold mb-3">📊 Reporte de Asistencia</h4>

    <!-- FILTRO POR FECHA -->
    <form method="GET" class="mb-3">
        <input type="date" name="fecha" value="<?php echo $fecha; ?>" 
               class="form-control w-auto d-inline">
        <button class="btn btn-primary">Filtrar</button>
    </form>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Deportista</th>
                <th>Categoría</th>
                <th>Fecha</th>
                <th>Estado</th>
            </tr>
        </thead>

        <tbody>

        <?php
        $stmt = $conexion->prepare("
            SELECT 
                a.id,
                d.nombre AS deportista,
                c.nombre AS categoria,
                a.fecha,
                a.estado
            FROM asistencia a
            INNER JOIN deportista d ON a.deportista_id = d.id
            INNER JOIN categoria c ON d.categoria_id = c.id
            WHERE a.fecha = ?
            ORDER BY a.id DESC
        ");

        $stmt->execute([$fecha]);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if(empty($datos)){
            echo "<tr><td colspan='5' class='text-center'>No hay registros</td></tr>";
        }

        foreach($datos as $row){
        ?>

            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['deportista']; ?></td>
                <td><?php echo $row['categoria']; ?></td>
                <td><?php echo $row['fecha']; ?></td>

                <td>
                    <?php
                    if($row['estado']=="presente"){
                        echo "<span class='badge bg-success'>Presente</span>";
                    }elseif($row['estado']=="ausente"){
                        echo "<span class='badge bg-danger'>Ausente</span>";
                    }else{
                        echo "<span class='badge bg-warning text-dark'>Tarde</span>";
                    }
                    ?>
                </td>
            </tr>

        <?php } ?>

        </tbody>
    </table>

</div>

<?php include("../../includes/footer_dashboard.php"); ?>
