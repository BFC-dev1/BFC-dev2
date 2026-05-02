<?php
session_start(); // iniciar sesión

// Mostrar alerta si hay mensaje en sesión
if(isset($_SESSION['msg'])){
    echo "<script>alert('".$_SESSION['msg']."');</script>";
    unset($_SESSION['msg']); // eliminar mensaje para que no se repita
}


include("../includes/conexion.php");

// Inicializar array vacío
$deportistas = [];

try {
    // Ejecutar la consulta
    $stmt = $conexion->query("
    SELECT 
        d.id,
        d.nombre,
        c.nombre AS categoria,
        a.nombre AS acudiente,
        a.telefono
    FROM deportista d
    LEFT JOIN categoria c ON d.categoria_id = c.id
    LEFT JOIN acudiente a ON d.padre_id = a.id
");

    // Guardar los resultados en un array asociativo
    $deportistas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error en la consulta: ".$e->getMessage()."</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deportistas - BellavistaFC</title>
    <link rel="stylesheet" href="/BFC-dev1.github.io/assets/estilo.css">
</head>
<body>

<?php include("../includes/header.php"); ?>

<div class="btn-wrapper">
    <button type="button" class="btn btn-primary btn-nuevo"
            onclick="window.location.href='../admin/crear_deportista.php'">
        Nuevo
    </button>
</div>

<div class="table-responsive">
    <table class="table table-dark table-bordered mi-tabla-negra">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Categoria</th>
                <th scope="col">Nombre</th>
                <th scope="col">Acudiente</th>
                <th scope="col">Teléfono</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($deportistas)): ?>
                <?php foreach($deportistas as $deportista): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($deportista['id'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($deportista['categoria'] ?? 'Sin categoría'); ?></td>
                        <td><?php echo htmlspecialchars($deportista['nombre'] ?? 'Sin nombre'); ?></td>
                        <td><?php echo htmlspecialchars($deportista['acudiente'] ?? 'Sin acudiente'); ?></td>
                        <td><?php echo htmlspecialchars($deportista['telefono'] ?? 'Sin teléfono'); ?></td>
                        <td>
                            <a href="../admin/editar_deportista.php?id=<?php echo $deportista['id']; ?>" 
                            class="btn btn-sm btn-warning">
                             Editar
                            </a>
                            <button class="btn btn-sm btn-danger">Borrar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No hay deportistas registrados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div> <!-- fin table-responsive -->
<br><br><br><br>

<?php include("../includes/footer.php"); ?>

</body>
</html>

