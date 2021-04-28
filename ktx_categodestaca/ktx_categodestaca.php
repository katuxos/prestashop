<?php
/** Copyright Katuxos 2019
 * 
 * Actualización de categoría de productos destacados.
 * Funciona con OWLPRODUCTFILTER
 * En owlproductfilter.php hay que cambiar:
 * - justo antes de las llamadas a la función getProductFeature, hay que cambiar:
 * - $id_cat = (int)Context::getContext()->shop->getCategory(); por
 * - $id_cat = (int)Configuration::get('KTX_CATEGORY_DESTACADOS');
 * 
 */

if (!defined('_PS_VERSION_')) {
    exit;
}
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Ktx_CategoDestaca extends Module
{

    public function __construct()
    {
        $this->name = 'ktx_categodestaca';
        $this->version = '1.0.0';
        $this->author = 'Katuxos';
        $this->bootstrap = true;
        $this->tab = 'product';
        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);
        $this->displayName = $this->l('Actualización de categoría para productos destacados');
        $this->description = $this->l('Funciona con owlproductfilter.');

        parent::__construct();
    }

    public function install()
    {
        if (!parent::install() || !Configuration::updateValue('KTX_CATEGORY_DESTACADOS', 1))
        {
            return false;
        }
        return true;
    }
    
    public function uninstall()
    {
        if (!parent::uninstall() || !Configuration::deletebyName('KTX_CATEGORY_DESTACADOS'))
        {
            return false;
        }
        return true;
    }

    public function getContent()
    {
        if (Tools::isSubmit('saveKtx_CategoriaDestacada')) {
            /* Salvar nueva categoría */
            $categoria = (int)Tools::getValue('categoria', 'Error');
            Configuration::updateValue('KTX_CATEGORY_DESTACADOS', $categoria);
            return $this->generaFormulario($categoria);
        } else {    /* Pantalla inicial, mostrar listado */
            $categoria = Configuration::get('KTX_CATEGORY_DESTACADOS');
            return $this->generaFormulario($categoria);
        }
    }

    public function generaFormulario($categoria)
    {

        $this->fields_form = array (
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Categoría para tomar productos destacados'),
                ),
                'input' => array(
                    'categoria' => array(
                        'type' => 'text',
                        'desc' => $this->l('ID de la Categoría de donde quieres tomar los destacados.'),
                        'label' => $this->l('Categoria:'),
                        'name' => 'categoria',
                        'class' => 'fixed-width-xs'
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Guardar'),
                )
            )
        );

        $helper = new HelperForm();
        $helper->title = $this->l('Configurar categoría para Productos Destacados');
        $helper->submit_action = 'saveKtx_CategoriaDestacada';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->tpl_vars = array(
            'fields_value' => array(
            'categoria' => $categoria
            )
        );
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        return $helper->generateForm(array($this->fields_form));
    }

}
