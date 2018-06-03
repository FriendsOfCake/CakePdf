<?php
namespace CakePdf\View;

use CakePdf\Pdf\CakePdf;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\View\View;

class PdfView extends View
{

    /**
     * The subdirectory.  PDF views are always in pdf.
     *
     * @var string|null
     */
    public $subDir = 'pdf';

    /**
     * The name of the layouts subfolder containing layouts for this View.
     *
     * @var string|null
     */
    public $layoutPath = 'pdf';

    /**
     * CakePdf Instance
     *
     * @var \CakePdf\Pdf\CakePdf|null
     */
    protected $_renderer = null;

    /**
     * List of pdf configs collected from the associated controller.
     *
     * @var array
     */
    public $pdfConfig = [];

    /**
     * Constructor
     *
     * @param \Cake\Http\ServerRequest $request Request instance.
     * @param \Cake\Http\Response $response Response instance.
     * @param \Cake\Event\EventManager $eventManager Event manager instance.
     * @param array $viewOptions View options. See View::$_passedVars for list of
     *   options which get set as class properties.
     *
     * @throws \Cake\Core\Exception\Exception
     */
    public function __construct(
        ServerRequest $request = null,
        Response $response = null,
        EventManager $eventManager = null,
        array $viewOptions = []
    ) {
        $this->_passedVars[] = 'pdfConfig';

        parent::__construct($request, $response, $eventManager, $viewOptions);

        $this->pdfConfig = array_merge(
            (array)Configure::read('CakePdf'),
            (array)$this->pdfConfig
        );

        $this->response = $this->response->withType('pdf');
        if (isset($viewOptions['templatePath']) && $viewOptions['templatePath'] == 'Error') {
            $this->subDir = null;
            $this->layoutPath = null;
            $this->response = $this->response->withType('html');

            return;
        }
        if (!$this->pdfConfig) {
            throw new Exception(__d('cakepdf', 'Controller attribute $pdfConfig is not correct or missing'));
        }
        $this->renderer($this->pdfConfig);
    }

    /**
     * Return CakePdf instance, optionally set engine to be used
     *
     * @param array $config Array of pdf configs. When empty CakePdf instance will be returned.
     * @return \CakePdf\Pdf\CakePdf
     */
    public function renderer($config = null)
    {
        if ($config !== null) {
            $this->_renderer = new CakePdf($config);
        }

        return $this->_renderer;
    }

    /**
     * Render a Pdf view.
     *
     * @param string $view The view being rendered.
     * @param string $layout The layout being rendered.
     * @return string The rendered view.
     */
    public function render($view = null, $layout = null)
    {
        $content = parent::render($view, $layout);

        if (version_compare(Configure::version(), '3.6.0', '<')) {
            $type = $this->response->type();
        } else {
            $type = $this->response->getType();
        }

        if ($type === 'text/html') {
            return $content;
        }
        if ($this->renderer() === null) {
            $this->response = $this->response->withType('html');

            return $content;
        }

        if (!empty($this->pdfConfig['filename']) || !empty($this->pdfConfig['download'])) {
            $this->response = $this->response->withDownload($this->getFilename());
        }

        $this->Blocks->set('content', $this->renderer()->output($content));

        return $this->Blocks->get('content');
    }

    /**
     * Get or build a filename for forced download
     *
     * @return string The filename
     */
    public function getFilename()
    {
        if (isset($this->pdfConfig['filename'])) {
            return $this->pdfConfig['filename'];
        }

        $id = current($this->request->getParam('pass'));

        return strtolower($this->getTemplatePath()) . $id . '.pdf';
    }
}
