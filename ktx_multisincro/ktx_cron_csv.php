<?php
/** *************************************************************
 * Función que ejecuta el cron para cargar los datos del
 * archivo CSV
 * **************************************************************
 */


include(dirname(__FILE__).'/../../config/config.inc.php');

include(dirname(__FILE__).'/../../init.php');

include_once _PS_MODULE_DIR_.'ktx_multisincro/classes/katuxosCsvClass.php';

/* ****************************************************************
 * Traer registro de KtuxosClass para validar datos
 * Revisar si el token enviado en URL para el id enviado, coincide con el
 * token y el id correspondiente en la BD
 * ****************************************************************
*/

$katuxos = new KatuxosCsvClass((int)Tools::getValue('id'));
if (Tools::getValue('token') == $katuxos->getToken()) {
    /* Abrir archivo remoto */
    $ruta = Tools::file_get_contents($katuxos->getRuta());
    /* Obtener nombre del proveedor para nombrar el archivo */
    $proveedor = Supplier::getNamebyId($katuxos->getid_Proveedor());
    $proveedor = str_replace(' ', '', $proveedor);
    /* Guardar el archivo */
    $nombreArchivo = dirname(__FILE__).'/archivos/'.$proveedor.'.csv';
    file_put_contents($nombreArchivo, $ruta);
    $archivo = fopen($nombreArchivo, 'rb');
    $renglon = fgetcsv($archivo, 0, ';', '"', "\\");
    /* Buscar columna PRECIO en el archivo CSV */
    if ($katuxos->getPrecio() != '0') {
        $colPrecio = array_search($katuxos->getPrecio(), $renglon);
    } else {
        $colPrecio = 'No';
    }
    /* Buscar columna STOCK en el archivo CSV */
    if ($katuxos->getStock() != '0') {
        $colStock = array_search($katuxos->getStock(), $renglon);
    } else {
        $colStock = 'No';
    }
    /* Buscar columna CANON en el archivo CSV */
    if ($katuxos->getCanon() != '0') {
        $colCanon = array_search($katuxos->getCanon(), $renglon);
    } else {
        $colCanon = 'No';
    }
    /* Buscar columna REFERENCIA en el archivo CSV */
    if ($katuxos->getReferencia() != 'Error') {
        $colReferencia = array_search($katuxos->getReferencia(), $renglon);
    } else {
        $colReferencia = 'No';
        echo ('Error, no se puede procesar porque no se tiene la referencia definida');
    }
    if ($colReferencia != 'No') {
        $LOTE = 1000; //Cantidad de líneas a procesar
        $i = 0;
        while (!feof($archivo)) {
            creaArchivos($i, $LOTE, $proveedor, $archivo, $colPrecio, $colCanon, $colStock, $colReferencia);
            $i = $i + $LOTE;
        }
    }
    fclose($archivo);
    $totalarchivos = $i/$LOTE;
    $i = 0;
    //procesar archivos pequeños
    while ($i < $totalarchivos) {
        $archivoProcesar = dirname(__FILE__).'/archivos/'.$proveedor.($i+1).'.csv';
        $margen = $katuxos->getMargen();
        $adicional = $katuxos->getCosto_Adicional();
        $prov = $katuxos->getId_Proveedor();
        procesaArchivos($archivoProcesar, $margen, $adicional, $prov);
        $i++;
    }
} else {
    echo('Error en el token.');
}
//Eliminar archivo
unlink($nombreArchivo);
return true;

function creaArchivos($indice, $LOTE, $proveedor, $apuntador, $colPrecio, $colCanon, $colStock, $colReferencia)
{
    /* VARIABLES */
    /* Índices para el array */
    $escribir = null;
    $indice_ref = 0;
    $indice_precio = 1;
    $indice_canon = 2;
    $indice_stock = 3;
    if ($indice == 0) {
        //Leer fila de encabezado y avanzar apuntador
        $datos = fgetcsv($apuntador, 0, ';', '"', "\\");
        $i = 1;
    } else {
        $i = $indice / $LOTE;
    }
    $nombre = dirname(__FILE__).'/archivos/'.$proveedor.(($indice/$LOTE)+1).'.csv';
    $archivo = fopen($nombre, 'w');
    while ($i <= $LOTE) {
        $datos = fgetcsv($apuntador, 0, ';', '"', "\\");
        $escribir[$indice_ref] = $datos[$colReferencia];
        if ($colPrecio == 'No') {
            $escribir[$indice_precio] = 'No';
        } else {
            $escribir[$indice_precio] = $datos[$colPrecio];
        }
        if ($colCanon == 'No') {
            $escribir[$indice_canon] = 'No';
        } else {
            $escribir[$indice_canon] = $datos[$colCanon];
        }
        if ($colStock == 'No') {
            $escribir[$indice_stock] = 'No';
        } else {
            $escribir[$indice_stock] = $datos[$colStock];
        }
        if (!feof($apuntador)) {
            fputcsv($archivo, $escribir, ';');
            $i++;
        } else {
            $i = $LOTE + 1;
        }
    }
    fclose($archivo);
    unset($archivo);
    unset($datos);
    unset($escribir);
}

function procesaArchivos($nombre, $margen, $otrosCostos, $idProveedor)
{
    /* CONSTANTES */
    $INACTIVO = 0;
    $ACTIVO = 1;
    /* VARIABLES */
    /* Índices para el array */
    $indice_ref = 0;
    $indice_precio = 1;
    $indice_canon = 2;
    $indice_stock = 3;
    $archivo = fopen($nombre, 'r');
    while (!feof($archivo)) {
        $datos = fgetcsv($archivo, 0, ';', '"', "\\");
        $sql = 'No';
        $sql_stock = 'No';
        $sql_precio = 'No';
        $canon = 0;
        /* Revisar qué hay que actualizar */
        if ($datos[$indice_precio] == 'No') {    /* NO hay que actualizar el PRECIO */
            if ($datos[$indice_stock] != 'No') {    /* Actualizar sólo el STOCK */
                if ($datos[$indice_stock] == 0) {
                    /* Si STOCK es cero, desactivar producto. En caso contrario, activarlo */
                    $sql = 'UPDATE '._DB_PREFIX_.'product SET quantity = '.(int)$datos[$indice_stock].
                            ', active = '.$INACTIVO.' WHERE reference = \''.$datos[$indice_ref].
                            '\' and id_supplier = '.$idProveedor;
                    $sql_precio = 'UPDATE '._DB_PREFIX_.'product_shop ps SET ps.quantity = '.(int)$datos[$indice_stock].
                            ', ps.active = '.$INACTIVO.' WHERE ps.id_product IN (SELECT p.id_product FROM '.
                            _DB_PREFIX_.'product p WHERE p.reference = \''.$datos[$indice_ref].
                            '\' and p.id_supplier = '.$idProveedor.')';                    
                    $sql_stock = 'UPDATE '._DB_PREFIX_.'stock_available s SET s.quantity = '.
                            (int)$datos[$indice_stock].' WHERE s.id_product IN (SELECT p.id_product FROM '.
                            _DB_PREFIX_.'product p WHERE p.reference = \''.$datos[$indice_ref].
                            '\' and p.id_supplier = '.$idProveedor.')';                 
                } else {
                    $sql = 'UPDATE '._DB_PREFIX_.'product SET quantity = '.(int)$datos[$indice_stock].
                            ', active = '.$ACTIVO.' WHERE reference = \''.$datos[$indice_ref].'\' and id_supplier = '.
                            $idProveedor;
                    $sql_precio = 'UPDATE '._DB_PREFIX_.'product_shop ps SET ps.quantity = '.(int)$datos[$indice_stock].
                            ', ps.active = '.$ACTIVO.' WHERE ps.id_product IN (SELECT p.id_product FROM '.
                            _DB_PREFIX_.'product p WHERE p.reference = \''.$datos[$indice_ref].
                            '\' and p.id_supplier = '.$idProveedor.')';                    
                    $sql_stock = 'UPDATE '._DB_PREFIX_.'stock_available s SET s.quantity = '.
                            (int)$datos[$indice_stock].' WHERE s.id_product IN (SELECT p. FROM '._DB_PREFIX_.
                            'product p WHERE p.reference = \''.$datos[$indice_ref].'\' and p.id_supplier = '.
                            $idProveedor.')';
                }
            }
        } else {    /* SI hay que actualizar el PRECIO */
            if ($datos[$indice_canon] != 'No') {    /* SI hay CANON */
                $canon = (float)$datos[$indice_canon];
            }
            $precioTotal = round((($datos[$indice_precio] + $otrosCostos + $canon) * (1 + ($margen/100))), 2);
            if ($datos[$indice_stock] == 'No') {    /* Solamente hay que actualizar el PRECIO */
                $sql = 'UPDATE '._DB_PREFIX_.'product SET price = '.$precioTotal.' WHERE reference = \''.
                        $datos[$indice_ref].'\' and id_supplier = '.$idProveedor;
                $sql_precio = 'UPDATE '._DB_PREFIX_.'product_shop ps SET ps.price = '.$precioTotal.
                        ' WHERE ps.id_product IN (SELECT p.id_product FROM '._DB_PREFIX_.
                        'product p WHERE p.reference = \''.$datos[$indice_ref].'\' and p.id_supplier = '.
                        $idProveedor.')';
            } else {    /* Hay que actualizar TODO */
                if ($datos[$indice_stock] == 0) {
                    /* Si STOCK es cero, desactivar producto. En caso contrario, activarlo */
                    $sql = 'UPDATE '._DB_PREFIX_.'product SET price = '.$precioTotal.', quantity = '.
                            (int)$datos[$indice_stock].', active = '.$INACTIVO.' WHERE reference = \''.
                            $datos[$indice_ref].'\' and id_supplier = '.$idProveedor;
                    $sql_stock = 'UPDATE '._DB_PREFIX_.'stock_available s SET s.quantity = '.
                            (int)$datos[$indice_stock].' WHERE s.id_product IN (SELECT p.id_product FROM '.
                            _DB_PREFIX_.'product p WHERE p.reference = \''.$datos[$indice_ref].
                            '\' and p.id_supplier = '.$idProveedor.')';
                    $sql_precio = 'UPDATE '._DB_PREFIX_.'product_shop ps SET ps.price = '.$precioTotal.
                            ', ps.active = '.$INACTIVO.' WHERE ps.id_product IN (SELECT p.id_product FROM '._DB_PREFIX_.
                            'product p WHERE p.reference = \''.$datos[$indice_ref].'\' and p.id_supplier = '.
                            $idProveedor.')';                 
                } else {
                    $sql = 'UPDATE '._DB_PREFIX_.'product SET price = '.$precioTotal.', quantity = '.
                            (int)$datos[$indice_stock].', active = '.$ACTIVO.' WHERE reference = \''.
                            $datos[$indice_ref].'\' and id_supplier = '.$idProveedor;
                    $sql_stock = 'UPDATE '._DB_PREFIX_.'stock_available s SET s.quantity = '.
                            (int)$datos[$indice_stock].' WHERE s.id_product IN (SELECT p.id_product FROM '
                            ._DB_PREFIX_.'product p WHERE p.reference = \''.$datos[$indice_ref].
                            '\' and p.id_supplier = '.$idProveedor.')';
                    $sql_precio = 'UPDATE '._DB_PREFIX_.'product_shop ps SET ps.price = '.$precioTotal.
                            ', ps.active = '.$ACTIVO.' WHERE ps.id_product IN (SELECT p.id_product FROM '._DB_PREFIX_.
                            'product p WHERE p.reference = \''.$datos[$indice_ref].'\' and p.id_supplier = '.
                            $idProveedor.')';                    
                }
            }
        }
        if ($sql <> 'No') {
            Db::getInstance()->Execute($sql);
        }
        if ($sql_stock <> 'No') {
            Db::getInstance()->Execute($sql_stock);
        }
        if ($sql_precio <> 'No') {
            Db::getInstance()->Execute($sql_precio);
        }
    }
    fclose($archivo);
    //Borrar archivo
    unlink($nombre);
    //Limpiar memoria
    unset($datos);
    unset($archivo);
}
