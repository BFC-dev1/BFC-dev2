<?php

/*
=================================================
MÓDULO FINANCIERO - VER UNIFORME
=================================================

Este archivo permite consultar en detalle
un uniforme registrado.

Información mostrada:

- Deportista
- Documento
- Categoría
- Tipo de uniforme
- Talla
- Cantidad
- Valor unitario
- Valor total
- Fecha del pedido
- Fecha de entrega
- Estado de entrega
- Estado de pago
- Observaciones
- Fecha de creación

Este archivo sigue exactamente el mismo
framework utilizado por el módulo Financiero.

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
2. VERIFICAR PERMISO
=================================================

El permiso utilizado para Uniformes es:

financiero_uniformes

Si el usuario no tiene permiso, vuelve
al Dashboard.

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
3. AUDITORÍA
=================================================
*/

include(
    "../../../modulos/auditoria/funciones/registrar_auditoria.php"
);


/*
=================================================
4. CONEXIÓN A LA BASE DE DATOS
=================================================

IMPORTANTE:

Se utiliza exactamente la misma conexión
que utiliza index.php y nuevo_uniforme.php.

NO utilizar:

../../../includes/conexion.php

La conexión correcta del proyecto es:

../../../modulos/conexion_modulos.php

=================================================
*/

include(
    "../../../modulos/conexion_modulos.php"
);


/*
=================================================
5. VALIDAR ID DEL UNIFORME
=================================================

El ID llega mediante:

ver_uniforme.php?id=1

=================================================
*/

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


/*
-------------------------------------------------
SI EL ID NO ES VÁLIDO
-------------------------------------------------
*/

if (!$id) {

    header(
        "Location: index.php"
    );

    exit;
}


/*
=================================================
6. CONSULTAR UNIFORME
=================================================

Relaciones:

uniformes.deportista_id
        ↓
deportista.id

deportista.categoria_id
        ↓
categoria.id

=================================================
*/

$sql = "

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

        c.nombre AS categoria_nombre

    FROM uniformes u

    INNER JOIN deportista d
        ON d.id = u.deportista_id

    LEFT JOIN categoria c
        ON c.id = d.categoria_id

    WHERE u.id = :id

    LIMIT 1

";


$stmt = $conexion->prepare($sql);

$stmt->execute([
    ':id' => $id
]);


/*
=================================================
7. OBTENER RESULTADO
=================================================
*/

$uniforme = $stmt->fetch(
    PDO::FETCH_ASSOC
);


/*
=================================================
8. VALIDAR QUE EXISTA EL UNIFORME
=================================================
*/

if (!$uniforme) {

    header(
        "Location: index.php"
    );

    exit;
}


/*
=================================================
9. VARIABLES
=================================================
*/

$deportista_nombre =
    $uniforme['deportista_nombre'] ?? '-';

$deportista_documento =
    $uniforme['deportista_documento'] ?? '-';

$categoria_nombre =
    $uniforme['categoria_nombre'] ?? '-';

$tipo_uniforme =
    $uniforme['tipo_uniforme'] ?? '-';

$talla =
    $uniforme['talla'] ?? '-';

$cantidad =
    (int) (
        $uniforme['cantidad'] ?? 0
    );

$valor =
    (float) (
        $uniforme['valor'] ?? 0
    );

$fecha_pedido =
    $uniforme['fecha_pedido'] ?? null;

$fecha_entrega =
    $uniforme['fecha_entrega'] ?? null;

$estado_entrega =
    $uniforme['estado_entrega']
    ?? 'pendiente';

$estado_pago =
    $uniforme['estado_pago']
    ?? 'pendiente';

$observaciones =
    $uniforme['observaciones'] ?? '';

$fecha_creacion =
    $uniforme['fecha_creacion'] ?? null;


/*
=================================================
10. CALCULAR VALOR TOTAL
=================================================

Valor total:

valor unitario × cantidad

=================================================
*/

$valor_total =
    $valor * $cantidad;


/*
=================================================
11. FORMATEAR VALORES MONETARIOS
=================================================
*/

$valor_formateado =
    number_format(
        $valor,
        2,
        ',',
        '.'
    );


$valor_total_formateado =
    number_format(
        $valor_total,
        2,
        ',',
        '.'
    );


/*
=================================================
12. FORMATEAR FECHA DEL PEDIDO
=================================================
*/

$fecha_pedido_formateada = '-';

if (!empty($fecha_pedido)) {

    $fecha_pedido_formateada =
        date(
            'd/m/Y',
            strtotime($fecha_pedido)
        );
}


/*
=================================================
13. FORMATEAR FECHA DE ENTREGA
=================================================
*/

$fecha_entrega_formateada = '-';

if (!empty($fecha_entrega)) {

    $fecha_entrega_formateada =
        date(
            'd/m/Y',
            strtotime($fecha_entrega)
        );
}


/*
=================================================
14. FORMATEAR FECHA DE CREACIÓN
=================================================
*/

$fecha_creacion_formateada = '-';

if (!empty($fecha_creacion)) {

    $fecha_creacion_formateada =
        date(
            'd/m/Y H:i',
            strtotime($fecha_creacion)
        );
}


/*
=================================================
15. ESTADO DE ENTREGA
=================================================

Estados reales:

pendiente
entregado

=================================================
*/

$estado_entrega_clase =
    'warning';

$estado_entrega_texto =
    'Pendiente';


if ($estado_entrega === 'entregado') {

    $estado_entrega_clase =
        'success';

    $estado_entrega_texto =
        'Entregado';
}


/*
=================================================
16. ESTADO DE PAGO
=================================================

Estados reales:

pendiente
parcial
pagado

=================================================
*/

$estado_pago_clase =
    'warning';

$estado_pago_texto =
    'Pendiente';


if ($estado_pago === 'parcial') {

    $estado_pago_clase =
        'info';

    $estado_pago_texto =
        'Parcial';

}


elseif ($estado_pago === 'pagado') {

    $estado_pago_clase =
        'success';

    $estado_pago_texto =
        'Pagado';
}


/*
=================================================
17. HEADER DEL MÓDULO
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
18. CONTENIDO PRINCIPAL
=================================================
-->

<div class="container-fluid py-4">


    <!--
    =================================================
    VOLVER A UNIFORMES
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

        <h2 class="fw-bold mb-1">

            <i
                class="fa-solid
                       fa-shirt
                       text-primary
                       me-2"
            ></i>

            Ver Uniforme

        </h2>


        <p class="text-muted mb-0">

            Consulta detallada del uniforme
            registrado.

        </p>

    </div>


    <!--
    =================================================
    INFORMACIÓN DEL DEPORTISTA
    =================================================
    -->

    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <h5 class="fw-bold mb-3">

                <i
                    class="fa-solid
                           fa-person-running
                           text-primary
                           me-2"
                ></i>

                Deportista

            </h5>


            <div class="row g-3">


                <!-- NOMBRE -->

                <div class="col-md-4">

                    <label
                        class="form-label
                               text-muted
                               small
                               mb-1"
                    >
                        Nombre
                    </label>

                    <div class="fw-bold">

                        <?= htmlspecialchars(
                            $deportista_nombre
                        ) ?>

                    </div>

                </div>


                <!-- DOCUMENTO -->

                <div class="col-md-4">

                    <label
                        class="form-label
                               text-muted
                               small
                               mb-1"
                    >
                        Documento
                    </label>

                    <div>

                        <?= htmlspecialchars(
                            $deportista_documento
                        ) ?>

                    </div>

                </div>


                <!-- CATEGORÍA -->

                <div class="col-md-4">

                    <label
                        class="form-label
                               text-muted
                               small
                               mb-1"
                    >
                        Categoría
                    </label>

                    <div>

                        <?= htmlspecialchars(
                            $categoria_nombre
                        ) ?>

                    </div>

                </div>


            </div>

        </div>

    </div>


    <!--
    =================================================
    DATOS DEL UNIFORME
    =================================================
    -->

    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <h5 class="fw-bold mb-3">

                <i
                    class="fa-solid
                           fa-shirt
                           text-primary
                           me-2"
                ></i>

                Datos del Uniforme

            </h5>


            <div class="row g-3">


                <!-- TIPO -->

                <div class="col-md-4">

                    <label
                        class="form-label
                               text-muted
                               small
                               mb-1"
                    >
                        Tipo de uniforme
                    </label>

                    <div class="fw-bold">

                        <?= htmlspecialchars(
                            $tipo_uniforme
                        ) ?>

                    </div>

                </div>


                <!-- TALLA -->

                <div class="col-md-2">

                    <label
                        class="form-label
                               text-muted
                               small
                               mb-1"
                    >
                        Talla
                    </label>

                    <div>

                        <span
                            class="badge
                                   bg-secondary"
                        >

                            <?= htmlspecialchars(
                                $talla
                            ) ?>

                        </span>

                    </div>

                </div>


                <!-- CANTIDAD -->

                <div class="col-md-2">

                    <label
                        class="form-label
                               text-muted
                               small
                               mb-1"
                    >
                        Cantidad
                    </label>

                    <div class="fw-bold">

                        <?= $cantidad ?>

                    </div>

                </div>


                <!-- VALOR UNITARIO -->

                <div class="col-md-2">

                    <label
                        class="form-label
                               text-muted
                               small
                               mb-1"
                    >
                        Valor unitario
                    </label>

                    <div class="fw-bold">

                        $<?= $valor_formateado ?>

                    </div>

                </div>


                <!-- VALOR TOTAL -->

                <div class="col-md-2">

                    <label
                        class="form-label
                               text-muted
                               small
                               mb-1"
                    >
                        Valor total
                    </label>

                    <div
                        class="fw-bold
                               text-primary"
                    >

                        $<?= $valor_total_formateado ?>

                    </div>

                </div>


            </div>

        </div>

    </div>


    <!--
    =================================================
    PEDIDO, ENTREGA Y PAGO
    =================================================
    -->

    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <h5 class="fw-bold mb-3">

                <i
                    class="fa-solid
                           fa-box
                           text-primary
                           me-2"
                ></i>

                Pedido, Entrega y Cobro

            </h5>


            <div class="row g-3">


                <!-- FECHA PEDIDO -->

                <div class="col-md-3">

                    <label
                        class="form-label
                               text-muted
                               small
                               mb-1"
                    >
                        Fecha del pedido
                    </label>

                    <div class="fw-bold">

                        <?= $fecha_pedido_formateada ?>

                    </div>

                </div>


                <!-- FECHA ENTREGA -->

                <div class="col-md-3">

                    <label
                        class="form-label
                               text-muted
                               small
                               mb-1"
                    >
                        Fecha de entrega
                    </label>

                    <div class="fw-bold">

                        <?= $fecha_entrega_formateada ?>

                    </div>

                </div>


                <!-- ESTADO ENTREGA -->

                <div class="col-md-3">

                    <label
                        class="form-label
                               text-muted
                               small
                               mb-1"
                    >
                        Estado de entrega
                    </label>

                    <div>

                        <span
                            class="badge
                                   bg-<?= $estado_entrega_clase ?>"
                        >

                            <?= $estado_entrega_texto ?>

                        </span>

                    </div>

                </div>


                <!-- ESTADO PAGO -->

                <div class="col-md-3">

                    <label
                        class="form-label
                               text-muted
                               small
                               mb-1"
                    >
                        Estado de pago
                    </label>

                    <div>

                        <span
                            class="badge
                                   bg-<?= $estado_pago_clase ?>
                                   <?php if (
                                       $estado_pago === 'parcial'
                                   ): ?>
                                       text-dark
                                   <?php endif; ?>"
                        >

                            <?= $estado_pago_texto ?>

                        </span>

                    </div>

                </div>


            </div>

        </div>

    </div>


    <!--
    =================================================
    OBSERVACIONES
    =================================================
    -->

    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <h5 class="fw-bold mb-3">

                <i
                    class="fa-solid
                           fa-note-sticky
                           text-primary
                           me-2"
                ></i>

                Observaciones

            </h5>


            <?php if (
                trim($observaciones) !== ''
            ): ?>

                <div
                    class="bg-light
                           rounded
                           p-3"
                >

                    <?= nl2br(
                        htmlspecialchars(
                            $observaciones
                        )
                    ) ?>

                </div>

            <?php else: ?>

                <p class="text-muted mb-0">

                    No hay observaciones registradas.

                </p>

            <?php endif; ?>


        </div>

    </div>


    <!--
    =================================================
    INFORMACIÓN DEL REGISTRO
    =================================================
    -->

    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <h5 class="fw-bold mb-3">

                <i
                    class="fa-solid
                           fa-circle-info
                           text-primary
                           me-2"
                ></i>

                Información del registro

            </h5>


            <div class="row">


                <!-- ID -->

                <div class="col-md-4">

                    <label
                        class="form-label
                               text-muted
                               small
                               mb-1"
                    >
                        ID del uniforme
                    </label>

                    <div>

                        #<?= (int)$uniforme['id'] ?>

                    </div>

                </div>


                <!-- FECHA CREACIÓN -->

                <div class="col-md-4">

                    <label
                        class="form-label
                               text-muted
                               small
                               mb-1"
                    >
                        Fecha de creación
                    </label>

                    <div>

                        <?= $fecha_creacion_formateada ?>

                    </div>

                </div>


            </div>

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


        <!-- VOLVER -->

        <a
            href="index.php"
            class="btn btn-outline-dark"
        >

            <i
                class="fa-solid
                       fa-arrow-left
                       me-1"
            ></i>

            Volver

        </a>


        <!-- EDITAR -->

        <?php if (
            tiene_permiso(
                'financiero_uniformes'
            )
        ): ?>

            <a
                href="editar_uniforme.php?id=<?= (int)$uniforme['id'] ?>"
                class="btn btn-primary"
            >

                <i
                    class="fa-solid
                           fa-pen
                           me-1"
                ></i>

                Editar Uniforme

            </a>

        <?php endif; ?>


    </div>


</div>


<?php

/*
=================================================
19. FOOTER DEL MÓDULO
=================================================
*/

include(
    "../../../template/footer_modulos.php"
);

?>