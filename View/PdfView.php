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
 * Pdf engine name
 *
 * @var string
 */
	protected $_engine = null;

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

		if (isset($controller->response) && $controller->response instanceof CakeResponse) {
			$controller->response->type('pdf');
		}
		$engine = Configure::read('Pdf.engine');
		if (!empty($controller->pdfEngine)) {
			$engine = $controller->pdfEngine;
		}
		if ($engine) {
			$this->_engine = $engine;
		}
		$this->renderer($this->_engine);
	}

/**
 * Return CakePdf instance, optionally set engine to be used
 * @param string $engine
 * @return CakePdf
 */
	public function renderer($engine = null) {
		if ($engine) {
			$this->_renderer = new CakePdf($engine);
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
		return $this->renderer()->render($content);
	}

}
