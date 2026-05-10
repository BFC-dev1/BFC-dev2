<?php 
include("../includes/conexion.php"); // $conexion es PDO

// Inicializar array vacío
$deportistas = [];

try {
    // Ejecutar la consulta
    $stmt = $conexion->query("
        SELECT 
            d.id,
            d.nombre,
            c.nombre AS categoria,
            p.nombre AS acudiente,
            p.telefono
        FROM deportista d
        LEFT JOIN categorias c ON d.categoria_id = c.id
        LEFT JOIN padres p ON d.padre_id = p.id
    ");

    // Guardar los resultados en un array asociativo
    $deportistas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error en la consulta: ".$e->getMessage()."</div>";
}
?>

<div class="table-responsive">
    <table class="table table-dark">
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
                            <button class="btn btn-sm btn-warning">Editar</button>
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
