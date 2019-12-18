<?php
declare(strict_types=1);

namespace CakePdf\Pdf\Crypto;

use CakePdf\Pdf\CakePdf;

abstract class AbstractPdfCrypto
{
    /**
     * Instance of CakePdf class
     *
     * @var \CakePdf\Pdf\CakePdf
     */
    protected $_Pdf;

    /**
     * Configurations
     *
     * @var array
     */
    protected $_config = [];

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
     * @param string $data raw pdf file
     * @return string
     */
    abstract public function encrypt(string $data): string;

    /**
     * Implement in subclass.
     *
     * @param string $permission permission to check
     * @return bool
     */
    abstract public function permissionImplemented(string $permission): bool;

    /**
     * Set the config
     *
     * @param null|string|array $config Null, string or array. Pass array of configs to set.
     * @return null|string|array Returns config value if $config is string, else returns config array.
     */
    public function config($config = null)
    {
        if (is_array($config)) {
            $this->_config = $config;
        } elseif (is_string($config)) {
            if (!empty($this->_config[$config])) {
                return $this->_config[$config];
            }

            return null;
        }

        return $this->_config;
    }
}
