<?php  

include("../../modulos/conexion_modulos.php");

// 🔍 OBTENER DATOS
if(isset($_GET['id'])){

    $txtid = $_GET['id'];

    // ✅ traer deportista + acudiente manual
    $stm = $conexion->prepare("
        SELECT 
            d.*, 
            ud.acudiente,
            ud.parentesco

        FROM deportista d

        LEFT JOIN usuario_deportista ud 
            ON d.id = ud.deportista_id

        WHERE d.id = :id
    ");

    $stm->bindParam(":id", $txtid);
    $stm->execute();

    $registro = $stm->fetch(PDO::FETCH_ASSOC);

    if($registro){

        $tipo_documento = $registro['tipo_documento'];
        $documento = $registro['documento'];
        $telefono = $registro['telefono'];
        $nombre = $registro['nombre'];
        $fecha_nacimiento = $registro['fecha_nacimiento'];
        $categoria_id = $registro['categoria_id'];

        // ✅ NUEVO ESTADO
        $estado = $registro['estado'];

        // ✅ ACUDIENTE MANUAL
        $acudiente = $registro['acudiente'];

        $parentesco = $registro['parentesco'];

    }

}


// 🔄 ACTUALIZAR
if($_POST){

$txtid = $_POST['id'] ?? "";

$tipo_documento = $_POST['tipo_documento'] ?? "";
$documento = $_POST['documento'] ?? "";
$telefono = $_POST['telefono'] ?? "";
$nombre = $_POST['nombre'] ?? "";
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? "";
$categoria_id = $_POST['categoria_id'] ?? "";

// ✅ NUEVO ESTADO
$estado = $_POST['estado'] ?? "activo";

// ✅ ACUDIENTE MANUAL
$acudiente = trim($_POST['acudiente'] ?? "");

$parentesco = $_POST['parentesco'] ?? "";


// 🔒 VALIDACIONES

if(empty($acudiente)){
    echo "<script>alert('Ingresa un acudiente');</script>";
    exit;
}

if(empty($categoria_id)){
    echo "<script>alert('Selecciona una categoría');</script>";
    exit;
}


// validar categoría
$stmt_cat = $conexion->prepare("
SELECT id 
FROM categoria 
WHERE id = :id
");

$stmt_cat->execute([
    ":id"=>$categoria_id
]);

if(!$stmt_cat->fetch()){
    echo "<script>alert('Categoría inválida');</script>";
    exit;
}


// validar documento único
$stmt_check = $conexion->prepare("
SELECT id 
FROM deportista 
WHERE documento = :documento 
AND id != :id
");

$stmt_check->execute([
    ":documento"=>$documento,
    ":id"=>$txtid
]);

if($stmt_check->fetch()){
    echo "<script>alert('Este documento ya está registrado');</script>";
    exit;
}


// ✅ actualizar deportista
$stm = $conexion->prepare("
UPDATE deportista 
SET 
    tipo_documento = :tipo_documento,
    documento = :documento,
    telefono = :telefono,
    nombre = :nombre,
    fecha_nacimiento = :fecha_nacimiento,
    categoria_id = :categoria_id,
    estado = :estado

WHERE id = :id
");

$stm->execute([
    ":tipo_documento"=>$tipo_documento,
    ":documento"=>$documento,
    ":telefono"=>$telefono,
    ":nombre"=>$nombre,
    ":fecha_nacimiento"=>$fecha_nacimiento,
    ":categoria_id"=>$categoria_id,
    ":estado"=>$estado,
    ":id"=>$txtid
]);


// ✅ actualizar acudiente manual
$stmt_rel = $conexion->prepare("
UPDATE usuario_deportista 

SET 
    acudiente = :acudiente,
    parentesco = :parentesco

WHERE deportista_id = :deportista_id
");

$stmt_rel->execute([
    ":acudiente"=>$acudiente,
    ":parentesco"=>$parentesco,
    ":deportista_id"=>$txtid
]);


header("location:index.php");
exit;

}

?>

<?php include("../../template/header_modulos.php") ?>

<form action="" method="post">

<input 
    type="hidden" 
    name="id" 
    value="<?php echo $txtid; ?>"
>

<label>Tipo Documento</label>

<input 
    type="text" 
    class="form-control" 
    name="tipo_documento" 
    value="<?php echo $tipo_documento; ?>"
>

<label>Documento</label>

<input 
    type="text" 
    class="form-control" 
    name="documento" 
    value="<?php echo $documento; ?>"
>

<label>Teléfono</label>

<input 
    type="text" 
    class="form-control" 
    name="telefono" 
    value="<?php echo $telefono; ?>"
>

<label>Nombre</label>

<input 
    type="text" 
    class="form-control" 
    name="nombre" 
    value="<?php echo $nombre; ?>"
>

<label>Fecha de nacimiento</label>

<input 
    type="date" 
    class="form-control" 
    name="fecha_nacimiento" 
    value="<?php echo $fecha_nacimiento; ?>"
>

<!-- ✅ ACUDIENTE MANUAL -->
<label>Acudiente</label>

<input 
    type="text" 
    name="acudiente" 
    class="form-control" 
    value="<?php echo $acudiente; ?>"
    placeholder="Ingrese acudiente"
    required
>

<!-- ✅ PARENTESCO DROPDOWN -->
<label>Parentesco</label>

<select 
    name="parentesco" 
    class="form-control"
    required
>

    <option value="">
        Seleccione parentesco
    </option>

    <option 
        value="Papá"
        <?php if($parentesco=="Papá"){ echo "selected"; } ?>
    >
        Papá
    </option>

    <option 
        value="Mamá"
        <?php if($parentesco=="Mamá"){ echo "selected"; } ?>
    >
        Mamá
    </option>

    <option 
        value="Acudiente"
        <?php if($parentesco=="Acudiente"){ echo "selected"; } ?>
    >
        Acudiente
    </option>

</select>

<!-- ✅ NUEVO CAMPO ESTADO -->
<label>Estado</label>

<select 
    name="estado" 
    class="form-control"
    required
>

    <option 
        value="activo"
        <?php if($estado=="activo"){ echo "selected"; } ?>
    >
        Activo
    </option>

    <option 
        value="inactivo"
        <?php if($estado=="inactivo"){ echo "selected"; } ?>
    >
        Inactivo
    </option>

</select>

<!-- ✅ CATEGORIA -->
<label>Categoria</label>

<select 
    name="categoria_id" 
    class="form-control" 
    required
>

    <option value="">
        Seleccionar categoria
    </option>

    <?php

    $stmt = $conexion->query("
    SELECT id, nombre 
    FROM categoria
    ");

    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        $selected = ($row['id'] == $categoria_id) ? "selected" : "";

        echo "
        <option value='".$row['id']."' $selected>
            ".$row['nombre']."
        </option>
        ";
    }

    ?>

</select>

<br>

<a href="index.php" class="btn btn-danger">
    Cancelar
</a>

<button type="submit" class="btn btn-primary">
    Actualizar
</button>

</form>

<?php include("../../template/footer_modulos.php") ?>