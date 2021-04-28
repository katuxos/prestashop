<?php

include(dirname(__FILE__).'/../../config/config.inc.php');

include(dirname(__FILE__).'/../../init.php');

include_once _PS_MODULE_DIR_.'ktx_multisincro_xml/classes/katuxosXmlClass.php';
//
///* ****************************************************************
// * Traer registro de KtuxosClass para validar datos
// * Revisar si el token enviado en URL para el id enviado, coincide con el
// * token y el id correspondiente en la BD
// * ****************************************************************
//*/
//
$katuxos = new KatuxosXmlClass((int)Tools::getValue('id'));
if (Tools::getValue('token') == $katuxos->getToken()) {

    $servicio=$katuxos->getRuta(); //"http://www.dmi.es/sw/productos.asmx"; 
    $url=$servicio."?WSDL"; 
    $parametros=array(); 
    $parametros['Usuario']=$katuxos->getUsuario();
    $parametros['Password']=$katuxos->getPassword();
//    $parametros['Usuario']='CT076710';
//    $parametros['Password']='uiwywz7e';
    try {
        $client = @new SoapClient($url, $parametros);
    } catch (Exception $e) {
        echo $e->getMessage().'<br><br>';
    }
    try {
        $result = $client->Catalogo($parametros); 
    } catch (Exception $e) {
        echo $e->getMessage().'<br><br>';
    }
    $Datos = $result->CatalogoResult->any; 
    $xml = str_replace(array("diffgr:","msdata:"),'', $Datos); 
    $xml = "<package>".$xml."</package>";
    $data = simplexml_load_string($xml);
    $nombreDatosXml = (string)$katuxos->getXml();
    $total = count($data->diffgram->DocumentElement->$nombreDatosXml);  //DMI_RecuperarXMLTodosLosProductos
    /* Preparar columnas */
    if ($katuxos->getPrecio() != '0') {
        $datosPrecio = (string)$katuxos->getPrecio();
    } else {
        $datosPrecio = 'No';
    }
    if ($katuxos->getStock() != '0') {
        $datosStock = (string)$katuxos->getStock();
    } else {
        $datosStock = 'No';
    }    
    if ($katuxos->getCanon() != '0') {
        $datosCanon = (string)$katuxos->getCanon();
    } else {
        $datosCanon = 'No';
    }  
    if ($katuxos->getOtros() != '0') {
        $datosOtros = (string)$katuxos->getOtros();
    } else {
        $datosOtros = 'No';
    }    
    if ($katuxos->getReferencia() != 'Error') {
        $datosReferencia = (string)$katuxos->getReferencia();
    } else {
        $datosReferencia = 'No';
    }     
    /* ***************** Columnas preparadas ***************** */
    /* Crear archivo CSV maestro */
    $proveedor = str_replace(' ', '', Supplier::getNamebyId($katuxos->getid_Proveedor()));
    /* Guardar el archivo */
    $nombreArchivo = dirname(__FILE__).'/archivos/'.$proveedor.'.csv';
    $archivoMaster = fopen($nombreArchivo, 'w');
    $i = 0;
    $renglon = null;
    $indice_precio = 0;
    $indice_stock = 1;
    $indice_canon = 2;
    $indice_otros = 3;
    $indice_referencia = 4;    
    while ($i <= $total) {
        $renglon = '';
        /* Buscar PRECIO en datos XML */
        if ($datosPrecio != 'No') {
            $renglon[$indice_precio] = $data->diffgram->DocumentElement->$nombreDatosXml[$i]->$datosPrecio;
        } else {
            $renglon[$indice_precio] = 'No';
        }
        /* Buscar STOCK en datos XML */
        if ($datosStock != 'No') {
            $renglon[$indice_stock] = $data->diffgram->DocumentElement->$nombreDatosXml[$i]->$datosStock;
        } else {
            $renglon[$indice_stock] = 'No';
        }
        /* Buscar CANON en datos XML */
        if ($datosCanon != 'No') {
            $renglon[$indice_canon] = $data->diffgram->DocumentElement->$nombreDatosXml[$i]->$datosCanon;
        } else {
            $renglon[$indice_canon] = 'No';
        }
        /* Buscar OTROS en datos XML */
        if ($datosOtros != 'No') {
            $renglon[$indice_otros] = $data->diffgram->DocumentElement->$nombreDatosXml[$i]->$datosOtros;
        } else {
            $renglon[$indice_otros] = 'No';
        }
        /* Buscar REFERENCIA en datos XML */
        if ($datosReferencia != 'No') {
            $renglon[$indice_referencia] = $data->diffgram->DocumentElement->$nombreDatosXml[$i]->$datosReferencia;
        } else {
            $renglon[$indice_referencia] = 'No';
            echo ('Error, no se puede procesar porque no se tiene la referencia definida');
        }
        fputcsv($archivoMaster, $renglon, ';');
        $i++;
    }
    fclose($archivoMaster);
    unset($archivoMaster);

    /* Procesar el archivo para actualizar lo que se tenga que actualizar */
    procesaArchivo($nombreArchivo, $katuxos->getMargen(), $katuxos->getCosto_Adicional(), $katuxos->getId_Proveedor());
} else {
    echo('Error en el token.');
}
//Eliminar archivo
unlink($nombreArchivo);
return true;

function procesaArchivo($nombre, $margen, $otrosCostos, $idProveedor)
{
    /* CONSTANTES */
    $INACTIVO = 0;
    $ACTIVO = 1;
    /* VARIABLES */
    /* Índices para el array */
    $indice_precio = 0;
    $indice_stock = 1;
    $indice_canon = 2;
    $indice_otros = 3;
    $indice_ref = 4;
    $archivo = fopen($nombre, 'r');
    while (!feof($archivo)) {
        $datos = fgetcsv($archivo, 0, ';', '"', "\\");
        $sql = 'No';
        $sql_stock = 'No';
        $sql_precio = 'No';
        $canon = 0;
        $otros = 0;
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
            if ($datos[$indice_otros] != 'No') {    /* SI hay OTROS costos extra*/
                $otros = (float)$datos[$indice_otros];
            }            
            $precioTotal = round((($datos[$indice_precio] + $otrosCostos + $canon + $otros) * (1 + ($margen/100))), 2);
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
    //Limpiar memoria
    unset($datos);
    unset($archivo);
}