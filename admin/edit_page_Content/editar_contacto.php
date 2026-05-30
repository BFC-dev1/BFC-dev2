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

        <!-- BOOTSTRAP -->
        <link 
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" 
            rel="stylesheet"
        >

        <!-- FONT AWESOME -->
        <link 
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        >

    </head>

    <body class="bg-light">

    <div class="container py-5">

        <div class="card shadow p-4">

            <!-- TITULO -->
            <h2 class="mb-4">

                <i class="fa-solid fa-phone"></i>
                Editar Página de Contacto

            </h2>

            <!-- ALERTA -->
            <?php if(isset($_GET['ok'])){ ?>

                <div class="alert alert-success">

                    <i class="fa-solid fa-circle-check"></i>
                    Información de contacto actualizada correctamente

                </div>

            <?php } ?>

            <form method="POST">

                <!-- TITULO -->
                <div class="mb-3">

                    <label class="form-label fw-bold">

                        <i class="fa-solid fa-heading"></i>
                        Título

                    </label>

                    <input 
                        type="text" 
                        name="titulo" 
                        class="form-control"
                        value="<?php echo $contacto['titulo']; ?>"
                    >

                </div>

                <!-- DESCRIPCION -->
                <div class="mb-3">

                    <label class="form-label fw-bold">

                        <i class="fa-solid fa-align-left"></i>
                        Descripción

                    </label>

                    <textarea 
                        name="descripcion" 
                        class="form-control" 
                        rows="3"
                    ><?php echo $contacto['descripcion']; ?></textarea>

                </div>

                <hr>

                <!-- CONTACTO -->
                <h5 class="mb-3">

                    <i class="fa-solid fa-address-book"></i>
                    Información de contacto

                </h5>

                <!-- LINEA UNICA -->
                <div class="input-group mb-3">

                    <span class="input-group-text">

                        <i class="fa-solid fa-phone-volume"></i>

                    </span>

                    <input 
                        type="text" 
                        name="linea_unica" 
                        class="form-control"
                        value="<?php echo $contacto['linea_unica']; ?>" 
                        placeholder="Línea única"
                    >

                </div>

                <!-- SERVICIO CLIENTE -->
                <div class="input-group mb-3">

                    <span class="input-group-text">

                        <i class="fa-solid fa-headset"></i>

                    </span>

                    <input 
                        type="text" 
                        name="servicio_cliente" 
                        class="form-control"
                        value="<?php echo $contacto['servicio_cliente']; ?>" 
                        placeholder="Servicio cliente"
                    >

                </div>

                <!-- TELEFONO FIJO -->
                <div class="input-group mb-3">

                    <span class="input-group-text">

                        <i class="fa-solid fa-phone"></i>

                    </span>

                    <input 
                        type="text" 
                        name="fijo" 
                        class="form-control"
                        value="<?php echo $contacto['fijo']; ?>" 
                        placeholder="Fijo"
                    >

                </div>

                <!-- WHATSAPP -->
                <div class="input-group mb-3">

                    <span class="input-group-text">

                        <i class="fa-brands fa-whatsapp"></i>

                    </span>

                    <input 
                        type="text" 
                        name="whatsapp" 
                        class="form-control"
                        value="<?php echo $contacto['whatsapp']; ?>" 
                        placeholder="WhatsApp"
                    >

                </div>

                <!-- CORREO -->
                <div class="input-group mb-3">

                    <span class="input-group-text">

                        <i class="fa-solid fa-envelope"></i>

                    </span>

                    <input 
                        type="email" 
                        name="correo" 
                        class="form-control"
                        value="<?php echo $contacto['correo']; ?>" 
                        placeholder="Correo"
                    >

                </div>

                <hr>

                <!-- HORARIOS -->
                <h5 class="mb-3">

                    <i class="fa-solid fa-clock"></i>
                    Horarios

                </h5>

                <!-- LUNES A VIERNES -->
                <div class="input-group mb-3">

                    <span class="input-group-text">

                        <i class="fa-solid fa-calendar-days"></i>

                    </span>

                    <input 
                        type="text" 
                        name="horario_lun_vie" 
                        class="form-control"
                        value="<?php echo $contacto['horario_lun_vie']; ?>"
                        placeholder="Horario lunes a viernes"
                    >

                </div>

                <!-- SABADO -->
                <div class="input-group mb-4">

                    <span class="input-group-text">

                        <i class="fa-solid fa-calendar"></i>

                    </span>

                    <input 
                        type="text" 
                        name="horario_sab" 
                        class="form-control"
                        value="<?php echo $contacto['horario_sab']; ?>"
                        placeholder="Horario sábado"
                    >

                </div>

                <!-- BOTON -->
                <button class="btn btn-primary">

                    <i class="fa-solid fa-floppy-disk"></i>
                    Guardar Cambios

                </button>

            </form>

        </div>

    </div>

    </body>
    </html>