<?php

/*
==========================================================
 MÓDULO: FINANCIERO
 SUBMÓDULO: MENSUALIDADES
 ARCHIVO: index.php
==========================================================

 FUNCIÓN:

 - Consultar las cuotas mensuales.
 - Filtrar por mes, año y estado.
 - Mostrar resumen financiero.
 - Registrar pagos.
 - Enviar recordatorios por WhatsApp.
 - Permitir visualizar el comprobante de pago cuando
   una mensualidad ya se encuentra pagada.

 PERMISOS:

 - mensualidades
      Permite gestionar cuotas y registrar pagos.

 - ver_mensualidades
      Permite consultar las mensualidades en modo lectura.

==========================================================
*/


/*
==========================================================
 1. VERIFICAR PERMISOS
==========================================================
*/

require_once("../../../includes/verificar_roles.php");
require_once("../../../includes/config.php");


/*
----------------------------------------------------------
 El usuario debe tener al menos uno de estos permisos:

 - mensualidades
 - ver_mensualidades

 Si no posee ninguno, regresamos al inicio.
----------------------------------------------------------
*/

if (
    !tiene_permiso('mensualidades') &&
    !tiene_permiso('ver_mensualidades')
) {

    header(
        "Location: " .
        $url_base .
        "/index.php"
    );

    exit;
}


/*
==========================================================
 2. FUNCIONES DE AUDITORÍA
==========================================================
*/

require_once(
    "../../../modulos/auditoria/funciones/registrar_auditoria.php"
);


/*
==========================================================
 3. CONEXIÓN A BASE DE DATOS
==========================================================
*/

require_once(
    "../../../modulos/conexion_modulos.php"
);


/*
==========================================================
 4. FILTROS
==========================================================
*/

/*
----------------------------------------------------------
 Valores por defecto:

 Mes  = mes actual
 Año  = año actual
 Estado = todos
----------------------------------------------------------
*/

$mes_actual = date('m');
$anio_actual = date('Y');


$filtro_mes = $_GET['mes'] ?? $mes_actual;

$filtro_anio = $_GET['anio'] ?? $anio_actual;

$filtro_estado = $_GET['estado'] ?? '';


/*
----------------------------------------------------------
 Validar mes.
----------------------------------------------------------
*/

if (
    !is_numeric($filtro_mes) ||
    (int)$filtro_mes < 1 ||
    (int)$filtro_mes > 12
) {

    $filtro_mes = $mes_actual;
}


/*
----------------------------------------------------------
 Validar año.

 Permitimos años razonables para evitar valores inválidos
 enviados manualmente por URL.
----------------------------------------------------------
*/

if (
    !is_numeric($filtro_anio) ||
    (int)$filtro_anio < 2020 ||
    (int)$filtro_anio > 2100
) {

    $filtro_anio = $anio_actual;
}


/*
----------------------------------------------------------
 Validar estado.
----------------------------------------------------------
*/

$estados_validos = [
    '',
    'pendiente',
    'pagado',
    'vencido'
];

if (!in_array($filtro_estado, $estados_validos, true)) {

    $filtro_estado = '';
}


/*
==========================================================
 5. DASHBOARD FINANCIERO
==========================================================
*/


/*
----------------------------------------------------------
 TOTAL COBRADO

 Sumamos los pagos registrados durante el mes/año
 seleccionado.
----------------------------------------------------------
*/

$sql_cobrado = "

    SELECT
        COALESCE(
            SUM(t.monto_pagado),
            0
        ) AS total

    FROM transacciones_pago t

    INNER JOIN cuotas_mensuales c
        ON t.id_cuota = c.id

    WHERE MONTH(t.fecha_pago) = ?
      AND YEAR(t.fecha_pago) = ?

";


$stmt = $conexion->prepare($sql_cobrado);

$stmt->execute([
    $filtro_mes,
    $filtro_anio
]);

$total_cobrado = $stmt->fetchColumn();


/*
----------------------------------------------------------
 TOTAL PENDIENTE

 Suma las cuotas que todavía no han sido pagadas.
----------------------------------------------------------
*/

$sql_pendiente = "

    SELECT
        COALESCE(
            SUM(monto),
            0
        ) AS total

    FROM cuotas_mensuales

    WHERE mes = ?
      AND anio = ?
      AND estado = 'pendiente'

";


$stmt = $conexion->prepare($sql_pendiente);

$stmt->execute([
    $filtro_mes,
    $filtro_anio
]);

$total_pendiente = $stmt->fetchColumn();


/*
----------------------------------------------------------
 TOTAL VENCIDOS

 Cuenta la cantidad de deportistas que poseen
 cuotas vencidas.
----------------------------------------------------------
*/

$sql_vencidos = "

    SELECT
        COUNT(DISTINCT id_deportista)

    FROM cuotas_mensuales

    WHERE estado = 'vencido'

";


$total_vencidos = $conexion
    ->query($sql_vencidos)
    ->fetchColumn();


/*
==========================================================
 6. CONSULTAR CUOTAS MENSUALES
==========================================================
*/


/*
----------------------------------------------------------
 Obtenemos:

 - Datos de la cuota.
 - Nombre del deportista.
 - Teléfono.

 IMPORTANTE:

 El campo c.* incluye:

 - id
 - id_deportista
 - mes
 - anio
 - monto
 - fecha_vencimiento
 - fecha_pago
 - estado

 Por eso podemos utilizar:

 $c['id']
 $c['estado']
 $c['fecha_pago']

 etc.
----------------------------------------------------------
*/

$query = "
    SELECT
        c.*,

        d.nombre AS deportista_nombre,
        d.telefono,

        /* ==========================================
           INFORMACIÓN DEL PAGO
           ========================================== */

        t.id AS id_pago,
        t.monto_pagado,
        t.fecha_pago,
        t.metodo_pago,
        t.observacion

    FROM cuotas_mensuales c

    INNER JOIN deportista d
        ON c.id_deportista = d.id

    LEFT JOIN transacciones_pago t
        ON t.id_cuota = c.id

    WHERE c.mes = :mes
      AND c.anio = :anio
";


/*
----------------------------------------------------------
 Si se seleccionó un estado específico, agregamos
 el filtro.
----------------------------------------------------------
*/

if ($filtro_estado !== '') {

    $query .= "

        AND c.estado = :estado

    ";
}


/*
----------------------------------------------------------
 Ordenar alfabéticamente por deportista.
----------------------------------------------------------
*/

$query .= "

    ORDER BY d.nombre ASC

";


/*
----------------------------------------------------------
 Preparar consulta.
----------------------------------------------------------
*/

$stmt = $conexion->prepare($query);


/*
----------------------------------------------------------
 Parámetros.
----------------------------------------------------------
*/

$params = [

    ':mes' => $filtro_mes,

    ':anio' => $filtro_anio

];


if ($filtro_estado !== '') {

    $params[':estado'] = $filtro_estado;
}


/*
----------------------------------------------------------
 Ejecutar consulta.
----------------------------------------------------------
*/

$stmt->execute($params);


/*
----------------------------------------------------------
 Obtener resultados.
----------------------------------------------------------
*/

$cuotas = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
==========================================================
 7. HEADER DEL MÓDULO
==========================================================
*/

$modulo_actual = 'Financiero';

$submodulo_actual = 'Mensualidades';


include(
    "../../../template/header_modulos.php"
);

?>


<!-- =====================================================
     BOTÓN VOLVER Y GENERAR CUOTAS
====================================================== -->

<div class="d-flex justify-content-between align-items-center mb-3">

    <div class="d-flex gap-2">


        <!-- =================================================
             VOLVER AL DASHBOARD
        ================================================== -->

        <a
            href="<?= htmlspecialchars($url_base) ?>/modulos/dashboard/index.php"
            class="btn btn-outline-dark"
        >

            <i class="fa-solid fa-arrow-left me-1"></i>

            Volver al Dashboard

        </a>


        <!-- =================================================
             GENERAR CUOTAS

             SOLO USUARIOS CON PERMISO DE GESTIÓN
        ================================================== -->

        <?php if (tiene_permiso('mensualidades')): ?>

            <form
                action="generar_cuotas.php"
                method="POST"
                onsubmit="return confirm(
                    '¿Deseas generar las cuotas mensuales para todos los deportistas activos?'
                );"
            >

                <input
                    type="hidden"
                    name="mes"
                    value="<?= htmlspecialchars($filtro_mes) ?>"
                >

                <input
                    type="hidden"
                    name="anio"
                    value="<?= htmlspecialchars($filtro_anio) ?>"
                >

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i class="fa-solid fa-rotate-right me-1"></i>

                    Generar Cuotas del Mes

                </button>

            </form>

        <?php endif; ?>

    </div>

</div>


<!-- =====================================================
     MENSAJE DEL SISTEMA
====================================================== -->

<?php if (isset($_GET['msg'])): ?>

    <div
        class="alert alert-success alert-dismissible fade show"
        role="alert"
    >

        <i class="fa-solid fa-circle-check me-1"></i>

        <?= htmlspecialchars($_GET['msg']) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

<?php endif; ?>


<!-- =====================================================
     TÍTULO
====================================================== -->

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h2 class="fw-bold">

            <i class="fa-solid fa-credit-card text-primary"></i>

            Mensualidades

        </h2>

        <p class="text-muted mb-0">

            Gestión de cuotas mensuales y estado de cartera

        </p>

    </div>

</div>


<!-- =====================================================
     DASHBOARD FINANCIERO
====================================================== -->

<div class="row g-3 mb-4">


    <!-- =================================================
         TOTAL COBRADO
    ================================================== -->

    <div class="col-md-4">

        <div
            class="card border-0 shadow-sm
                   border-start border-success border-4"
        >

            <div class="card-body">

                <small
                    class="text-muted fw-bold text-uppercase"
                >

                    Total Cobrado (Mes)

                </small>

                <h3 class="fw-bold text-success mb-0">

                    $
                    <?= number_format(
                        (float)$total_cobrado,
                        2,
                        ',',
                        '.'
                    ) ?>

                </h3>

            </div>

        </div>

    </div>


    <!-- =================================================
         TOTAL PENDIENTE
    ================================================== -->

    <div class="col-md-4">

        <div
            class="card border-0 shadow-sm
                   border-start border-warning border-4"
        >

            <div class="card-body">

                <small
                    class="text-muted fw-bold text-uppercase"
                >

                    Total Pendiente

                </small>

                <h3 class="fw-bold text-warning mb-0">

                    $
                    <?= number_format(
                        (float)$total_pendiente,
                        2,
                        ',',
                        '.'
                    ) ?>

                </h3>

            </div>

        </div>

    </div>


    <!-- =================================================
         TOTAL VENCIDOS
    ================================================== -->

    <div class="col-md-4">

        <div
            class="card border-0 shadow-sm
                   border-start border-danger border-4"
        >

            <div class="card-body">

                <small
                    class="text-muted fw-bold text-uppercase"
                >

                    Deportistas Vencidos

                </small>

                <h3 class="fw-bold text-danger mb-0">

                    <?= (int)$total_vencidos ?>

                </h3>

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     FILTROS
====================================================== -->

<div class="card border-0 shadow-sm mb-4">

    <div class="card-body">

        <form
            method="GET"
            class="row g-3 align-items-center"
        >


            <!-- =================================================
                 MES
            ================================================== -->

            <div class="col-md-3">

                <label class="form-label fw-bold">

                    Mes

                </label>

                <select
                    name="mes"
                    class="form-select"
                >

                    <?php

                    $meses = [

                        1  => 'Enero',
                        2  => 'Febrero',
                        3  => 'Marzo',
                        4  => 'Abril',
                        5  => 'Mayo',
                        6  => 'Junio',
                        7  => 'Julio',
                        8  => 'Agosto',
                        9  => 'Septiembre',
                        10 => 'Octubre',
                        11 => 'Noviembre',
                        12 => 'Diciembre'

                    ];

                    ?>

                    <?php foreach ($meses as $numero => $nombre): ?>

                        <option
                            value="<?= sprintf('%02d', $numero) ?>"
                            <?= (
                                (int)$filtro_mes === $numero
                            )
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars($nombre) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- =================================================
                 AÑO
            ================================================== -->

            <div class="col-md-3">

                <label class="form-label fw-bold">

                    Año

                </label>

                <select
                    name="anio"
                    class="form-select"
                >

                    <?php

                    /*
                    ------------------------------------------------
                    Generamos algunos años automáticamente.
                    ------------------------------------------------
                    */

                    $anio_inicio = 2025;

                    $anio_fin = max(
                        2026,
                        (int)$anio_actual
                    );

                    ?>

                    <?php for (
                        $anio = $anio_fin;
                        $anio >= $anio_inicio;
                        $anio--
                    ): ?>

                        <option
                            value="<?= $anio ?>"
                            <?= (
                                (int)$filtro_anio === $anio
                            )
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= $anio ?>

                        </option>

                    <?php endfor; ?>

                </select>

            </div>


            <!-- =================================================
                 ESTADO
            ================================================== -->

            <div class="col-md-3">

                <label class="form-label fw-bold">

                    Estado

                </label>

                <select
                    name="estado"
                    class="form-select"
                >

                    <option value="">

                        Todos

                    </option>


                    <option
                        value="pendiente"
                        <?= $filtro_estado === 'pendiente'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Pendiente

                    </option>


                    <option
                        value="pagado"
                        <?= $filtro_estado === 'pagado'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Pagado

                    </option>


                    <option
                        value="vencido"
                        <?= $filtro_estado === 'vencido'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Vencido

                    </option>

                </select>

            </div>


            <!-- =================================================
                 BOTÓN FILTRAR
            ================================================== -->

            <div class="col-md-3 d-flex align-items-end">

                <button
                    type="submit"
                    class="btn btn-dark w-100 fw-bold mt-4"
                >

                    <i class="fa-solid fa-filter me-1"></i>

                    Filtrar

                </button>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     TABLA DE MENSUALIDADES
====================================================== -->

<div class="card border-0 shadow-sm">

    <div class="card-body p-0">

        <div class="table-responsive">

            <table
                class="table table-hover align-middle mb-0"
            >

                <thead class="table-dark">

                    <tr>

                        <th>
                            Deportista
                        </th>

                        <th>
                            Mes / Año
                        </th>

                        <th>
                            Vencimiento
                        </th>

                        <th>
                            Monto
                        </th>

                        <th>
                            Estado
                        </th>

                        <th class="text-center">
                            Acciones
                        </th>

                    </tr>

                </thead>


                <tbody>


                <!-- =================================================
                     SIN RESULTADOS
                ================================================== -->

                <?php if (empty($cuotas)): ?>

                    <tr>

                        <td
                            colspan="6"
                            class="text-center py-4 text-muted"
                        >

                            <i
                                class="fa-solid fa-circle-info me-1"
                            ></i>

                            No hay cuotas registradas para este
                            periodo.

                            <?php if (
                                tiene_permiso('mensualidades')
                            ): ?>

                                Haz clic en
                                "Generar Cuotas del Mes".

                            <?php endif; ?>

                        </td>

                    </tr>


                <?php else: ?>


                    <!-- =================================================
                         RECORRER CUOTAS
                    ================================================== -->

                    <?php foreach ($cuotas as $c): ?>

                        <tr>


                            <!-- =========================================
                                 DEPORTISTA
                            ========================================== -->

                            <td class="fw-bold">

                                <?= htmlspecialchars(
                                    $c['deportista_nombre']
                                ) ?>

                            </td>


                            <!-- =========================================
                                 MES / AÑO
                            ========================================== -->

                            <td>

                                <?= sprintf(
                                    '%02d',
                                    (int)$c['mes']
                                ) ?>

                                /

                                <?= (int)$c['anio'] ?>

                            </td>


                            <!-- =========================================
                                 FECHA DE VENCIMIENTO
                            ========================================== -->

                            <td>

                                <?php if (
                                    !empty(
                                        $c['fecha_vencimiento']
                                    )
                                ): ?>

                                    <?= date(
                                        'd/m/Y',
                                        strtotime(
                                            $c['fecha_vencimiento']
                                        )
                                    ) ?>

                                <?php else: ?>

                                    <span class="text-muted">

                                        No definida

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- =========================================
                                 MONTO
                            ========================================== -->

                            <td class="fw-bold">

                                $
                                <?= number_format(
                                    (float)$c['monto'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>

                            </td>


                            <!-- =========================================
                                 ESTADO
                            ========================================== -->

                            <td>

                                <?php if (
                                    $c['estado'] === 'pagado'
                                ): ?>

                                    <span class="badge bg-success">

                                        <i
                                            class="fa-solid fa-check me-1"
                                        ></i>

                                        Pagado

                                    </span>


                                <?php elseif (
                                    $c['estado'] === 'pendiente'
                                ): ?>

                                    <span
                                        class="badge bg-warning text-dark"
                                    >

                                        <i
                                            class="fa-solid fa-clock me-1"
                                        ></i>

                                        Pendiente

                                    </span>


                                <?php else: ?>

                                    <span class="badge bg-danger">

                                        <i
                                            class="fa-solid fa-triangle-exclamation me-1"
                                        ></i>

                                        Vencido

                                    </span>

                                <?php endif; ?>

                            </td>


<!-- ==========================================
     ACCIONES
     ========================================== -->

<td class="text-center">

    <?php
    /*
    =================================================
    PREPARAR TELÉFONO PARA WHATSAPP
    =================================================
    */

    $telefono = preg_replace(
        '/[^0-9]/',
        '',
        $c['telefono'] ?? ''
    );


    /*
    =================================================
    MENSAJE DE WHATSAPP
    =================================================
    */

    $estado_texto = strtoupper($c['estado']);

    $msg_wa =
        "Hola " .
        $c['deportista_nombre'] .
        ", te recordamos que tienes una cuota mensual " .
        $estado_texto .
        " por valor de $" .
        number_format($c['monto'], 2) .
        ".";


    /*
    =================================================
    URL DE WHATSAPP
    =================================================
    */

    $url_wa = !empty($telefono)
        ? "https://wa.me/" .
          $telefono .
          "?text=" .
          urlencode($msg_wa)
        : "#";
    ?>


    <div class="d-flex justify-content-center gap-2 flex-wrap">


        <!-- ==================================================
             BOTÓN PAGAR
             SOLO USUARIOS CON PERMISO DE GESTIÓN
             ================================================== -->

        <?php if (
            $c['estado'] !== 'pagado'
            && tiene_permiso('mensualidades')
        ): ?>

            <button
                type="button"
                class="btn btn-sm btn-success fw-bold"
                onclick="abrirModalPago(
                    <?= (int) $c['id'] ?>,
                    '<?= htmlspecialchars(
                        $c['deportista_nombre'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>',
                    <?= (float) $c['monto'] ?>
                )">

                <i class="fa-solid fa-dollar-sign"></i>
                Pagar

            </button>

        <?php endif; ?>


        <!-- ==================================================
             BOTÓN WHATSAPP
             SOLO CUOTAS NO PAGADAS
             ================================================== -->

        <?php if ($c['estado'] !== 'pagado'): ?>

            <a
                href="<?= htmlspecialchars($url_wa) ?>"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-sm btn-outline-success
                <?= empty($telefono) ? 'disabled' : '' ?>"
                title="Enviar recordatorio por WhatsApp">

                <i class="fa-brands fa-whatsapp"></i>
                Recordar

            </a>

        <?php endif; ?>


        <!-- ==================================================
             BOTÓN VER COMPROBANTE
             SOLO CUOTAS PAGADAS
             Y QUE TENGAN UNA TRANSACCIÓN REGISTRADA
             ================================================== -->

        <?php if (
            $c['estado'] === 'pagado'
            && !empty($c['id_pago'])
        ): ?>

<a
    href="<?= htmlspecialchars($url_base) ?>/modulos/financiero/mensualidades/comprobantes_pago/ver_comprobante.php?id=<?= (int) $c['id_pago'] ?>"
    target="_blank"
    class="btn btn-sm btn-outline-primary fw-bold"
    title="Ver comprobante de pago">

    <i class="fa-solid fa-file-invoice"></i>
    Comprobante

</a>

        <?php endif; ?>


        <!-- ==================================================
             INDICADOR PARA USUARIOS DE SOLO LECTURA
             ================================================== -->

        <?php if (!tiene_permiso('mensualidades')): ?>

            <span class="badge bg-secondary d-flex align-items-center">

                Solo lectura

            </span>

        <?php endif; ?>


    </div>

</td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>

</div>


<!-- =====================================================
     MODAL REGISTRAR PAGO

     Solo se carga si el usuario tiene permiso
     de gestión de mensualidades.
====================================================== -->

<?php if (tiene_permiso('mensualidades')): ?>

    <div
        class="modal fade"
        id="modalPago"
        tabindex="-1"
        aria-hidden="true"
    >

        <div class="modal-dialog">

            <form
                action="procesar_pago.php"
                method="POST"
                class="modal-content"
            >


                <!-- =========================================
                     CABECERA
                ========================================== -->

                <div
                    class="modal-header bg-success text-white"
                >

                    <h5
                        class="modal-title fw-bold"
                    >

                        <i
                            class="fa-solid fa-cash-register me-1"
                        ></i>

                        Registrar Pago

                    </h5>


                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar"
                    ></button>

                </div>


                <!-- =========================================
                     CUERPO
                ========================================== -->

                <div class="modal-body">


                    <!-- =====================================
                         ID DE CUOTA
                    ====================================== -->

                    <input
                        type="hidden"
                        name="id_cuota"
                        id="modal_id_cuota"
                    >


                    <!-- =====================================
                         DEPORTISTA
                    ====================================== -->

                    <div class="mb-3">

                        <label
                            class="form-label fw-bold"
                        >

                            Deportista

                        </label>

                        <input
                            type="text"
                            id="modal_deportista"
                            class="form-control"
                            readonly
                        >

                    </div>


                    <!-- =====================================
                         MONTO
                    ====================================== -->

                    <div class="mb-3">

                        <label
                            class="form-label fw-bold"
                        >

                            Monto ($)

                        </label>

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="monto_pagado"
                            id="modal_monto"
                            class="form-control"
                            required
                        >

                    </div>


                    <!-- =====================================
                         MÉTODO DE PAGO
                    ====================================== -->

                    <div class="mb-3">

                        <label
                            class="form-label fw-bold"
                        >

                            Método de Pago

                        </label>

                        <select
                            name="metodo_pago"
                            class="form-select"
                            required
                        >

                            <option value="Efectivo">

                                Efectivo

                            </option>

                            <option value="Transferencia">

                                Transferencia

                            </option>

                            <option value="Tarjeta">

                                Tarjeta

                            </option>

                        </select>

                    </div>

                </div>


                <!-- =========================================
                     PIE DEL MODAL
                ========================================== -->

                <div class="modal-footer">


                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >

                        Cancelar

                    </button>


                    <button
                        type="submit"
                        class="btn btn-success fw-bold"
                    >

                        <i
                            class="fa-solid fa-check me-1"
                        ></i>

                        Confirmar Pago

                    </button>

                </div>

            </form>

        </div>

    </div>

<?php endif; ?>


<!-- =====================================================
     JAVASCRIPT
====================================================== -->

<script>

/*
==========================================================
 ABRIR MODAL DE PAGO
==========================================================

 Recibe:

 - idCuota
 - deportista
 - monto

 Y los coloca dentro del formulario.
==========================================================
*/

function abrirModalPago(
    idCuota,
    deportista,
    monto
) {

    /*
    ------------------------------------------------------
    ID DE LA CUOTA
    ------------------------------------------------------
    */

    document.getElementById(
        'modal_id_cuota'
    ).value = idCuota;


    /*
    ------------------------------------------------------
    NOMBRE DEL DEPORTISTA
    ------------------------------------------------------
    */

    document.getElementById(
        'modal_deportista'
    ).value = deportista;


    /*
    ------------------------------------------------------
    MONTO
    ------------------------------------------------------
    */

    document.getElementById(
        'modal_monto'
    ).value = monto;


    /*
    ------------------------------------------------------
    ABRIR MODAL
    ------------------------------------------------------
    */

    const modalElemento =
        document.getElementById('modalPago');


    const modal =
        new bootstrap.Modal(
            modalElemento
        );


    modal.show();

}

</script>


<?php

/*
==========================================================
 FOOTER DEL MÓDULO
==========================================================
*/

include(
    "../../../template/footer_modulos.php"
);

?>