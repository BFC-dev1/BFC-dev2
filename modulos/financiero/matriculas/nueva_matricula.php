<?php

/*
=================================================
NUEVA MATRÍCULA
MÓDULO FINANCIERO
=================================================
*/

/*
=================================================
CONFIGURACIÓN Y PERMISOS
=================================================
*/

require_once "../../../includes/config.php";
require_once "../../../includes/verificar_roles.php";


/*
=================================================
VERIFICAR PERMISO
=================================================
*/

if (!tiene_permiso('financiero')) {
    header("Location: " . $url_base . "/index.php");
    exit;
}


/*
=================================================
CONEXIÓN
=================================================
*/

require_once "../../../includes/conexion.php";

/** @var PDO $conexion */


/*
=================================================
AUDITORÍA
=================================================
*/

require_once "../../auditoria/funciones/registrar_auditoria.php";


/*
=================================================
VARIABLES
=================================================
*/

$mensajeError = '';

$deportista_id = $_POST['deportista_id'] ?? '';
$monto = $_POST['monto'] ?? '';
$fecha_matricula = $_POST['fecha_matricula'] ?? date('Y-m-d');
$estado = $_POST['estado'] ?? 'pagado';
$metodo_pago = $_POST['metodo_pago'] ?? '';
$observacion = $_POST['observacion'] ?? '';

$deportistaSeleccionado = null;


/*
=================================================
OBTENER DEPORTISTAS
=================================================

Se cargan los deportistas activos para permitir
seleccionar al deportista que realizará su
matrícula de ingreso.

=================================================
*/

$stmtDeportistas = $conexion->query("
    SELECT
        d.id,
        d.nombre,
        d.documento,
        d.categoria_id,
        d.estado,
        d.foto,
        c.nombre AS categoria_nombre,
        ud.acudiente,
        ud.parentesco

    FROM deportista d

    LEFT JOIN categoria c
        ON c.id = d.categoria_id

    LEFT JOIN usuario_deportista ud
        ON ud.deportista_id = d.id

    WHERE d.estado = 'activo'

    ORDER BY d.nombre ASC
");

$deportistas = $stmtDeportistas->fetchAll(PDO::FETCH_ASSOC);


/*
=================================================
OBTENER DEPORTISTA SELECCIONADO
=================================================

Cuando el usuario selecciona un deportista,
se cargan sus datos para mostrarlos en pantalla.

=================================================
*/

if (!empty($deportista_id)) {

    foreach ($deportistas as $deportista) {

        if ((int)$deportista['id'] === (int)$deportista_id) {

            $deportistaSeleccionado = $deportista;

            break;
        }
    }
}


/*
=================================================
PROCESAR FORMULARIO
=================================================
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        /*
        =============================================
        VALIDAR DEPORTISTA
        =============================================
        */

        if (empty($deportista_id)) {

            throw new Exception(
                "Debe seleccionar un deportista."
            );
        }


        /*
        =============================================
        VALIDAR EXISTENCIA
        =============================================
        */

        $stmt = $conexion->prepare("
            SELECT
                d.*,
                c.nombre AS categoria_nombre,
                ud.acudiente,
                ud.parentesco

            FROM deportista d

            LEFT JOIN categoria c
                ON c.id = d.categoria_id

            LEFT JOIN usuario_deportista ud
                ON ud.deportista_id = d.id

            WHERE d.id = :id
        ");

        $stmt->execute([
            ':id' => (int)$deportista_id
        ]);

        $deportista = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$deportista) {

            throw new Exception(
                "El deportista seleccionado no existe."
            );
        }


        /*
        =============================================
        VALIDAR MATRÍCULA EXISTENTE
        =============================================

        La matrícula es de ingreso y solamente
        puede existir una matrícula por deportista.

        =============================================
        */

        $stmtExiste = $conexion->prepare("
            SELECT id
            FROM matriculas
            WHERE id_deportista = :deportista_id
            LIMIT 1
        ");

        $stmtExiste->execute([
            ':deportista_id' => (int)$deportista_id
        ]);

        $matriculaExistente = $stmtExiste->fetch(PDO::FETCH_ASSOC);


        if ($matriculaExistente) {

            throw new Exception(
                "Este deportista ya tiene una matrícula registrada."
            );
        }


        /*
        =============================================
        VALIDAR MONTO
        =============================================
        */

        if ($monto === '' || !is_numeric($monto)) {

            throw new Exception(
                "Debe ingresar un valor válido para la matrícula."
            );
        }


        $monto = (float)$monto;


        if ($monto <= 0) {

            throw new Exception(
                "El valor de la matrícula debe ser mayor que cero."
            );
        }


        /*
        =============================================
        VALIDAR FECHA
        =============================================
        */

        if (empty($fecha_matricula)) {

            throw new Exception(
                "Debe seleccionar la fecha de matrícula."
            );
        }


        /*
        =============================================
        VALIDAR ESTADO
        =============================================
        */

        $estadosPermitidos = [
            'pendiente',
            'pagado',
            'anulado'
        ];

        if (!in_array($estado, $estadosPermitidos, true)) {

            throw new Exception(
                "El estado seleccionado no es válido."
            );
        }


        /*
        =============================================
        VALIDAR MÉTODO DE PAGO
        =============================================
        */

        $metodosPermitidos = [
            'Efectivo',
            'Transferencia',
            'Nequi',
            'Daviplata',
            'Tarjeta'
        ];

        if ($estado === 'pagado') {

            if (
                empty($metodo_pago) ||
                !in_array($metodo_pago, $metodosPermitidos, true)
            ) {

                throw new Exception(
                    "Debe seleccionar el método de pago."
                );
            }

        } else {

            $metodo_pago = null;
        }


        /*
        =============================================
        FECHA DE PAGO
        =============================================
        */

        $fecha_pago = null;

        if ($estado === 'pagado') {

            $fecha_pago = date('Y-m-d H:i:s');
        }


        /*
        =============================================
        AÑO
        =============================================

        Se registra automáticamente el año de la
        fecha de matrícula.

        =============================================
        */

        $anio = date(
            'Y',
            strtotime($fecha_matricula)
        );


        /*
        =============================================
        INSERTAR MATRÍCULA
        =============================================
        */

        $stmtInsert = $conexion->prepare("
            INSERT INTO matriculas (
                id_deportista,
                anio,
                monto,
                fecha_matricula,
                estado,
                fecha_pago,
                metodo_pago,
                observacion,
                id_usuario_registra
            )

            VALUES (
                :id_deportista,
                :anio,
                :monto,
                :fecha_matricula,
                :estado,
                :fecha_pago,
                :metodo_pago,
                :observacion,
                :id_usuario_registra
            )
        ");

        $stmtInsert->execute([

            ':id_deportista' =>
                (int)$deportista_id,

            ':anio' =>
                $anio,

            ':monto' =>
                $monto,

            ':fecha_matricula' =>
                $fecha_matricula,

            ':estado' =>
                $estado,

            ':fecha_pago' =>
                $fecha_pago,

            ':metodo_pago' =>
                $metodo_pago,

            ':observacion' =>
                !empty(trim($observacion))
                    ? trim($observacion)
                    : null,

            ':id_usuario_registra' =>
                $_SESSION['id'] ?? null
        ]);


        /*
        =============================================
        ID DE LA MATRÍCULA
        =============================================
        */

        $matricula_id = $conexion->lastInsertId();


        /*
        =============================================
        PREPARAR AUDITORÍA
        =============================================
        */

        $cambios = [

            "id_deportista" => [
                "antes" => null,
                "despues" => (int)$deportista_id
            ],

            "anio" => [
                "antes" => null,
                "despues" => $anio
            ],

            "monto" => [
                "antes" => null,
                "despues" => $monto
            ],

            "fecha_matricula" => [
                "antes" => null,
                "despues" => $fecha_matricula
            ],

            "estado" => [
                "antes" => null,
                "despues" => $estado
            ],

            "fecha_pago" => [
                "antes" => null,
                "despues" => $fecha_pago
            ],

            "metodo_pago" => [
                "antes" => null,
                "despues" => $metodo_pago
            ],

            "observacion" => [
                "antes" => null,
                "despues" => $observacion
            ]

        ];


        /*
        =============================================
        REGISTRAR AUDITORÍA
        =============================================
        */

        registrarAuditoria(

            $conexion,

            "matriculas",

            $matricula_id,

            "CREAR",

            $cambios,

            "Registro de matrícula de ingreso del deportista: "
            . $deportista['nombre']

        );


        /*
        =============================================
        REDIRECCIÓN
        =============================================
        */

        header(
            "Location: index.php?registrado=1"
        );

        exit;


    } catch (Exception $e) {

        $mensajeError = $e->getMessage();

        /*
        =============================================
        RECARGAR DEPORTISTA SELECCIONADO
        =============================================
        */

        if (!empty($deportista_id)) {

            foreach ($deportistas as $deportista) {

                if (
                    (int)$deportista['id'] ===
                    (int)$deportista_id
                ) {

                    $deportistaSeleccionado =
                        $deportista;

                    break;
                }
            }
        }
    }
}

/*
=================================================
IDENTIFICACIÓN DEL MÓDULO
=================================================
*/

$modulo_actual = 'Financiero';
$submodulo_actual = 'Matrículas';

/*
=================================================
HEADER DEL MÓDULO
=================================================

Se carga antes del contenido visual siguiendo
el mismo framework utilizado en Deportistas.

=================================================
*/

$modulo_actual = 'Financiero';
$submodulo_actual = 'Matrículas';

include("../../../template/header_modulos.php");

?>

<!-- =================================================
     CONTENIDO
     ================================================= -->

<div class="container-fluid py-4">

    <!-- ENCABEZADO -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                Nueva Matrícula
            </h2>

            <p class="text-muted mb-0">
                Registrar matrícula de ingreso al club.
            </p>

        </div>


        <a
            href="index.php"
            class="btn btn-outline-secondary"
        >

            <i class="fa-solid fa-arrow-left me-1"></i>

            Volver

        </a>

    </div>


    <!-- MENSAJE DE ERROR -->

    <?php if (!empty($mensajeError)): ?>

        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >

            <strong>Error:</strong>

            <?= htmlspecialchars($mensajeError) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Close"
            ></button>

        </div>

    <?php endif; ?>


    <!-- FORMULARIO -->

    <div class="card shadow-sm border-0">

        <div class="card-body p-4">

            <form
                method="POST"
                action=""
            >

                <!-- =================================================
                     INFORMACIÓN DEL DEPORTISTA
                     ================================================= -->

                <h5 class="fw-bold mb-3">

                    <i class="fa-solid fa-user me-2"></i>

                    Información del deportista

                </h5>


                <div class="row">

                    <div class="col-md-8 mb-3">

                        <label
                            for="deportista_id"
                            class="form-label fw-bold"
                        >
                            Deportista *
                        </label>


                        <select
                            name="deportista_id"
                            id="deportista_id"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Seleccionar deportista...
                            </option>


                            <?php foreach ($deportistas as $deportista): ?>

                                <option
                                    value="<?= (int)$deportista['id'] ?>"
                                    <?= (
                                        (string)$deportista_id ===
                                        (string)$deportista['id']
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= htmlspecialchars(
                                        $deportista['nombre']
                                    ) ?>

                                    -
                                    <?= htmlspecialchars(
                                        $deportista['documento']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <div class="form-text">

                            Seleccione el deportista que realizará
                            la matrícula de ingreso.

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     INFORMACIÓN DEL DEPORTISTA SELECCIONADO
                     ================================================= -->

                <div
                    id="datosDeportista"
                    class="<?= $deportistaSeleccionado ? '' : 'd-none' ?>"
                >

                    <div class="card bg-light border mb-4">

                        <div class="card-body">

                            <div class="row align-items-center">

                                <!-- FOTO -->

                                <div class="col-md-2 text-center">

                                    <?php

                                    $foto = $deportistaSeleccionado['foto'] ?? '';

                                    if (!empty($foto)) {

                                        $fotoUrl =
                                            $url_base .
                                            '/uploads/fotos/' .
                                            rawurlencode($foto);

                                    } else {

                                        $fotoUrl =
                                            $url_base .
                                            '/public/img/user.png';
                                    }

                                    ?>

                                    <img
                                        id="fotoDeportista"
                                        src="<?= htmlspecialchars($fotoUrl) ?>"
                                        class="rounded-circle shadow"
                                        width="100"
                                        height="100"
                                        style="object-fit:cover;"
                                        alt="Foto del deportista"
                                    >

                                </div>


                                <!-- DATOS -->

                                <div class="col-md-10">

                                    <h5
                                        id="nombreDeportista"
                                        class="fw-bold mb-3"
                                    >

                                        <?=
                                        htmlspecialchars(
                                            $deportistaSeleccionado['nombre']
                                        )
                                        ?>

                                    </h5>


                                    <div class="row">

                                        <div class="col-md-4 mb-2">

                                            <small class="text-muted">
                                                Documento
                                            </small>

                                            <div
                                                id="documentoDeportista"
                                                class="fw-bold"
                                            >

                                                <?=
                                                htmlspecialchars(
                                                    $deportistaSeleccionado['documento']
                                                )
                                                ?>

                                            </div>

                                        </div>


                                        <div class="col-md-4 mb-2">

                                            <small class="text-muted">
                                                Categoría
                                            </small>

                                            <div
                                                id="categoriaDeportista"
                                                class="fw-bold"
                                            >

                                                <?=
                                                htmlspecialchars(
                                                    $deportistaSeleccionado['categoria_nombre']
                                                    ?? '-'
                                                )
                                                ?>

                                            </div>

                                        </div>


                                        <div class="col-md-4 mb-2">

                                            <small class="text-muted">
                                                Acudiente
                                            </small>

                                            <div
                                                id="acudienteDeportista"
                                                class="fw-bold"
                                            >

                                                <?=
                                                htmlspecialchars(
                                                    $deportistaSeleccionado['acudiente']
                                                    ?? '-'
                                                )
                                                ?>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     INFORMACIÓN DE MATRÍCULA
                     ================================================= -->

                <h5 class="fw-bold mb-3">

                    <i class="fa-solid fa-file-invoice-dollar me-2"></i>

                    Información de matrícula

                </h5>


                <div class="row">

                    <!-- VALOR -->

                    <div class="col-md-6 mb-3">

                        <label
                            for="monto"
                            class="form-label fw-bold"
                        >
                            Valor de matrícula *
                        </label>

<input
    type="text"
    name="monto"
    id="monto"
    class="form-control"
    inputmode="decimal"
    autocomplete="off"
    placeholder="0,00"
    required
>

                    </div>


                    <!-- FECHA -->

                    <div class="col-md-6 mb-3">

                        <label
                            for="fecha_matricula"
                            class="form-label fw-bold"
                        >
                            Fecha de matrícula *
                        </label>

                        <input
                            type="date"
                            name="fecha_matricula"
                            id="fecha_matricula"
                            class="form-control"
                            value="<?= htmlspecialchars($fecha_matricula) ?>"
                            required
                        >

                    </div>


                    <!-- ESTADO -->

                    <div class="col-md-6 mb-3">

                        <label
                            for="estado"
                            class="form-label fw-bold"
                        >
                            Estado *
                        </label>

                        <select
                            name="estado"
                            id="estado"
                            class="form-select"
                            required
                        >

                            <option
                                value="pendiente"
                                <?= $estado === 'pendiente'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Pendiente
                            </option>

                            <option
                                value="pagado"
                                <?= $estado === 'pagado'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Pagado
                            </option>

                            <option
                                value="anulado"
                                <?= $estado === 'anulado'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Anulado
                            </option>

                        </select>

                    </div>


                    <!-- MÉTODO DE PAGO -->

                    <div class="col-md-6 mb-3">

                        <label
                            for="metodo_pago"
                            class="form-label fw-bold"
                        >
                            Método de pago
                        </label>

                        <select
                            name="metodo_pago"
                            id="metodo_pago"
                            class="form-select"
                        >

                            <option value="">
                                Seleccionar método...
                            </option>

                            <option
                                value="Efectivo"
                                <?= $metodo_pago === 'Efectivo'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Efectivo
                            </option>

                            <option
                                value="Transferencia"
                                <?= $metodo_pago === 'Transferencia'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Transferencia
                            </option>

                            <option
                                value="Nequi"
                                <?= $metodo_pago === 'Nequi'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Nequi
                            </option>

                            <option
                                value="Daviplata"
                                <?= $metodo_pago === 'Daviplata'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Daviplata
                            </option>

                            <option
                                value="Tarjeta"
                                <?= $metodo_pago === 'Tarjeta'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Tarjeta
                            </option>

                        </select>

                    </div>


                    <!-- OBSERVACIÓN -->

                    <div class="col-md-12 mb-4">

                        <label
                            for="observacion"
                            class="form-label fw-bold"
                        >
                            Observación
                        </label>

                        <textarea
                            name="observacion"
                            id="observacion"
                            class="form-control"
                            rows="4"
                            placeholder="Observaciones relacionadas con la matrícula..."
                        ><?= htmlspecialchars($observacion) ?></textarea>

                    </div>

                </div>


                <!-- =================================================
                     BOTONES
                     ================================================= -->

                <div class="d-flex gap-2 mt-3">

                    <a
                        href="index.php"
                        class="btn btn-danger"
                    >

                        <i class="fa-solid fa-xmark me-1"></i>

                        Cancelar

                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="fa-solid fa-save me-1"></i>

                        Registrar Matrícula

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<script>

/*
=================================================
DATOS DEPORTISTAS
=================================================
*/

const deportistas = <?= json_encode(
    $deportistas,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
) ?>;


const selectDeportista =
    document.getElementById('deportista_id');

const datosDeportista =
    document.getElementById('datosDeportista');

const nombreDeportista =
    document.getElementById('nombreDeportista');

const documentoDeportista =
    document.getElementById('documentoDeportista');

const categoriaDeportista =
    document.getElementById('categoriaDeportista');

const acudienteDeportista =
    document.getElementById('acudienteDeportista');

const fotoDeportista =
    document.getElementById('fotoDeportista');


/*
=================================================
CAMBIAR DEPORTISTA
=================================================
*/

selectDeportista.addEventListener(
    'change',
    function () {

        const id = this.value;


        if (!id) {

            datosDeportista.classList.add('d-none');

            return;
        }


        const deportista =
            deportistas.find(
                d => String(d.id) === String(id)
            );


        if (!deportista) {

            datosDeportista.classList.add('d-none');

            return;
        }


        /*
        =============================================
        MOSTRAR INFORMACIÓN
        =============================================
        */

        nombreDeportista.textContent =
            deportista.nombre || '-';

        documentoDeportista.textContent =
            deportista.documento || '-';

        categoriaDeportista.textContent =
            deportista.categoria_nombre || '-';

        acudienteDeportista.textContent =
            deportista.acudiente || '-';


        /*
        =============================================
        FOTO
        =============================================
        */

        if (deportista.foto) {

            fotoDeportista.src =
                <?= json_encode(
                    $url_base . '/uploads/fotos/'
                ) ?>
                + encodeURIComponent(deportista.foto);

        } else {

            fotoDeportista.src =
                <?= json_encode(
                    $url_base . '/public/img/user.png'
                ) ?>;

        }


        /*
        =============================================
        MOSTRAR TARJETA
        =============================================
        */

        datosDeportista.classList.remove('d-none');

    }
);


/*
=================================================
CONTROL DEL MÉTODO DE PAGO
=================================================

Si la matrícula está pagada, el método de pago
es obligatorio.

=================================================
*/

const estado =
    document.getElementById('estado');

const metodoPago =
    document.getElementById('metodo_pago');


function actualizarMetodoPago() {

    if (estado.value === 'pagado') {

        metodoPago.required = true;

    } else {

        metodoPago.required = false;

    }
}


estado.addEventListener(
    'change',
    actualizarMetodoPago
);


actualizarMetodoPago();

</script>

<?php
include("../../../template/footer_modulos.php");
?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const campoMonto = document.getElementById('monto');

    if (!campoMonto) {
        return;
    }

    campoMonto.addEventListener('input', function () {

        let valor = this.value;

        // Dejar únicamente números y coma
        valor = valor.replace(/[^\d,]/g, '');

        // Permitir solamente una coma decimal
        const partes = valor.split(',');

        let enteros = partes[0] || '';
        let decimales = partes[1] || '';

        // Máximo 2 decimales
        decimales = decimales.substring(0, 2);

        // Eliminar ceros innecesarios al inicio
        if (enteros.length > 1) {
            enteros = enteros.replace(/^0+(?=\d)/, '');
        }

        // Formatear miles con punto
        enteros = enteros.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        // Reconstruir valor
        this.value = decimales !== ''
            ? enteros + ',' + decimales
            : enteros;
    });

});
</script>