<?php  

include("../../modulos/conexion_modulos.php");

include("crear_deportista.php");


// ✅ CONSULTAR DEPORTISTAS
$stm = $conexion->prepare("
    SELECT 
        d.*, 
        c.nombre AS categoria_nombre,

        -- ✅ ACUDIENTE
        ud.acudiente AS acudiente_nombre

    FROM deportista d

    LEFT JOIN categoria c 
        ON d.categoria_id = c.id

    LEFT JOIN usuario_deportista ud
        ON ud.deportista_id = d.id

    ORDER BY d.id DESC
");

$stm->execute();

$deportista = $stm->fetchAll(PDO::FETCH_ASSOC);



// ✅ ELIMINAR
if(isset($_GET['id'])){

    $txtid = (isset($_GET['id']) ? $_GET['id'] : "");

    $stm = $conexion->prepare("
    DELETE FROM deportista 
    WHERE id = :id
    ");

    $stm->bindParam(":id", $txtid);

    $stm->execute();

    echo "
    <script>
        window.location='index.php';
    </script>
    ";

    exit;

}



// ✅ CAMBIAR ESTADO
if(isset($_GET['estado'])){

    $id = $_GET['estado'];

    $stm = $conexion->prepare("
    SELECT estado
    FROM deportista
    WHERE id = :id
    ");

    $stm->execute([
        ":id"=>$id
    ]);

    $deportista_estado = $stm->fetch(PDO::FETCH_ASSOC);

    if($deportista_estado){

        $nuevo_estado = ($deportista_estado['estado'] == 'activo')
        ? 'inactivo'
        : 'activo';

        $update = $conexion->prepare("
        UPDATE deportista
        SET estado = :estado
        WHERE id = :id
        ");

        $update->execute([
            ":estado"=>$nuevo_estado,
            ":id"=>$id
        ]);

    }

    echo "
    <script>
        window.location='index.php';
    </script>
    ";

    exit;

}

?>


<?php include("../../template/header_modulos.php") ?>


<div class="d-flex align-items-center gap-2 mb-3">

    <a 
        href="http://localhost/BFC-dev2/modulos/dashboard/index.php" 
        class="btn btn-outline-dark"
    >
        ← Volver al Dashboard
    </a>

    <button 
        type="button" 
        class="btn btn-primary" 
        data-bs-toggle="modal" 
        data-bs-target="#create"
    >
        Crear Deportista
    </button>

</div> 


<table class="table table-bordered table-hover text-center align-middle">

    <thead class="table-dark">

        <tr>

            <th>Tipo de Documento</th>
            <th>Documento</th>
            <th>Teléfono</th>
            <th>Nombre</th>
            <th>Fecha</th>
            <th>Categoría</th>

            <!-- ✅ NUEVA COLUMNA -->
            <th>Entrenador</th>

            <th>Acudiente</th>
            <th>Estado</th>
            <th>Acciones</th>

        </tr>

    </thead>

    <tbody>

    <?php foreach($deportista as $deportista) { ?>

        <tr>

            <td>
                <?php echo $deportista['tipo_documento']; ?>
            </td>

            <td>
                <?php echo $deportista['documento']; ?>
            </td>

            <td>
                <?php echo $deportista['telefono']; ?>
            </td>

            <td>
                <?php echo $deportista['nombre']; ?>
            </td>

            <td>
                <?php echo $deportista['fecha_nacimiento']; ?>
            </td>

            <td>
                <?php echo $deportista['categoria_nombre']; ?>
            </td>

            <!-- ✅ MOSTRAR ENTRENADOR -->
            <td>
                <?php echo $deportista['entrenador']; ?>
            </td>

            <!-- ✅ ACUDIENTE -->
            <td>
                <?php echo $deportista['acudiente_nombre']; ?>
            </td>

            <!-- ✅ SWITCH ESTADO -->
            <td>

                <div class="form-check form-switch d-flex justify-content-center">

                    <input 
                        class="form-check-input"
                        type="checkbox"

                        <?php 
                        if($deportista['estado'] == 'activo'){
                            echo 'checked';
                        }
                        ?>

                        onclick="cambiarEstado(<?php echo $deportista['id']; ?>)"
                    >

                </div>

            </td>

            <td>

                <div class="d-flex justify-content-center gap-2">

                    <a 
                        href="editar.php?id=<?php echo $deportista['id']; ?>" 
                        class="btn btn-success btn-sm"
                    >
                        Editar
                    </a>

                    <!-- ✅ ELIMINAR -->
                    <a 
                        href="javascript:void(0)"
                        onclick="confirmarEliminacion(<?php echo $deportista['id']; ?>)"
                        class="btn btn-danger btn-sm"
                    >
                        Eliminar
                    </a>

                </div>

            </td>

        </tr>

    <?php } ?>

    </tbody>

</table>


<!-- ✅ SCRIPT ELIMINAR -->

<script>

function confirmarEliminacion(id){

    let confirmar = confirm("¿Seguro que deseas eliminar este deportista?");

    if(confirmar){

        window.location = "index.php?id=" + id;

    }

}


// ✅ CAMBIAR ESTADO
function cambiarEstado(id){

    window.location = "index.php?estado=" + id;

}

</script>


<?php include("../../template/footer_modulos.php") ?>