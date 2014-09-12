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
namespace CakePdf\View;

use CakePdf\Pdf\CakePdf;
use Cake\Core\Configure;
use Cake\View\View;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Event\EventManager;
use Cake\Core\Exception\Exception;

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
 * The name of the layouts subfolder containing layouts for this View.
 *
 * @var string
 */
	public $layoutPath = 'pdf';

/**
 * CakePdf Instance
 *
 * @var object
 */
	protected $_renderer = null;

/**
 * List of pdf configs collected from the associated controller.
 *
 * @var array
 */
	public $pdfConfig = array();

/**
 * Constructor
 *
 * @return void
 */
	public function __construct(Request $request = null, Response $response = null,
		EventManager $eventManager = null, array $viewOptions = []) {
		$this->_passedVars[] = 'pdfConfig';
		parent::__construct($request, $response, $eventManager, $viewOptions);
		$this->pdfConfig = array_merge(
			(array)Configure::read('CakePdf'),
			(array)$this->pdfConfig
		);

		$response->type('pdf');
		if ($viewOptions['name'] == 'Error') {
			$this->subDir = null;
			$this->layoutPath = null;
			$response->type('html');
			return;
		}
		if (!$this->pdfConfig) {
			throw new Exception(__d('cakepdf', 'Controller attribute $pdfConfig is not correct or missing'));
		}
		$this->renderer($this->pdfConfig);
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
		if ($this->response->type() == 'text/html') {
			return $content;
		}
		if ($this->renderer() == null) {
			$this->response->type('html');

			return $content;
		}

		if (isset($this->pdfConfig['download']) && $this->pdfConfig['download'] === true) {
			$this->response->download($this->getFilename());
		}

		$this->Blocks->set('content', $this->renderer()->output($content));
		return $this->Blocks->get('content');
	}

/**
 * Get or build a filename for forced download
 * @return string The filename
 */
	public function getFilename() {
		if (isset($this->pdfConfig['filename'])) {
			return $this->pdfConfig['filename'];
		}
		$id = current($this->request->params['pass']);
		return strtolower($this->viewPath) . $id . '.pdf';
	}
}
