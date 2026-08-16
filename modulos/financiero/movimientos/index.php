<?php

/*
=========================================================
MÓDULO FINANCIERO - MOVIMIENTOS
SISTEMA: BELLAVISTA FC
ARCHIVO: index.php

FUNCIÓN:

Mostrar el libro general de movimientos financieros
registrados en el sistema.

Los movimientos pueden corresponder a:

- INGRESOS
- EGRESOS

Y pueden tener diferentes orígenes, por ejemplo:

- MENSUALIDAD
- MATRÍCULA
- EGRESO
- OTRO

IMPORTANTE:

Esta pantalla NO crea movimientos.

Los movimientos generados automáticamente por otros
módulos, como los pagos de mensualidades, llegan a esta
tabla desde sus respectivos procesos.

La tabla movimientos utiliza actualmente:

- id
- tipo
- origen
- concepto
- monto
- fecha
- metodo_pago
- referencia_id
- id_usuario
- descripcion
=========================================================
*/


/*
=========================================================
1. CONFIGURACIÓN Y PERMISOS
=========================================================
*/

/*
---------------------------------------------------------
CARGAR SISTEMA DE ROLES
---------------------------------------------------------
*/
require_once("../../../includes/verificar_roles.php");


/*
---------------------------------------------------------
CARGAR CONFIGURACIÓN GENERAL
---------------------------------------------------------
*/
require_once("../../../includes/config.php");


/*
---------------------------------------------------------
VERIFICAR PERMISO DEL MÓDULO
---------------------------------------------------------

El sidebar utiliza:

    financiero_movimientos

Por lo tanto utilizamos el mismo permiso aquí.
---------------------------------------------------------
*/
if (!tiene_permiso('financiero_movimientos')) {

    header(
        "Location: " .
        $url_base .
        "/index.php"
    );

    exit;
}


/*
=========================================================
2. AUDITORÍA
=========================================================
*/

/*
---------------------------------------------------------
CARGAR FUNCIONES DE AUDITORÍA
---------------------------------------------------------

El index no modifica información, pero dejamos
disponible la función porque el módulo financiero
utiliza el sistema general de auditoría.
---------------------------------------------------------
*/
include(
    "../../../modulos/auditoria/funciones/registrar_auditoria.php"
);


/*
=========================================================
3. CONEXIÓN A BASE DE DATOS
=========================================================
*/

include(
    "../../../modulos/conexion_modulos.php"
);


/*
=========================================================
4. FECHA ACTUAL
=========================================================
*/

$mes_actual = date('m');

$anio_actual = date('Y');


/*
=========================================================
5. RECIBIR FILTROS
=========================================================
*/

/*
---------------------------------------------------------
MES
---------------------------------------------------------
*/
$filtro_mes = $_GET['mes'] ?? $mes_actual;


/*
---------------------------------------------------------
AÑO
---------------------------------------------------------
*/
$filtro_anio = $_GET['anio'] ?? $anio_actual;


/*
---------------------------------------------------------
TIPO
---------------------------------------------------------

Valores esperados:

- INGRESO
- EGRESO
---------------------------------------------------------
*/
$filtro_tipo = trim(
    $_GET['tipo'] ?? ''
);


/*
---------------------------------------------------------
ORIGEN
---------------------------------------------------------

Se obtiene posteriormente de la base de datos para
evitar depender de valores escritos manualmente.
---------------------------------------------------------
*/
$filtro_origen = trim(
    $_GET['origen'] ?? ''
);


/*
---------------------------------------------------------
MÉTODO DE PAGO
---------------------------------------------------------
*/
$filtro_metodo = trim(
    $_GET['metodo_pago'] ?? ''
);


/*
---------------------------------------------------------
BÚSQUEDA
---------------------------------------------------------

Se utilizará para buscar por:

- concepto
- origen
- método de pago
- descripción
---------------------------------------------------------
*/
$filtro_buscar = trim(
    $_GET['buscar'] ?? ''
);


/*
=========================================================
6. VALIDAR MES
=========================================================
*/

/*
---------------------------------------------------------
Evitar valores inválidos enviados manualmente por URL.
---------------------------------------------------------
*/
if (
    !is_numeric($filtro_mes) ||
    (int) $filtro_mes < 1 ||
    (int) $filtro_mes > 12
) {

    $filtro_mes = $mes_actual;
}


/*
=========================================================
7. VALIDAR AÑO
=========================================================
*/

if (
    !is_numeric($filtro_anio) ||
    (int) $filtro_anio < 2000 ||
    (int) $filtro_anio > 2100
) {

    $filtro_anio = $anio_actual;
}


/*
=========================================================
8. OBTENER ORÍGENES EXISTENTES
=========================================================
*/

/*
---------------------------------------------------------
Los orígenes se obtienen directamente de movimientos.

Esto permite que si posteriormente aparece:

- MENSUALIDAD
- MATRICULA
- EGRESO
- UNIFORME
- OTRO

el filtro pueda mostrarlos automáticamente.
---------------------------------------------------------
*/

$stmtOrigenes = $conexion->query("
    SELECT DISTINCT
        origen
    FROM movimientos
    WHERE origen IS NOT NULL
      AND origen <> ''
    ORDER BY origen ASC
");


$origenes = $stmtOrigenes->fetchAll(
    PDO::FETCH_COLUMN
);


/*
=========================================================
9. OBTENER MÉTODOS DE PAGO EXISTENTES
=========================================================
*/

/*
---------------------------------------------------------
También obtenemos los métodos directamente de la tabla.

Esto evita asumir que únicamente existen:

- Efectivo
- Transferencia
- Tarjeta
---------------------------------------------------------
*/

$stmtMetodos = $conexion->query("
    SELECT DISTINCT
        metodo_pago
    FROM movimientos
    WHERE metodo_pago IS NOT NULL
      AND metodo_pago <> ''
    ORDER BY metodo_pago ASC
");


$metodos_pago = $stmtMetodos->fetchAll(
    PDO::FETCH_COLUMN
);


/*
=========================================================
10. DASHBOARD FINANCIERO
=========================================================
*/

/*
---------------------------------------------------------
10.1 TOTAL DE INGRESOS
---------------------------------------------------------
*/

$sql_total_ingresos = "

    SELECT
        COALESCE(
            SUM(monto),
            0
        )

    FROM movimientos

    WHERE tipo = 'INGRESO'

      AND MONTH(fecha) = ?

      AND YEAR(fecha) = ?

";


$stmt = $conexion->prepare(
    $sql_total_ingresos
);


$stmt->execute([
    $filtro_mes,
    $filtro_anio
]);


$total_ingresos = $stmt->fetchColumn();


/*
---------------------------------------------------------
10.2 TOTAL DE EGRESOS
---------------------------------------------------------
*/

$sql_total_egresos = "

    SELECT
        COALESCE(
            SUM(monto),
            0
        )

    FROM movimientos

    WHERE tipo = 'EGRESO'

      AND MONTH(fecha) = ?

      AND YEAR(fecha) = ?

";


$stmt = $conexion->prepare(
    $sql_total_egresos
);


$stmt->execute([
    $filtro_mes,
    $filtro_anio
]);


$total_egresos = $stmt->fetchColumn();


/*
---------------------------------------------------------
10.3 BALANCE
---------------------------------------------------------

Balance:

    ingresos - egresos
---------------------------------------------------------
*/

$balance =
    (float) $total_ingresos -
    (float) $total_egresos;


/*
---------------------------------------------------------
10.4 CANTIDAD DE MOVIMIENTOS
---------------------------------------------------------
*/

$sql_cantidad_movimientos = "

    SELECT
        COUNT(*)

    FROM movimientos

    WHERE MONTH(fecha) = ?

      AND YEAR(fecha) = ?

";


$stmt = $conexion->prepare(
    $sql_cantidad_movimientos
);


$stmt->execute([
    $filtro_mes,
    $filtro_anio
]);


$cantidad_movimientos =
    $stmt->fetchColumn();


/*
=========================================================
11. CONSTRUIR CONSULTA PRINCIPAL
=========================================================
*/

/*
---------------------------------------------------------
WHERE BASE
---------------------------------------------------------
*/

$where = [];

$params = [];


/*
---------------------------------------------------------
FILTRO DE MES
---------------------------------------------------------
*/

$where[] = "
    MONTH(m.fecha) = :mes
";

$params[':mes'] =
    $filtro_mes;


/*
---------------------------------------------------------
FILTRO DE AÑO
---------------------------------------------------------
*/

$where[] = "
    YEAR(m.fecha) = :anio
";

$params[':anio'] =
    $filtro_anio;


/*
---------------------------------------------------------
FILTRO POR TIPO
---------------------------------------------------------
*/

if ($filtro_tipo !== '') {

    $where[] = "
        m.tipo = :tipo
    ";

    $params[':tipo'] =
        $filtro_tipo;
}


/*
---------------------------------------------------------
FILTRO POR ORIGEN
---------------------------------------------------------
*/

if ($filtro_origen !== '') {

    $where[] = "
        m.origen = :origen
    ";

    $params[':origen'] =
        $filtro_origen;
}


/*
---------------------------------------------------------
FILTRO POR MÉTODO DE PAGO
---------------------------------------------------------
*/

if ($filtro_metodo !== '') {

    $where[] = "
        m.metodo_pago = :metodo_pago
    ";

    $params[':metodo_pago'] =
        $filtro_metodo;
}


/*
---------------------------------------------------------
FILTRO DE BÚSQUEDA
---------------------------------------------------------

Se busca en:

- concepto
- origen
- método de pago
- descripción
---------------------------------------------------------
*/

if ($filtro_buscar !== '') {

    $where[] = "

        (
            m.concepto LIKE :buscar
            OR m.origen LIKE :buscar
            OR m.metodo_pago LIKE :buscar
            OR m.descripcion LIKE :buscar
        )

    ";

    $params[':buscar'] =
        '%' .
        $filtro_buscar .
        '%';
}


/*
=========================================================
12. WHERE FINAL
=========================================================
*/

$whereSQL = '';

if (!empty($where)) {

    $whereSQL =
        "WHERE " .
        implode(
            " AND ",
            $where
        );
}


/*
=========================================================
13. CONSULTAR MOVIMIENTOS
=========================================================
*/

$sql = "

    SELECT

        m.id,
        m.tipo,
        m.origen,
        m.concepto,
        m.monto,
        m.fecha,
        m.metodo_pago,
        m.referencia_id,
        m.id_usuario,
        m.descripcion

    FROM movimientos m

    $whereSQL

    ORDER BY
        m.fecha DESC,
        m.id DESC

";


$stmt = $conexion->prepare(
    $sql
);


$stmt->execute(
    $params
);


/*
---------------------------------------------------------
OBTENER RESULTADOS
---------------------------------------------------------
*/

$movimientos =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
=========================================================
14. NOMBRE DEL MES PARA LA INTERFAZ
=========================================================
*/

$nombres_meses = [

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


$nombre_mes_actual =
    $nombres_meses[
        (int) $filtro_mes
    ];


/*
=========================================================
15. HEADER DEL MÓDULO
=========================================================
*/

$modulo_actual =
    'Financiero';


$submodulo_actual =
    'Movimientos';


/*
---------------------------------------------------------
CARGAR HEADER GENERAL
---------------------------------------------------------
*/

include(
    "../../../template/header_modulos.php"
);

?>


<!-- =====================================================
     16. BOTÓN VOLVER
     ===================================================== -->

<div class="d-flex justify-content-between align-items-center mb-3">

    <div>

        <!-- =================================================
             VOLVER AL DASHBOARD
             ================================================= -->

        <a
            href="<?= $url_base ?>/modulos/dashboard/index.php"
            class="btn btn-outline-dark"
        >

            <i class="fa-solid fa-arrow-left me-1"></i>

            Volver al Dashboard

        </a>

    </div>


    <!-- =====================================================
         NUEVO MOVIMIENTO

         ESTE BOTÓN SE DEJA PREPARADO PARA EL CRUD.
         ===================================================== -->

    <?php if (tiene_permiso('financiero_movimientos')): ?>

        <div>

            <a
                href="nuevo_movimiento.php"
                class="btn btn-primary fw-bold"
            >

                <i class="fa-solid fa-plus me-1"></i>

                Nuevo Movimiento

            </a>

        </div>

    <?php endif; ?>

</div>


<!-- =====================================================
     17. MENSAJE DE ÉXITO
     ===================================================== -->

<?php if (isset($_GET['msg'])): ?>

    <div
        class="alert alert-success alert-dismissible fade show"
        role="alert"
    >

        <?= htmlspecialchars(
            $_GET['msg']
        ) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

<?php endif; ?>


<!-- =====================================================
     18. MENSAJE DE ERROR
     ===================================================== -->

<?php if (isset($_GET['error'])): ?>

    <div
        class="alert alert-danger alert-dismissible fade show"
        role="alert"
    >

        <?= htmlspecialchars(
            $_GET['error']
        ) ?>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

<?php endif; ?>


<!-- =====================================================
     19. TÍTULO DEL MÓDULO
     ===================================================== -->

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h2 class="fw-bold">

            <i
                class="fa-solid fa-money-bill-transfer text-primary"
            ></i>

            Movimientos Financieros

        </h2>


        <p class="text-muted mb-0">

            Registro general de ingresos y egresos
            de Bellavista FC.

        </p>

    </div>

</div>


<!-- =====================================================
     20. TARJETAS RESUMEN
     ===================================================== -->

<div class="row g-3 mb-4">


    <!-- =================================================
         TOTAL INGRESOS
         ================================================= -->

    <div class="col-md-3">

        <div
            class="
                card
                border-0
                shadow-sm
                border-start
                border-success
                border-4
            "
        >

            <div class="card-body">

                <small
                    class="
                        text-muted
                        fw-bold
                        text-uppercase
                    "
                >

                    Total Ingresos

                </small>


                <h3
                    class="
                        fw-bold
                        text-success
                        mb-0
                    "
                >

                    $<?= number_format(
                        $total_ingresos,
                        2
                    ) ?>

                </h3>


                <small class="text-muted">

                    <?= htmlspecialchars(
                        $nombre_mes_actual
                    ) ?>

                    /

                    <?= htmlspecialchars(
                        $filtro_anio
                    ) ?>

                </small>

            </div>

        </div>

    </div>


    <!-- =================================================
         TOTAL EGRESOS
         ================================================= -->

    <div class="col-md-3">

        <div
            class="
                card
                border-0
                shadow-sm
                border-start
                border-danger
                border-4
            "
        >

            <div class="card-body">

                <small
                    class="
                        text-muted
                        fw-bold
                        text-uppercase
                    "
                >

                    Total Egresos

                </small>


                <h3
                    class="
                        fw-bold
                        text-danger
                        mb-0
                    "
                >

                    $<?= number_format(
                        $total_egresos,
                        2
                    ) ?>

                </h3>


                <small class="text-muted">

                    <?= htmlspecialchars(
                        $nombre_mes_actual
                    ) ?>

                    /

                    <?= htmlspecialchars(
                        $filtro_anio
                    ) ?>

                </small>

            </div>

        </div>

    </div>


    <!-- =================================================
         BALANCE
         ================================================= -->

    <div class="col-md-3">

        <div
            class="
                card
                border-0
                shadow-sm
                border-start
                border-primary
                border-4
            "
        >

            <div class="card-body">

                <small
                    class="
                        text-muted
                        fw-bold
                        text-uppercase
                    "
                >

                    Balance

                </small>


                <h3
                    class="
                        fw-bold
                        mb-0
                        <?= $balance >= 0
                            ? 'text-primary'
                            : 'text-danger'
                        ?>
                    "
                >

                    $<?= number_format(
                        $balance,
                        2
                    ) ?>

                </h3>


                <small class="text-muted">

                    Ingresos - Egresos

                </small>

            </div>

        </div>

    </div>


    <!-- =================================================
         CANTIDAD DE MOVIMIENTOS
         ================================================= -->

    <div class="col-md-3">

        <div
            class="
                card
                border-0
                shadow-sm
                border-start
                border-warning
                border-4
            "
        >

            <div class="card-body">

                <small
                    class="
                        text-muted
                        fw-bold
                        text-uppercase
                    "
                >

                    Movimientos

                </small>


                <h3
                    class="
                        fw-bold
                        text-warning
                        mb-0
                    "
                >

                    <?= (int)
                        $cantidad_movimientos ?>

                </h3>


                <small class="text-muted">

                    Registros del periodo

                </small>

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     21. FILTROS
     ===================================================== -->

<div class="card border-0 shadow-sm mb-4">

    <div class="card-body">

        <form
            method="GET"
            class="row g-3 align-items-end"
        >


            <!-- =================================================
                 MES
                 ================================================= -->

            <div class="col-md-2">

                <label
                    class="form-label fw-bold"
                >

                    Mes

                </label>


                <select
                    name="mes"
                    class="form-select"
                >

                    <?php foreach (
                        $nombres_meses
                        as $numero_mes => $nombre_mes
                    ): ?>

                        <option
                            value="<?= sprintf(
                                '%02d',
                                $numero_mes
                            ) ?>"
                            <?= (int) $filtro_mes ===
                                $numero_mes
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars(
                                $nombre_mes
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- =================================================
                 AÑO
                 ================================================= -->

            <div class="col-md-2">

                <label
                    class="form-label fw-bold"
                >

                    Año

                </label>


                <select
                    name="anio"
                    class="form-select"
                >

                    <?php

                    $anio_desde =
                        (int) $anio_actual - 2;

                    $anio_hasta =
                        (int) $anio_actual + 1;

                    ?>


                    <?php for (
                        $anio = $anio_hasta;
                        $anio >= $anio_desde;
                        $anio--
                    ): ?>

                        <option
                            value="<?= $anio ?>"
                            <?= (int) $filtro_anio ===
                                $anio
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
                 TIPO
                 ================================================= -->

            <div class="col-md-2">

                <label
                    class="form-label fw-bold"
                >

                    Tipo

                </label>


                <select
                    name="tipo"
                    class="form-select"
                >

                    <option value="">

                        Todos

                    </option>


                    <option
                        value="INGRESO"
                        <?= $filtro_tipo ===
                            'INGRESO'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Ingresos

                    </option>


                    <option
                        value="EGRESO"
                        <?= $filtro_tipo ===
                            'EGRESO'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Egresos

                    </option>

                </select>

            </div>


            <!-- =================================================
                 ORIGEN
                 ================================================= -->

            <div class="col-md-2">

                <label
                    class="form-label fw-bold"
                >

                    Origen

                </label>


                <select
                    name="origen"
                    class="form-select"
                >

                    <option value="">

                        Todos

                    </option>


                    <?php foreach (
                        $origenes
                        as $origen
                    ): ?>

                        <option
                            value="<?= htmlspecialchars(
                                $origen
                            ) ?>"
                            <?= $filtro_origen ===
                                $origen
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars(
                                $origen
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- =================================================
                 MÉTODO DE PAGO
                 ================================================= -->

            <div class="col-md-2">

                <label
                    class="form-label fw-bold"
                >

                    Método de pago

                </label>


                <select
                    name="metodo_pago"
                    class="form-select"
                >

                    <option value="">

                        Todos

                    </option>


                    <?php foreach (
                        $metodos_pago
                        as $metodo
                    ): ?>

                        <option
                            value="<?= htmlspecialchars(
                                $metodo
                            ) ?>"
                            <?= $filtro_metodo ===
                                $metodo
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars(
                                $metodo
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- =================================================
                 BÚSQUEDA
                 ================================================= -->

            <div class="col-md-2">

                <label
                    class="form-label fw-bold"
                >

                    Buscar

                </label>


                <input
                    type="text"
                    name="buscar"
                    class="form-control"
                    placeholder="Concepto..."
                    value="<?= htmlspecialchars(
                        $filtro_buscar
                    ) ?>"
                >

            </div>


            <!-- =================================================
                 BOTONES
                 ================================================= -->

            <div class="col-12">

                <div class="d-flex gap-2">

                    <!-- =========================================
                         FILTRAR
                         ========================================= -->

                    <button
                        type="submit"
                        class="
                            btn
                            btn-dark
                            fw-bold
                        "
                    >

                        <i
                            class="
                                fa-solid
                                fa-filter
                                me-1
                            "
                        ></i>

                        Filtrar

                    </button>


                    <!-- =========================================
                         LIMPIAR
                         ========================================= -->

                    <a
                        href="index.php"
                        class="
                            btn
                            btn-outline-secondary
                            fw-bold
                        "
                    >

                        <i
                            class="
                                fa-solid
                                fa-rotate-left
                                me-1
                            "
                        ></i>

                        Limpiar

                    </a>

                </div>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     22. TABLA DE MOVIMIENTOS
     ===================================================== -->

<div class="card border-0 shadow-sm">

    <div class="card-body p-0">

        <div class="table-responsive">

            <table
                class="
                    table
                    table-hover
                    align-middle
                    mb-0
                "
            >

                <thead class="table-dark">

                    <tr>

                        <th>
                            Fecha
                        </th>

                        <th>
                            Tipo
                        </th>

                        <th>
                            Origen
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
                            Descripción
                        </th>

                        <th class="text-center">
                            Acciones
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <!-- =================================================
                         SIN RESULTADOS
                         ================================================= -->

                    <?php if (
                        empty($movimientos)
                    ): ?>

                        <tr>

                            <td
                                colspan="8"
                                class="
                                    text-center
                                    py-5
                                    text-muted
                                "
                            >

                                <i
                                    class="
                                        fa-solid
                                        fa-money-bill-transfer
                                        fa-2x
                                        mb-3
                                    "
                                ></i>


                                <br>


                                No hay movimientos registrados
                                para los filtros seleccionados.


                                <?php if (
                                    tiene_permiso(
                                        'financiero_movimientos'
                                    )
                                ): ?>

                                    <br>


                                    <a
                                        href="nuevo_movimiento.php"
                                        class="
                                            btn
                                            btn-primary
                                            mt-3
                                        "
                                    >

                                        <i
                                            class="
                                                fa-solid
                                                fa-plus
                                                me-1
                                            "
                                        ></i>

                                        Registrar movimiento

                                    </a>

                                <?php endif; ?>

                            </td>

                        </tr>


                    <?php else: ?>


                        <!-- =================================================
                             RECORRER MOVIMIENTOS
                             ================================================= -->

                        <?php foreach (
                            $movimientos
                            as $movimiento
                        ): ?>


                            <tr>


                                <!-- =========================================
                                     FECHA
                                     ========================================= -->

                                <td>

                                    <?= date(
                                        'd/m/Y H:i',
                                        strtotime(
                                            $movimiento['fecha']
                                        )
                                    ) ?>

                                </td>


                                <!-- =========================================
                                     TIPO
                                     ========================================= -->

                                <td>

                                    <?php if (
                                        $movimiento['tipo'] ===
                                        'INGRESO'
                                    ): ?>

                                        <span
                                            class="
                                                badge
                                                bg-success
                                            "
                                        >

                                            <i
                                                class="
                                                    fa-solid
                                                    fa-arrow-down
                                                    me-1
                                                "
                                            ></i>

                                            Ingreso

                                        </span>


                                    <?php elseif (
                                        $movimiento['tipo'] ===
                                        'EGRESO'
                                    ): ?>

                                        <span
                                            class="
                                                badge
                                                bg-danger
                                            "
                                        >

                                            <i
                                                class="
                                                    fa-solid
                                                    fa-arrow-up
                                                    me-1
                                                "
                                            ></i>

                                            Egreso

                                        </span>


                                    <?php else: ?>

                                        <span
                                            class="
                                                badge
                                                bg-secondary
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                $movimiento['tipo']
                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- =========================================
                                     ORIGEN
                                     ========================================= -->

                                <td>

                                    <?php if (
                                        !empty(
                                            $movimiento['origen']
                                        )
                                    ): ?>

                                        <span
                                            class="
                                                badge
                                                bg-secondary
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                $movimiento['origen']
                                            ) ?>

                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="text-muted"
                                        >

                                            Sin origen

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- =========================================
                                     CONCEPTO
                                     ========================================= -->

                                <td class="fw-bold">

                                    <?= htmlspecialchars(
                                        $movimiento['concepto']
                                    ) ?>

                                </td>


                                <!-- =========================================
                                     MÉTODO DE PAGO
                                     ========================================= -->

                                <td>

                                    <?php if (
                                        !empty(
                                            $movimiento['metodo_pago']
                                        )
                                    ): ?>

                                        <span
                                            class="
                                                badge
                                                bg-light
                                                text-dark
                                                border
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                $movimiento['metodo_pago']
                                            ) ?>

                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="text-muted"
                                        >

                                            No especificado

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- =========================================
                                     MONTO
                                     ========================================= -->

                                <td>

                                    <?php if (
                                        $movimiento['tipo'] ===
                                        'INGRESO'
                                    ): ?>

                                        <span
                                            class="
                                                fw-bold
                                                text-success
                                            "
                                        >

                                            +
                                            $<?= number_format(
                                                $movimiento['monto'],
                                                2
                                            ) ?>

                                        </span>


                                    <?php elseif (
                                        $movimiento['tipo'] ===
                                        'EGRESO'
                                    ): ?>

                                        <span
                                            class="
                                                fw-bold
                                                text-danger
                                            "
                                        >

                                            -
                                            $<?= number_format(
                                                $movimiento['monto'],
                                                2
                                            ) ?>

                                        </span>


                                    <?php else: ?>

                                        <span
                                            class="fw-bold"
                                        >

                                            $<?= number_format(
                                                $movimiento['monto'],
                                                2
                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- =========================================
                                     DESCRIPCIÓN
                                     ========================================= -->

                                <td>

                                    <?php if (
                                        !empty(
                                            $movimiento['descripcion']
                                        )
                                    ): ?>

                                        <?= htmlspecialchars(
                                            $movimiento['descripcion']
                                        ) ?>

                                    <?php else: ?>

                                        <span
                                            class="text-muted"
                                        >

                                            Sin descripción

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- =========================================
                                     ACCIONES
                                     ========================================= -->

                                <td class="text-center">

                                    <div
                                        class="
                                            d-flex
                                            justify-content-center
                                            align-items-center
                                            gap-1
                                        "
                                    >


                                        <!-- =================================
                                             VER MOVIMIENTO
                                             ================================= -->

                                        <a
                                            href="ver_movimiento.php?id=<?= (int)
                                                $movimiento['id'] ?>"
                                            class="
                                                btn
                                                btn-sm
                                                btn-outline-secondary
                                            "
                                            title="Ver movimiento"
                                        >

                                            <i
                                                class="
                                                    fa-solid
                                                    fa-eye
                                                "
                                            ></i>

                                            <span
                                                class="
                                                    d-none
                                                    d-lg-inline
                                                    ms-1
                                                "
                                            >

                                                Ver

                                            </span>

                                        </a>


                                        <?php

                                        /*
                                        -------------------------------------------------
                                        MOVIMIENTOS AUTOMÁTICOS
                                        -------------------------------------------------

                                        Los movimientos provenientes de módulos como
                                        mensualidades no deberían editarse directamente
                                        desde esta pantalla.

                                        Por eso:

                                        - MENSUALIDAD
                                        - MATRICULA

                                        se consideran movimientos vinculados.

                                        Los movimientos con otros orígenes podrán
                                        gestionarse posteriormente.
                                        -------------------------------------------------
                                        */

                                        $es_movimiento_vinculado =
                                            in_array(
                                                strtoupper(
                                                    trim(
                                                        $movimiento['origen']
                                                    )
                                                ),
                                                [
                                                    'MENSUALIDAD',
                                                    'MATRICULA',
                                                    'MATRÍCULA'
                                                ],
                                                true
                                            );

                                        ?>


                                        <?php if (
                                            !$es_movimiento_vinculado
                                        ): ?>


                                            <!-- =============================
                                                 EDITAR
                                                 ============================= -->

                                            <a
                                                href="editar_movimiento.php?id=<?= (int)
                                                    $movimiento['id'] ?>"
                                                class="
                                                    btn
                                                    btn-sm
                                                    btn-outline-primary
                                                "
                                                title="Editar movimiento"
                                            >

                                                <i
                                                    class="
                                                        fa-solid
                                                        fa-pen
                                                    "
                                                ></i>

                                                <span
                                                    class="
                                                        d-none
                                                        d-lg-inline
                                                        ms-1
                                                    "
                                                >

                                                    Editar

                                                </span>

                                            </a>


                                            <!-- =============================
                                                 ELIMINAR
                                                 ============================= -->

                                            <a
                                                href="eliminar_movimiento.php?id=<?= (int)
                                                    $movimiento['id'] ?>"
                                                class="
                                                    btn
                                                    btn-sm
                                                    btn-outline-danger
                                                "
                                                title="Eliminar movimiento"
                                                onclick="
                                                    return confirm(
                                                        '¿Estás seguro de eliminar este movimiento?'
                                                    );
                                                "
                                            >

                                                <i
                                                    class="
                                                        fa-solid
                                                        fa-trash
                                                    "
                                                ></i>

                                                <span
                                                    class="
                                                        d-none
                                                        d-lg-inline
                                                        ms-1
                                                    "
                                                >

                                                    Eliminar

                                                </span>

                                            </a>


                                        <?php else: ?>


                                            <!-- =============================
                                                 MOVIMIENTO AUTOMÁTICO
                                                 ============================= -->

                                            <span
                                                class="
                                                    badge
                                                    bg-light
                                                    text-secondary
                                                    border
                                                "
                                                title="
                                                    Movimiento generado automáticamente
                                                "
                                            >

                                                <i
                                                    class="
                                                        fa-solid
                                                        fa-lock
                                                        me-1
                                                    "
                                                ></i>

                                                Automático

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


<?php

/*
=========================================================
23. FIN DEL MÓDULO
=========================================================
*/

?>