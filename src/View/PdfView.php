<?php
declare(strict_types=1);

namespace CakePdf\View;

use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\View\View;
use CakePdf\Pdf\CakePdf;

class PdfView extends View
{
    /**
     * The subdirectory.  PDF views are always in pdf.
     *
     * @var string|null
     */
    protected $subDir = 'pdf';

    /**
     * The name of the layouts subfolder containing layouts for this View.
     *
     * @var string|null
     */
    protected $layoutPath = 'pdf';

    /**
     * CakePdf Instance
     *
     * @var \CakePdf\Pdf\CakePdf|null
     */
    protected $_renderer;

    /**
     * Default config options.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'pdfConfig' => [],
    ];

    /**
     * Constructor
     *
     * @param \Cake\Http\ServerRequest $request Request instance.
     * @param \Cake\Http\Response $response Response instance.
     * @param \Cake\Event\EventManager $eventManager Event manager instance.
     * @param array $viewOptions View options. See View::$_passedVars for list of
     *   options which get set as class properties.
     * @throws \Cake\Core\Exception\Exception
     */
    public function __construct(
        ?ServerRequest $request = null,
        ?Response $response = null,
        ?EventManager $eventManager = null,
        array $viewOptions = []
    ) {
        $this->setConfig('pdfConfig', (array)Configure::read('CakePdf'));

        parent::__construct($request, $response, $eventManager, $viewOptions);

        if (isset($viewOptions['templatePath']) && $viewOptions['templatePath'] === 'Error') {
            $this->subDir = '';
            $this->layoutPath = '';

            return;
        }

        $this->response = $this->response->withType('pdf');

        $pdfConfig = $this->getConfig('pdfConfig');
        if (empty($pdfConfig)) {
            throw new Exception('No PDF config set. Use ViewBuilder::setOption(\'pdfConfig\', $config) to do so.');
        }

        $this->renderer($pdfConfig);
    }

    /**
     * Return CakePdf instance, optionally set engine to be used
     *
     * @param array $config Array of pdf configs. When empty CakePdf instance will be returned.
     * @return \CakePdf\Pdf\CakePdf|null
     */
    public function renderer(?array $config = null): ?CakePdf
    {
        if ($config !== null) {
            $this->_renderer = new CakePdf($config);
        }

        return $this->_renderer;
    }

    /**
     * Render a Pdf view.
     *
     * @param string $template The view being rendered.
     * @param false|null|string $layout The layout being rendered.
     * @return string The rendered view.
     */
    public function render(?string $template = null, $layout = null): string
    {
        $content = parent::render($template, $layout);

        $type = $this->response->getType();
        if ($type === 'text/html') {
            return $content;
        }

        $renderer = $this->renderer();

        if ($renderer === null) {
            $this->response = $this->response->withType('html');

            return $content;
        }

        if ($this->getConfig('pdfConfig.filename') || $this->getConfig('pdfConfig.download')) {
            $this->response = $this->response->withDownload($this->getFilename());
        }

        $this->Blocks->set('content', $renderer->output($content));

        return $this->Blocks->get('content');
    }

    /**
     * Get or build a filename for forced download
     *
     * @return string The filename
     */
    public function getFilename(): string
    {
        $filename = $this->getConfig('pdfConfig.filename');
        if ($filename) {
            return $filename;
        }

        $id = current($this->request->getParam('pass'));

        return strtolower($this->getTemplatePath()) . $id . '.pdf';
    }
}
