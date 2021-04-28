<?php

/* 
 * Copyright Katuxos 2019
 * Muestra un texto personalizado para llamar la atención de tus clientes
 * para los productos de un Proveedor determinado.
 * 
 */

include_once _PS_MODULE_DIR_.'ktx_textoaviso/classes/katuxosTextoClass.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ktx_TextoAviso extends Module {
    
    public function __construct()
    {
        $this->name = 'ktx_textoaviso';
        $this->version = '1.0.0';
        $this->author = 'Katuxos';
        $this->bootstrap = true;
        $this->tab = 'sales';
        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);
        $this->displayName = $this->l('Muestra un texto personalizado para llamar la atención de tus clientes');
        $this->description = $this->l('para los productos de un Proveedor determinado.');
        
        parent::__construct();

    }    
    
    public function install() {
        if (!parent::install() || !(Db::getInstance()->Execute('CREATE TABLE IF NOT EXISTS `'. _DB_PREFIX_.
                'ktx_textos` (`id` INT NOT NULL AUTO_INCREMENT, `id_proveedor` INT(11) DEFAULT 0, '
                . '`textoantes` VARCHAR(255) DEFAULT NULL, `disminucion` DECIMAL(20,2) DEFAULT 0, '
                . '`textodespues` VARCHAR(255) DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE='
                . _MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;')) || !$this->registerHook('displayReassurance'))
        {
            return false;
        }
        return true;        
    }
    
    public function uninstall() {
        if (!parent::uninstall() || !(Db::getInstance()->Execute('DROP TABLE `'. _DB_PREFIX_.
                'ktx_textos`;')) || !$this->unregisterHook('displayReassurance'))
        {
            return false;
        }
        return true;        
    }
    
    public function hookDisplayReassurance($params) {
        $product = new Product((int)Tools::getValue('id_product'));
        $resultados = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'ktx_textos` '
                . ' WHERE id_proveedor = '.$product->id_supplier.' ORDER BY id DESC');
        if (count($resultados) == 0) {
            $resultados = null;
        }
        if ($resultados){
            $textoAntes = $resultados[0]['textoantes'];
            $textoDespues = $resultados[0]['textodespues'];  
            $disminucion = $resultados[0]['disminucion'];
            $precio = $product->getPrice();
            $textoSmarty = $textoAntes.' '.number_format(($precio-$disminucion), 2, ",", ".").$textoDespues;
        } else {
            $textoSmarty = '';
        }
        $this->context->smarty->assign('texto', $textoSmarty);
        return $this->display(__FILE__, 'displayReassurance.tpl');
    }
    
    public function getContent() {
        if (Tools::isSubmit('addktx_textoaviso')) {  /* Crear nuevo texto para un proveedor */
            return $this->generaFormulario();
        } elseif (Tools::isSubmit('saveKtx_Texto')) {
            /* Salvar nuevo texto para un proveedor */
            $katuxos = new katuxosTextoClass();
            $katuxos->setId_Proveedor((int)Tools::getValue('id_proveedor', 'Error'));
            $katuxos->setTexto_Antes((string)Tools::getValue('textoAntes', 'Error'));
            $katuxos->setTexto_Despues((string)Tools::getValue('textoDespues', 'Error'));
            $katuxos->setDisminucion((float)Tools::getValue('disminucion', '0'));
            if ($katuxos->save()) {
                $this->_confirmations[] = $this->l('Guardado');
            } else {
                $this->_errors[] = $this->l('Error al guardar.  Revisa los datos');
            }
            Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='
                    .Tools::getAdminTokenLite('AdminModules'));
        } elseif (Tools::isSubmit('viewktx_textoaviso')) {  /* Hacer cambios a un registro existente */
            return $this->formularioEdicion();
        } elseif (Tools::isSubmit('saveEditKtx_Texto')) {  /* Salvar cambios a un registro existente */
            $katuxos = new katuxosTextoClass((int)Tools::getValue('id'));
            $katuxos->setId_Proveedor((int)Tools::getValue('id_proveedor', 'Error'));
            $katuxos->setTexto_Antes((string)Tools::getValue('textoAntes', 'Error'));
            $katuxos->setTexto_Despues((string)Tools::getValue('textoDespues', 'Error'));
            $katuxos->setDisminucion((float)Tools::getValue('disminucion', '0'));

            if ($katuxos->save()) {
                $this->_confirmations[] = $this->l('Guardado');
            } else {
                $this->_errors[] = $this->l('Error al guardar.  Revisa los datos');
            }

            Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='
                    .Tools::getAdminTokenLite('AdminModules'));
        } elseif (Tools::isSubmit('deletektx_textoaviso')) {
            /* Eliminar texto de aviso para un proveedor */
            $katuxos = new katuxosTextoClass((int)Tools::getValue('id_texto'));
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
        $resultados = Db::getInstance()->ExecuteS('SELECT kt.id as id_texto, sp.name as nombre_proveedor, '
                . 'kt.disminucion as disminucion FROM `'._DB_PREFIX_.'ktx_textos` kt JOIN `'._DB_PREFIX_.
                'supplier` sp on kt.id_proveedor = sp.id_supplier ORDER BY kt.id DESC');
        if (count($resultados) == 0) {
            $resultados = null;
        }

        $this->fields_list = array(
            'id_texto' => array(
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
            'disminucion' => array (
                    'title' => $this->l('Disminución'),
                    'type' => 'text',
                    'orderby' => true,
                    'search' => true,
            )
        );

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        $helper->actions = array('view', 'delete');
        $helper->identifier = 'id_texto';
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->toolbar_btn ['new'] = array(
            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&add'.$this->name.'&token='.
                Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Añadir nuevo')
        );
        $helper->listTotal = count($resultados);
        $helper->module = $this;
        $helper->title = $this->l('Proveedores con Texto Llamativo de Aviso');
        $helper->_pagination = array(10, 20, 50, 100, 200);
        $helper->table = _DB_PREFIX_.'ktx_textos';
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
                    'title' => $this->l('Nuevo proveedor con texto de aviso'),
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
                    'textoAntes' => array(
                        'type' => 'text',
                        'label' => $this->l('Texto Antes del Precio:'),
                        'desc' => $this->l('Texto que irá antes del precio especial.'),
                        'name' => 'textoAntes',
                        'required' => true,
                    ),
                    'textoDespues' => array(
                        'type' => 'text',
                        'label' => $this->l('Texto Después del Precio:'),
                        'desc' => $this->l('Texto que irá después del precio especial.'),
                        'name' => 'textoDespues',
                        'required' => true,
                    ),
                    'disminucion' => array(
                        'type' => 'text',
                        'label' => $this->l('Disminución a aplicar al precio:'),
                        'desc' => $this->l('Disminución que se aplicará el precio (en euros).'),
                        'name' => 'disminucion',
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Guardar'),
                )
            )
        );

        $helper = new HelperForm();
        $helper->title = $this->l('Configurar nuevo texto de aviso');
        $helper->submit_action = 'saveKtx_Texto';
        $helper->table = _DB_PREFIX_.'ktx_textos';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->tpl_vars = array(
            'fields_value' => array(
            'id_proveedor' => '',
            'textoAntes' => '',
            'textoDespues' => '',
            'disminucion' => '0'
            )
        );
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        return $helper->generateForm(array($this->fields_form));
    }

    public function formularioEdicion()
    {
        if ((int)Tools::getValue('id_texto')) {
            $katuxos = new katuxosTextoClass((int)Tools::getValue('id_texto'));
        }

        $proveedores = Supplier::getSuppliers();
        if (empty($proveedores)) {
            $proveedores = null;
        }

        $this->fields_form = array (
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Nuevo texto de aviso'),
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
                    'textoAntes' => array(
                        'type' => 'text',
                        'label' => $this->l('Texto antes del precio especial:'),
                        'desc' => $this->l('Texto que irá antes del texto especial'),
                        'name' => 'textoAntes',
                    ),
                    'textoDespues' => array(
                        'type' => 'text',
                        'label' => $this->l('Texto después del precio especial:'),
                        'desc' => $this->l('Texto que irá después del texto especial.'),
                        'name' => 'textoDespues',
                    ),
                    'disminucion' => array(
                        'type' => 'text',
                        'label' => $this->l('Disminución a aplicar al precio:'),
                        'desc' => $this->l('Disminución que se aplicará el precio (en euros).'),
                        'name' => 'disminucion',
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Guardar Cambios'),
                )
            )
        );

        $helper = new HelperForm();
        $helper->title = $this->l('Actualizar texto');
        $helper->submit_action = 'saveEditKtx_Texto';
        $helper->table = _DB_PREFIX_.'ktx_textos';
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
                'id' => (int)Tools::getValue('id_texto'),
                'id_proveedor' => $katuxos->getId_Proveedor(),
                'textoAntes' => $katuxos->getTexto_Antes(),
                'textoDespues' => $katuxos->getTexto_Despues(),
                'disminucion' => $katuxos->getDisminucion()
            )
        );
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        return $helper->generateForm(array($this->fields_form));
    }
}


