<?php
namespace CakePdf\Pdf\Engine;

use CakePdf\Pdf\CakePdf;
use Cake\Core\InstanceConfigTrait;

abstract class AbstractPdfEngine
{

    use InstanceConfigTrait;

    protected $_defaultConfig = [];

    /**
     * Instance of CakePdf class
     *
     * @var \CakePdf\Pdf\CakePdf
     */
    protected $_Pdf = null;

    /**
     * Constructor
     *
     * @param \CakePdf\Pdf\CakePdf $Pdf CakePdf instance
     */
    public function __construct(CakePdf $Pdf)
    {
        $this->_Pdf = $Pdf;
    }

    /**
     * Implement in subclass to return raw pdf data.
     *
     * @return string
     */
    abstract public function output();
}
