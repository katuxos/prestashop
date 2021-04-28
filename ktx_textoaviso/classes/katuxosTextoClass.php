<?php
/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Description of katuxosTextoClass
 *
 * @author Katuxos
 */
class KatuxosTextoClass extends ObjectModel
{
    /**
     * @var integer
     */
    public $id;

    /**
     * @var integer
     */
    public $id_proveedor = 0;

    /**
     * @var string
     */
    public $textoAntes;

    /**
     * @var string
     */
    public $textoDespues;
    
    /**
     * @var float
     */
    public $disminucion = 0;

    public static $definition = array(
        'table' => 'ktx_textos',
        'primary' => 'id',
        'multilang' => false,
        'fields' => array(
            'id_proveedor' => array('type' => self::TYPE_INT),
            'textoantes' => array('type' => self::TYPE_STRING),
            'textodespues' => array('type' => self::TYPE_STRING),
            'disminucion' => array('type' => self::TYPE_FLOAT),
        )
    );

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id_proveedor
     *
     * @param int $id_proveedor
     *
     * @return ktx_textos
     */
    public function setId_Proveedor($id_proveedor)
    {
        $this->id_proveedor = $id_proveedor;

        return $this;
    }

    /**
     * Get id_proveedor
     *
     * @return int
     */
    public function getId_Proveedor()
    {
        return $this->id_proveedor;
    }

    /**
     * Get textoantes
     *
     * @return string
     */
    public function getTexto_Antes()
    {
        return $this->textoantes;
    }

    /**
     * Set textoantes
     *
     * @param string $textoAntes
     *
     * @return ktx_textos
     */
    public function setTexto_Antes($textoAntes)
    {
        $this->textoantes = $textoAntes;

        return $this;
    }

    /**
     * Get textodespues
     *
     * @return string
     */
    public function getTexto_Despues()
    {
        return $this->textodespues;
    }

    /**
     * Set textodespues
     *
     * @param string $textoDespues
     *
     * @return ktx_textos
     */
    public function setTexto_Despues($textoDespues)
    {
        $this->textodespues = $textoDespues;

        return $this;
    }    
    
    /**
     * Set disminucion
     *
     * @param float $disminucion
     *
     * @return ktx_textos
     */
    public function setDisminucion($disminucion)
    {
        $this->disminucion = $disminucion;

        return $this;
    }

    /**
     * Get disminucion
     *
     * @return float
     */
    public function getDisminucion()
    {
        return $this->disminucion;
    }
}
