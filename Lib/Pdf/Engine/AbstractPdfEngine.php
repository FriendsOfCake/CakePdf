<?php

Abstract class AbstractPdfEngine {
/**
 * Configurations
 *
 * @var array
 */
	protected $_config = array();

	abstract public function output($html);

/**
 * Set the config
 *
 * @param array $config
 * @return array Returns config array
 */
	public function config($config = null) {
		if (is_array($config)) {
			$this->_config = $config;
		}
		return $this->_config;
	}

}