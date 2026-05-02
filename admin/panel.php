<?php
session_start();
include("../includes/conexion.php");

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit();
}

$result = $conn->query("SELECT * FROM administradores");
?>

<h2>Panel de Usuarios</h2>

<a href="crear.php"> Crear usuario</a><br><br>

<table border="1">
<tr>
    <th>ID</th>
    <th>Usuario</th>
    <th>Rol</th>
    <th>Acciones</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['usuario']) ?></td>
    <td><?= htmlspecialchars($row['rol']) ?></td>
    <td>
        <a href="editar.php?id=<?= $row['id'] ?>">Editar</a> |
        <a href="eliminar.php?id=<?= $row['id'] ?>" 
        onclick="return confirm('¿Seguro que deseas eliminar este usuario?')">
        Eliminar
     </a>
    </td>
</tr>
<?php endwhile; ?>

</table>
