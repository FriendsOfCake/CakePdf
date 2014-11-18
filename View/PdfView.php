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
 * List of pdf configs collected from the associated controller.
 *
 * @var array
 */
	public $pdfConfig = array();

/**
 * Constructor
 *
 * @param Controller $controller
 * @return void
 */
	public function __construct(Controller $Controller = null) {
		$this->_passedVars[] = 'pdfConfig';
		parent::__construct($Controller);
		$this->pdfConfig = array_merge(
			(array)Configure::read('Pdf'),//BC line, remove later @todo
			(array)Configure::read('CakePdf'),
			(array)$this->pdfConfig
		);

		$this->response->type('pdf');
		if ($Controller instanceof CakeErrorController) {
			$this->subDir = null;
			return $this->response->type('html');
		}
		if (!$this->pdfConfig) {
			throw new CakeException(__d('cakepdf', 'Controller attribute $pdfConfig is not correct or missing'));
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

		// reset config to CakePdf (updates with anythin set from View)
		$this->renderer()->config($this->pdfConfig);

		// force download
		if (isset($this->pdfConfig['download']) && $this->pdfConfig['download'] === true) {
			$this->response->download($this->getFilename());
		}

		// set content
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

/**
 * Set footer
 *
 * @link http://wkhtmltopdf.org/usage/wkhtmltopdf.txt
 *
 * Headers and footers can be added to the document by the --header-* and --footer*
 * arguments respectfully.  In header and footer text string supplied to e.g.
 * --header-left, the following variables will be substituted.
 *
 * [page]       Replaced by the number of the pages currently being printed
 * [frompage]   Replaced by the number of the first page to be printed
 * [topage]     Replaced by the number of the last page to be printed
 * [webpage]    Replaced by the URL of the page being printed
 * [section]    Replaced by the name of the current section
 * [subsection] Replaced by the name of the current subsection
 * [date]       Replaced by the current date in system local format
 * [time]       Replaced by the current time in system local format
 * [title]      Replaced by the title of the of the current page object
 * [doctitle]   Replaced by the title of the output document
 * [sitepage]   Replaced by the number of the page in the current site being converted
 * [sitepages]  Replaced by the number of pages in the current site being converted
 *
 * As an example specifying --header-right "Page [page] of [toPage]", will result
 * in the text "Page x of y" where x is the number of the current page and y is the
 * number of the last page, to appear in the upper left corner in the document.
 *
 * Headers and footers can also be supplied with HTML documents. As an example one
 * could specify --header-html header.html, and use the following content in
 *
 * @param mixed $key
 *               1. string $key [center, left, right, html, font-name, font-size, ...]
 *               2. array $footer eg: ['center' => <content>, 'left' => <content>, ...]
 * @param string $content
 * @return void
 */
	public function pdfFooter($key='center', $content='') {
		if (is_array($key)) {
			foreach ($key as $_key => $content) {
				$this->pdfOptions(array("footer-{$_key}" => $content));
			}
			return;
		}
		$this->pdfOptions(array("footer-{$_key}" => $content));
	}

/**
 * Set header
 *
 * @link http://wkhtmltopdf.org/usage/wkhtmltopdf.txt
 *
 * Headers and footers can be added to the document by the --header-* and --footer*
 * arguments respectfully.  In header and footer text string supplied to e.g.
 * --header-left, the following variables will be substituted.
 *
 * [page]       Replaced by the number of the pages currently being printed
 * [frompage]   Replaced by the number of the first page to be printed
 * [topage]     Replaced by the number of the last page to be printed
 * [webpage]    Replaced by the URL of the page being printed
 * [section]    Replaced by the name of the current section
 * [subsection] Replaced by the name of the current subsection
 * [date]       Replaced by the current date in system local format
 * [time]       Replaced by the current time in system local format
 * [title]      Replaced by the title of the of the current page object
 * [doctitle]   Replaced by the title of the output document
 * [sitepage]   Replaced by the number of the page in the current site being converted
 * [sitepages]  Replaced by the number of pages in the current site being converted
 *
 * As an example specifying --header-right "Page [page] of [toPage]", will result
 * in the text "Page x of y" where x is the number of the current page and y is the
 * number of the last page, to appear in the upper left corner in the document.
 *
 * Headers and footers can also be supplied with HTML documents. As an example one
 * could specify --header-html header.html, and use the following content in
 *
 * @param mixed $key
 *               1. string $key [center, left, right, html, font-name, font-size, ...]
 *               2. array $header eg: ['center' => <content>, 'left' => <content>, ...]
 * @param string $content
 * @return void
 */
	public function pdfHeader($key='center', $content='') {
		if (is_array($key)) {
			foreach ($key as $_key => $content) {
				$this->pdfOptions(array("header-{$_key}" => $content));
			}
			return;
		}
		$this->pdfOptions(array("header-{$_key}" => $content));
	}

/**
 * Set options as needed to render header/footer/etc. or change Config
 *
 * Allows you to alter PDF config from a View or Layout
 *
 * @param array $options
 * @return array $options
 */
	public function pdfOptions($options) {
		if (is_array($options)) {
			$this->pdfConfig['options'] = array_merge($this->pdfConfig['options'], $options);
		}
		return $this->pdfConfig['options'];
	}

/**
 * Set all PDF config as needed
 *
 * Allows you to alter PDF config from a View or Layout
 *
 * @param array $config
 * @return array $config
 */
	public function pdfConfig($config) {
		if (is_array($config)) {
			$this->pdfConfig = Hash::merge($this->pdfConfig, $config);
		}
		return $this->pdfConfig;
	}

}
