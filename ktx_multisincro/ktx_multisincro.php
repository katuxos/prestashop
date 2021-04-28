<?php
/** Copyright Katuxos 2019
 * 
 * Actualización de stock y precios proporcionados por el proveedor en
 * archivo CSV.  Genera CRON para automatizar la tarea en CRON manager
 * del servidor
 * 
 */

include_once _PS_MODULE_DIR_.'ktx_multisincro/classes/katuxosCsvClass.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ktx_MultiSincro extends Module
{

    public function __construct()
    {
        $this->name = 'ktx_multisincro';
        $this->version = '1.0.0';
        $this->author = 'Katuxos';
        $this->bootstrap = true;
        $this->tab = 'quick_bulk_update';
        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);
        $this->displayName = $this->l('Actualización de Precios y Stock de Productos de distintos Proveedores');
        $this->description = $this->l('Configuración para actualizar Stock y Precio con tarea CRON. (CSV)');

        parent::__construct();
    }

    public function install()
    {
        if (!parent::install() || !(Db::getInstance()->Execute('CREATE TABLE IF NOT EXISTS `'. _DB_PREFIX_ .
                'ktx_multisincro_csv` (`id` INT NOT NULL AUTO_INCREMENT, `id_proveedor` INT(11) DEFAULT 0, '
                . '`ruta` VARCHAR(255) DEFAULT NULL, `referencia` VARCHAR(255) DEFAULT NULL, '
                . '`stock` VARCHAR(255) DEFAULT NULL, `precio` VARCHAR(255) DEFAULT NULL, '
                . '`canon` VARCHAR(255) DEFAULT NULL, `cron` VARCHAR(255) DEFAULT NULL, `margen` DECIMAL(20,2) '
                . 'DEFAULT 0, `costo_adicional` DECIMAL(20,2) DEFAULT 0, `token` VARCHAR(255) DEFAULT NULL, '
                . 'PRIMARY KEY (`id`)) ENGINE='. _MYSQL_ENGINE_ .' DEFAULT CHARSET=utf8;')))
        {
            return false;
        }
        return true;
    }
    
    public function uninstall()
    {
        if (!parent::uninstall() || !(Db::getInstance()->Execute('DROP TABLE `'. _DB_PREFIX_.
                'ktx_multisincro_csv`;')))
        {
            return false;
        }
        return true;
    }

    public function getContent()
    {
        if (Tools::isSubmit('addktx_multisincro')) {  /* Crear nueva configuración de archivo CSV para un proveedor */
            return $this->generaFormulario();
        } elseif (Tools::isSubmit('saveKtx_Multisincro')) {
            /* Salvar nueva configuración de archivo CSV para un proveedor */
            $katuxos = new katuxosCsvClass();
            $katuxos->setId_Proveedor((int)Tools::getValue('id_proveedor', 'Error'));
            $katuxos->setRuta((string)Tools::getValue('ruta', 'Error'));
            $katuxos->setReferencia((string)Tools::getValue('referencia', 'Error'));
            $katuxos->setStock((string)Tools::getValue('stock', '0'));
            $katuxos->setPrecio((string)Tools::getValue('precio', '0'));
            $katuxos->setCanon((string)Tools::getValue('canon', '0'));
            $katuxos->setCosto_Adicional((float)Tools::getValue('costo_adicional', 0));
            $katuxos->setMargen((float)Tools::getValue('margen', 0));
            if ($katuxos->save()) {
                $this->_confirmations[] = $this->l('Guardado');
            } else {
                $this->_errors[] = $this->l('Error al guardar.  Revisa los datos');
            }
            /* Generar token para tarea CRON */
            $codigo = $this->secure_key = md5(uniqid(rand(), true));
            $katuxos->setCron((string)$this->generaCron($codigo, $katuxos->getId()));
            $katuxos->setToken((string)$codigo);

            if ($katuxos->update()) {
                $this->_confirmations[] = $this->l('Guardado');
            } else {
                $this->_errors[] = $this->l('Error al guardar.  Revisa los datos');
            }

            Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='
                    .Tools::getAdminTokenLite('AdminModules'));
        } elseif (Tools::isSubmit('viewktx_multisincro')) {  /* Hacer cambios a un registro existente */
            return $this->formularioEdicion();
        } elseif (Tools::isSubmit('saveEditKtx_Multisincro')) {  /* Salvar cambios a un registro existente */
            $katuxos = new katuxosCsvClass((int)Tools::getValue('id'));
            $katuxos->setId_Proveedor((int)Tools::getValue('id_proveedor', 'Error'));
            $katuxos->setRuta((string)Tools::getValue('ruta', 'Error'));
            $katuxos->setStock((string)Tools::getValue('referencia', 'Error'));
            $katuxos->setStock((string)Tools::getValue('stock', '0'));
            $katuxos->setPrecio((string)Tools::getValue('precio', '0'));
            $katuxos->setCanon((string)Tools::getValue('canon', '0'));
            $katuxos->setCosto_Adicional((float)Tools::getValue('costo_adicional', 0));
            $katuxos->setMargen((float)Tools::getValue('margen', 0));
            $codigo = $this->secure_key = md5(uniqid(rand(), true));
            $katuxos->setCron((string)$this->generaCron($codigo, $katuxos->getId()));
            $katuxos->setToken((string)$codigo);

            if ($katuxos->save()) {
                $this->_confirmations[] = $this->l('Guardado');
            } else {
                $this->_errors[] = $this->l('Error al guardar.  Revisa los datos');
            }

            Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='
                    .Tools::getAdminTokenLite('AdminModules'));
        } elseif (Tools::isSubmit('deletektx_multisincro')) {
            /* Eliminar configuración de archivo CSV para un proveedor */
            $katuxos = new katuxosCsvClass((int)Tools::getValue('id_csv'));
            if ($katuxos->delete()) {
                $this->_confirmations[] = $this->l('Borrado');
            } else {
                $this->_errors[] = $this->l('Error al eliminar.');
            }
            $this->_clearCache('*');
            Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='
                    .Tools::getAdminTokenLite('AdminModules'));
        } else {    /* Pantalla inicial, mostrar listado */
            return $this->generaLista();
        }
    }

    public function generaLista()
    {
        $resultados = Db::getInstance()->ExecuteS('SELECT kc.id as id_csv, sp.name as nombre_proveedor, '
                . 'kc.cron as tareacron FROM `'._DB_PREFIX_.'ktx_multisincro_csv` kc JOIN `'._DB_PREFIX_.
                'supplier` sp on kc.id_proveedor = sp.id_supplier ORDER BY kc.id DESC');
        if (count($resultados) == 0) {
            $resultados = null;
        }

        $this->fields_list = array(
            'id_csv' => array(
                    'title' => $this->l('Id'),
                    'type' => 'number',
                    'orderby' => true,
                    'search' => true,
            ),
            'nombre_proveedor' => array (
                    'title' => $this->l('Proveedor'),
                    'type' => 'text',
                    'orderby' => true,
                    'search' => true,
            ),
            'tareacron' => array (
                    'title' => $this->l('Tarea CRON'),
                    'type' => 'text',
                    'orderby' => true,
                    'search' => true,
            )
        );

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        $helper->actions = array('view', 'delete');
        $helper->identifier = 'id_csv';
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->toolbar_btn ['new'] = array(
            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&add'.$this->name.'&token='.
                Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Añadir nuevo')
        );
        $helper->listTotal = count($resultados);
        $helper->module = $this;
        $helper->title = $this->l('Proveedores con Actualización mediante CSV');
        $helper->_pagination = array(10, 20, 50, 100, 200);
        $helper->table = _DB_PREFIX_.$this->name.'_csv';
        $helper->table = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        return $helper->generateList($resultados, $this->fields_list);
    }

    public function generaFormulario()
    {
        $proveedores = Supplier::getSuppliers();
        if (empty($proveedores)) {
            $proveedores = null;
        }

        $this->fields_form = array (
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Nueva sincronización CSV'),
                ),
                'input' => array(
                    'id_proveedor' => array(
                        'type' => 'select',
                        'desc' => $this->l('Elige un Proveedor'),
                        'label' => $this->l('Proveedor:'),
                        'name' => 'id_proveedor',
                        'required' => true,
                        'options' => array(
                            'query' => $proveedores,
                            'id' => 'id_supplier',
                            'name' => 'name',
                        )
                    ),
                    'ruta' => array(
                        'type' => 'text',
                        'label' => $this->l('Ruta del archivo CSV:'),
                        'desc' => $this->l('Ruta del archivo CSV (ejemplo: http://webdetuproveedor.com/archivo.csv)'),
                        'name' => 'ruta',
                        'required' => true,
                    ),
                    'referencia' => array(
                        'type' => 'text',
                        'label' => $this->l('Referencia:'),
                        'desc' => $this->l('Nombre de la columna que tiene la referencia del producto'),
                        'name' => 'referencia',
                        'required' => true,
                    ),
                    'stock' => array(
                        'type' => 'text',
                        'label' => $this->l('Stock:'),
                        'desc' => $this->l('Nombre de la columna que tiene el stock. Si no deseas actualizar '
                                . 'el stock, deja en 0.'),
                        'name' => 'stock',
                    ),
                    'precio' => array(
                        'type' => 'text',
                        'label' => $this->l('Precio base del proveedor:'),
                        'desc' => $this->l('Nombre de la columna que tiene el precio base del proveedor '
                                . '(sin impuestos).Si no quieres actualizar el precio, deja en 0.'),
                        'name' => 'precio',
                    ),
                    'canon' => array(
                        'type' => 'text',
                        'label' => $this->l('Canon:'),
                        'desc' => $this->l('Nombre de la columna que tiene el canon a añadir al precio del producto.'
                                . 'Si no hay canon, deja en 0.'),
                        'name' => 'canon',
                    ),
                    'costo_adicional' => array(
                        'type' => 'text',
                        'label' => $this->l('Costos adicionales sin impuestos:'),
                        'desc' => $this->l('Costos adicionales (por ejemplo: tasas) que quieras sumar al precio.'),
                        'name' => 'costo_adicional',
                    ),
                    'margen' => array(
                        'type' => 'text',
                        'label' => $this->l('Margen para cálculo de precio:'),
                        'desc' => $this->l('Margen para cálculo de precio (ejemplo: 20, 5, etc.). El precio antes '
                                . 'de impuestos que se actualizará en tu producto será de acuerdo a esta fórmula: '
                                . '(PRECIO DEL PROVEEDOR + Otros Costos + Canon) * (1+ (Margen/100))'),
                        'name' => 'margen',
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Guardar'),
                )
            )
        );

        $helper = new HelperForm();
        $helper->title = $this->l('Configurar nueva actualización por CSV');
        $helper->submit_action = 'saveKtx_Multisincro';
        $helper->table = _DB_PREFIX_.$this->name.'_csv';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->tpl_vars = array(
            'fields_value' => array(
            'id_proveedor' => '',
            'ruta' => '',
            'referencia' => '',
            'stock' => '0',
            'precio' => '0',
            'canon' => '0',
            'costo_adicional' => '0',
            'margen' => '0'
            )
        );
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        return $helper->generateForm(array($this->fields_form));
    }

    public function formularioEdicion()
    {
        if ((int)Tools::getValue('id_csv')) {
            $katuxos = new katuxosCsvClass((int)Tools::getValue('id_csv'));
        }

        $proveedores = Supplier::getSuppliers();
        if (empty($proveedores)) {
            $proveedores = null;
        }

        $this->fields_form = array (
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Nueva sincronización CSV'),
                ),
                'input' => array(
                    'id' => array(
                        'type' => 'hidden',
                        'width' => 5,
                        'label' => $this->l('ID:'),
                        'desc' => $this->l('Id'),
                        'name' => 'id',
                        'readonly' => true
                    ),
                    'id_proveedor' => array(
                        'type' => 'select',
                        'desc' => $this->l('Proveedor Elegido'),
                        'label' => $this->l('Proveedor:'),
                        'name' => 'id_proveedor',
                        'options' => array(
                            'query' => $proveedores,
                            'id' => 'id_supplier',
                            'name' => 'name',
                        ),
                    ),
                    'ruta' => array(
                        'type' => 'text',
                        'label' => $this->l('Ruta del archivo CSV:'),
                        'desc' => $this->l('Ruta del archivo CSV (ejemplo: http://webdetuproveedor.com/archivo.csv)'),
                        'name' => 'ruta',
                    ),
                    'referencia' => array(
                        'type' => 'text',
                        'label' => $this->l('Referencia:'),
                        'desc' => $this->l('Nombre de la columna que tiene la referencia del producto.'),
                        'name' => 'referencia',
                    ),
                    'stock' => array(
                        'type' => 'text',
                        'label' => $this->l('Stock:'),
                        'desc' => $this->l('Nombre de la columna que tiene el stock. Para dejar de actualizar el '
                                . 'stock, escribe 0.'),
                        'name' => 'stock',
                    ),
                    'precio' => array(
                        'type' => 'text',
                        'label' => $this->l('Precio base del proveedor:'),
                        'desc' => $this->l('Nombre de la columna que tiene el precio base del proveedor '
                                . '(sin impuestos).  Para dejar de actualizar el precio, escribe 0.'),
                        'name' => 'precio',
                    ),
                    'canon' => array(
                        'type' => 'text',
                        'label' => $this->l('Canon:'),
                        'desc' => $this->l('Nombre de la columna que tiene el canon a añadir al precio del producto.'
                                . 'Si no hay canon o no quieres añadirlo, escribe 0.'),
                        'name' => 'canon',
                    ),
                    'costo_adicional' => array(
                        'type' => 'text',
                        'label' => $this->l('Costos adicionales sin impuestos:'),
                        'desc' => $this->l('Costos adicionales (por ejemplo: tasas) que quieras sumar al precio. '
                                . 'Para no sumar nada, escribe 0.'),
                        'name' => 'costo_adicional',
                    ),
                    'margen' => array(
                        'type' => 'text',
                        'label' => $this->l('Margen para cálculo de precio:'),
                        'desc' => $this->l('Margen para cálculo de precio (ejemplo: 20, 5, etc.). El precio antes'
                                . ' de impuestos que se actualizará en tu producto será de acuerdo a esta fórmula:'
                                . ' (PRECIO DEL PROVEEDOR + Otros Costos + Canon) * (1+ (Margen/100))'),
                        'name' => 'margen',
                    ),
                    'cron' => array(
                        'type' => 'hidden',
                        'label' => $this->l('Tarea cron:'),
                        'desc' => $this->l('Tarea cron que debes incluir en el CRON manager de tu servidor.'),
                        'name' => 'cron',
                        'readonly' => true,
                    ),
                    'labelCron' => array(
                        'type' => 'text',
                        'label' => $this->l('Tarea cron:'),
                        'desc' => $this->l('Copia para llamar desde el CRON manager de tu servidor.'),
                        'name' => 'labelCron',
                        'disabled' => true,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Guardar Cambios'),
                )
            )
        );

        $helper = new HelperForm();
        $helper->title = $this->l('Actualizar configuración de actualización por CSV');
        $helper->submit_action = 'saveEditKtx_Multisincro';
        $helper->table = _DB_PREFIX_.$this->name.'_csv';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->toolbar_btn = array(
          'back' => array(
              'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
              'desc' => $this->l('Regresar a la lista')
              )
        );
        $helper->tpl_vars = array(
            'fields_value' => array(
                'id' => (int)Tools::getValue('id_csv'),
                'id_proveedor' => $katuxos->getId_Proveedor(),
                'ruta' => $katuxos->getRuta(),
                'referencia' => $katuxos->getReferencia(),
                'stock' => $katuxos->getStock(),
                'precio' => $katuxos->getPrecio(),
                'canon' => $katuxos->getCanon(),
                'costo_adicional' => $katuxos->getCosto_Adicional(),
                'margen' => $katuxos->getMargen(),
                'cron' => $katuxos->getCron(),
                'labelCron' => $katuxos->getCron()
            )
        );
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        return $helper->generateForm(array($this->fields_form));
    }

    public function generaCron($codigo, $id)
    {
        $cron = _PS_BASE_URL_.__PS_BASE_URI__.'modules/ktx_multisincro/ktx_cron_csv.php?token='.$codigo.'&id='.$id;
        return $cron;
    }
}
