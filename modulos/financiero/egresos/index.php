<?php

/*
=========================================================
VERIFICAR PERMISOS DEL MÓDULO EGRESOS
=========================================================
*/

/*
 * Cargamos el sistema de roles y configuración general.
 * Seguimos exactamente el mismo patrón utilizado
 * actualmente en Mensualidades.
 */
require_once("../../../includes/verificar_roles.php");
require_once("../../../includes/config.php");


/*
 * El permiso principal de este módulo es:
 *
 *     egresos
 *
 * Este permiso ya existe en la tabla permiso.
 *
 * Si el usuario no tiene permiso, regresamos
 * al inicio del sistema.
 */
if (!tiene_permiso('egresos')) {

    header("Location: " . $url_base . "/index.php");
    exit;
}


/*
=========================================================
AUDITORÍA
=========================================================
*/

/*
 * Dejamos preparada la integración con el sistema
 * de auditoría existente.
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
FILTROS
=========================================================
*/

/*
 * Por defecto mostramos los egresos del mes actual.
 */
$mes_actual  = date('m');
$anio_actual = date('Y');


/*
 * Recuperar filtros enviados por GET.
 */
$filtro_mes = $_GET['mes'] ?? $mes_actual;

$filtro_anio = $_GET['anio'] ?? $anio_actual;

$filtro_categoria = trim($_GET['categoria'] ?? '');

$filtro_metodo = trim($_GET['metodo_pago'] ?? '');

$filtro_buscar = trim($_GET['buscar'] ?? '');


/*
=========================================================
CATEGORÍAS EXISTENTES
=========================================================
*/

/*
 * Como decidimos NO crear una tabla separada
 * para categorías, las categorías salen directamente
 * de la tabla egresos_financieros.
 *
 * DISTINCT evita mostrar categorías repetidas.
 */
$stmtCategorias = $conexion->query("
    SELECT DISTINCT categoria
    FROM egresos_financieros
    WHERE categoria IS NOT NULL
      AND categoria <> ''
    ORDER BY categoria ASC
");

$categorias = $stmtCategorias->fetchAll(PDO::FETCH_COLUMN);


/*
=========================================================
DASHBOARD FINANCIERO
=========================================================
*/

/*
 * TOTAL EGRESADO DEL MES
 */
$sql_total_egresos = "
    SELECT COALESCE(SUM(monto), 0)
    FROM egresos_financieros
    WHERE MONTH(fecha) = ?
      AND YEAR(fecha) = ?
";

$stmt = $conexion->prepare($sql_total_egresos);

$stmt->execute([
    $filtro_mes,
    $filtro_anio
]);

$total_egresos = $stmt->fetchColumn();


/*
 * CANTIDAD DE EGRESOS DEL MES
 */
$sql_cantidad_egresos = "
    SELECT COUNT(*)
    FROM egresos_financieros
    WHERE MONTH(fecha) = ?
      AND YEAR(fecha) = ?
";

$stmt = $conexion->prepare($sql_cantidad_egresos);

$stmt->execute([
    $filtro_mes,
    $filtro_anio
]);

$cantidad_egresos = $stmt->fetchColumn();


/*
 * MAYOR EGRESO DEL MES
 */
$sql_mayor_egreso = "
    SELECT COALESCE(MAX(monto), 0)
    FROM egresos_financieros
    WHERE MONTH(fecha) = ?
      AND YEAR(fecha) = ?
";

$stmt = $conexion->prepare($sql_mayor_egreso);

$stmt->execute([
    $filtro_mes,
    $filtro_anio
]);

$mayor_egreso = $stmt->fetchColumn();


/*
=========================================================
CONSULTAR EGRESOS
=========================================================
*/

/*
 * Consulta principal.
 *
 * Partimos de una consulta sencilla porque
 * egresos_financieros no depende de otras tablas.
 */
$query = "
    SELECT
        e.*
    FROM egresos_financieros e
    WHERE MONTH(e.fecha) = :mes
      AND YEAR(e.fecha) = :anio
";


/*
 * FILTRO POR CATEGORÍA
 */
if ($filtro_categoria !== '') {

    $query .= "
        AND e.categoria = :categoria
    ";
}


/*
 * FILTRO POR MÉTODO DE PAGO
 */
if ($filtro_metodo !== '') {

    $query .= "
        AND e.metodo_pago = :metodo_pago
    ";
}


/*
 * FILTRO DE BÚSQUEDA
 *
 * Busca en:
 *
 * - categoría
 * - concepto
 * - observación
 */
if ($filtro_buscar !== '') {

    $query .= "
        AND (
            e.categoria LIKE :buscar
            OR e.concepto LIKE :buscar
            OR e.observacion LIKE :buscar
        )
    ";
}


/*
 * Ordenamos del egreso más reciente
 * al más antiguo.
 */
$query .= "
    ORDER BY e.fecha DESC, e.id DESC
";


/*
=========================================================
EJECUTAR CONSULTA
=========================================================
*/

$stmt = $conexion->prepare($query);


/*
 * Parámetros obligatorios.
 */
$params = [

    ':mes'  => $filtro_mes,

    ':anio' => $filtro_anio

];


/*
 * Parámetro categoría.
 */
if ($filtro_categoria !== '') {

    $params[':categoria'] = $filtro_categoria;
}


/*
 * Parámetro método de pago.
 */
if ($filtro_metodo !== '') {

    $params[':metodo_pago'] = $filtro_metodo;
}


/*
 * Parámetro búsqueda.
 */
if ($filtro_buscar !== '') {

    $params[':buscar'] = '%' . $filtro_buscar . '%';
}


/*
 * Ejecutar consulta.
 */
$stmt->execute($params);


/*
 * Obtener todos los egresos.
 */
$egresos = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
=========================================================
HEADER DEL MÓDULO
=========================================================
*/

$modulo_actual = 'Financiero';

$submodulo_actual = 'Egresos';


/*
 * Cargar header general del módulo.
 *
 * Esto mantiene el mismo framework de Mensualidades.
 */
include("../../../template/header_modulos.php");

?>

<!-- =====================================================
     BOTÓN VOLVER Y NUEVO EGRESO
     ===================================================== -->

<div class="d-flex justify-content-between align-items-center mb-3">

    <div class="d-flex gap-2">

        <!-- ==========================================
             BOTÓN VOLVER AL DASHBOARD
             ========================================== -->

        <a
            href="<?= $url_base ?>/modulos/dashboard/index.php"
            class="btn btn-outline-dark"
        >

            ← Volver al Dashboard

        </a>


        <!-- ==========================================
             NUEVO EGRESO
             SOLO USUARIOS CON PERMISO DE GESTIÓN
             ========================================== -->

        <?php if (tiene_permiso('egresos')): ?>

            <a
                href="nuevo_egreso.php"
                class="btn btn-primary fw-bold"
            >

                <i class="fa-solid fa-plus me-1"></i>

                Nuevo Egreso

            </a>

        <?php endif; ?>

    </div>

</div>


<!-- =====================================================
     MENSAJE DE ÉXITO
     ===================================================== -->

<?php if (isset($_GET['msg'])): ?>

    <div
        class="alert alert-success alert-dismissible fade show"
        role="alert"
    >

        <?= htmlspecialchars($_GET['msg']) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

<?php endif; ?>


<!-- =====================================================
     TÍTULO DEL MÓDULO
     ===================================================== -->

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h2 class="fw-bold">

            <i class="fa-solid fa-money-bill-trend-up text-danger"></i>

            Egresos Financieros

        </h2>

        <p class="text-muted mb-0">

            Gestión y control de las salidas de dinero de Bellavista FC

        </p>

    </div>

</div>


<!-- =====================================================
     DASHBOARD CARDS
     ===================================================== -->

<div class="row g-3 mb-4">


    <!-- ================================================
         TOTAL EGRESADO
         ================================================ -->

    <div class="col-md-4">

        <div
            class="card border-0 shadow-sm
                   border-start border-danger border-4"
        >

            <div class="card-body">

                <small class="text-muted fw-bold text-uppercase">

                    Total Egresado

                </small>

                <h3 class="fw-bold text-danger mb-0">

                    $<?= number_format($total_egresos, 2) ?>

                </h3>

                <small class="text-muted">

                    <?= $filtro_mes ?>/<?= $filtro_anio ?>

                </small>

            </div>

        </div>

    </div>


    <!-- ================================================
         CANTIDAD DE EGRESOS
         ================================================ -->

    <div class="col-md-4">

        <div
            class="card border-0 shadow-sm
                   border-start border-primary border-4"
        >

            <div class="card-body">

                <small class="text-muted fw-bold text-uppercase">

                    Cantidad de Egresos

                </small>

                <h3 class="fw-bold text-primary mb-0">

                    <?= $cantidad_egresos ?>

                </h3>

                <small class="text-muted">

                    Registros del periodo

                </small>

            </div>

        </div>

    </div>


    <!-- ================================================
         MAYOR EGRESO
         ================================================ -->

    <div class="col-md-4">

        <div
            class="card border-0 shadow-sm
                   border-start border-warning border-4"
        >

            <div class="card-body">

                <small class="text-muted fw-bold text-uppercase">

                    Mayor Egreso

                </small>

                <h3 class="fw-bold text-warning mb-0">

                    $<?= number_format($mayor_egreso, 2) ?>

                </h3>

                <small class="text-muted">

                    Del periodo seleccionado

                </small>

            </div>

        </div>

    </div>


</div>


<!-- =====================================================
     FILTROS
     ===================================================== -->

<div class="card border-0 shadow-sm mb-4">

    <div class="card-body">

        <form
            method="GET"
            class="row g-3 align-items-end"
        >


            <!-- ==========================================
                 MES
                 ========================================== -->

            <div class="col-md-2">

                <label class="form-label fw-bold">

                    Mes

                </label>

                <select
                    name="mes"
                    class="form-select"
                >

                    <?php for ($m = 1; $m <= 12; $m++): ?>

                        <?php

                        $mes_formato = sprintf('%02d', $m);

                        ?>

                        <option
                            value="<?= $mes_formato ?>"
                            <?= $filtro_mes == $mes_formato
                                ? 'selected'
                                : '' ?>
                        >

                            <?= date(
                                "F",
                                mktime(0, 0, 0, $m, 10)
                            ) ?>

                        </option>

                    <?php endfor; ?>

                </select>

            </div>


            <!-- ==========================================
                 AÑO
                 ========================================== -->

            <div class="col-md-2">

                <label class="form-label fw-bold">

                    Año

                </label>

                <select
                    name="anio"
                    class="form-select"
                >

                    <?php

                    $anio_desde = $anio_actual - 2;

                    $anio_hasta = $anio_actual + 1;

                    ?>

                    <?php for (
                        $anio = $anio_hasta;
                        $anio >= $anio_desde;
                        $anio--
                    ): ?>

                        <option
                            value="<?= $anio ?>"
                            <?= $filtro_anio == $anio
                                ? 'selected'
                                : '' ?>
                        >

                            <?= $anio ?>

                        </option>

                    <?php endfor; ?>

                </select>

            </div>


            <!-- ==========================================
                 CATEGORÍA
                 ========================================== -->

            <div class="col-md-2">

                <label class="form-label fw-bold">

                    Categoría

                </label>

                <select
                    name="categoria"
                    class="form-select"
                >

                    <option value="">

                        Todas

                    </option>


                    <?php foreach ($categorias as $categoria): ?>

                        <option
                            value="<?= htmlspecialchars($categoria) ?>"
                            <?= $filtro_categoria === $categoria
                                ? 'selected'
                                : '' ?>
                        >

                            <?= htmlspecialchars($categoria) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- ==========================================
                 MÉTODO DE PAGO
                 ========================================== -->

            <div class="col-md-2">

                <label class="form-label fw-bold">

                    Método de pago

                </label>

                <select
                    name="metodo_pago"
                    class="form-select"
                >

                    <option value="">

                        Todos

                    </option>

                    <option
                        value="Efectivo"
                        <?= $filtro_metodo === 'Efectivo'
                            ? 'selected'
                            : '' ?>
                    >

                        Efectivo

                    </option>

                    <option
                        value="Transferencia"
                        <?= $filtro_metodo === 'Transferencia'
                            ? 'selected'
                            : '' ?>
                    >

                        Transferencia

                    </option>

                    <option
                        value="Tarjeta"
                        <?= $filtro_metodo === 'Tarjeta'
                            ? 'selected'
                            : '' ?>
                    >

                        Tarjeta

                    </option>

                </select>

            </div>


            <!-- ==========================================
                 BÚSQUEDA
                 ========================================== -->

            <div class="col-md-2">

                <label class="form-label fw-bold">

                    Buscar

                </label>

                <input
                    type="text"
                    name="buscar"
                    class="form-control"
                    placeholder="Concepto..."
                    value="<?= htmlspecialchars($filtro_buscar) ?>"
                >

            </div>


            <!-- ==========================================
                 BOTÓN FILTRAR
                 ========================================== -->

            <div class="col-md-2">

                <button
                    type="submit"
                    class="btn btn-dark w-100 fw-bold"
                >

                    <i class="fa-solid fa-filter me-1"></i>

                    Filtrar

                </button>

            </div>


        </form>

    </div>

</div>


<!-- =====================================================
     TABLA DE EGRESOS
     ===================================================== -->

<div class="card border-0 shadow-sm">

    <div class="card-body p-0">

        <div class="table-responsive">

            <table
                class="table table-hover align-middle mb-0"
            >

                <thead class="table-dark">

                    <tr>

                        <th>

                            Fecha

                        </th>

                        <th>

                            Categoría

                        </th>

                        <th>

                            Concepto

                        </th>

                        <th>

                            Método de pago

                        </th>

                        <th>

                            Monto

                        </th>

                        <th>

                            Observación

                        </th>

                        <th class="text-center">

                            Acciones

                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (empty($egresos)): ?>

                    <tr>

                        <td
                            colspan="7"
                            class="text-center py-5 text-muted"
                        >

                            <i
                                class="fa-solid fa-receipt fa-2x mb-3"
                            ></i>

                            <br>

                            No hay egresos registrados
                            para los filtros seleccionados.

                            <?php if (tiene_permiso('egresos')): ?>

                                <br>

                                <a
                                    href="nuevo_egreso.php"
                                    class="btn btn-primary mt-3"
                                >

                                    <i class="fa-solid fa-plus me-1"></i>

                                    Registrar primer egreso

                                </a>

                            <?php endif; ?>

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach ($egresos as $e): ?>

                        <tr>


                            <!-- ==================================
                                 FECHA
                                 ================================== -->

                            <td>

                                <?= date(
                                    'd/m/Y',
                                    strtotime($e['fecha'])
                                ) ?>

                            </td>


                            <!-- ==================================
                                 CATEGORÍA
                                 ================================== -->

                            <td>

                                <span
                                    class="badge bg-secondary"
                                >

                                    <?= htmlspecialchars(
                                        $e['categoria']
                                    ) ?>

                                </span>

                            </td>


                            <!-- ==================================
                                 CONCEPTO
                                 ================================== -->

                            <td class="fw-bold">

                                <?= htmlspecialchars(
                                    $e['concepto']
                                ) ?>

                            </td>


                            <!-- ==================================
                                 MÉTODO DE PAGO
                                 ================================== -->

                            <td>

                                <?php if (
                                    $e['metodo_pago']
                                    === 'Efectivo'
                                ): ?>

                                    <span
                                        class="badge bg-success"
                                    >

                                        <i
                                            class="fa-solid fa-money-bill"
                                        ></i>

                                        Efectivo

                                    </span>


                                <?php elseif (
                                    $e['metodo_pago']
                                    === 'Transferencia'
                                ): ?>

                                    <span
                                        class="badge bg-primary"
                                    >

                                        <i
                                            class="fa-solid fa-building-columns"
                                        ></i>

                                        Transferencia

                                    </span>


                                <?php else: ?>

                                    <span
                                        class="badge bg-dark"
                                    >

                                        <i
                                            class="fa-solid fa-credit-card"
                                        ></i>

                                        <?= htmlspecialchars(
                                            $e['metodo_pago']
                                        ) ?>

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ==================================
                                 MONTO
                                 ================================== -->

                            <td class="fw-bold text-danger">

                                $<?= number_format(
                                    $e['monto'],
                                    2
                                ) ?>

                            </td>


                            <!-- ==================================
                                 OBSERVACIÓN
                                 ================================== -->

                            <td>

                                <?php if (
                                    !empty($e['observacion'])
                                ): ?>

                                    <?= htmlspecialchars(
                                        $e['observacion']
                                    ) ?>

                                <?php else: ?>

                                    <span class="text-muted">

                                        Sin observación

                                    </span>

                                <?php endif; ?>

                            </td>


<!-- ==================================
     ACCIONES
     ================================== -->
<td class="text-center">

    <?php if (tiene_permiso('egresos')): ?>

        <div class="d-flex justify-content-center align-items-center gap-1">

            <!-- ==========================================
                 VER EGRESO
                 ========================================== -->
            <a
                href="ver_egreso.php?id=<?= (int) $e['id'] ?>"
                class="btn btn-sm btn-outline-secondary"
                title="Ver detalle del egreso"
            >
                <i class="fa-solid fa-eye"></i>
                <span class="d-none d-lg-inline ms-1">
                    Ver
                </span>
            </a>


            <!-- ==========================================
                 EDITAR EGRESO
                 ========================================== -->
            <a
                href="editar_egreso.php?id=<?= (int) $e['id'] ?>"
                class="btn btn-sm btn-outline-primary"
                title="Editar egreso"
            >
                <i class="fa-solid fa-pen"></i>
                <span class="d-none d-lg-inline ms-1">
                    Editar
                </span>
            </a>


            <!-- ==========================================
                 ELIMINAR EGRESO
                 ========================================== -->
            <a
                href="eliminar_egreso.php?id=<?= (int) $e['id'] ?>"
                class="btn btn-sm btn-outline-danger"
                title="Eliminar egreso"
                onclick="return confirm(
                    '¿Estás seguro de eliminar este egreso?'
                );"
            >
                <i class="fa-solid fa-trash"></i>
                <span class="d-none d-lg-inline ms-1">
                    Eliminar
                </span>
            </a>

        </div>


    <?php else: ?>

        <!-- ==========================================
             USUARIO SIN PERMISO DE GESTIÓN
             ========================================== -->
        <span class="badge bg-secondary">
            Solo lectura
        </span>

    <?php endif; ?>

</td>


                        </tr>

                    <?php endforeach; ?>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

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