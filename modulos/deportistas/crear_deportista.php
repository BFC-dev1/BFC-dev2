<?php 

if($_POST){

<<<<<<< HEAD
$tipo_documento = trim($_POST['tipo_documento'] ?? "");
$documento = trim($_POST['documento'] ?? "");
$telefono = trim($_POST['telefono'] ?? "");
$nombre = trim($_POST['nombre'] ?? "");
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? "";
$categoria_id = $_POST['categoria_id'] ?? "";
$acudiente = trim($_POST['acudiente'] ?? "");
$parentesco = trim($_POST['parentesco'] ?? "");

// 🔒 VALIDACIONES

if(empty($acudiente)){
    header("Location: index.php?error=acudiente");
    exit();
=======
$tipo_documento = $_POST['tipo_documento'] ?? "";
$documento = $_POST['documento'] ?? "";
$telefono = $_POST['telefono'] ?? "";
$nombre = $_POST['nombre'] ?? "";
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? "";
$categoria_id = $_POST['categoria_id'] ?? "";
$usuario_id = $_POST['usuario_id'] ?? "";
$parentesco = $_POST['parentesco'] ?? "";

// 🔒 VALIDACIONES

if(empty($usuario_id)){
    header("Location: index.php?error=acudiente");
    exit;
>>>>>>> 1db6aaf8a5476ce21e7c33f0213aaf15de3f5d62
}

if(empty($categoria_id)){
    header("Location: index.php?error=categoria");
<<<<<<< HEAD
    exit();
}

// 🔍 validar categoría
$stmt_cat = $conexion->prepare("
SELECT id 
FROM categoria 
WHERE id = :id
");

$stmt_cat->execute([
    ":id"=>$categoria_id
]);

if(!$stmt_cat->fetch()){
    header("Location: index.php?error=categoria_invalida");
    exit();
}

// 🔍 validar documento único
$stmt_check = $conexion->prepare("
SELECT id 
FROM deportista 
WHERE documento = :documento
");

$stmt_check->execute([
    ":documento"=>$documento
]);

if($stmt_check->fetch()){
    header("Location: index.php?error=documento");
    exit();
=======
    exit;
}

// 🔍 validar que categoría exista
$stmt_cat = $conexion->prepare("SELECT id FROM categoria WHERE id = :id");
$stmt_cat->execute([":id"=>$categoria_id]);

if(!$stmt_cat->fetch()){
    header("Location: index.php?error=categoria_invalida");
    exit;
}

// 🔍 validar documento único
$stmt_check = $conexion->prepare("SELECT id FROM deportista WHERE documento = :documento");
$stmt_check->execute([":documento"=>$documento]);

if($stmt_check->fetch()){
    header("Location: index.php?error=documento");
    exit;
>>>>>>> 1db6aaf8a5476ce21e7c33f0213aaf15de3f5d62
}

// ✅ INSERTAR DEPORTISTA
$stm = $conexion->prepare("
INSERT INTO deportista(
    tipo_documento,
    documento,
    telefono,
    nombre,
    fecha_nacimiento,
    categoria_id,
    estado
)
VALUES(
    :tipo_documento,
    :documento,
    :telefono,
    :nombre,
    :fecha_nacimiento,
    :categoria_id,
    'activo'
)
");

$stm->execute([
<<<<<<< HEAD
    ":tipo_documento"=>$tipo_documento,
    ":documento"=>$documento,
    ":telefono"=>$telefono,
    ":nombre"=>$nombre,
    ":fecha_nacimiento"=>$fecha_nacimiento,
    ":categoria_id"=>$categoria_id
]);

// ✅ obtener ID deportista
$deportista_id = $conexion->lastInsertId();

// ✅ guardar acudiente
$stmt_rel = $conexion->prepare("
INSERT INTO usuario_deportista(
    deportista_id,
    acudiente,
    parentesco
)
VALUES(
    :deportista_id,
    :acudiente,
    :parentesco
)
");

$stmt_rel->execute([
    ":deportista_id"=>$deportista_id,
    ":acudiente"=>$acudiente,
    ":parentesco"=>$parentesco
]);

header("Location: index.php?success=1");
exit();
=======
":tipo_documento"=>$tipo_documento,
":documento"=>$documento,
":telefono"=>$telefono,
":nombre"=>$nombre,
":fecha_nacimiento"=>$fecha_nacimiento,
":categoria_id"=>$categoria_id
]);

$stm->execute([
":tipo_documento"=>$tipo_documento,
":documento"=>$documento,
":telefono"=>$telefono,
":nombre"=>$nombre,
":fecha_nacimiento"=>$fecha_nacimiento,
":categoria_id"=>$categoria_id
]);

// ✅ obtener ID
$deportista_id = $conexion->lastInsertId();

// ✅ guardar relación usuario-deportista
$stmt_rel = $conexion->prepare("
INSERT INTO usuario_deportista (usuario_id, deportista_id, parentesco)
VALUES (:usuario_id, :deportista_id, :parentesco)
");

$stmt_rel->execute([
":usuario_id"=>$usuario_id,
":deportista_id"=>$deportista_id,
":parentesco"=>$parentesco
]);

header("Location: index.php?success=1");
exit;
>>>>>>> 1db6aaf8a5476ce21e7c33f0213aaf15de3f5d62

}
?>

<!-- Modal create -->
<div class="modal fade" id="create" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Agregar Deportista</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form action="" method="post">
<<<<<<< HEAD

      <div class="modal-body">

        <label>Tipo Documento</label>
        <input 
            type="text" 
            name="tipo_documento" 
            class="form-control" 
            placeholder="Ingrese Tipo de Documento"
        >

        <label>Documento</label>
        <input 
            type="text" 
            name="documento" 
            class="form-control" 
            placeholder="Ingrese Documento"
        >

        <label>Teléfono</label>
        <input 
            type="text" 
            name="telefono" 
            class="form-control" 
            placeholder="Ingrese Teléfono"
        >

        <label>Nombre</label>
        <input 
            type="text" 
            name="nombre" 
            class="form-control" 
            placeholder="Ingrese Nombre"
        >

        <label>Fecha de nacimiento</label>
        <input 
            type="date" 
            class="form-control" 
            name="fecha_nacimiento"
        >

        <!-- ✅ ACUDIENTE MANUAL -->
        <label>Acudiente</label>
        <input 
            type="text" 
            name="acudiente" 
            class="form-control" 
            placeholder="Ingrese nombre del acudiente"
            required
        >

        <label>Parentesco</label>
        <input 
            type="text" 
            name="parentesco" 
            class="form-control" 
            placeholder="Ej: Padre, Madre"
        >

        <!-- ✅ CATEGORIA -->
        <label>Categoria</label>

        <select name="categoria_id" class="form-control" required>

            <option value="">
                Seleccionar categoria
            </option>

            <?php
            $stmt = $conexion->query("
            SELECT id, nombre 
            FROM categoria
            ");

            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                echo "
                <option value='".$row['id']."'>
                    ".$row['nombre']."
                </option>
                ";
            }
            ?>

=======
      <div class="modal-body">

        <label>Tipo Documento</label>
        <input type="text" name="tipo_documento" class="form-control" placeholder="Ingrese Tipo de Documento">

        <label>Documento</label>
        <input type="text" name="documento" class="form-control" placeholder="Ingrese Documento">

        <label>Teléfono</label>
        <input type="text" name="telefono" class="form-control" placeholder="Ingrese Teléfono">

        <label>Nombre</label>
        <input type="text" name="nombre" class="form-control" placeholder="Ingrese Nombre">

        <label>Fecha de nacimiento</label>
        <input type="date" class="form-control" name="fecha_nacimiento">



        <!-- ✅ ACUDIENTE -->
        <label>Acudiente</label>
        <select name="usuario_id" class="form-control" required>
            <option value="">Seleccionar acudiente</option>
            <?php
            $stmt = $conexion->query("SELECT id, usuario FROM usuario");
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                echo "<option value='".$row['id']."'>".$row['usuario']."</option>";
            }
            ?>
        </select>

        <label>Parentesco</label>
        <input type="text" name="parentesco" class="form-control" placeholder="Ej: Padre, Madre">

        <!-- ✅ CATEGORIA -->
        <label>Categoria</label>
        <select name="categoria_id" class="form-control" required>
            <option value="">Seleccionar categoria</option>
            <?php
            $stmt = $conexion->query("SELECT id, nombre FROM categoria");
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                echo "<option value='".$row['id']."'>".$row['nombre']."</option>";
            }
            ?>
>>>>>>> 1db6aaf8a5476ce21e7c33f0213aaf15de3f5d62
        </select>

      </div>

      <div class="modal-footer">
<<<<<<< HEAD

        <button 
            type="button" 
            class="btn btn-secondary" 
            data-bs-dismiss="modal"
        >
            Cerrar
        </button>

        <button 
            type="submit" 
            class="btn btn-primary"
        >
            Guardar
        </button>

      </div>

      </form>

    </div>
  </div>
</div>
=======
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>

      </form>
    </div>
  </div>
</div>
>>>>>>> 1db6aaf8a5476ce21e7c33f0213aaf15de3f5d62
