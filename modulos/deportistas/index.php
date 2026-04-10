<?php  
include("../../modulos/conexion_modulos.php");


include("crear_deportista.php");


$stm = $conexion->prepare("
    SELECT 
        d.*, 
        c.nombre AS categoria_nombre,
        (
            SELECT u.usuario 
            FROM usuario u
            INNER JOIN usuario_deportista ud 
                ON u.id = ud.usuario_id
            WHERE ud.deportista_id = d.id
            LIMIT 1
        ) AS acudiente_nombre
    FROM deportista d
    LEFT JOIN categoria c ON d.categoria_id = c.id
");
$stm->execute();
$deportista=$stm->fetchAll(PDO::FETCH_ASSOC);



if(isset($_GET['id'])){

$txtid=(isset($_GET['id'])?$_GET['id']:"");
$stm=$conexion->prepare("DELETE FROM deportista WHERE id=:id");
$stm->bindparam(":id",$txtid);
$stm->execute();
header("location:index.php");

}


?>


<?php include("../../template/header_modulos.php") ?>

<!-- Button trigger modal -->
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#create">
  Nuevo
</button>

<div class="table-responsive">
<table class="table text-center align-middle">

    <thead class="table-dark">
        <tr>
            <th>Tipo de Documento</th>
            <th>Documento</th>
            <th>Teléfono</th>
            <th>Nombre</th>
            <th>Fecha</th>
            <th>Categoría</th>
            <th>Acudiente</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>

    <tbody>
    <?php foreach($deportista as $deportista) { ?>
        <tr>
            <td><?php echo $deportista['tipo_documento']; ?></td>
            <td><?php echo $deportista['documento']; ?></td>
            <td><?php echo $deportista['telefono']; ?></td>
            <td><?php echo $deportista['nombre']; ?></td>
            <td><?php echo $deportista['fecha_nacimiento']; ?></td>
            <td><?php echo $deportista['categoria_nombre']; ?></td>
            <td><?php echo $deportista['acudiente_nombre']; ?></td>
            <td><?php echo $deportista['estado']; ?></td>

            <td>
                <div class="d-flex justify-content-center gap-2">
                    <a href="editar.php?id=<?php echo $deportista['id']; ?>" class="btn btn-success btn-sm">Editar</a>
                    <a href="index.php?id=<?php echo $deportista['id']; ?>" class="btn btn-danger btn-sm">Eliminar</a>
                </div>
            </td>
        </tr>
    <?php } ?>
    </tbody>

</table>
</div>








<?php include("../../template/footer_modulos.php") ?>


