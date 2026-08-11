<?php

/*
=================================================
MÓDULO FINANCIERO - NUEVO UNIFORME
=================================================

Este archivo permite registrar un nuevo uniforme
para un deportista.

Datos registrados:

- Deportista
- Tipo de uniforme
- Talla
- Cantidad
- Valor
- Fecha de entrega
- Estado de entrega
- Estado de pago
- Observaciones

=================================================
*/


/*
=================================================
1. CONFIGURACIÓN Y PERMISOS
=================================================
*/

require_once("../../../includes/config.php");
require_once("../../../includes/verificar_roles.php");


/*
=================================================
VERIFICAR PERMISO

El permiso utilizado para Uniformes es:

financiero_uniformes

Si el usuario no tiene permiso, vuelve al
Dashboard.

=================================================
*/

if (!tiene_permiso('financiero_uniformes')) {

    header(
        "Location: " . $url_base . "/index.php"
    );

    exit;
}


/*
=================================================
2. AUDITORÍA
=================================================
*/

include(
    "../../../modulos/auditoria/funciones/registrar_auditoria.php"
);


/*
=================================================
3. CONEXIÓN A LA BASE DE DATOS
=================================================
*/

include(
    "../../../modulos/conexion_modulos.php"
);


/*
=================================================
4. VARIABLES INICIALES DEL FORMULARIO
=================================================
*/

$deportista_id = '';
$tipo_uniforme = '';
$talla = '';
$cantidad = 1;
$valor = '';
$fecha_pedido = '';
$fecha_entrega = '';
$estado_entrega = 'pendiente';
$estado_pago = 'pendiente';
$observaciones = '';


/*
=================================================
5. MENSAJE DE ERROR
=================================================
*/

$error = '';


/*
=================================================
6. OBTENER DEPORTISTAS
=================================================

Se muestran solamente deportistas activos.

La relación utilizada es:

deportista
    ↓
categoria

=================================================
*/

$stmtDeportistas = $conexion->query("

    SELECT

        d.id,
        d.nombre,
        d.documento,

        c.nombre AS categoria_nombre

    FROM deportista d

    LEFT JOIN categoria c
        ON c.id = d.categoria_id

    WHERE d.estado = 'activo'

    ORDER BY
        d.nombre ASC

");


$deportistas = $stmtDeportistas->fetchAll(
    PDO::FETCH_ASSOC
);


/*
=================================================
7. PROCESAR FORMULARIO
=================================================
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    =================================================
    RECIBIR DATOS
    =================================================
    */

    $deportista_id = $_POST['deportista_id'] ?? '';

    $tipo_uniforme = trim(
        $_POST['tipo_uniforme'] ?? ''
    );

    $talla = trim(
        $_POST['talla'] ?? ''
    );

    $cantidad = $_POST['cantidad'] ?? 1;

$valor = $_POST['valor'] ?? '';

$fecha_pedido = $_POST['fecha_pedido'] ?? '';

$fecha_entrega = $_POST['fecha_entrega'] ?? '';

    $estado_entrega =
        $_POST['estado_entrega'] ?? 'pendiente';

    $estado_pago =
        $_POST['estado_pago'] ?? 'pendiente';

    $observaciones = trim(
        $_POST['observaciones'] ?? ''
    );


    /*
    =================================================
    8. VALIDACIONES
    =================================================
    */


    /*
    -------------------------------------------------
    VALIDAR DEPORTISTA
    -------------------------------------------------
    */

    if (
        $deportista_id === '' ||
        !ctype_digit((string)$deportista_id)
    ) {

        $error =
            'Debe seleccionar un deportista.';

    }


    /*
    -------------------------------------------------
    VALIDAR TIPO DE UNIFORME
    -------------------------------------------------
    */

    elseif ($tipo_uniforme === '') {

        $error =
            'Debe ingresar el tipo de uniforme.';

    }


    /*
    -------------------------------------------------
    VALIDAR TALLA
    -------------------------------------------------
    */

    elseif ($talla === '') {

        $error =
            'Debe ingresar la talla.';

    }


    /*
    -------------------------------------------------
    VALIDAR CANTIDAD
    -------------------------------------------------
    */

    elseif (
        !ctype_digit((string)$cantidad) ||
        (int)$cantidad < 1
    ) {

        $error =
            'La cantidad debe ser un número mayor que cero.';

    }


    /*
    -------------------------------------------------
    VALIDAR VALOR
    -------------------------------------------------
    */

    elseif (
        $valor === '' ||
        !is_numeric($valor) ||
        (float)$valor < 0
    ) {

        $error =
            'Debe ingresar un valor válido.';

    }


    /*
    -------------------------------------------------
    VALIDAR ESTADO DE ENTREGA
    -------------------------------------------------
    */

    elseif (
        !in_array(
            $estado_entrega,
            [
                'pendiente',
                'entregado'
            ],
            true
        )
    ) {

        $error =
            'El estado de entrega no es válido.';

    }


    /*
    -------------------------------------------------
    VALIDAR ESTADO DE PAGO
    -------------------------------------------------
    */

    elseif (
        !in_array(
            $estado_pago,
            [
                'pendiente',
                'parcial',
                'pagado'
            ],
            true
        )
    ) {

        $error =
            'El estado de pago no es válido.';

    }


    /*
    =================================================
    9. GUARDAR UNIFORME
    =================================================
    */

    if ($error === '') {


        try {


            /*
            -----------------------------------------
            NORMALIZAR VALORES
            -----------------------------------------
            */

            $deportista_id =
                (int)$deportista_id;

            $cantidad =
                (int)$cantidad;

            $valor =
                (float)$valor;


            /*
            -----------------------------------------
            INSERTAR UNIFORME
            -----------------------------------------
            */

            $sql = "

INSERT INTO uniformes (

    deportista_id,
    tipo_uniforme,
    talla,
    cantidad,
    valor,
    fecha_pedido,
    fecha_entrega,
    estado_entrega,
    estado_pago,
    observaciones

)

VALUES (

    :deportista_id,
    :tipo_uniforme,
    :talla,
    :cantidad,
    :valor,
    :fecha_pedido,
    :fecha_entrega,
    :estado_entrega,
    :estado_pago,
    :observaciones

)

            ";


            $stmt = $conexion->prepare($sql);


            /*
            -----------------------------------------
            EJECUTAR INSERT
            -----------------------------------------
            */

$stmt->execute([

    ':deportista_id' =>
        $deportista_id,

    ':tipo_uniforme' =>
        $tipo_uniforme,

    ':talla' =>
        $talla,

    ':cantidad' =>
        $cantidad,

    ':valor' =>
        $valor,

    ':fecha_pedido' =>
        $fecha_pedido !== ''
            ? $fecha_pedido
            : null,

    ':fecha_entrega' =>
        $fecha_entrega !== ''
            ? $fecha_entrega
            : null,

    ':estado_entrega' =>
        $estado_entrega,

    ':estado_pago' =>
        $estado_pago,

    ':observaciones' =>
        $observaciones !== ''
            ? $observaciones
            : null

]);


            /*
            -----------------------------------------
            OBTENER ID DEL NUEVO REGISTRO
            -----------------------------------------
            */

            $uniforme_id =
                $conexion->lastInsertId();


            /*
            =================================================
            AUDITORÍA

            El archivo registrar_auditoria.php ya se carga
            al comienzo del módulo, igual que en Matrículas.

            No se ejecuta una llamada aquí porque debemos
            conservar exactamente la firma de la función
            existente en tu proyecto.
            =================================================
            */


            /*
            -----------------------------------------
            REDIRECCIÓN
            -----------------------------------------

            Se utiliza PRG:

            POST
              ↓
            REDIRECT
              ↓
            index.php

            Esto evita que el navegador vuelva a
            enviar el formulario al actualizar.
            -----------------------------------------
            */

            header(
                "Location: index.php?creado=1"
            );

            exit;


        } catch (PDOException $e) {


            /*
            -----------------------------------------
            ERROR DE BASE DE DATOS
            -----------------------------------------
            */

            $error =
                'No fue posible registrar el uniforme. '
                . 'Verifique los datos e inténtelo nuevamente.';

        }

    }

}


/*
=================================================
10. HEADER DEL MÓDULO
=================================================
*/

$modulo_actual = 'Financiero';

$submodulo_actual = 'Uniformes';


include(
    "../../../template/header_modulos.php"
);

?>


<!--
=================================================
11. CONTENIDO PRINCIPAL
=================================================
-->

<div class="container-fluid py-4">


    <!--
    =================================================
    VOLVER
    =================================================
    -->

    <div class="d-flex
                justify-content-between
                align-items-center
                mb-3">

        <div>

            <a
                href="index.php"
                class="btn btn-outline-dark"
            >

                <i
                    class="fa-solid
                           fa-arrow-left
                           me-1"
                ></i>

                Volver a Uniformes

            </a>

        </div>

    </div>


    <!--
    =================================================
    ENCABEZADO
    =================================================
    -->

    <div class="mb-4">

        <h2 class="fw-bold mb-1">

            <i
                class="fa-solid
                       fa-shirt
                       text-primary
                       me-2"
            ></i>

            Nuevo Uniforme

        </h2>


        <p class="text-muted mb-0">

            Registra la entrega y el cobro
            de un uniforme.

        </p>

    </div>


    <!--
    =================================================
    MENSAJE DE ERROR
    =================================================
    -->

    <?php if ($error !== ''): ?>

        <div
            class="alert alert-danger
                   d-flex align-items-center"
            role="alert"
        >

            <i
                class="fa-solid
                       fa-circle-exclamation
                       me-2"
            ></i>

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <!--
    =================================================
    FORMULARIO
    =================================================
    -->

    <div
        class="card border-0
               shadow-sm"
    >

        <div class="card-body p-4">


            <form
                method="POST"
                action=""
            >


                <!--
                =================================================
                DATOS DEL DEPORTISTA
                =================================================
                -->

                <div class="mb-4">

                    <h5
                        class="fw-bold
                               border-bottom
                               pb-2"
                    >

                        <i
                            class="fa-solid
                                   fa-person-running
                                   text-primary
                                   me-2"
                        ></i>

                        Deportista

                    </h5>

                </div>


                <div class="row g-3 mb-4">


                    <!--
                    -------------------------------------------------
                    DEPORTISTA
                    -------------------------------------------------
                    -->

                    <div class="col-md-8">

                        <label
                            for="deportista_id"
                            class="form-label fw-bold"
                        >

                            Deportista
                            <span class="text-danger">
                                *
                            </span>

                        </label>


                        <select
                            name="deportista_id"
                            id="deportista_id"
                            class="form-select"
                            required
                        >

                            <option value="">

                                Seleccione un deportista

                            </option>


                            <?php foreach (
                                $deportistas
                                as $deportista
                            ): ?>

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

                                    <?php if (
                                        !empty(
                                            $deportista[
                                                'categoria_nombre'
                                            ]
                                        )
                                    ): ?>

                                        -

                                        <?= htmlspecialchars(
                                            $deportista[
                                                'categoria_nombre'
                                            ]
                                        ) ?>

                                    <?php endif; ?>

                                </option>

                            <?php endforeach; ?>

                        </select>


                        <div class="form-text">

                            Solo aparecen deportistas activos.

                        </div>

                    </div>


                </div>


                <!--
                =================================================
                DATOS DEL UNIFORME
                =================================================
                -->

                <div class="mb-4">

                    <h5
                        class="fw-bold
                               border-bottom
                               pb-2"
                    >

                        <i
                            class="fa-solid
                                   fa-shirt
                                   text-primary
                                   me-2"
                        ></i>

                        Datos del Uniforme

                    </h5>

                </div>


                <div class="row g-3 mb-4">


                    <!--
                    -------------------------------------------------
                    TIPO DE UNIFORME
                    -------------------------------------------------
                    -->

                    <div class="col-md-4">

                        <label
                            for="tipo_uniforme"
                            class="form-label fw-bold"
                        >

                            Tipo de uniforme
                            <span class="text-danger">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            name="tipo_uniforme"
                            id="tipo_uniforme"
                            class="form-control"
                            maxlength="50"
                            placeholder="Ej: Juego"
                            value="<?= htmlspecialchars(
                                $tipo_uniforme
                            ) ?>"
                            required
                        >

                    </div>


                    <!--
                    -------------------------------------------------
                    TALLA
                    -------------------------------------------------
                    -->

                    <div class="col-md-3">

                        <label
                            for="talla"
                            class="form-label fw-bold"
                        >

                            Talla
                            <span class="text-danger">
                                *
                            </span>

                        </label>


                        <select
                            name="talla"
                            id="talla"
                            class="form-select"
                            required
                        >

                            <option value="">

                                Seleccione

                            </option>


                            <?php
                            $tallas = [
                                'XS',
                                'S',
                                'M',
                                'L',
                                'XL',
                                'XXL'
                            ];
                            ?>


                            <?php foreach (
                                $tallas
                                as $talla_opcion
                            ): ?>

                                <option
                                    value="<?= $talla_opcion ?>"
                                    <?= $talla ===
                                        $talla_opcion
                                            ? 'selected'
                                            : ''
                                    ?>
                                >

                                    <?= $talla_opcion ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!--
                    -------------------------------------------------
                    CANTIDAD
                    -------------------------------------------------
                    -->

                    <div class="col-md-2">

                        <label
                            for="cantidad"
                            class="form-label fw-bold"
                        >

                            Cantidad
                            <span class="text-danger">
                                *
                            </span>

                        </label>


                        <input
                            type="number"
                            name="cantidad"
                            id="cantidad"
                            class="form-control"
                            min="1"
                            step="1"
                            value="<?= htmlspecialchars(
                                $cantidad
                            ) ?>"
                            required
                        >

                    </div>


                    <!--
                    -------------------------------------------------
                    VALOR
                    -------------------------------------------------
                    -->

                    <div class="col-md-3">

                        <label
                            for="valor"
                            class="form-label fw-bold"
                        >

                            Valor unitario
                            <span class="text-danger">
                                *
                            </span>

                        </label>


                        <div class="input-group">

                            <span
                                class="input-group-text"
                            >

                                $

                            </span>


                            <input
                                type="number"
                                name="valor"
                                id="valor"
                                class="form-control"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                                value="<?= htmlspecialchars(
                                    $valor
                                ) ?>"
                                required
                            >

                        </div>

                    </div>


                </div>


 <!--
=================================================
FECHAS, ENTREGA Y COBRO
=================================================
-->

<div class="mb-4">

    <h5
        class="fw-bold
               border-bottom
               pb-2"
    >

        <i
            class="fa-solid
                   fa-hand-holding-dollar
                   text-primary
                   me-2"
        ></i>

        Pedido, Entrega y Cobro

    </h5>

</div>


<div class="row g-3 mb-4">


    <!--
    -------------------------------------------------
    FECHA DEL PEDIDO
    -------------------------------------------------
    -->

    <div class="col-md-3">

        <label
            for="fecha_pedido"
            class="form-label fw-bold"
        >

            Fecha del pedido

            <span class="text-danger">
                *
            </span>

        </label>


        <input
            type="date"
            name="fecha_pedido"
            id="fecha_pedido"
            class="form-control"
            value="<?= htmlspecialchars(
                $fecha_pedido
            ) ?>"
            required
        >

    </div>


    <!--
    -------------------------------------------------
    FECHA DE ENTREGA
    -------------------------------------------------
    -->

    <div class="col-md-3">

        <label
            for="fecha_entrega"
            class="form-label fw-bold"
        >

            Fecha de entrega

        </label>


        <input
            type="date"
            name="fecha_entrega"
            id="fecha_entrega"
            class="form-control"
            value="<?= htmlspecialchars(
                $fecha_entrega
            ) ?>"
        >

    </div>


    <!--
    -------------------------------------------------
    ESTADO DE ENTREGA
    -------------------------------------------------
    -->

    <div class="col-md-3">

        <label
            for="estado_entrega"
            class="form-label fw-bold"
        >

            Estado de entrega

            <span class="text-danger">
                *
            </span>

        </label>


        <select
            name="estado_entrega"
            id="estado_entrega"
            class="form-select"
            required
        >

            <option
                value="pendiente"
                <?= $estado_entrega === 'pendiente'
                    ? 'selected'
                    : ''
                ?>
            >

                Pendiente

            </option>


            <option
                value="entregado"
                <?= $estado_entrega === 'entregado'
                    ? 'selected'
                    : ''
                ?>
            >

                Entregado

            </option>

        </select>

    </div>


    <!--
    -------------------------------------------------
    ESTADO DE PAGO
    -------------------------------------------------
    -->

    <div class="col-md-3">

        <label
            for="estado_pago"
            class="form-label fw-bold"
        >

            Estado de pago

            <span class="text-danger">
                *
            </span>

        </label>


        <select
            name="estado_pago"
            id="estado_pago"
            class="form-select"
            required
        >

            <option
                value="pendiente"
                <?= $estado_pago === 'pendiente'
                    ? 'selected'
                    : ''
                ?>
            >

                Pendiente

            </option>


            <option
                value="parcial"
                <?= $estado_pago === 'parcial'
                    ? 'selected'
                    : ''
                ?>
            >

                Parcial

            </option>


            <option
                value="pagado"
                <?= $estado_pago === 'pagado'
                    ? 'selected'
                    : ''
                ?>
            >

                Pagado

            </option>

        </select>

    </div>


</div>

                <!--
                =================================================
                OBSERVACIONES
                =================================================
                -->

                <div class="mb-4">

                    <label
                        for="observaciones"
                        class="form-label fw-bold"
                    >

                        Observaciones

                    </label>


                    <textarea
                        name="observaciones"
                        id="observaciones"
                        class="form-control"
                        rows="4"
                        placeholder="Observaciones sobre el uniforme..."
                    ><?= htmlspecialchars(
                        $observaciones
                    ) ?></textarea>

                </div>


                <!--
                =================================================
                RESUMEN DEL VALOR
                =================================================
                -->

                <div
                    class="alert alert-light
                           border
                           mb-4"
                >

                    <div
                        class="d-flex
                               justify-content-between
                               align-items-center"
                    >

                        <span class="fw-bold">

                            Valor total:

                        </span>


                        <span
                            class="fw-bold
                                   fs-4
                                   text-primary"
                            id="valor_total"
                        >

                            $0.00

                        </span>

                    </div>

                </div>


                <!--
                =================================================
                BOTONES
                =================================================
                -->

                <div
                    class="d-flex
                           justify-content-end
                           gap-2"
                >


                    <a
                        href="index.php"
                        class="btn
                               btn-outline-secondary"
                    >

                        <i
                            class="fa-solid
                                   fa-xmark
                                   me-1"
                        ></i>

                        Cancelar

                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary fw-bold"
                    >

                        <i
                            class="fa-solid
                                   fa-floppy-disk
                                   me-1"
                        ></i>

                        Guardar Uniforme

                    </button>


                </div>


            </form>

        </div>

    </div>


</div>


<!--
=================================================
12. CÁLCULO DEL VALOR TOTAL
=================================================

Valor total:

valor unitario × cantidad

Este cálculo solamente es visual.
El valor almacenado en la BD sigue siendo
el valor unitario de la columna "valor".

=================================================
-->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const cantidad =
            document.getElementById('cantidad');

        const valor =
            document.getElementById('valor');

        const valorTotal =
            document.getElementById('valor_total');


        function actualizarTotal() {

            const cantidadNumero =
                parseFloat(cantidad.value) || 0;

            const valorNumero =
                parseFloat(valor.value) || 0;


            const total =
                cantidadNumero *
                valorNumero;


            valorTotal.textContent =
                '$' +
                total.toLocaleString(
                    'es-CO',
                    {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }
                );

        }


        cantidad.addEventListener(
            'input',
            actualizarTotal
        );


        valor.addEventListener(
            'input',
            actualizarTotal
        );


        actualizarTotal();

    }

);

</script>


<?php

/*
=================================================
13. FOOTER DEL MÓDULO
=================================================
*/

include(
    "../../../template/footer_modulos.php"
);

?>