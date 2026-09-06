<?php

/*
===========================================================
VERIFICAR PERMISOS DEL MÓDULO NOVEDADES
===========================================================
*/

/*
 * Cargamos el sistema de roles y configuración general.
 *
 * Utilizamos exactamente el mismo framework
 * implementado en el resto de los módulos de Bellavista FC.
 */

require_once("../../../includes/verificar_roles.php");
require_once("../../../includes/config.php");


/*
=========================================================
PERMISO DE GESTIÓN
=========================================================
*/

if (!tiene_permiso('novedades')) {

    header("Location: " . $url_base . "/modulos/financiero/novedades/index.php");
    exit;

}


/*
=========================================================
AUDITORÍA
=========================================================
*/

include("../../../modulos/auditoria/funciones/registrar_auditoria.php");


/*
=========================================================
CONEXIÓN A BASE DE DATOS
=========================================================
*/

include("../../../modulos/conexion_modulos.php");


/*
=========================================================
DATOS INICIALES Y CONSULTAS
=========================================================
*/

$fecha = date('Y-m-d');

$id_deportista = '';
$tipo = '';
$concepto = '';
$monto = '';
$observacion = '';

/*
 * Consultar deportistas activos para el select
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
=========================================================
RECUPERAR DATOS SI HUBO ERROR
=========================================================
*/

if (isset($_GET['error'])) {

    $fecha = $_GET['fecha'] ?? $fecha;
    $id_deportista = $_GET['id_deportista'] ?? '';
    $tipo = $_GET['tipo'] ?? '';
    $concepto = $_GET['concepto'] ?? '';
    $monto = $_GET['monto'] ?? '';
    $observacion = $_GET['observacion'] ?? '';

}


/*
=========================================================
HEADER DEL MÓDULO
=========================================================
*/

$modulo_actual = 'Financiero';

$submodulo_actual = 'Novedades';

include("../../../template/header_modulos.php");

?>


<!-- =====================================================
     BOTONES SUPERIORES
     ===================================================== -->

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <a
            href="index.php"
            class="btn btn-outline-dark"
        >

            <i class="fa-solid fa-arrow-left me-1"></i>

            Volver a Novedades

        </a>

    </div>

</div>


<!-- =====================================================
     MENSAJE DE ERROR
     ===================================================== -->

<?php if (isset($_GET['error'])): ?>

    <div
        class="alert alert-danger alert-dismissible fade show"
        role="alert"
    >

        <i class="fa-solid fa-triangle-exclamation me-2"></i>

        Ocurrió un error al registrar la novedad. Verifique los datos.

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

<?php endif; ?>


<!-- =====================================================
     TÍTULO DEL FORMULARIO
     ===================================================== -->

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h2 class="fw-bold">

            <i
                class="fa-solid fa-file-circle-plus text-primary"
            ></i>

            Registrar Nueva Novedad Financiera

        </h2>

        <p class="text-muted mb-0">

            Registrar un descuento, recargo, beca o ajuste para Bellavista FC.

        </p>

    </div>

</div>


<!-- =====================================================
     FORMULARIO PRINCIPAL
     ===================================================== -->

<div class="card border-0 shadow-sm">

    <div class="card-body p-4">

        <form
            action="guardar_novedad.php"
            method="POST"
            autocomplete="off"
        >

            <!-- =================================================
                 INFORMACIÓN DE LA NOVEDAD
                 ================================================= -->

            <div class="mb-4">

                <h5 class="fw-bold border-bottom pb-2">

                    <i class="fa-solid fa-pen-to-square text-primary me-2"></i>

                    Información de la novedad

                </h5>

            </div>


            <div class="row g-3">

                <!-- ==============================================
                     DEPORTISTA
                     ============================================== -->

                <div class="col-md-6">

                    <label
                        for="id_deportista"
                        class="form-label fw-bold"
                    >

                        Deportista

                    </label>

                    <select
                        id="id_deportista"
                        name="id_deportista"
                        class="form-select"
                    >

                        <option value="">

                            Seleccione un deportista (Opcional)...

                        </option>

                        <?php foreach ($deportistas as $dep): ?>

                            <option
                                value="<?= (int)$dep['id'] ?>"
                                <?= (string)$id_deportista === (string)$dep['id'] ? 'selected' : '' ?>
                            >

                                <?= htmlspecialchars($dep['nombre']) ?> - <?= htmlspecialchars($dep['documento']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                    <div class="form-text">

                        Puede dejar este campo vacío si la novedad no está asociada a un deportista específico.

                    </div>

                </div>


                <!-- ==============================================
                     TIPO DE NOVEDAD
                     ============================================== -->

                <div class="col-md-6">

                    <label
                        for="tipo"
                        class="form-label fw-bold"
                    >

                        Tipo de novedad

                        <span class="text-danger">*</span>

                    </label>

                    <select
                        id="tipo"
                        name="tipo"
                        class="form-select"
                        required
                    >

                        <option value="">

                            Seleccione el tipo...

                        </option>

                        <option value="DESCUENTO" <?= $tipo === 'DESCUENTO' ? 'selected' : '' ?>>Descuento</option>

                        <option value="RECARGO" <?= $tipo === 'RECARGO' ? 'selected' : '' ?>>Recargo</option>

                        <option value="BECA" <?= $tipo === 'BECA' ? 'selected' : '' ?>>Beca</option>

                        <option value="EXONERACION" <?= $tipo === 'EXONERACION' ? 'selected' : '' ?>>Exoneración</option>

                        <option value="AJUSTE" <?= $tipo === 'AJUSTE' ? 'selected' : '' ?>>Ajuste</option>

                    </select>

                    <div class="form-text">

                        Seleccione la categoría de la novedad financiera.

                    </div>

                </div>

            </div>


            <!-- =================================================
                 CONCEPTO
                 ================================================= -->

            <div class="mt-4">

                <label
                    for="concepto"
                    class="form-label fw-bold"
                >

                    Concepto

                    <span class="text-danger">*</span>

                </label>

                <input
                    type="text"
                    id="concepto"
                    name="concepto"
                    class="form-control"
                    maxlength="255"
                    placeholder="Ejemplo: Descuento por pronto pago de mensualidad"
                    value="<?= htmlspecialchars($concepto) ?>"
                    required
                >

                <div class="form-text">

                    Describa claramente el motivo de la novedad.

                </div>

            </div>


            <!-- =================================================
                 MONTO Y FECHA
                 ================================================= -->

            <div class="row g-3 mt-2">

                <!-- ==============================================
                     MONTO (Con separación de miles)
                     ============================================== -->

                <div class="col-md-6">

                    <label
                        for="monto"
                        class="form-label fw-bold"
                    >

                        Monto

                        <span class="text-danger">*</span>

                    </label>

                    <div class="input-group">

                        <span class="input-group-text">

                            $

                        </span>

                        <input
                            type="text"
                            id="monto"
                            name="monto"
                            class="form-control"
                            inputmode="numeric"
                            placeholder="0"
                            value="<?= htmlspecialchars($monto) ?>"
                            required
                        >

                    </div>

                    <div class="form-text">

                        Valor total de la novedad financiera.

                    </div>

                </div>


                <!-- ==============================================
                     FECHA
                     ============================================== -->

                <div class="col-md-6">

                    <label
                        for="fecha"
                        class="form-label fw-bold"
                    >

                        Fecha

                        <span class="text-danger">*</span>

                    </label>

                    <input
                        type="date"
                        id="fecha"
                        name="fecha"
                        class="form-control"
                        value="<?= htmlspecialchars($fecha) ?>"
                        required
                    >

                    <div class="form-text">

                        Fecha en la que se registra la novedad.

                    </div>

                </div>

            </div>


            <!-- =================================================
                 OBSERVACIÓN
                 ================================================= -->

            <div class="mt-4">

                <label
                    for="observacion"
                    class="form-label fw-bold"
                >

                    Observación

                </label>

                <textarea
                    id="observacion"
                    name="observacion"
                    class="form-control"
                    rows="4"
                    maxlength="1000"
                    placeholder="Información adicional relacionada con la novedad..."
                ><?= htmlspecialchars($observacion) ?></textarea>

                <div class="form-text">

                    Campo opcional para registrar detalles adicionales.

                </div>

            </div>


            <!-- =================================================
                 AVISO INFORMATIVO
                 ================================================= -->

            <div class="alert alert-light border mt-4">

                <div class="d-flex">

                    <div class="me-3">

                        <i
                            class="fa-solid fa-circle-info text-primary fa-lg"
                        ></i>

                    </div>

                    <div>

                        <strong>Control financiero</strong>

                        <div class="text-muted small mt-1">

                            El usuario que registra la novedad será asociado automáticamente al movimiento en el sistema.

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 BOTONES
                 ================================================= -->

            <div
                class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top"
            >

                <a
                    href="index.php"
                    class="btn btn-outline-secondary"
                >

                    <i class="fa-solid fa-xmark me-1"></i>

                    Cancelar

                </a>

                <button
                    type="submit"
                    class="btn btn-primary fw-bold"
                >

                    <i class="fa-solid fa-floppy-disk me-1"></i>

                    Registrar Novedad

                </button>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     SCRIPT DE FORMATEO DE MILES EN TIEMPO REAL
     ===================================================== -->

<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {
        const montoInput = document.getElementById('monto');

        function formatearMiles(numero) {
            if (isNaN(numero)) return '';
            return Number(numero).toLocaleString('es-CO');
        }

        // Si ya trae un valor previo (por ejemplo, al retornar por error)
        if (montoInput.value) {
            let limpio = montoInput.value.replace(/\D/g, '');
            if (limpio) {
                montoInput.value = formatearMiles(limpio);
            }
        }

        montoInput.addEventListener('input', function (e) {
            let cursorPosition = e.target.selectionStart;
            let originalLength = e.target.value.length;

            let limpio = e.target.value.replace(/\D/g, '');

            if (limpio !== '') {
                e.target.value = formatearMiles(limpio);
            } else {
                e.target.value = '';
            }

            let newLength = e.target.value.length;
            cursorPosition = cursorPosition + (newLength - originalLength);
            e.target.setSelectionRange(cursorPosition, cursorPosition);
        });
    }
);
</script>


<?php

/*
=========================================================
FOOTER DEL MÓDULO
=========================================================
*/

include("../../../template/footer_modulos.php");

?>