<?php

/*
=================================================
MÓDULO FINANCIERO - EDITAR UNIFORME
=================================================

Este archivo permite editar un uniforme
previamente registrado.

Datos editables:

- Deportista
- Tipo de uniforme
- Talla
- Cantidad
- Valor
- Fecha del pedido
- Fecha de entrega
- Estado de entrega
- Estado de pago
- Observaciones

Flujo:

Pedido
   ↓
Llegada del uniforme
   ↓
Entrega al deportista
   ↓
Pago

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
4. OBTENER ID DEL UNIFORME
=================================================

El archivo se abre mediante:

editar_uniforme.php?id=1

=================================================
*/

$uniforme_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


/*
=================================================
VALIDAR ID
=================================================
*/

if (
    !$uniforme_id ||
    $uniforme_id < 1
) {

    header(
        "Location: index.php?error=id_invalido"
    );

    exit;
}


/*
=================================================
5. VARIABLES INICIALES
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

$error = '';


/*
=================================================
6. OBTENER UNIFORME
=================================================

Se obtiene:

uniformes
    ↓
deportista
    ↓
categoria

=================================================
*/

$stmt = $conexion->prepare("

    SELECT

        u.id,
        u.deportista_id,
        u.tipo_uniforme,
        u.talla,
        u.cantidad,
        u.valor,
        u.fecha_pedido,
        u.fecha_entrega,
        u.estado_entrega,
        u.estado_pago,
        u.observaciones,
        u.fecha_creacion,

        d.nombre AS deportista_nombre,
        d.documento AS deportista_documento,
        d.estado AS deportista_estado,

        c.nombre AS categoria_nombre

    FROM uniformes u

    INNER JOIN deportista d
        ON d.id = u.deportista_id

    LEFT JOIN categoria c
        ON c.id = d.categoria_id

    WHERE u.id = :id

    LIMIT 1

");


$stmt->execute([
    ':id' => $uniforme_id
]);


$uniforme = $stmt->fetch(
    PDO::FETCH_ASSOC
);


/*
=================================================
VERIFICAR EXISTENCIA
=================================================
*/

if (!$uniforme) {

    header(
        "Location: index.php?error=no_encontrado"
    );

    exit;
}


/*
=================================================
7. CARGAR DATOS DEL REGISTRO
=================================================
*/

$deportista_id =
    (string)$uniforme['deportista_id'];

$tipo_uniforme =
    $uniforme['tipo_uniforme'] ?? '';

$talla =
    $uniforme['talla'] ?? '';

$cantidad =
    (int)($uniforme['cantidad'] ?? 1);

$valor =
    $uniforme['valor'] ?? '';

$fecha_pedido =
    $uniforme['fecha_pedido'] ?? '';

$fecha_entrega =
    $uniforme['fecha_entrega'] ?? '';

$estado_entrega =
    $uniforme['estado_entrega']
    ?? 'pendiente';

$estado_pago =
    $uniforme['estado_pago']
    ?? 'pendiente';

$observaciones =
    $uniforme['observaciones']
    ?? '';


/*
=================================================
8. OBTENER DEPORTISTAS
=================================================

Se muestran:

- Deportistas activos.

Además:

- Si el deportista actualmente asociado
  está inactivo, también se incluye.

Esto evita que un uniforme histórico
pierda su deportista al editarlo.

=================================================
*/

$stmtDeportistas = $conexion->prepare("

    SELECT

        d.id,
        d.nombre,
        d.documento,
        d.estado,

        c.nombre AS categoria_nombre

    FROM deportista d

    LEFT JOIN categoria c
        ON c.id = d.categoria_id

    WHERE
        d.estado = 'activo'
        OR d.id = :deportista_actual

    ORDER BY

        d.nombre ASC

");


$stmtDeportistas->execute([

    ':deportista_actual' =>
        $deportista_id

]);


$deportistas =
    $stmtDeportistas->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
=================================================
9. PROCESAR FORMULARIO
=================================================
*/

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {


    /*
    =================================================
    RECIBIR DATOS
    =================================================
    */

    $deportista_id =
        $_POST['deportista_id'] ?? '';

    $tipo_uniforme =
        trim(
            $_POST['tipo_uniforme']
            ?? ''
        );

    $talla =
        trim(
            $_POST['talla']
            ?? ''
        );

    $cantidad =
        $_POST['cantidad']
        ?? 1;

    $valor =
        $_POST['valor']
        ?? '';

    $fecha_pedido =
        $_POST['fecha_pedido']
        ?? '';

    $fecha_entrega =
        $_POST['fecha_entrega']
        ?? '';

    $estado_entrega =
        $_POST['estado_entrega']
        ?? 'pendiente';

    $estado_pago =
        $_POST['estado_pago']
        ?? 'pendiente';

    $observaciones =
        trim(
            $_POST['observaciones']
            ?? ''
        );


    /*
    =================================================
    10. VALIDACIONES
    =================================================
    */


    /*
    -------------------------------------------------
    VALIDAR DEPORTISTA
    -------------------------------------------------
    */

    if (
        $deportista_id === ''
        ||
        !ctype_digit(
            (string)$deportista_id
        )
    ) {

        $error =
            'Debe seleccionar un deportista.';

    }


    /*
    -------------------------------------------------
    VALIDAR QUE EL DEPORTISTA EXISTA
    -------------------------------------------------
    */

    elseif (!$error) {

        $stmtExisteDeportista =
            $conexion->prepare("

                SELECT id

                FROM deportista

                WHERE id = :id

                LIMIT 1

            ");

        $stmtExisteDeportista->execute([

            ':id' =>
                (int)$deportista_id

        ]);

        if (
            !$stmtExisteDeportista->fetch()
        ) {

            $error =
                'El deportista seleccionado no existe.';

        }

    }


    /*
    -------------------------------------------------
    VALIDAR TIPO DE UNIFORME
    -------------------------------------------------
    */

    elseif (
        $tipo_uniforme === ''
    ) {

        $error =
            'Debe ingresar el tipo de uniforme.';

    }


    /*
    -------------------------------------------------
    VALIDAR TALLA
    -------------------------------------------------
    */

    elseif (
        $talla === ''
    ) {

        $error =
            'Debe ingresar la talla.';

    }


    /*
    -------------------------------------------------
    VALIDAR CANTIDAD
    -------------------------------------------------
    */

    elseif (

        !ctype_digit(
            (string)$cantidad
        )

        ||

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

        $valor === ''

        ||

        !is_numeric($valor)

        ||

        (float)$valor < 0

    ) {

        $error =
            'Debe ingresar un valor válido.';

    }


    /*
    -------------------------------------------------
    VALIDAR FECHA DE PEDIDO
    -------------------------------------------------
    */

    elseif (
        $fecha_pedido === ''
    ) {

        $error =
            'Debe ingresar la fecha del pedido.';

    }


    /*
    -------------------------------------------------
    VALIDAR FECHAS
    -------------------------------------------------

    La fecha de entrega no puede ser anterior
    a la fecha del pedido.

    -------------------------------------------------
    */

    elseif (

        $fecha_entrega !== ''

        &&

        $fecha_entrega < $fecha_pedido

    ) {

        $error =
            'La fecha de entrega no puede ser anterior a la fecha del pedido.';

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
    VALIDAR FECHA SI ESTÁ ENTREGADO
    -------------------------------------------------

    Si el uniforme está marcado como entregado,
    debe existir una fecha de entrega.

    -------------------------------------------------
    */

    elseif (

        $estado_entrega === 'entregado'

        &&

        $fecha_entrega === ''

    ) {

        $error =
            'Debe ingresar la fecha de entrega cuando el uniforme está marcado como entregado.';

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
    11. ACTUALIZAR UNIFORME
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
            ACTUALIZAR REGISTRO
            -----------------------------------------
            */

            $sql = "

                UPDATE uniformes

                SET

                    deportista_id =
                        :deportista_id,

                    tipo_uniforme =
                        :tipo_uniforme,

                    talla =
                        :talla,

                    cantidad =
                        :cantidad,

                    valor =
                        :valor,

                    fecha_pedido =
                        :fecha_pedido,

                    fecha_entrega =
                        :fecha_entrega,

                    estado_entrega =
                        :estado_entrega,

                    estado_pago =
                        :estado_pago,

                    observaciones =
                        :observaciones

                WHERE id =
                    :id

                LIMIT 1

            ";


            $stmt =
                $conexion->prepare($sql);


            /*
            -----------------------------------------
            EJECUTAR UPDATE
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
                        : null,

                ':id' =>
                    $uniforme_id

            ]);


            /*
            =================================================
            AUDITORÍA

            registrar_auditoria.php se mantiene cargado
            igual que en nuevo_uniforme.php.

            No se agrega una llamada manual aquí porque
            debemos conservar la firma existente del
            sistema de auditoría del proyecto.
            =================================================
            */


            /*
            =================================================
            REDIRECCIÓN

            POST
              ↓
            UPDATE
              ↓
            REDIRECT
              ↓
            index.php

            Se utiliza PRG para evitar que el navegador
            vuelva a enviar el formulario al actualizar.
            =================================================
            */

            header(
                "Location: index.php?editado=1"
            );

            exit;


        } catch (PDOException $e) {


            /*
            -----------------------------------------
            ERROR DE BASE DE DATOS
            -----------------------------------------
            */

            $error =
                'No fue posible actualizar el uniforme. '
                . 'Verifique los datos e inténtelo nuevamente.';

        }

    }


    /*
    =================================================
    12. RECARGAR LISTA DE DEPORTISTAS

    Si hubo un error de validación y el usuario
    cambió el deportista, volvemos a cargar la
    lista para conservar correctamente el valor.
    =================================================
    */

    $stmtDeportistas = $conexion->prepare("

        SELECT

            d.id,
            d.nombre,
            d.documento,
            d.estado,

            c.nombre AS categoria_nombre

        FROM deportista d

        LEFT JOIN categoria c
            ON c.id = d.categoria_id

        WHERE
            d.estado = 'activo'
            OR d.id = :deportista_actual

        ORDER BY
            d.nombre ASC

    ");


    $stmtDeportistas->execute([

        ':deportista_actual' =>
            (int)$deportista_id

    ]);


    $deportistas =
        $stmtDeportistas->fetchAll(
            PDO::FETCH_ASSOC
        );

}


/*
=================================================
13. HEADER DEL MÓDULO
=================================================
*/

$modulo_actual =
    'Financiero';

$submodulo_actual =
    'Uniformes';


include(
    "../../../template/header_modulos.php"
);

?>


<!--
=================================================
14. CONTENIDO PRINCIPAL
=================================================
-->

<div class="container-fluid py-4">


    <!--
    =================================================
    VOLVER
    =================================================
    -->

    <div
        class="d-flex
               justify-content-between
               align-items-center
               mb-3"
    >

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

        <h2
            class="fw-bold mb-1"
        >

            <i
                class="fa-solid
                       fa-shirt
                       text-primary
                       me-2"
            ></i>

            Editar Uniforme

        </h2>


        <p class="text-muted mb-0">

            Modifica la información del pedido,
            entrega y cobro del uniforme.

        </p>

    </div>


    <!--
    =================================================
    MENSAJE DE ERROR
    =================================================
    -->

    <?php if ($error !== ''): ?>

        <div
            class="alert
                   alert-danger
                   alert-dismissible
                   fade show"
            role="alert"
        >

            <i
                class="fa-solid
                       fa-circle-exclamation
                       me-2"
            ></i>

            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                'UTF-8'
            ) ?>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Cerrar"
            ></button>

        </div>

    <?php endif; ?>


    <!--
    =================================================
    FORMULARIO
    =================================================
    -->

    <div class="card border-0 shadow-sm">

        <div class="card-body p-4">


            <form
                method="POST"
                action="editar_uniforme.php?id=<?= (int)$uniforme_id ?>"
            >


                <!--
                =================================================
                DEPORTISTA
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


                <div class="mb-4">

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
                                <?= (string)$deportista_id
                                    ===
                                    (string)$deportista['id']
                                        ? 'selected'
                                        : ''
                                ?>
                            >

                                <?= htmlspecialchars(
                                    $deportista['nombre'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                                -

                                <?= htmlspecialchars(
                                    $deportista['documento'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>


                                <?php if (
                                    $deportista['estado']
                                    === 'inactivo'
                                ): ?>

                                    (Inactivo)

                                <?php endif; ?>

                            </option>

                        <?php endforeach; ?>

                    </select>


                    <div
                        class="form-text"
                    >

                        Se muestran los deportistas
                        activos y el deportista actualmente
                        asociado al uniforme.

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


                <div
                    class="row
                           g-3
                           mb-4"
                >


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
                                $tipo_uniforme,
                                ENT_QUOTES,
                                'UTF-8'
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
                                    <?= $talla
                                        ===
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
                            value="<?= (int)$cantidad ?>"
                            required
                        >

                    </div>


                    <!--
                    -------------------------------------------------
                    VALOR UNITARIO
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


                        <div
                            class="input-group"
                        >

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
                                value="<?= htmlspecialchars(
                                    $valor,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                required
                            >

                        </div>

                    </div>

                </div>


                <!--
                =================================================
                PEDIDO, ENTREGA Y COBRO
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


                <div
                    class="row
                           g-3
                           mb-4"
                >


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
                                $fecha_pedido,
                                ENT_QUOTES,
                                'UTF-8'
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
                                $fecha_entrega,
                                ENT_QUOTES,
                                'UTF-8'
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
                                <?= $estado_entrega
                                    ===
                                    'pendiente'
                                        ? 'selected'
                                        : ''
                                ?>
                            >

                                Pendiente

                            </option>


                            <option
                                value="entregado"
                                <?= $estado_entrega
                                    ===
                                    'entregado'
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
                                <?= $estado_pago
                                    ===
                                    'pendiente'
                                        ? 'selected'
                                        : ''
                                ?>
                            >

                                Pendiente

                            </option>


                            <option
                                value="parcial"
                                <?= $estado_pago
                                    ===
                                    'parcial'
                                        ? 'selected'
                                        : ''
                                ?>
                            >

                                Parcial

                            </option>


                            <option
                                value="pagado"
                                <?= $estado_pago
                                    ===
                                    'pagado'
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
                        $observaciones,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?></textarea>

                </div>


                <!--
                =================================================
                RESUMEN DEL VALOR
                =================================================
                -->

                <div
                    class="alert
                           alert-light
                           border
                           mb-4"
                >

                    <div
                        class="d-flex
                               justify-content-between
                               align-items-center"
                    >

                        <span
                            class="fw-bold"
                        >

                            Valor total:

                        </span>


                        <strong
                            class="text-primary
                                   fs-5"
                            id="valor_total"
                        >

                            $0,00

                        </strong>

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
                        class="btn btn-outline-secondary"
                    >

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

                        Guardar Cambios

                    </button>

                </div>


            </form>

        </div>

    </div>

</div>


<!--
=================================================
15. JAVASCRIPT
=================================================

Calcula automáticamente:

cantidad × valor unitario

=================================================
-->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const cantidad =
            document.getElementById(
                'cantidad'
            );

        const valor =
            document.getElementById(
                'valor'
            );

        const total =
            document.getElementById(
                'valor_total'
            );


        function calcularTotal() {

            const cantidadValor =
                parseFloat(
                    cantidad.value
                ) || 0;

            const valorValor =
                parseFloat(
                    valor.value
                ) || 0;


            const resultado =
                cantidadValor *
                valorValor;


            total.textContent =
                resultado.toLocaleString(
                    'es-CO',
                    {
                        style: 'currency',
                        currency: 'COP',
                        minimumFractionDigits: 2
                    }
                );

        }


        cantidad.addEventListener(
            'input',
            calcularTotal
        );


        valor.addEventListener(
            'input',
            calcularTotal
        );


        calcularTotal();

    }

);

</script>


<?php

/*
=================================================
16. FOOTER DEL MÓDULO
=================================================
*/

include(
    "../../../template/footer_modulos.php"
);

?>