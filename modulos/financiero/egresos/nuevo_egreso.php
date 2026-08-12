<?php

/*
=========================================================
VERIFICAR PERMISOS DEL MÓDULO EGRESOS
=========================================================
*/

/*
 * Cargamos el sistema de roles y configuración general.
 *
 * Utilizamos exactamente el mismo framework
 * implementado en el index.php de Egresos.
 */

require_once("../../../includes/verificar_roles.php");
require_once("../../../includes/config.php");


/*
=========================================================
PERMISO DE GESTIÓN
=========================================================
*/

/*
 * Para registrar un nuevo egreso se requiere
 * el permiso completo de gestión:
 *
 *     egresos
 *
 * Si el usuario solamente tiene permisos de consulta,
 * no puede acceder a este formulario.
 */

if (!tiene_permiso('egresos')) {

    header("Location: " . $url_base . "/modulos/financiero/egresos/index.php");
    exit;

}


/*
=========================================================
AUDITORÍA
=========================================================
*/

/*
 * Cargamos las funciones de auditoría existentes
 * en el proyecto.
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
DATOS INICIALES DEL FORMULARIO
=========================================================
*/

/*
 * Fecha actual como valor predeterminado.
 */

$fecha = date('Y-m-d');


/*
 * Campos del formulario.
 *
 * Se inicializan vacíos para evitar
 * variables no definidas.
 */

$categoria = '';
$concepto = '';
$monto = '';
$metodo_pago = '';
$observacion = '';


/*
=========================================================
CATEGORÍAS DE EGRESOS
=========================================================
*/

/*
 * IMPORTANTE:
 *
 * NO utilizamos una tabla categoria_egreso.
 *
 * La tabla del módulo continúa siendo:
 *
 *     egresos_financieros
 *
 * Las categorías se manejan directamente
 * como valores del campo:
 *
 *     categoria
 *
 * Estas son las categorías definidas
 * para Bellavista FC.
 */

$categorias = [

    'Pago de deportistas',

    'Pago de entrenadores',

    'Pago de administración',

    'Compra de balones',

    'Compra de implementos deportivos',

    'Compra de uniformes',

    'Transporte',

    'Alimentación',

    'Mantenimiento',

    'Servicios públicos',

    'Arriendo',

    'Publicidad y comunicaciones',

    'Inscripciones y competencias',

    'Gastos médicos',

    'Papelería y suministros',

    'Otros gastos'

];


/*
=========================================================
MÉTODOS DE PAGO
=========================================================
*/

/*
 * Estos valores coinciden con los utilizados
 * actualmente por el index.php de Egresos.
 */

$metodos_pago = [

    'Efectivo',

    'Transferencia',

    'Tarjeta'

];


/*
=========================================================
RECUPERAR DATOS SI HUBO ERROR
=========================================================
*/

/*
 * Si guardar_egreso.php devuelve al formulario
 * mediante parámetros GET, recuperamos los datos.
 *
 * Esto permite que el usuario no tenga que
 * escribir nuevamente todo el formulario.
 */

if (isset($_GET['error'])) {

    $fecha = $_GET['fecha'] ?? $fecha;

    $categoria = $_GET['categoria'] ?? '';

    $concepto = $_GET['concepto'] ?? '';

    $monto = $_GET['monto'] ?? '';

    $metodo_pago = $_GET['metodo_pago'] ?? '';

    $observacion = $_GET['observacion'] ?? '';

}


/*
=========================================================
HEADER DEL MÓDULO
=========================================================
*/

/*
 * Definimos el módulo y submódulo actual
 * para que el header general del sistema
 * pueda identificar correctamente dónde estamos.
 */

$modulo_actual = 'Financiero';

$submodulo_actual = 'Egresos';


/*
 * Cargamos el header general.
 */

include("../../../template/header_modulos.php");

?>

<!-- =====================================================
     BOTONES SUPERIORES
     ===================================================== -->

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <!-- ==============================================
             VOLVER A EGRESOS
             ============================================== -->

        <a
            href="index.php"
            class="btn btn-outline-dark"
        >

            <i class="fa-solid fa-arrow-left me-1"></i>

            Volver a Egresos

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

        <?= htmlspecialchars($_GET['error']) ?>

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
                class="fa-solid fa-money-bill-transfer text-danger"
            ></i>

            Nuevo Egreso Financiero

        </h2>

        <p class="text-muted mb-0">

            Registrar una salida de dinero de Bellavista FC.

        </p>

    </div>

</div>


<!-- =====================================================
     FORMULARIO PRINCIPAL
     ===================================================== -->

<div class="card border-0 shadow-sm">

    <div class="card-body p-4">

        <form
            action="guardar_egreso.php"
            method="POST"
            autocomplete="off"
        >


            <!-- =================================================
                 INFORMACIÓN DEL EGRESO
                 ================================================= -->

            <div class="mb-4">

                <h5 class="fw-bold border-bottom pb-2">

                    <i class="fa-solid fa-receipt text-danger me-2"></i>

                    Información del egreso

                </h5>

            </div>


            <!-- =================================================
                 FECHA
                 ================================================= -->

            <div class="row g-3">

                <div class="col-md-4">

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

                        Fecha en la que se realizó el egreso.

                    </div>

                </div>


                <!-- =================================================
                     CATEGORÍA
                     ================================================= -->

                <div class="col-md-8">

                    <label
                        for="categoria"
                        class="form-label fw-bold"
                    >

                        Categoría

                        <span class="text-danger">*</span>

                    </label>

                    <select
                        id="categoria"
                        name="categoria"
                        class="form-select"
                        required
                    >

                        <option value="">

                            Seleccione una categoría...

                        </option>


                        <?php foreach ($categorias as $opcion): ?>

                            <option
                                value="<?= htmlspecialchars($opcion) ?>"
                                <?= $categoria === $opcion
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= htmlspecialchars($opcion) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                    <div class="form-text">

                        Seleccione el tipo de gasto que representa
                        el egreso.

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
                    placeholder="Ejemplo: Compra de 10 balones No. 5 para entrenamientos"
                    value="<?= htmlspecialchars($concepto) ?>"
                    required
                >

                <div class="form-text">

                    Describa claramente qué se pagó o compró.

                </div>

            </div>


            <!-- =================================================
                 MONTO Y MÉTODO DE PAGO
                 ================================================= -->

            <div class="row g-3 mt-2">


                <!-- ==============================================
                     MONTO
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
                            type="number"
                            id="monto"
                            name="monto"
                            class="form-control"
                            min="0.01"
                            step="0.01"
                            placeholder="0.00"
                            value="<?= htmlspecialchars($monto) ?>"
                            required
                        >

                    </div>

                    <div class="form-text">

                        Valor total pagado por el egreso.

                    </div>

                </div>


                <!-- ==============================================
                     MÉTODO DE PAGO
                     ============================================== -->

                <div class="col-md-6">

                    <label
                        for="metodo_pago"
                        class="form-label fw-bold"
                    >

                        Método de pago

                        <span class="text-danger">*</span>

                    </label>

                    <select
                        id="metodo_pago"
                        name="metodo_pago"
                        class="form-select"
                        required
                    >

                        <option value="">

                            Seleccione un método...

                        </option>


                        <?php foreach ($metodos_pago as $metodo): ?>

                            <option
                                value="<?= htmlspecialchars($metodo) ?>"
                                <?= $metodo_pago === $metodo
                                    ? 'selected'
                                    : '' ?>
                            >

                                <?= htmlspecialchars($metodo) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

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
                    placeholder="Información adicional relacionada con el egreso..."
                ><?= htmlspecialchars($observacion) ?></textarea>

                <div class="form-text">

                    Campo opcional. Puede registrar proveedor,
                    número de factura, motivo del gasto,
                    detalle adicional, etc.

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

                        <strong>Registro financiero</strong>

                        <div class="text-muted small mt-1">

                            El usuario que registra el egreso
                            será asociado automáticamente al movimiento.
                            No es necesario seleccionarlo manualmente.

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

                <!-- ==============================================
                     CANCELAR
                     ============================================== -->

                <a
                    href="index.php"
                    class="btn btn-outline-secondary"
                >

                    <i class="fa-solid fa-xmark me-1"></i>

                    Cancelar

                </a>


                <!-- ==============================================
                     GUARDAR
                     ============================================== -->

                <button
                    type="submit"
                    class="btn btn-danger fw-bold"
                >

                    <i class="fa-solid fa-floppy-disk me-1"></i>

                    Guardar Egreso

                </button>

            </div>


        </form>

    </div>

</div>


<?php

/*
=========================================================
FOOTER DEL MÓDULO
=========================================================
*/

include("../../../template/footer_modulos.php");

?>