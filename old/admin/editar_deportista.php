<?php
include("../includes/conexion.php");

$id = $_GET['id'] ?? null;
if(!$id){
    header("Location: deportistas.php");
    exit;
}

// Traer los datos del deportista
$stmt = $conexion->prepare("
    SELECT d.*, d.padre_id, c.nombre AS categoria, p.nombre AS acudiente, p.telefono
    FROM deportista d
    LEFT JOIN categoria c ON d.categoria_id = c.id
    LEFT JOIN acudiente p ON d.padre_id = p.id
    WHERE d.id = ?
");
$stmt->execute([$id]);
$deportista = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$deportista){
    echo "Deportista no encontrado";
    exit;
}

// Procesar el formulario
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $nombre = $_POST['nombre'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $acudiente = $_POST['acudiente'] ?? '';
    $telefono = $_POST['telefono'] ?? '';

    // Manejar categoría
    $stmtCat = $conexion->prepare("SELECT id FROM categoria WHERE nombre=?");
    $stmtCat->execute([$categoria]);
    $cat = $stmtCat->fetch(PDO::FETCH_ASSOC);
    if(!$cat){
        $conexion->prepare("INSERT INTO categoria(nombre) VALUES(?)")->execute([$categoria]);
        $categoria_id = $conexion->lastInsertId();
    } else {
        $categoria_id = $cat['id'];
    }

    // Manejar padre
$padre_id = $_POST['padre_id'] ?? null;

if($padre_id){
    // actualizar el acudiente existente
    $conexion->prepare("
        UPDATE acudiente 
        SET nombre=?, telefono=? 
        WHERE id=?
    ")->execute([$acudiente, $telefono, $padre_id]);
} elseif($acudiente != '') {
    // crear nuevo solo si no existe
    $conexion->prepare("
        INSERT INTO acudiente(nombre, telefono) 
        VALUES(?, ?)
    ")->execute([$acudiente, $telefono]);

    $padre_id = $conexion->lastInsertId();
}


    // Actualizar deportista
    $stmtUpdate = $conexion->prepare("UPDATE deportista SET nombre=?, categoria_id=?, padre_id=? WHERE id=?");
    $stmtUpdate->execute([$nombre, $categoria_id, $padre_id, $id]);

    // Guardar mensaje en sesión
    $_SESSION['msg'] = "Deportista actualizado correctamente.";

    // Redirigir a pages/deportista.php con el mismo ID
    header("Location: ../pages/deportista.php?id=$id");
    exit;
}
?>

<h2>Editar Deportista</h2>

<form method="POST">

    <input type="hidden" name="padre_id" value="<?= $deportista['padre_id'] ?>">

    <div>
        <label>Nombre:</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($deportista['nombre']) ?>" required>
    </div>

    <div>
        <label>Categoría:</label>
        <input type="text" name="categoria" value="<?= htmlspecialchars($deportista['categoria'] ?? '') ?>" required>
    </div>

    <div>
        <label>Acudiente:</label>
        <input type="text" name="acudiente" value="<?= htmlspecialchars($deportista['acudiente'] ?? '') ?>">
    </div>

    <div>
        <label>Teléfono:</label>
        <input type="text" name="telefono" value="<?= htmlspecialchars($deportista['telefono'] ?? '') ?>">
    </div>

    <button type="submit">Guardar</button>
</form>


