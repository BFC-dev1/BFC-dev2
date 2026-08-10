<?php
/*
=================================================
VERIFICAR PERMISOS DEL MÓDULO MENSUALIDADES
=================================================
*/

require_once("../../../includes/verificar_roles.php");
require_once("../../../includes/config.php");

// Permite la entrada si tiene permiso de gestión completa
// O permiso de solo lectura.
if (!tiene_permiso('mensualidades') && !tiene_permiso('ver_mensualidades')) {
    header("Location: " . $url_base . "/index.php");
    exit;
}

include("../../../modulos/auditoria/funciones/registrar_auditoria.php");

/*
=================================================
CONEXIÓN
=================================================
*/

include("../../../modulos/conexion_modulos.php");

/*
=================================================
FILTROS
=================================================
*/

$mes_actual = date('m');
$anio_actual = date('Y');

$filtro_mes = $_GET['mes'] ?? $mes_actual;
$filtro_anio = $_GET['anio'] ?? $anio_actual;
$filtro_estado = $_GET['estado'] ?? '';

/*
=================================================
DASHBOARD FINANCIERO
=================================================
*/

// TOTAL COBRADO
$sql_cobrado = "
    SELECT COALESCE(SUM(t.monto_pagado), 0) AS total
    FROM transacciones_pago t
    JOIN cuotas_mensuales c ON t.id_cuota = c.id
    WHERE MONTH(t.fecha_pago) = ?
      AND YEAR(t.fecha_pago) = ?
";

$stmt = $conexion->prepare($sql_cobrado);
$stmt->execute([$filtro_mes, $filtro_anio]);

$total_cobrado = $stmt->fetchColumn();


// TOTAL PENDIENTE
$sql_pendiente = "
    SELECT COALESCE(SUM(monto), 0) AS total
    FROM cuotas_mensuales
    WHERE mes = ?
      AND anio = ?
      AND estado = 'pendiente'
";

$stmt = $conexion->prepare($sql_pendiente);
$stmt->execute([$filtro_mes, $filtro_anio]);

$total_pendiente = $stmt->fetchColumn();


// TOTAL VENCIDOS
$sql_vencidos = "
    SELECT COUNT(DISTINCT id_deportista)
    FROM cuotas_mensuales
    WHERE estado = 'vencido'
";

$total_vencidos = $conexion->query($sql_vencidos)->fetchColumn();

/*
=================================================
CONSULTAR CUOTAS MENSUALES
=================================================
*/

$query = "
    SELECT
        c.*,
        d.nombre AS deportista_nombre,
        d.telefono
    FROM cuotas_mensuales c
    JOIN deportista d ON c.id_deportista = d.id
    WHERE c.mes = :mes
      AND c.anio = :anio
";

if (!empty($filtro_estado)) {
    $query .= " AND c.estado = :estado";
}

$query .= " ORDER BY d.nombre ASC";

$stmt = $conexion->prepare($query);

$params = [
    ':mes' => $filtro_mes,
    ':anio' => $filtro_anio
];

if (!empty($filtro_estado)) {
    $params[':estado'] = $filtro_estado;
}

$stmt->execute($params);

$cuotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
=================================================
HEADER DEL MÓDULO
=================================================
*/

$modulo_actual = 'Financiero';
$submodulo_actual = 'Mensualidades';

include("../../../template/header_modulos.php");
?>

<!-- ==========================================
     BOTÓN VOLVER
     ========================================== -->

<div class="d-flex justify-content-between align-items-center mb-3">

    <div class="d-flex gap-2">

        <a href="<?= $url_base ?>/modulos/dashboard/index.php"
           class="btn btn-outline-dark">

            ← Volver al Dashboard

        </a>

        <!-- ==========================================
             BOTÓN GENERAR CUOTAS
             SOLO PARA USUARIOS CON PERMISO DE GESTIÓN
             ========================================== -->

        <?php if (tiene_permiso('mensualidades')): ?>

            <form action="generar_cuotas.php"
                  method="POST"
                  onsubmit="return confirm('¿Deseas generar las cuotas mensuales para todos los deportistas activos?');">

                <input type="hidden"
                       name="mes"
                       value="<?= htmlspecialchars($filtro_mes) ?>">

                <input type="hidden"
                       name="anio"
                       value="<?= htmlspecialchars($filtro_anio) ?>">

                <button type="submit"
                        class="btn btn-primary">

                    <i class="fa-solid fa-rotate-right me-1"></i>
                    Generar Cuotas del Mes

                </button>

            </form>

        <?php endif; ?>

    </div>

</div>


<!-- ==========================================
     MENSAJE
     ========================================== -->

<?php if (isset($_GET['msg'])): ?>

    <div class="alert alert-success alert-dismissible fade show"
         role="alert">

        <?= htmlspecialchars($_GET['msg']) ?>

        <button type="button"
                class="btn-close"
                data-bs-dismiss="alert">
        </button>

    </div>

<?php endif; ?>


<!-- ==========================================
     TÍTULO DEL MÓDULO
     ========================================== -->

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


<!-- ==========================================
     DASHBOARD CARDS
     ========================================== -->

<div class="row g-3 mb-4">

    <!-- TOTAL COBRADO -->

    <div class="col-md-4">

        <div class="card border-0 shadow-sm border-start border-success border-4">

            <div class="card-body">

                <small class="text-muted fw-bold text-uppercase">
                    Total Cobrado (Mes)
                </small>

                <h3 class="fw-bold text-success mb-0">

                    $<?= number_format($total_cobrado, 2) ?>

                </h3>

            </div>

        </div>

    </div>


    <!-- TOTAL PENDIENTE -->

    <div class="col-md-4">

        <div class="card border-0 shadow-sm border-start border-warning border-4">

            <div class="card-body">

                <small class="text-muted fw-bold text-uppercase">
                    Total Pendiente
                </small>

                <h3 class="fw-bold text-warning mb-0">

                    $<?= number_format($total_pendiente, 2) ?>

                </h3>

            </div>

        </div>

    </div>


    <!-- TOTAL VENCIDOS -->

    <div class="col-md-4">

        <div class="card border-0 shadow-sm border-start border-danger border-4">

            <div class="card-body">

                <small class="text-muted fw-bold text-uppercase">
                    Deportistas Vencidos
                </small>

                <h3 class="fw-bold text-danger mb-0">

                    <?= $total_vencidos ?>

                </h3>

            </div>

        </div>

    </div>

</div>


<!-- ==========================================
     FILTROS
     ========================================== -->

<div class="card border-0 shadow-sm mb-4">

    <div class="card-body">

        <form method="GET"
              class="row g-3 align-items-center">

            <!-- MES -->

            <div class="col-md-3">

                <label class="form-label fw-bold">
                    Mes
                </label>

                <select name="mes"
                        class="form-select">

                    <?php for ($m = 1; $m <= 12; $m++): ?>

                        <option value="<?= sprintf('%02d', $m) ?>"
                            <?= $filtro_mes == sprintf('%02d', $m) ? 'selected' : '' ?>>

                            <?= date("F", mktime(0, 0, 0, $m, 10)) ?>

                        </option>

                    <?php endfor; ?>

                </select>

            </div>


            <!-- AÑO -->

            <div class="col-md-3">

                <label class="form-label fw-bold">
                    Año
                </label>

                <select name="anio"
                        class="form-select">

                    <option value="2026"
                        <?= $filtro_anio == '2026' ? 'selected' : '' ?>>

                        2026

                    </option>

                    <option value="2025"
                        <?= $filtro_anio == '2025' ? 'selected' : '' ?>>

                        2025

                    </option>

                </select>

            </div>


            <!-- ESTADO -->

            <div class="col-md-3">

                <label class="form-label fw-bold">
                    Estado
                </label>

                <select name="estado"
                        class="form-select">

                    <option value="">
                        Todos
                    </option>

                    <option value="pendiente"
                        <?= $filtro_estado == 'pendiente' ? 'selected' : '' ?>>

                        Pendiente

                    </option>

                    <option value="pagado"
                        <?= $filtro_estado == 'pagado' ? 'selected' : '' ?>>

                        Pagado

                    </option>

                    <option value="vencido"
                        <?= $filtro_estado == 'vencido' ? 'selected' : '' ?>>

                        Vencido

                    </option>

                </select>

            </div>


            <!-- BOTÓN FILTRAR -->

            <div class="col-md-3 d-flex align-items-end">

                <button type="submit"
                        class="btn btn-dark w-100 fw-bold mt-4">

                    <i class="fa-solid fa-filter me-1"></i>
                    Filtrar

                </button>

            </div>

        </form>

    </div>

</div>


<!-- ==========================================
     TABLA DE MENSUALIDADES
     ========================================== -->

<div class="card border-0 shadow-sm">

    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

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

                <?php if (empty($cuotas)): ?>

                    <tr>

                        <td colspan="6"
                            class="text-center py-4 text-muted">

                            No hay cuotas registradas para este periodo.
                            <?php if (tiene_permiso('mensualidades')): ?>
                                Haz clic en "Generar Cuotas del Mes".
                            <?php endif; ?>

                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($cuotas as $c): ?>

                        <tr>

                            <!-- DEPORTISTA -->

                            <td class="fw-bold">

                                <?= htmlspecialchars($c['deportista_nombre']) ?>

                            </td>


                            <!-- MES / AÑO -->

                            <td>

                                <?= sprintf('%02d', $c['mes']) ?>
                                /
                                <?= $c['anio'] ?>

                            </td>


                            <!-- VENCIMIENTO -->

                            <td>

                                <?= date(
                                    'd/m/Y',
                                    strtotime($c['fecha_vencimiento'])
                                ) ?>

                            </td>


                            <!-- MONTO -->

                            <td class="fw-bold">

                                $<?= number_format($c['monto'], 2) ?>

                            </td>


                            <!-- ESTADO -->

                            <td>

                                <?php if ($c['estado'] === 'pagado'): ?>

                                    <span class="badge bg-success">
                                        Pagado
                                    </span>

                                <?php elseif ($c['estado'] === 'pendiente'): ?>

                                    <span class="badge bg-warning text-dark">
                                        Pendiente
                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-danger">
                                        Vencido
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ==========================================
                                 ACCIONES
                                 ========================================== -->

                            <td class="text-center">

                                <?php
                                $telefono = preg_replace(
                                    '/[^0-9]/',
                                    '',
                                    $c['telefono'] ?? ''
                                );

                                $estado_texto = strtoupper($c['estado']);

                                $msg_wa =
                                    "Hola " .
                                    $c['deportista_nombre'] .
                                    ", te recordamos que tienes una cuota mensual " .
                                    $estado_texto .
                                    " por valor de $" .
                                    number_format($c['monto'], 2) .
                                    ".";

                                $url_wa = !empty($telefono)
                                    ? "https://wa.me/" .
                                      $telefono .
                                      "?text=" .
                                      urlencode($msg_wa)
                                    : "#";
                                ?>


                                <div class="d-flex justify-content-center gap-2">


                                    <!-- ==================================
                                         PAGAR
                                         SOLO GESTIÓN
                                         ================================== -->

                                    <?php if (
                                        $c['estado'] !== 'pagado'
                                        && tiene_permiso('mensualidades')
                                    ): ?>

                                        <button
                                            class="btn btn-sm btn-success fw-bold"
                                            onclick="abrirModalPago(
                                                <?= $c['id'] ?>,
                                                '<?= htmlspecialchars(
                                                    $c['deportista_nombre'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>',
                                                <?= $c['monto'] ?>
                                            )">

                                            <i class="fa-solid fa-dollar-sign"></i>
                                            Pagar

                                        </button>

                                    <?php endif; ?>


                                    <!-- ==================================
                                         RECORDATORIO WHATSAPP
                                         DISPONIBLE PARA CONSULTA
                                         ================================== -->

                                    <?php if ($c['estado'] !== 'pagado'): ?>

                                        <a
                                            href="<?= $url_wa ?>"
                                            target="_blank"
                                            class="btn btn-sm btn-outline-success
                                            <?= empty($telefono) ? 'disabled' : '' ?>">

                                            <i class="fa-brands fa-whatsapp"></i>
                                            Recordar

                                        </a>

                                    <?php endif; ?>


                                    <!-- ==================================
                                         SOLO LECTURA
                                         ================================== -->

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


<!-- ==========================================
     MODAL REGISTRAR PAGO
     ========================================== -->

<?php if (tiene_permiso('mensualidades')): ?>

    <div class="modal fade"
         id="modalPago"
         tabindex="-1">

        <div class="modal-dialog">

            <form action="procesar_pago.php"
                  method="POST"
                  class="modal-content">

                <div class="modal-header bg-success text-white">

                    <h5 class="modal-title fw-bold">

                        <i class="fa-solid fa-cash-register"></i>
                        Registrar Pago

                    </h5>

                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal">
                    </button>

                </div>


                <div class="modal-body">

                    <input type="hidden"
                           name="id_cuota"
                           id="modal_id_cuota">


                    <!-- DEPORTISTA -->

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Deportista
                        </label>

                        <input type="text"
                               id="modal_deportista"
                               class="form-control"
                               readonly>

                    </div>


                    <!-- MONTO -->

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Monto ($)
                        </label>

                        <input type="number"
                               step="0.01"
                               name="monto_pagado"
                               id="modal_monto"
                               class="form-control"
                               required>

                    </div>


                    <!-- MÉTODO DE PAGO -->

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Método de Pago
                        </label>

                        <select name="metodo_pago"
                                class="form-select"
                                required>

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


                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">

                        Cancelar

                    </button>

                    <button type="submit"
                            class="btn btn-success fw-bold">

                        Confirmar Pago

                    </button>

                </div>

            </form>

        </div>

    </div>

<?php endif; ?>


<!-- ==========================================
     JAVASCRIPT
     ========================================== -->

<script>

function abrirModalPago(idCuota, deportista, monto) {

    document.getElementById('modal_id_cuota').value = idCuota;

    document.getElementById('modal_deportista').value = deportista;

    document.getElementById('modal_monto').value = monto;

    new bootstrap.Modal(
        document.getElementById('modalPago')
    ).show();

}

</script>


<?php
/*
=================================================
FOOTER DEL MÓDULO
=================================================
*/

include("../../../template/footer_modulos.php");
?>