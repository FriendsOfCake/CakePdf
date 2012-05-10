<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('CakePdf', 'CakePdf.Pdf');
App::uses('View', 'View');

/**
 * @package       Cake.View
 */
class PdfView extends View {

/**
 * The subdirectory.  PDF views are always in pdf.
 *
 * @var string
 */
	public $subDir = 'pdf';

/**
 * CakePdf Instance
 *
 * @var object
 */
	protected $_renderer = null;

/**
 * Constructor
 *
 * @param Controller $controller
 * @return void
 */
	public function __construct(Controller $controller = null) {
		parent::__construct($controller);

		$config = array();
		if (!empty($controller->pdfConfig)) {
			$config = $controller->pdfConfig;
		}
		$this->renderer($config);
		if (isset($controller->response) && $controller->response instanceof CakeResponse) {
			$controller->response->type('pdf');
		}
	}

/**
 * Return CakePdf instance, optionally set engine to be used
 * @param array $config Array of pdf configs. When empty CakePdf instance will be returned.
 * @return CakePdf
 */
	public function renderer($config = null) {
		if ($config !== null) {
			$this->_renderer = new CakePdf($config);
		}
		return $this->_renderer;
	}

/**
 * Render a Pdf view.
 * @param string $view The view being rendered.
 * @param string $layout The layout being rendered.
 * @return string The rendered view.
 */
	public function render($view = null, $layout = null) {
		$content = parent::render($view, $layout);
		return $this->renderer()->output($content);
	}

}
