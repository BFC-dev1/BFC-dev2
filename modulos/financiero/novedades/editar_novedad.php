<?php

/*
=========================================================
MÓDULO FINANCIERO - NOVEDADES
SISTEMA: BELLAVISTA FC
ARCHIVO: editar_novedad.php

FUNCIÓN:
Editar una novedad financiera existente.

COLUMNAS REALES DE novedades_financieras:
- id
- id_deportista
- tipo
- concepto
- monto
- fecha
- estado
- observacion
- id_usuario

NO UTILIZAR:
- tipo_novedad
- titulo
=========================================================
*/

/* =========================================================
   1. CONFIGURACIÓN Y PERMISOS
   ========================================================= */
require_once("../../../includes/verificar_roles.php");
require_once("../../../includes/config.php");

if (!tiene_permiso('novedades')) {
    header("Location: " . $url_base . "/index.php");
    exit;
}

/* =========================================================
   2. AUDITORÍA
   ========================================================= */
include("../../../modulos/auditoria/funciones/registrar_auditoria.php");

/* =========================================================
   3. CONEXIÓN
   ========================================================= */
include("../../../modulos/conexion_modulos.php");

/* =========================================================
   4. OBTENER ID
   ========================================================= */
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$id || $id <= 0) {
    header("Location: index.php?error=1");
    exit;
}

/* =========================================================
   5. OPCIONES DEL FORMULARIO
   ========================================================= */
$tipos_novedad = [
    'DESCUENTO',
    'RECARGO',
    'BECA',
    'EXONERACION',
    'AJUSTE'
];

$estados = [
    'ACTIVA',
    'INACTIVA'
];

$mensaje_error = '';

/* =========================================================
   6. PROCESAR ACTUALIZACIÓN
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_deportista = trim($_POST['id_deportista'] ?? '');
    $tipo          = trim($_POST['tipo'] ?? '');
    $concepto      = trim($_POST['concepto'] ?? '');
    $monto         = trim($_POST['monto'] ?? '');
    $fecha         = trim($_POST['fecha'] ?? '');
    $estado        = trim($_POST['estado'] ?? '');
    $observacion   = trim($_POST['observacion'] ?? '');

    /* -----------------------------------------------------
       DEPORTISTA
       ----------------------------------------------------- */
    if ($id_deportista !== '') {
        $id_deportista = (int) $id_deportista;

        if ($id_deportista <= 0) {
            $mensaje_error = "El deportista seleccionado no es válido.";
        } else {
            $stmt = $conexion->prepare(
                "SELECT id FROM deportista WHERE id = :id LIMIT 1"
            );
            $stmt->execute([':id' => $id_deportista]);

            if (!$stmt->fetchColumn()) {
                $mensaje_error = "El deportista seleccionado no existe.";
            }
        }
    } else {
        $id_deportista = null;
    }

    /* -----------------------------------------------------
       FECHA
       ----------------------------------------------------- */
    if ($mensaje_error === '' && $fecha === '') {
        $mensaje_error = "La fecha es obligatoria.";
    }

    if ($mensaje_error === '' && $fecha !== '') {
        $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);

        if (!$fecha_obj || $fecha_obj->format('Y-m-d') !== $fecha) {
            $mensaje_error = "La fecha ingresada no es válida.";
        }
    }

    /* -----------------------------------------------------
       TIPO
       ----------------------------------------------------- */
    if ($mensaje_error === '' && $tipo === '') {
        $mensaje_error = "Debes seleccionar el tipo de novedad.";
    }

    if (
        $mensaje_error === '' &&
        !in_array($tipo, $tipos_novedad, true)
    ) {
        $mensaje_error = "El tipo de novedad seleccionado no es válido.";
    }

    /* -----------------------------------------------------
       CONCEPTO
       ----------------------------------------------------- */
    if ($mensaje_error === '' && $concepto === '') {
        $mensaje_error = "El concepto es obligatorio.";
    }

    if (
        $mensaje_error === '' &&
        mb_strlen($concepto) > 255
    ) {
        $mensaje_error = "El concepto no puede superar los 255 caracteres.";
    }

    /* -----------------------------------------------------
       MONTO
       ----------------------------------------------------- */
    if ($mensaje_error === '' && $monto === '') {
        $mensaje_error = "El monto es obligatorio.";
    }

    if ($mensaje_error === '' && $monto !== '') {
        $monto_normalizado = trim($monto);

        // 120.000,00 -> 120000.00
        if (
            strpos($monto_normalizado, ',') !== false &&
            strpos($monto_normalizado, '.') !== false
        ) {
            $monto_normalizado = str_replace('.', '', $monto_normalizado);
            $monto_normalizado = str_replace(',', '.', $monto_normalizado);
        }
        // 120000,00 -> 120000.00
        elseif (strpos($monto_normalizado, ',') !== false) {
            $monto_normalizado = str_replace(',', '.', $monto_normalizado);
        }

        if (!is_numeric($monto_normalizado)) {
            $mensaje_error = "El monto ingresado no es válido.";
        } else {
            $monto = (float) $monto_normalizado;

            if ($monto < 0) {
                $mensaje_error = "El monto no puede ser negativo.";
            }
        }
    }

    /* -----------------------------------------------------
       ESTADO
       ----------------------------------------------------- */
    if ($mensaje_error === '' && $estado === '') {
        $mensaje_error = "Debes seleccionar un estado.";
    }

    if (
        $mensaje_error === '' &&
        !in_array($estado, $estados, true)
    ) {
        $mensaje_error = "El estado seleccionado no es válido.";
    }

    /* -----------------------------------------------------
       OBSERVACIÓN
       ----------------------------------------------------- */
    if (
        $mensaje_error === '' &&
        mb_strlen($observacion) > 1000
    ) {
        $mensaje_error = "La observación no puede superar los 1000 caracteres.";
    }

    /* -----------------------------------------------------
       ACTUALIZAR
       ----------------------------------------------------- */
    if ($mensaje_error === '') {
        try {
            $conexion->beginTransaction();

            /* Obtener valores anteriores para auditoría */
            $stmtAnterior = $conexion->prepare(
                "SELECT
                    id_deportista,
                    tipo,
                    concepto,
                    monto,
                    fecha,
                    estado,
                    observacion
                 FROM novedades_financieras
                 WHERE id = :id
                 LIMIT 1"
            );

            $stmtAnterior->execute([':id' => $id]);
            $anterior = $stmtAnterior->fetch(PDO::FETCH_ASSOC);

            if (!$anterior) {
                $conexion->rollBack();
                header("Location: index.php?error=1");
                exit;
            }

            /* Actualizar usando SOLO las columnas reales */
            $sql = "UPDATE novedades_financieras
                    SET
                        id_deportista = :id_deportista,
                        tipo = :tipo,
                        concepto = :concepto,
                        monto = :monto,
                        fecha = :fecha,
                        estado = :estado,
                        observacion = :observacion
                    WHERE id = :id";

            $stmt = $conexion->prepare($sql);

            $stmt->execute([
                ':id_deportista' => $id_deportista,
                ':tipo' => $tipo,
                ':concepto' => $concepto,
                ':monto' => $monto,
                ':fecha' => $fecha,
                ':estado' => $estado,
                ':observacion' => $observacion !== '' ? $observacion : null,
                ':id' => $id
            ]);

            /* Auditoría */
            $cambios = [
                'id_deportista' => [
                    'antes' => $anterior['id_deportista'],
                    'despues' => $id_deportista
                ],
                'tipo' => [
                    'antes' => $anterior['tipo'],
                    'despues' => $tipo
                ],
                'concepto' => [
                    'antes' => $anterior['concepto'],
                    'despues' => $concepto
                ],
                'monto' => [
                    'antes' => $anterior['monto'],
                    'despues' => $monto
                ],
                'fecha' => [
                    'antes' => $anterior['fecha'],
                    'despues' => $fecha
                ],
                'estado' => [
                    'antes' => $anterior['estado'],
                    'despues' => $estado
                ],
                'observacion' => [
                    'antes' => $anterior['observacion'],
                    'despues' => $observacion
                ]
            ];

            registrarAuditoria(
                $conexion,
                'novedades_financieras',
                $id,
                'EDITAR',
                $cambios,
                'Actualización de novedad financiera: ' . $concepto
            );

            $conexion->commit();

            header("Location: index.php?actualizado=1");
            exit;

        } catch (Exception $e) {

            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            error_log(
                "Error editar_novedad.php: " . $e->getMessage()
            );

            $mensaje_error = "No fue posible actualizar la novedad.";
        }
    }
}

/* =========================================================
   7. CONSULTAR NOVEDAD
   ========================================================= */
$stmt = $conexion->prepare(
    "SELECT
        n.*,
        d.nombre AS nombre_deportista
     FROM novedades_financieras n
     LEFT JOIN deportista d
        ON d.id = n.id_deportista
     WHERE n.id = :id
     LIMIT 1"
);

$stmt->execute([':id' => $id]);
$novedad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$novedad) {
    header("Location: index.php?error=1");
    exit;
}

/* Mantener datos escritos si hubo error */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novedad['id_deportista'] = $id_deportista;
    $novedad['tipo'] = $tipo;
    $novedad['concepto'] = $concepto;
    $novedad['monto'] = $monto;
    $novedad['fecha'] = $fecha;
    $novedad['estado'] = $estado;
    $novedad['observacion'] = $observacion;
}

/* =========================================================
   8. OBTENER DEPORTISTAS
   ========================================================= */
$stmtDeportistas = $conexion->query(
    "SELECT id, nombre, documento
     FROM deportista
     ORDER BY nombre ASC"
);

$deportistas = $stmtDeportistas->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   9. HEADER DEL MÓDULO
   ========================================================= */
$modulo_actual = 'Financiero';
$submodulo_actual = 'Novedades';

include("../../../template/header_modulos.php");

?>

<!-- =====================================================
     BOTÓN VOLVER
     ===================================================== -->
<div class="mb-4">
    <a href="index.php" class="btn btn-outline-dark">
        <i class="fa-solid fa-arrow-left me-1"></i>
        Volver a Novedades
    </a>
</div>

<!-- =====================================================
     TÍTULO
     ===================================================== -->
<div class="mb-4">
    <h2 class="fw-bold">
        <i class="fa-solid fa-pen-to-square text-primary"></i>
        Editar Novedad
    </h2>

    <p class="text-muted mb-0">
        Actualiza la información de la novedad financiera.
    </p>
</div>

<!-- =====================================================
     MENSAJE DE ERROR
     ===================================================== -->
<?php if ($mensaje_error !== ''): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        <?= htmlspecialchars($mensaje_error) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>
    </div>
<?php endif; ?>

<!-- =====================================================
     FORMULARIO
     ===================================================== -->
<div class="card border-0 shadow-sm">

    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">
            <i class="fa-solid fa-note-sticky me-2"></i>
            Información de la novedad
        </h5>
    </div>

    <div class="card-body">

        <form
            method="POST"
            action="editar_novedad.php?id=<?= (int) $novedad['id'] ?>"
        >

            <input
                type="hidden"
                name="id"
                value="<?= (int) $novedad['id'] ?>"
            >

            <div class="row g-3">

                <!-- DEPORTISTA -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        Deportista
                    </label>

                    <select name="id_deportista" class="form-select">
                        <option value="">
                            Novedad general
                        </option>

                        <?php foreach ($deportistas as $deportista): ?>
                            <option
                                value="<?= (int) $deportista['id'] ?>"
                                <?= (
                                    (string) ($novedad['id_deportista'] ?? '') ===
                                    (string) $deportista['id']
                                ) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($deportista['nombre']) ?>

                                <?php if (!empty($deportista['documento'])): ?>
                                    - <?= htmlspecialchars($deportista['documento']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <small class="text-muted">
                        Puedes dejarlo vacío si la novedad es general.
                    </small>
                </div>

                <!-- FECHA -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        Fecha
                    </label>

                    <input
                        type="date"
                        name="fecha"
                        class="form-control"
                        value="<?= htmlspecialchars($novedad['fecha'] ?? '') ?>"
                        required
                    >
                </div>

                <!-- TIPO -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        Tipo de novedad
                    </label>

                    <select name="tipo" class="form-select" required>
                        <option value="">
                            Seleccionar tipo
                        </option>

                        <?php foreach ($tipos_novedad as $tipo_opcion): ?>
                            <option
                                value="<?= htmlspecialchars($tipo_opcion) ?>"
                                <?= (($novedad['tipo'] ?? '') === $tipo_opcion) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($tipo_opcion) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- ESTADO -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        Estado
                    </label>

                    <select name="estado" class="form-select" required>
                        <?php foreach ($estados as $estado_opcion): ?>
                            <option
                                value="<?= htmlspecialchars($estado_opcion) ?>"
                                <?= (($novedad['estado'] ?? '') === $estado_opcion) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($estado_opcion) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- CONCEPTO -->
                <div class="col-md-8">
                    <label class="form-label fw-bold">
                        Concepto
                    </label>

                    <input
                        type="text"
                        name="concepto"
                        class="form-control"
                        maxlength="255"
                        value="<?= htmlspecialchars($novedad['concepto'] ?? '') ?>"
                        required
                    >
                </div>

                <!-- MONTO -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">
                        Monto
                    </label>

                    <input
                        type="text"
                        name="monto"
                        class="form-control"
                        value="<?= htmlspecialchars($novedad['monto'] ?? '') ?>"
                        placeholder="0.00"
                        required
                    >

                    <small class="text-muted">
                        Ingresa el valor de la novedad.
                    </small>
                </div>

                <!-- OBSERVACIÓN -->
                <div class="col-12">
                    <label class="form-label fw-bold">
                        Observación
                    </label>

                    <textarea
                        name="observacion"
                        class="form-control"
                        rows="4"
                        maxlength="1000"
                        placeholder="Observaciones adicionales..."
                    ><?= htmlspecialchars($novedad['observacion'] ?? '') ?></textarea>

                    <small class="text-muted">
                        Campo opcional.
                    </small>
                </div>

            </div>

            <!-- BOTONES -->
            <div class="d-flex gap-2 mt-4">

                <a href="index.php" class="btn btn-danger">
                    <i class="fa-solid fa-xmark me-1"></i>
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="btn btn-primary fw-bold"
                >
                    <i class="fa-solid fa-floppy-disk me-1"></i>
                    Guardar cambios
                </button>

            </div>

        </form>
    </div>
</div>

<?php

/* =========================================================
   10. FOOTER
   ========================================================= */
include("../../../template/footer_modulos.php");

?>