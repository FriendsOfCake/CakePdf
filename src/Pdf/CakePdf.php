<?php
declare(strict_types=1);

namespace CakePdf\Pdf;

use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Http\ServerRequestFactory;
use Cake\Routing\Router;
use CakePdf\Pdf\Crypto\AbstractPdfCrypto;
use CakePdf\Pdf\Engine\AbstractPdfEngine;
use SplFileInfo;

class CakePdf
{
    /**
     * Layout for the View
     *
     * @var string
     */
    protected $_layout = 'default';

    /**
     * Path to the layout - defaults to 'pdf'
     *
     * @var string
     */
    protected $_layoutPath = 'pdf';

    /**
     * Template for the view
     *
     * @var string|null
     */
    protected $_template;

    /**
     * Path to the template - defaults to 'pdf'
     *
     * @var string
     */
    protected $_templatePath = 'pdf';

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
    protected $_viewVars = [];

    /**
     * Theme for the View
     *
     * @var string|null
     */
    protected $_theme = null;

    /**
     * Helpers to be used in the render
     *
     * @var array
     */
    protected $_helpers = ['Html'];

    /**
     * Instance of PdfEngine class
     *
     * @var \CakePdf\Pdf\Engine\AbstractPdfEngine
     */
    protected $_engineClass;

    /**
     * Instance of PdfCrypto class
     *
     * @var \CakePdf\Pdf\Crypto\AbstractPdfCrypto|null
     */
    protected $_cryptoClass;

    /**
     * Html to be rendered
     *
     * @var string
     */
    protected $_html;

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
     * Encoding
     *
     * @var string
     */
    protected $_encoding = 'UTF-8';

    /**
     * Footer HTML
     *
     * @var array
     */
    protected $_footer = ['left' => null, 'center' => null, 'right' => null];

    /**
     * Header HTML
     *
     * @var array
     */
    protected $_header = ['left' => null, 'center' => null, 'right' => null];

    /**
     * Bottom margin in mm
     *
     * @var string|int|null
     */
    protected $_marginBottom = null;

    /**
     * Left margin in mm
     *
     * @var string|int|null
     */
    protected $_marginLeft = null;

    /**
     * Right margin in mm
     *
     * @var string|int|null
     */
    protected $_marginRight = null;

    /**
     * Top margin in mm
     *
     * @var string|int|null
     */
    protected $_marginTop = null;

    /**
     * Title of the document
     *
     * @var string|null
     */
    protected $_title;

    /**
     * Javascript delay before rendering document in milliseconds
     *
     * @var int|null
     */
    protected $_delay;

    /**
     * Window status required before rendering document
     *
     * @var string|null
     */
    protected $_windowStatus;

    /**
     * Flag that tells if we need to pass it through crypto
     *
     * @var bool
     */
    protected $_protect = false;

    /**
     * User password, used with crypto
     *
     * @var string|null
     */
    protected $_userPassword;

    /**
     * Owner password, used with crypto
     *
     * @var string|null
     */
    protected $_ownerPassword;

    /**
     * Cache config name, if set to false cache is disabled
     *
     * @var string|false
     */
    protected $_cache = false;

    /**
     * Permissions that are allowed, used with crypto
     *
     * false: none
     * true: all
     * array: List of permissions that are allowed
     *
     * @var mixed
     */
    protected $_allow = false;

    /**
     * Available permissions
     *
     * @var array
     */
    protected $_availablePermissions = [
        'print',
        'degraded_print',
        'modify',
        'assembly',
        'copy_contents',
        'screen_readers',
        'annotate',
        'fill_in',
    ];

    /**
     * Constructor
     *
     * @param array $config Pdf configs to use
     */
    public function __construct(array $config = [])
    {
        $config = array_merge(
            (array)Configure::read('CakePdf'),
            $config
        );

        if (!empty($config['engine'])) {
            $this->engine($config['engine']);
        }
        if (!empty($config['crypto'])) {
            $this->crypto($config['crypto']);
        }

        $options = [
            'pageSize',
            'orientation',
            'margin',
            'title',
            'encoding',
            'protect',
            'userPassword',
            'ownerPassword',
            'permissions',
            'cache',
            'delay',
            'windowStatus',
        ];
        foreach ($options as $option) {
            if (isset($config[$option])) {
                $this->{$option}($config[$option]);
            }
        }
    }

    /**
     * Create pdf content from html. Can be used to write to file or with PdfView to display
     *
     * @param null|string $html Html content to render. If omitted, the template will be rendered with viewVars and layout that have been set.
     * @throws \Cake\Core\Exception\Exception
     * @return string
     */
    public function output(?string $html = null): string
    {
        $Engine = $this->engine();
        if ($Engine === null) {
            throw new Exception('Engine is not loaded');
        }

        if ($html === null) {
            $html = $this->_render();
        }
        $this->html($html);

        $cacheKey = '';
        $cache = $this->cache();
        if ($cache) {
            $cacheKey = md5(serialize($this));
            $cached = Cache::read($cacheKey, $cache);
            if ($cached) {
                return $cached;
            }
        }

        $output = $Engine->output();

        if ($this->protect()) {
            $output = $this->crypto()->encrypt($output);
        }

        if ($cache) {
            Cache::write($cacheKey, $output, $cache);
        }

        return $output;
    }

    /**
     * Get/Set Html.
     *
     * @param null|string $html Html to set
     * @return mixed
     */
    public function html(?string $html = null)
    {
        if ($html === null) {
            return $this->_html;
        }
        $this->_html = $html;

        return $this;
    }

    /**
     * Writes output to file
     *
     * @param string $destination Absolute file path to write to
     * @param bool $create Create file if it does not exist (if true)
     * @param string|null $html Html to write
     * @return bool
     */
    public function write(string $destination, bool $create = true, ?string $html = null): bool
    {
        $output = $this->output($html);

        $fileInfo = new SplFileInfo($destination);

        if (!$create || $fileInfo->isFile()) {
            return (bool)file_put_contents($destination, $output);
        }

        if (!$fileInfo->isFile() && !$fileInfo->getPathInfo()->getRealPath()) {
            mkdir($fileInfo->getPath(), 0777, true);
        }

        return (bool)file_put_contents($destination, $output);
    }

    /**
     * Load PdfEngine
     *
     * @param string|array $name Classname of pdf engine without `Engine` suffix. For example `CakePdf.DomPdf`
     * @throws \Cake\Core\Exception\Exception
     * @return \CakePdf\Pdf\Engine\AbstractPdfEngine|null
     */
    public function engine($name = null): ?AbstractPdfEngine
    {
        if ($name === null) {
            return $this->_engineClass;
        }
        $config = [];
        if (is_array($name)) {
            $config = $name;
            $name = $name['className'];
        }

        $engineClassName = App::className($name, 'Pdf/Engine', 'Engine');
        if ($engineClassName === null) {
            throw new Exception(sprintf('Pdf engine "%s" not found', $name));
        }
        if (!is_subclass_of($engineClassName, 'CakePdf\Pdf\Engine\AbstractPdfEngine')) {
            throw new Exception('Pdf engines must extend "AbstractPdfEngine"');
        }
        $this->_engineClass = new $engineClassName($this);
        $this->_engineClass->setConfig($config);

        return $this->_engineClass;
    }

    /**
     * Load PdfCrypto
     *
     * @param string|array $name Classname of crypto engine without `Crypto` suffix. For example `CakePdf.Pdftk`
     * @throws \Cake\Core\Exception\Exception
     * @return \CakePdf\Pdf\Crypto\AbstractPdfCrypto
     */
    public function crypto($name = null): AbstractPdfCrypto
    {
        if ($name === null) {
            if ($this->_cryptoClass !== null) {
                return $this->_cryptoClass;
            }
            throw new Exception('Crypto is not loaded');
        }
        $config = [];
        if (is_array($name)) {
            $config = $name;
            $name = $name['className'];
        }

        $engineClassName = App::className($name, 'Pdf/Crypto', 'Crypto');
        if ($engineClassName === null || !class_exists($engineClassName)) {
            throw new Exception(sprintf('Pdf crypto `%s` not found', $name));
        }
        if (!is_subclass_of($engineClassName, AbstractPdfCrypto::class)) {
            throw new Exception('Crypto engine must extend `AbstractPdfCrypto`');
        }
        $this->_cryptoClass = new $engineClassName($this);
        $this->_cryptoClass->config($config);

        return $this->_cryptoClass;
    }

    /**
     * Get/Set Page size.
     *
     * @param null|string $pageSize Page size to set
     * @return mixed
     */
    public function pageSize(?string $pageSize = null)
    {
        if ($pageSize === null) {
            return $this->_pageSize;
        }
        $this->_pageSize = $pageSize;

        return $this;
    }

    /**
     * Get/Set Orientation.
     *
     * @param null|string $orientation orientation to set
     * @return mixed
     */
    public function orientation(?string $orientation = null)
    {
        if ($orientation === null) {
            return $this->_orientation;
        }
        $this->_orientation = $orientation;

        return $this;
    }

    /**
     * Get/Set Encoding.
     *
     * @param null|string $encoding encoding to set
     * @return mixed
     */
    public function encoding(?string $encoding = null)
    {
        if ($encoding === null) {
            return $this->_encoding;
        }
        $this->_encoding = $encoding;

        return $this;
    }

    /**
     * Get/Set footer HTML.
     *
     * @param null|string|array $left left side footer
     * @param null|string $center center footer
     * @param null|string $right right side footer
     * @return mixed
     */
    public function footer($left = null, ?string $center = null, ?string $right = null)
    {
        if ($left === null && $center === null && $right === null) {
            return $this->_footer;
        }

        if (is_array($left)) {
            extract($left, EXTR_IF_EXISTS);
        }

        $this->_footer = compact('left', 'center', 'right');

        return $this;
    }

    /**
     * Get/Set header HTML.
     *
     * @param null|string|array $left left side header
     * @param null|string $center center header
     * @param null|string $right right side header
     * @return mixed
     */
    public function header($left = null, ?string $center = null, ?string $right = null)
    {
        if ($left === null && $center === null && $right === null) {
            return $this->_header;
        }

        if (is_array($left)) {
            extract($left, EXTR_IF_EXISTS);
        }

        $this->_header = compact('left', 'center', 'right');

        return $this;
    }

    /**
     * Get/Set page margins.
     *
     * Several options are available
     *
     * Array format
     * ------------
     * First param can be an array with the following options:
     * - bottom
     * - left
     * - right
     * - top
     *
     * Set margin for all borders
     * --------------------------
     * $bottom is set to a string
     * Leave all other parameters empty
     *
     * Set margin for horizontal and vertical
     * --------------------------------------
     * $bottom value will be set to bottom and top
     * $left value will be set to left and right
     *
     * @param null|string|array $bottom bottom margin, or array of margins
     * @param null|string $left left margin
     * @param null|string $right right margin
     * @param null|string $top top margin
     * @return mixed
     */
    public function margin($bottom = null, $left = null, $right = null, $top = null)
    {
        if ($bottom === null) {
            return [
                'bottom' => $this->_marginBottom,
                'left' => $this->_marginLeft,
                'right' => $this->_marginRight,
                'top' => $this->_marginTop,
            ];
        }

        if (is_array($bottom)) {
            extract($bottom, EXTR_IF_EXISTS);
        }

        if ($bottom && $left === null && $right === null && $top === null) {
            $left = $right = $top = $bottom;
        }

        if ($bottom && $top === null) {
            $top = $bottom;
        }

        if ($left && $right === null) {
            $right = $left;
        }

        $this->marginBottom($bottom);
        $this->marginLeft($left);
        $this->marginRight($right);
        $this->marginTop($top);

        return $this;
    }

    /**
     * Get/Set bottom margin.
     *
     * @param null|string $margin margin to set
     * @return mixed
     */
    public function marginBottom($margin = null)
    {
        if ($margin === null) {
            return $this->_marginBottom;
        }
        $this->_marginBottom = $margin;

        return $this;
    }

    /**
     * Get/Set left margin.
     *
     * @param null|string $margin margin to set
     * @return mixed
     */
    public function marginLeft($margin = null)
    {
        if ($margin === null) {
            return $this->_marginLeft;
        }
        $this->_marginLeft = $margin;

        return $this;
    }

    /**
     * Get/Set right margin.
     *
     * @param null|string $margin margin to set
     * @return mixed
     */
    public function marginRight($margin = null)
    {
        if ($margin === null) {
            return $this->_marginRight;
        }
        $this->_marginRight = $margin;

        return $this;
    }

    /**
     * Get/Set top margin.
     *
     * @param null|string $margin margin to set
     * @return mixed
     */
    public function marginTop($margin = null)
    {
        if ($margin === null) {
            return $this->_marginTop;
        }
        $this->_marginTop = $margin;

        return $this;
    }

    /**
     * Get/Set document title.
     *
     * @param null|string $title title to set
     * @return mixed
     */
    public function title(?string $title = null)
    {
        if ($title === null) {
            return $this->_title;
        }
        $this->_title = $title;

        return $this;
    }

    /**
     * Get/Set javascript delay.
     *
     * @param null|int $delay delay to set in milliseconds
     * @return mixed
     */
    public function delay(?int $delay = null)
    {
        if ($delay === null) {
            return $this->_delay;
        }
        $this->_delay = $delay;

        return $this;
    }

    /**
     * Get/Set the required window status for rendering
     * Waits until the status is equal to the string before rendering the pdf
     *
     * @param null|string $status status to set as string
     * @return mixed
     */
    public function windowStatus(?string $status = null)
    {
        if ($status === null) {
            return $this->_windowStatus;
        }
        $this->_windowStatus = $status;

        return $this;
    }

    /**
     * Get/Set protection.
     *
     * @param null|bool $protect True or false
     * @return mixed
     */
    public function protect(?bool $protect = null)
    {
        if ($protect === null) {
            return $this->_protect;
        }
        $this->_protect = $protect;

        return $this;
    }

    /**
     * Get/Set userPassword
     *
     * The user password is used to control who can open the PDF document.
     *
     * @param null|string $password password to set
     * @return mixed
     */
    public function userPassword(?string $password = null)
    {
        if ($password === null) {
            return $this->_userPassword;
        }
        $this->_userPassword = $password;

        return $this;
    }

    /**
     * Get/Set ownerPassword.
     *
     * The owner password is used to control who can modify, print, manage the PDF document.
     *
     * @param null|string $password password to set
     * @return mixed
     */
    public function ownerPassword(?string $password = null)
    {
        if ($password === null) {
            return $this->_ownerPassword;
        }
        $this->_ownerPassword = $password;

        return $this;
    }

    /**
     * Get/Set permissions.
     *
     * all: allow all permissions
     * none: allow no permissions
     * array: list of permissions that are allowed
     *
     * @param null|bool|array|string $permissions Permissions to set
     * @throws \Cake\Core\Exception\Exception
     * @return mixed
     */
    public function permissions($permissions = null)
    {
        if (!$this->protect()) {
            return $this;
        }

        if ($permissions === null) {
            return $this->_allow;
        }

        if (is_string($permissions) && $permissions === 'all') {
            $permissions = true;
        }

        if (is_string($permissions) && $permissions === 'none') {
            $permissions = false;
        }

        if (is_array($permissions)) {
            foreach ($permissions as $permission) {
                if (!in_array($permission, $this->_availablePermissions)) {
                    throw new Exception(sprintf('Invalid permission: %s', $permission));
                }

                if (!$this->crypto()->permissionImplemented($permission)) {
                    throw new Exception(sprintf('Permission not implemented in crypto engine: %s', $permission));
                }
            }
        }

        $this->_allow = $permissions;

        return $this;
    }

    /**
     * Get/Set caching.
     *
     * @param null|bool|string $cache Cache config name to use, If true is passed, 'cake_pdf' will be used.
     * @throws \Cake\Core\Exception\Exception
     * @return mixed
     */
    public function cache($cache = null)
    {
        if ($cache === null) {
            return $this->_cache;
        }

        if ($cache === false) {
            $this->_cache = false;

            return $this;
        }

        if ($cache === true) {
            $cache = 'cake_pdf';
        }

        if (!in_array($cache, Cache::configured())) {
            throw new Exception(sprintf('CakePdf cache is not configured: %s', $cache));
        }

        $this->_cache = $cache;

        return $this;
    }

    /**
     * Template and layout
     *
     * @param mixed $template Template name or null to not use
     * @param mixed $layout Layout name or null to not use
     * @return mixed
     */
    public function template($template = false, $layout = null)
    {
        if ($template === false) {
            return [
                'template' => $this->_template,
                'layout' => $this->_layout,
            ];
        }
        $this->_template = $template;
        if ($layout !== null) {
            $this->_layout = $layout;
        }

        return $this;
    }

    /**
     * Template path
     *
     * @param mixed $templatePath The path of the template to use
     * @return mixed
     */
    public function templatePath($templatePath = false)
    {
        if ($templatePath === false) {
            return $this->_templatePath;
        }

        $this->_templatePath = $templatePath;

        return $this;
    }

    /**
     * Layout path
     *
     * @param mixed $layoutPath The path of the layout file to use
     * @return mixed
     */
    public function layoutPath($layoutPath = false)
    {
        if ($layoutPath === false) {
            return $this->_layoutPath;
        }

        $this->_layoutPath = $layoutPath;

        return $this;
    }

    /**
     * View class for render
     *
     * @param string|null $viewClass name of the view class to use
     * @return mixed
     */
    public function viewRender(?string $viewClass = null)
    {
        if ($viewClass === null) {
            return $this->_viewRender;
        }
        $this->_viewRender = $viewClass;

        return $this;
    }

    /**
     * Variables to be set on render
     *
     * @param array $viewVars view variables to set
     * @return mixed
     */
    public function viewVars(?array $viewVars = null)
    {
        if ($viewVars === null) {
            return $this->_viewVars;
        }
        $this->_viewVars = array_merge($this->_viewVars, $viewVars);

        return $this;
    }

    /**
     * Theme to use when rendering
     *
     * @param string $theme theme to use
     * @return mixed
     */
    public function theme(?string $theme = null)
    {
        if ($theme === null) {
            return $this->_theme;
        }
        $this->_theme = $theme;

        return $this;
    }

    /**
     * Helpers to be used in render
     *
     * @param array $helpers helpers to use
     * @return mixed
     */
    public function helpers(?array $helpers = null)
    {
        if ($helpers === null) {
            return $this->_helpers;
        }
        $this->_helpers = $helpers;

        return $this;
    }

    /**
     * Build and set all the view properties needed to render the layout and template.
     *
     * @return string The rendered template wrapped in layout.
     */
    protected function _render(): string
    {
        $viewClass = $this->viewRender();
        /** @psalm-var class-string<\Cake\View\View> */
        $viewClass = App::className($viewClass, 'View', $viewClass === 'View' ? '' : 'View');

        $viewVars = [
            'theme',
            'layoutPath',
            'templatePath',
            'template',
            'layout',
            'helpers',
            'viewVars',
        ];
        $viewOptions = [];
        foreach ($viewVars as $var) {
            $prop = '_' . $var;
            $viewOptions[$var] = $this->{$prop};
        }

        $request = Router::getRequest();
        if (!$request) {
            $request = ServerRequestFactory::fromGlobals();
        }

        $View = new $viewClass(
            $request,
            null,
            null,
            $viewOptions
        );

        return $View->render();
    }
}
