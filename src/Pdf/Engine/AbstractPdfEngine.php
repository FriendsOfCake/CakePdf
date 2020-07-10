<?php
declare(strict_types=1);

namespace CakePdf\Pdf\Engine;

use Cake\Core\InstanceConfigTrait;
use CakePdf\Pdf\CakePdf;

abstract class AbstractPdfEngine
{
    use InstanceConfigTrait;

    /**
     * @var array
     */
    protected $_defaultConfig = [];

    /**
     * Instance of CakePdf class
     *
     * @var \CakePdf\Pdf\CakePdf
     */
    protected $_Pdf;

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
    abstract public function output(): string;
}
