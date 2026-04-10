<?php
include("../includes/conexion.php");

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nombre = $_POST['nombre'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $acudiente = $_POST['acudiente'] ?? '';
    $telefono = $_POST['telefono'] ?? '';

    try {
        // Manejo de categoría
        $stmtCat = $conexion->prepare("SELECT id FROM categoria WHERE nombre = ?");
        $stmtCat->execute([$categoria]);
        $cat = $stmtCat->fetch(PDO::FETCH_ASSOC);
        if(!$cat){
            $stmtInsertCat = $conexion->prepare("INSERT INTO categoria (nombre) VALUES (?)");
            $stmtInsertCat->execute([$categoria]);
            $categoria_id = $conexion->lastInsertId();
        } else {
            $categoria_id = $cat['id'];
        }

        // Manejo de acudiente
       if($acudiente != ''){
    $stmtInsertPadre = $conexion->prepare("
        INSERT INTO acudiente (nombre, telefono) 
        VALUES (?, ?)
    ");
    $stmtInsertPadre->execute([$acudiente, $telefono]);
    $padre_id = $conexion->lastInsertId();
} else {
    $padre_id = null;
}

    // INSERT
    $stmt = $conexion->prepare("
        INSERT INTO deportista (nombre, categoria_id, padre_id) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$nombre, $categoria_id, $padre_id]);


        //  Redirigir a la página de lista, no a sí mismo
        header("Location: /BFC-dev1.github.io/pages/deportista.php");
        exit;

    } catch(PDOException $e){
        echo "Error: " . $e->getMessage();
    }
}

// Si no es POST, simplemente mostrar el formulario
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Deportista</title>
    <link rel="stylesheet" href="/BFC-dev1.github.io/assets/estilo.css">
</head>
<body>

<h2>Nuevo Deportista</h2>

<form method="POST">

    <label>Nombre:</label>
    <input type="text" name="nombre" required><br>

    <label>Categoría:</label>
    <input type="text" name="categoria" required><br>

    <label>Acudiente:</label>
    <input type="text" name="acudiente"><br>

    <label>Teléfono:</label>
    <input type="text" name="telefono"><br>

    <button type="submit">Guardar</button>

</form>

</body>
</html>
