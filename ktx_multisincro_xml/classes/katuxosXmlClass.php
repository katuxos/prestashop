<?php
/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Description of katuxosXmlClass
 *
 * @author Katuxos
 */
class KatuxosXmlClass extends ObjectModel
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
    public $ruta;

    /**
     * @var string
     */
    public $referencia;
    
    /**
     * @var string
     */
    public $stock;

    /**
     * @var string
     */
    public $precio;

    /**
     * @var string
     */
    public $canon;
    
    /**
     * @var string
     */
    public $otros;    
    
    /**
     * @var string
     */
    public $cron;
    
    /**
     * @var float
     */
    public $margen = 0;
    
    /**
     * @var float
     */
    public $costo_adicional = 0;

    /**
     * @var string
     */
    public $token;
    
    /**
     * @var string
     */
    public $xml;

    /**
     * @var string
     */
    public $usuario;

    /**
     * @var string
     */
    public $password;    

    public static $definition = array(
        'table' => 'ktx_multisincro_xml',
        'primary' => 'id',
        'multilang' => false,
        'fields' => array(
            'id_proveedor' => array('type' => self::TYPE_INT),
            'ruta' => array('type' => self::TYPE_STRING),
            'referencia' => array('type' => self::TYPE_STRING),
            'stock' => array('type' => self::TYPE_STRING),
            'precio' => array('type' => self::TYPE_STRING),
            'canon' => array('type' => self::TYPE_STRING),
            'otros' => array('type' => self::TYPE_STRING),
            'cron' => array('type' => self::TYPE_STRING),
            'margen' => array('type' => self::TYPE_FLOAT),
            'costo_adicional' => array('type' => self::TYPE_FLOAT),
            'token' => array('type' => self::TYPE_STRING),
            'xml' => array('type' => self::TYPE_STRING),
            'usuario' => array('type' => self::TYPE_STRING),
            'password' => array('type' => self::TYPE_STRING),
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
     * @return ktx_multisincroXml
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
     * Get ruta
     *
     * @return string
     */
    public function getRuta()
    {
        return $this->ruta;
    }

    /**
     * Set ruta
     *
     * @param string $ruta
     *
     * @return ktx_multisincroXml
     */
    public function setRuta($ruta)
    {
        $this->ruta = $ruta;

        return $this;
    }

    /**
     * Set referencia
     *
     * @param int $referencia
     *
     * @return ktx_multisincroXml
     */
    public function setReferencia($referencia)
    {
        $this->referencia = $referencia;

        return $this;
    }

    /**
     * Get referencia
     *
     * @return int
     */
    public function getReferencia()
    {
        return $this->referencia;
    }

    /**
     * Set stock
     *
     * @param int $stock
     *
     * @return ktx_multisincroXml
     */
    public function setStock($stock)
    {
        $this->stock = $stock;

        return $this;
    }

    /**
     * Get stock
     *
     * @return int
     */
    public function getStock()
    {
        return $this->stock;
    }

    /**
     * Set precio
     *
     * @param string $precio
     *
     * @return ktx_multisincroXml
     */
    public function setPrecio($precio)
    {
        $this->precio = $precio;

        return $this;
    }

    /**
     * Get precio
     *
     * @return string
     */
    public function getPrecio()
    {
        return $this->precio;
    }

    /**
     * Set canon
     *
     * @param string $canon
     *
     * @return ktx_multisincroXml
     */
    public function setCanon($canon)
    {
        $this->canon = $canon;

        return $this;
    }

    /**
     * Get canon
     *
     * @return string
     */
    public function getCanon()
    {
        return $this->canon;
    }
    
    /**
     * Set otros
     *
     * @param string $otros
     *
     * @return ktx_multisincroXml
     */
    public function setOtros($otros)
    {
        $this->otros = $otros;

        return $this;
    }

    /**
     * Get otros
     *
     * @return string
     */
    public function getOtros()
    {
        return $this->otros;
    }    
    
    /**
     * Get cron
     *
     * @return string
     */
    public function getCron()
    {
        return $this->cron;
    }

    /**
     * Set cron
     *
     * @param string $cron
     *
     * @return ktx_multisincroXml
     */
    public function setCron($cron)
    {
        $this->cron = $cron;

        return $this;
    }

    /**
     * Set margen
     *
     * @param float $margen
     *
     * @return ktx_multisincroXml
     */
    public function setMargen($margen)
    {
        $this->margen = $margen;

        return $this;
    }

    /**
     * Get margen
     *
     * @return float
     */
    public function getMargen()
    {
        return $this->margen;
    }

    /**
     * Set costo_adicional
     *
     * @param float $costo_adicional
     *
     * @return ktx_multisincroXml
     */
    public function setCosto_Adicional($costo_adicional)
    {
        $this->costo_adicional = $costo_adicional;

        return $this;
    }

    /**
     * Get costo_adicional
     *
     * @return float
     */
    public function getCosto_Adicional()
    {
        return $this->costo_adicional;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set token
     *
     * @param string $token
     *
     * @return ktx_multisincroXml
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }
    
    /**
     * Set xml
     *
     * @param string $xml
     *
     * @return ktx_multisincroXml
     */
    public function setXml($xml)
    {
        $this->xml = $xml;

        return $this;
    }

    /**
     * Get xml
     *
     * @return string
     */
    public function getXml()
    {
        return $this->xml;
    }

    /**
     * Set usuario
     *
     * @param string $usuario
     *
     * @return ktx_multisincroXml
     */
    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;

        return $this;
    }

    /**
     * Get usuario
     *
     * @return string
     */
    public function getUsuario()
    {
        return $this->usuario;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return ktx_multisincroXml
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }    
}
