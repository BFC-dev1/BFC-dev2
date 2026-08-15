<?php

/*
===========================================================
MÓDULO FINANCIERO
NOVEDADES FINANCIERAS
REGISTRAR NUEVA NOVEDAD
===========================================================
*/

/*
===========================================================
1. INICIAR SESIÓN
===========================================================
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
===========================================================
2. CARGAR CONFIGURACIÓN Y CONEXIÓN
===========================================================
*/

require_once dirname(__DIR__, 3) . "/includes/config.php";
require_once dirname(__DIR__, 3) . "/includes/conexion.php";


/*
===========================================================
3. VERIFICAR SESIÓN
===========================================================
*/

if (
    !isset($_SESSION['usuario_id']) &&
    !isset($_SESSION['id_usuario'])
) {
    header("Location: " . $url_base . "/auth/login.php");
    exit;
}


/*
===========================================================
4. OBTENER ID DEL USUARIO
===========================================================
*/

$id_usuario_actual =
    $_SESSION['usuario_id']
    ?? $_SESSION['id_usuario']
    ?? null;


/*
===========================================================
5. CONSULTAR DEPORTISTAS
===========================================================
*/

$sql_deportistas = "
    SELECT
        id,
        nombre,
        documento
    FROM deportista
    WHERE estado = 'activo'
    ORDER BY nombre ASC
";

$stmt_deportistas = $conexion->prepare($sql_deportistas);
$stmt_deportistas->execute();

$deportistas = $stmt_deportistas->fetchAll(PDO::FETCH_ASSOC);


/*
===========================================================
6. MENSAJES
===========================================================
*/

$error = $_GET['error'] ?? null;

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nueva Novedad Financiera | Bellavista FC</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>


<body class="bg-light">


<div class="container-fluid py-4">

    <!-- =====================================================
         ENCABEZADO
         ===================================================== -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">

                <i class="fa-solid fa-file-circle-plus text-primary"></i>

                Registrar nueva novedad

            </h2>

            <p class="text-muted mb-0">

                Registre una novedad financiera para el sistema.

            </p>

        </div>


        <div>

            <a
                href="index.php"
                class="btn btn-outline-secondary"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Volver

            </a>

        </div>

    </div>


    <!-- =====================================================
         MENSAJE DE ERROR
         ===================================================== -->

    <?php if ($error): ?>

        <div class="alert alert-danger">

            <i class="fa-solid fa-circle-exclamation"></i>

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         FORMULARIO
         ===================================================== -->

    <div class="card shadow-sm border-0">

        <div class="card-header bg-primary text-white">

            <h5 class="mb-0">

                <i class="fa-solid fa-pen-to-square"></i>

                Información de la novedad

            </h5>

        </div>


        <div class="card-body">

            <form
                action="guardar_novedad.php"
                method="POST"
            >


                <!-- =================================================
                     DEPORTISTA
                     ================================================= -->

                <div class="mb-3">

                    <label
                        for="id_deportista"
                        class="form-label fw-semibold"
                    >

                        Deportista

                    </label>


                    <select
                        name="id_deportista"
                        id="id_deportista"
                        class="form-select"
                    >

                        <option value="">

                            Seleccione un deportista

                        </option>


                        <?php foreach ($deportistas as $deportista): ?>

                            <option
                                value="<?= (int)$deportista['id'] ?>"
                            >

                                <?= htmlspecialchars($deportista['nombre']) ?>

                                -

                                <?= htmlspecialchars($deportista['documento']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>


                    <div class="form-text">

                        Puede dejar este campo vacío si la novedad no está
                        asociada a un deportista específico.

                    </div>

                </div>


                <!-- =================================================
                     TIPO
                     ================================================= -->

                <div class="mb-3">

                    <label
                        for="tipo"
                        class="form-label fw-semibold"
                    >

                        Tipo de novedad
                        <span class="text-danger">*</span>

                    </label>


                    <select
                        name="tipo"
                        id="tipo"
                        class="form-select"
                        required
                    >

                        <option value="">

                            Seleccione el tipo

                        </option>


                        <option value="DESCUENTO">

                            Descuento

                        </option>


                        <option value="RECARGO">

                            Recargo

                        </option>


                        <option value="BECA">

                            Beca

                        </option>


                        <option value="EXONERACION">

                            Exoneración

                        </option>


                        <option value="AJUSTE">

                            Ajuste

                        </option>

                    </select>

                </div>


                <!-- =================================================
                     CONCEPTO
                     ================================================= -->

                <div class="mb-3">

                    <label
                        for="concepto"
                        class="form-label fw-semibold"
                    >

                        Concepto
                        <span class="text-danger">*</span>

                    </label>


                    <input
                        type="text"
                        name="concepto"
                        id="concepto"
                        class="form-control"
                        maxlength="255"
                        placeholder="Ejemplo: Descuento por pronto pago"
                        required
                    >

                </div>


                <!-- =================================================
                     MONTO
                     ================================================= -->

                <div class="mb-3">

                    <label
                        for="monto"
                        class="form-label fw-semibold"
                    >

                        Monto
                        <span class="text-danger">*</span>

                    </label>


                    <div class="input-group">

                        <span class="input-group-text">

                            $

                        </span>


                        <input
                            type="number"
                            name="monto"
                            id="monto"
                            class="form-control"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                            required
                        >

                    </div>


                    <div class="form-text">

                        Ingrese únicamente el valor correspondiente
                        a la novedad.

                    </div>

                </div>


                <!-- =================================================
                     FECHA
                     ================================================= -->

                <div class="mb-3">

                    <label
                        for="fecha"
                        class="form-label fw-semibold"
                    >

                        Fecha
                        <span class="text-danger">*</span>

                    </label>


                    <input
                        type="date"
                        name="fecha"
                        id="fecha"
                        class="form-control"
                        value="<?= date('Y-m-d') ?>"
                        required
                    >

                </div>


                <!-- =================================================
                     OBSERVACIÓN
                     ================================================= -->

                <div class="mb-4">

                    <label
                        for="observacion"
                        class="form-label fw-semibold"
                    >

                        Observación

                    </label>


                    <textarea
                        name="observacion"
                        id="observacion"
                        class="form-control"
                        rows="4"
                        placeholder="Ingrese una observación adicional..."
                    ></textarea>

                </div>


                <!-- =================================================
                     BOTONES
                     ================================================= -->

                <div class="d-flex justify-content-end gap-2">

                    <a
                        href="index.php"
                        class="btn btn-secondary"
                    >

                        <i class="fa-solid fa-xmark"></i>

                        Cancelar

                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="fa-solid fa-floppy-disk"></i>

                        Registrar novedad

                    </button>

                </div>


            </form>

        </div>

    </div>

</div>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>