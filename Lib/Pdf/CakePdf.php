<?php
App::uses('File', 'Utility');
App::uses('View', 'View');

class CakePdf {

/**
 * Layout for the View
 *
 * @var string
 */
	protected $_layout = 'default';

/**
 * Template for the view
 *
 * @var string
 */
	protected $_template = null;

/**
 * View for render
 *
 * @var string
 */
	protected $_viewRender = 'View';

/**
 * Vars to sent to render
 *
 * @var array
 */
	protected $_viewVars = array();

/**
 * Theme for the View
 *
 * @var array
 */
	protected $_theme = null;

/**
 * Helpers to be used in the render
 *
 * @var array
 */
	protected $_helpers = array('Html');

/**
 * Instance of PdfEngine class
 *
 * @var AbstractPdfEngine
 */
	protected $_engineClass = null;

/**
 * Html to be rendered
 *
 * @var string
 */
	protected $_html = null;

/**
 * Page size of the pdf
 *
 * @var string
 */
	protected $_pageSize = 'A4';

/**
 * Orientation of the pdf
 *
 * @var string
 */
	protected $_orientation = 'portrait';

/**
 * Constructor
 *
 * @param array $config Pdf configs to use
 */
	public function __construct($config = array()) {
		$config = array_merge(array('engine' => Configure::read('Pdf.engine')), $config);
		$this->engine($config['engine'])->config($config);
	}

/**
 * Create pdf content from html. Can be used to write to file or with PdfView to display
 *
 * @param mixed $html Html content to render. If left empty, the template will be rendered with viewVars and layout that have been set.
 * @return string
 */
	public function output($html = null) {
		if (!isset($this->_engineClass)) {
			throw new Exception(__d('cakepdf', 'No Pdf engine is set!'));
		}

		if ($html) {
			$this->html($html);
		} else {
			$this->html($this->_render());
		}

		return $this->engine()->output($this);
	}

/**
 * Get/Set Html.
 *
 * @param null|string $html
 * @return mixed
 */
	public function html($html = null) {
		if ($html === null) {
			return $this->_html;
		}
		$this->_html = $html;
		return $this;
	}

/**
 * Get/Set Page size.
 *
 * @param null|string $pageSize
 * @return mixed
 */
	public function pageSize($pageSize = null) {
		if ($pageSize === null) {
			return $this->_pageSize;
		}
		$this->_pageSize = $pageSize;
		return $this;
	}

/**
 * Get/Set Orientation.
 *
 * @param null|string $orientation
 * @return mixed
 */
	public function orientation($orientation = null) {
		if ($orientation === null) {
			return $this->_orientation;
		}
		$this->_orientation = $orientation;
		return $this;
	}

/**
 * Writes output to file
 *
 * @param srting $destination Absolute file path to write to
 * @param boolean $create Create file if it does not exist (if true)
 * @return boolean
 */
	public function write($destination, $create = true, $html = null) {
		$ouput = $this->output($html);
		$File = new File($destination, $create);
		return $File->write($output) && $File->close();
	}

/**
 * Load PdfEngine
 *
 * @param string $name Classname of pdf engine without `Engine` suffix. For example `CakePdf.DomPdf`
 * @return object PdfEngine
 */
	public function engine($name = null) {
		if (!$name) {
			if ($this->_engineClass) {
				return $this->_engineClass;
			}
			throw new Exception(__d('cake_pdf', 'Engine is not loaded'));
		}

		list($pluginDot, $engineClassName) = pluginSplit($name, true);
		$engineClassName = $engineClassName . 'Engine';
		App::uses($engineClassName, $pluginDot . 'Pdf/Engine');
		if (!class_exists($engineClassName)) {
			throw new Exception(__d('cake_pdf', 'Pdf engine "%s" not found', $name));
		}
		if (!is_subclass_of($engineClassName, 'AbstractPdfEngine')) {
			throw new Exception(__d('cake_pdf', 'Pdf engines must extend "AbstractPdfEngine"'));
		}
		return $this->_engineClass = new $engineClassName();
	}

/**
 * Template and layout
 *
 * @param mixed $template Template name or null to not use
 * @param mixed $layout Layout name or null to not use
 * @return mixed
 */
	public function template($template = false, $layout = null) {
		if ($template === false) {
			return array(
				'template' => $this->_template,
				'layout' => $this->_layout
			);
		}
		$this->_template = $template;
		if ($layout !== null) {
			$this->_layout = $layout;
		}

		return $this;
	}

/**
 * View class for render
 *
 * @param string $viewClass
 * @return mixed
 */
	public function viewRender($viewClass = null) {
		if ($viewClass === null) {
			return $this->_viewRender;
		}
		$this->_viewRender = $viewClass;
		return $this;
	}

/**
 * Variables to be set on render
 *
 * @param array $viewVars
 * @return mixed
 */
	public function viewVars($viewVars = null) {
		if ($viewVars === null) {
			return $this->_viewVars;
		}
		$this->_viewVars = array_merge($this->_viewVars, (array)$viewVars);
		return $this;
	}

/**
 * Theme to use when rendering
 *
 * @param string $theme
 * @return mixed
 */
	public function theme($theme = null) {
		if ($theme === null) {
			return $this->_theme;
		}
		$this->_theme = $theme;
		return $this;
	}

/**
 * Helpers to be used in render
 *
 * @param array $helpers
 * @return mixed
 */
	public function helpers($helpers = null) {
		if ($helpers === null) {
			return $this->_helpers;
		}
		$this->_helpers = (array)$helpers;
		return $this;
	}


/**
 * Build and set all the view properties needed to render the layout and template.
 *
 * @return array The rendered template wrapped in layout.
 */
	protected function _render() {
		$viewClass = $this->viewRender();
		if ($viewClass !== 'View') {
			list($pluginDot, $viewClass) = pluginSplit($viewClass, true);
			$viewClass .= 'View';
			App::uses($viewClass, $pluginDot . 'View');
		}
		$Controller = new Controller(new CakeRequest());
		$View = new $viewClass($Controller);
		$View->viewVars = $this->_viewVars;
		$View->helpers = $this->_helpers;
		$View->theme = $this->_theme;
		$View->layoutPath = 'pdf';
		$View->viewPath = 'Pdf';
		$View->view = $this->_template;
		$View->layout = $this->_layout;
		return $View->render();
	}

}