<?php

include("../../modulos/conexion_modulos.php");

$importados = 0;
$duplicados = 0;
$errores = 0;

if(isset($_FILES['archivo'])){

    $extension = strtolower(pathinfo($_FILES["archivo"]["name"], PATHINFO_EXTENSION));

    if($extension != "csv"){
        die("Solo se permiten archivos CSV.");
    }

    echo "Archivo recibido correctamente.<br><br>";

    $archivo = fopen($_FILES['archivo']['tmp_name'], "r");

    if(!$archivo){
        die("No fue posible abrir el archivo.");
    }

    // Saltar encabezados
    fgetcsv($archivo);

 while(($fila = fgetcsv($archivo, 1000, ";")) !== false){

    // Ignorar filas vacías
    if(empty(array_filter($fila))){
        continue;
    }

    // Limpiar espacios
    $fila = array_map("trim", $fila);

/*
=================================================
VALIDAR CANTIDAD DE COLUMNAS DEL CSV
Verifica que cada fila tenga las 7 columnas
esperadas antes de acceder a sus posiciones.
Si faltan datos, la fila se ignora y se registra
como un error.
=================================================
*/
if(count($fila) < 7){

    echo "<span style='color:red'>
            La fila no tiene el número de columnas esperado.
          </span><br>";

    $errores++;
    continue;
}

    $tipo_documento   = strtoupper(trim($fila[0]));
    $documento        = trim($fila[1]);
    $telefono         = trim($fila[2]);
    $nombre           = trim($fila[3]);
    $fecha_nacimiento = trim($fila[4]);
    $posicion         = ucwords(strtolower(trim($fila[5])));
    $entrenador       = trim($fila[6]);

        // Validar tipo documento
        if(!in_array($tipo_documento, ["CC","TI"])){

            echo "<span style='color:red'>
                    Tipo de documento inválido para <strong>$nombre</strong>.
                  </span><br>";

            $errores++;
            continue;
        }

        // Validar documento
        if(!ctype_digit($documento)){

            echo "<span style='color:red'>
                    Documento inválido para <strong>$nombre</strong>.
                  </span><br>";

            $errores++;
            continue;
        }

        // Validar teléfono
        if(!ctype_digit($telefono)){

            echo "<span style='color:red'>
                    Teléfono inválido para <strong>$nombre</strong>.
                  </span><br>";

            $errores++;
            continue;
        }

        // Validar nombre
        if(empty($nombre)){

            echo "<span style='color:red'>
                    Nombre vacío.
                  </span><br>";

            $errores++;
            continue;
        }

        // Validar posición
        $posiciones = [
            "Arquero",
            "Defensa",
            "Volante",
            "Delantero"
        ];

        if(!in_array($posicion, $posiciones)){

            echo "<span style='color:red'>
                    Posición inválida para <strong>$nombre</strong>.
                  </span><br>";

            $errores++;
            continue;
        }

        // Documento repetido
        $stm = $conexion->prepare("
            SELECT id
            FROM deportista
            WHERE documento = :documento
        ");

        $stm->execute([
            ":documento"=>$documento
        ]);

        if($stm->rowCount()>0){

            $duplicados++;

            echo "<span style='color:orange'>
                    Documento $documento ya existe.
                  </span><br>";

            continue;
        }

/*
=================================================
VALIDAR Y CONVERTIR FECHA DE NACIMIENTO
Convierte la fecha del formato d/m/Y a Y-m-d y
verifica que sea una fecha válida.
=================================================
*/
$fecha = DateTime::createFromFormat("d/m/Y", $fecha_nacimiento);

$fecha = DateTime::createFromFormat("d/m/Y", $fecha_nacimiento);

if(!$fecha){

    echo "<span style='color:red'>
            Fecha inválida para <strong>$nombre</strong>.
          </span><br>";

    $errores++;
    continue;
}

$fecha_nacimiento = $fecha->format("Y-m-d");

$fecha_nacimiento = $fecha->format("Y-m-d");

        // Año
        $anio_nacimiento = date("Y",strtotime($fecha_nacimiento));

        // Buscar categoría
        $stmtCategoria = $conexion->prepare("
            SELECT id
            FROM categoria
            WHERE anio_desde <= :anio
            AND anio_hasta >= :anio
            LIMIT 1
        ");

        $stmtCategoria->execute([
            ":anio"=>$anio_nacimiento
        ]);

        $categoria = $stmtCategoria->fetch(PDO::FETCH_ASSOC);

        if(!$categoria){

            echo "<span style='color:red'>
                    No existe categoría para el año $anio_nacimiento.
                  </span><br>";

            $errores++;
            continue;
        }

        $categoria_id = $categoria["id"];

        /*
=================================================
REGISTRAR DEPORTISTA
Inicia una transacción para insertar el
deportista y asociarlo con su entrenador.
=================================================
*/
        try{

            $conexion->beginTransaction();

            // Insertar deportista
            $stmtInsert = $conexion->prepare("

                INSERT INTO deportista(

                    tipo_documento,
                    documento,
                    telefono,
                    nombre,
                    fecha_nacimiento,
                    posicion,
                    categoria_id,
                    estado

                )

                VALUES(

                    :tipo_documento,
                    :documento,
                    :telefono,
                    :nombre,
                    :fecha_nacimiento,
                    :posicion,
                    :categoria_id,
                    'activo'

                )

            ");

            $stmtInsert->execute([

                ":tipo_documento"=>$tipo_documento,
                ":documento"=>$documento,
                ":telefono"=>$telefono,
                ":nombre"=>$nombre,
                ":fecha_nacimiento"=>$fecha_nacimiento,
                ":posicion"=>$posicion,
                ":categoria_id"=>$categoria_id

            ]);

            $deportista_id = $conexion->lastInsertId();

            // Guardar entrenador
            $stmtRelacion = $conexion->prepare("

                INSERT INTO usuario_deportista(

                    deportista_id,
                    entrenador

                )

                VALUES(

                    :deportista_id,
                    :entrenador

                )

            ");

            $stmtRelacion->execute([

                ":deportista_id"=>$deportista_id,
                ":entrenador"=>$entrenador

            ]);

            $conexion->commit();

            $importados++;

            echo "<span style='color:green'>
                    ✔ <strong>$nombre</strong> importado correctamente.
                  </span><br>";

        }catch(PDOException $e){

            if($conexion->inTransaction()){
            $conexion->rollBack();
        }

            $errores++;

            echo "<span style='color:red'>
                    Error al importar <strong>$nombre</strong>: ".$e->getMessage()."
                  </span><br>";
        }

    }

fclose($archivo);

echo "<hr>";

/*
=================================================
MENSAJE FINAL DE LA IMPORTACIÓN
Indica si la importación terminó correctamente
o si hubo errores o registros duplicados.
=================================================
*/
if($errores == 0 && $duplicados == 0){

    echo "<div class='alert alert-success'>
            <strong>Importación finalizada correctamente.</strong>
          </div>";

}else{

    echo "<div class='alert alert-warning'>
            <strong>La importación finalizó con novedades. Revise el resumen.</strong>
          </div>";
}

echo "<h2>Resumen de la importación</h2>";

echo "
<table border='1' cellpadding='8' cellspacing='0'>

    <tr style='background:#198754;color:white'>
        <th>Concepto</th>
        <th>Cantidad</th>
    </tr>

    <tr>
        <td>Importados</td>
        <td>$importados</td>
    </tr>

    <tr>
        <td>Duplicados</td>
        <td>$duplicados</td>
    </tr>

    <tr>
        <td>Errores</td>
        <td>$errores</td>
    </tr>

</table>";

}else{

    echo "No se recibió ningún archivo.";

}


echo "<br><br>";

echo "<a href='index.php' class='btn btn-primary'>
        ← Volver al listado
      </a>";