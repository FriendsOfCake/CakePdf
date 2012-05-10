<?php

Abstract class AbstractPdfEngine {
/**
 * Configurations
 *
 * @var array
 */
	protected $_config = array();

	abstract public function output(CakePdf $pdf);

/**
 * Set the config
 *
 * @param mixed $config Null, string or array. Pass array of configs to set.
 * @return mixed Returns Returns config value if $config is string, else returns config array.
 */
	public function config($config = null) {
		if (is_array($config)) {
			$this->_config = $config;
		} elseif (is_string($config)) {
			if (!empty($this->_config[$config])) {
				return $this->_config[$config];
			}
			return false;
		}
		return $this->_config;
	}

}