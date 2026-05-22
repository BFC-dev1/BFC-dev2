<?php
session_start();

include(__DIR__ . "/../../includes/conexion_BDcms.php");

/*
=========================
CONSULTAR CONTACTO
=========================
*/

$stmt = $conexion->prepare("
    SELECT *
    FROM cms_contacto
    LIMIT 1
");

$stmt->execute();

$contacto = $stmt->fetch(PDO::FETCH_ASSOC);

/* EVITAR ERRORES SI NO EXISTE REGISTRO */
if(!$contacto){

    $contacto = [
        "titulo" => "",
        "descripcion" => "",
        "linea_unica" => "",
        "servicio_cliente" => "",
        "fijo" => "",
        "whatsapp" => "",
        "correo" => "",
        "horario_lun_vie" => "",
        "horario_sab" => ""
    ];
}

/*
=========================
GUARDAR CAMBIOS
=========================
*/

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $titulo = trim($_POST['titulo'] ?? "");
    $descripcion = trim($_POST['descripcion'] ?? "");

    $linea_unica = trim($_POST['linea_unica'] ?? "");
    $servicio_cliente = trim($_POST['servicio_cliente'] ?? "");
    $fijo = trim($_POST['fijo'] ?? "");
    $whatsapp = trim($_POST['whatsapp'] ?? "");
    $correo = trim($_POST['correo'] ?? "");

    $horario_lun_vie = trim($_POST['horario_lun_vie'] ?? "");
    $horario_sab = trim($_POST['horario_sab'] ?? "");

    $stmtUpdate = $conexion->prepare("
        UPDATE cms_contacto SET

        titulo = :titulo,
        descripcion = :descripcion,

        linea_unica = :linea_unica,
        servicio_cliente = :servicio_cliente,
        fijo = :fijo,
        whatsapp = :whatsapp,
        correo = :correo,

        horario_lun_vie = :horario_lun_vie,
        horario_sab = :horario_sab

        WHERE id = 1
    ");

    $stmtUpdate->execute([

        ":titulo" => $titulo,
        ":descripcion" => $descripcion,

        ":linea_unica" => $linea_unica,
        ":servicio_cliente" => $servicio_cliente,
        ":fijo" => $fijo,
        ":whatsapp" => $whatsapp,
        ":correo" => $correo,

        ":horario_lun_vie" => $horario_lun_vie,
        ":horario_sab" => $horario_sab
    ]);

    header("Location: editar_contacto.php?ok=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Contacto</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="card shadow p-4">

        <h2 class="mb-4">📞 Editar Página de Contacto</h2>

        <?php if(isset($_GET['ok'])){ ?>
            <div class="alert alert-success">
                Información de contacto actualizada correctamente
            </div>
        <?php } ?>

        <form method="POST">

            <!-- TITULO -->
            <div class="mb-3">
                <label>Título</label>
                <input type="text" name="titulo" class="form-control"
                       value="<?php echo $contacto['titulo']; ?>">
            </div>

            <!-- DESCRIPCIÓN -->
            <div class="mb-3">
                <label>Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3"><?php echo $contacto['descripcion']; ?></textarea>
            </div>

            <hr>

            <!-- CONTACTO -->
            <h5>Información de contacto</h5>

            <input type="text" name="linea_unica" class="form-control mb-2"
                   value="<?php echo $contacto['linea_unica']; ?>" placeholder="Línea única">

            <input type="text" name="servicio_cliente" class="form-control mb-2"
                   value="<?php echo $contacto['servicio_cliente']; ?>" placeholder="Servicio cliente">

            <input type="text" name="fijo" class="form-control mb-2"
                   value="<?php echo $contacto['fijo']; ?>" placeholder="Fijo">

            <input type="text" name="whatsapp" class="form-control mb-2"
                   value="<?php echo $contacto['whatsapp']; ?>" placeholder="WhatsApp">

            <input type="email" name="correo" class="form-control mb-2"
                   value="<?php echo $contacto['correo']; ?>" placeholder="Correo">

            <hr>

            <!-- HORARIOS -->
            <h5>Horarios</h5>

            <input type="text" name="horario_lun_vie" class="form-control mb-2"
                   value="<?php echo $contacto['horario_lun_vie']; ?>">

            <input type="text" name="horario_sab" class="form-control mb-3"
                   value="<?php echo $contacto['horario_sab']; ?>">

            <button class="btn btn-primary">
                Guardar Cambios
            </button>

        </form>

    </div>

</div>

</body>
</html>