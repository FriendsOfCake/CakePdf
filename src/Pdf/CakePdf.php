<?php
declare(strict_types=1);

namespace CakePdf\Pdf;

use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
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
    protected string $_layout = 'default';

    /**
     * Path to the layout - defaults to 'pdf'
     *
     * @var string
     */
    protected string $_layoutPath = 'pdf';

    /**
     * Template for the view
     *
     * @var string|null
     */
    protected ?string $_template = null;

    /**
     * Path to the template - defaults to 'pdf'
     *
     * @var string
     */
    protected string $_templatePath = 'pdf';

    /**
     * View for render
     *
     * @var string
     */
    protected string $_viewRender = 'View';

    /**
     * Vars to sent to render
     *
     * @var array
     */
    protected array $_viewVars = [];

    /**
     * Theme for the View
     *
     * @var string|null
     */
    protected ?string $_theme = null;

    /**
     * Helpers to be used in the render
     *
     * @var array
     */
    protected array $_helpers = ['Html'];

    /**
     * Instance of PdfEngine class
     *
     * @var \CakePdf\Pdf\Engine\AbstractPdfEngine|null
     */
    protected ?AbstractPdfEngine $_engine = null;

    /**
     * Instance of PdfCrypto class
     *
     * @var \CakePdf\Pdf\Crypto\AbstractPdfCrypto|null
     */
    protected ?AbstractPdfCrypto $_crypto = null;

    /**
     * Html to be rendered
     *
     * @var string
     */
    protected string $_html = '';

    /**
     * Page size of the pdf
     *
     * @var string
     */
    protected string $_pageSize = 'A4';

    /**
     * Orientation of the pdf
     *
     * @var string
     */
    protected string $_orientation = 'portrait';

    /**
     * Encoding
     *
     * @var string
     */
    protected string $_encoding = 'UTF-8';

    /**
     * Footer HTML
     *
     * @var array
     */
    protected array $_footer = ['left' => null, 'center' => null, 'right' => null];

    /**
     * Header HTML
     *
     * @var array
     */
    protected array $_header = ['left' => null, 'center' => null, 'right' => null];

    /**
     * Bottom margin in mm
     *
     * @var string|int|null
     */
    protected string|int|null $_marginBottom = null;

    /**
     * Left margin in mm
     *
     * @var string|int|null
     */
    protected string|int|null $_marginLeft = null;

    /**
     * Right margin in mm
     *
     * @var string|int|null
     */
    protected string|int|null $_marginRight = null;

    /**
     * Top margin in mm
     *
     * @var string|int|null
     */
    protected string|int|null $_marginTop = null;

    /**
     * Title of the document
     *
     * @var string|null
     */
    protected ?string $_title = null;

    /**
     * Javascript delay before rendering document in milliseconds
     *
     * @var int|null
     */
    protected ?int $_delay = null;

    /**
     * Window status required before rendering document
     *
     * @var string|null
     */
    protected ?string $_windowStatus = null;

    /**
     * Flag that tells if we need to pass it through crypto
     *
     * @var bool
     */
    protected bool $_protect = false;

    /**
     * User password, used with crypto
     *
     * @var string|null
     */
    protected ?string $_userPassword = null;

    /**
     * Owner password, used with crypto
     *
     * @var string|null
     */
    protected ?string $_ownerPassword = null;

    /**
     * Cache config name, if set to false cache is disabled
     *
     * @var string|false
     */
    protected string|false $_cache = false;

    /**
     * Permissions that are allowed, used with crypto
     *
     * false: none
     * true: all
     * array: List of permissions that are allowed
     *
     * @var array|bool
     */
    protected array|bool $_allow = false;

    /**
     * Available permissions
     *
     * @var array
     */
    protected array $_availablePermissions = [
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
     * @param string|null $html Html content to render. If omitted, the template will be rendered with viewVars and layout that have been set.
     * @throws \Cake\Core\Exception\CakeException
     * @return string
     */
    public function output(?string $html = null): string
    {
        $engine = $this->engine();
        if ($engine === null) {
            throw new CakeException('Engine is not loaded');
        }

        $this->html($html ?? $this->_render());

        $cacheKey = '';
        $cache = $this->cache();
        if ($cache) {
            $cacheKey = md5(serialize($this));
            $cached = Cache::read($cacheKey, $cache);
            if ($cached) {
                return $cached;
            }
        }

        $output = $engine->output();

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
     * @param string|null $html Html to set
     * @return $this|string
     * @psalm-return ($html is null ? string : $this)
     */
    public function html(?string $html = null): static|string
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
     * @throws \Cake\Core\Exception\CakeException
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

        $splFileInfo = $fileInfo->getPathInfo();
        if ($splFileInfo === null) {
            throw new CakeException('Failed to retrieve path information');
        }
        if (!$splFileInfo->getRealPath()) {
            mkdir($fileInfo->getPath(), 0777, true);
        }

        return (bool)file_put_contents($destination, $output);
    }

    /**
     * Load PdfEngine
     *
     * @param \CakePdf\Pdf\Engine\AbstractPdfEngine|array|string|null $name Classname of pdf engine without `Engine` suffix. For example `CakePdf.DomPdf`
     * @throws \Cake\Core\Exception\CakeException
     * @return \CakePdf\Pdf\Engine\AbstractPdfEngine|null
     */
    public function engine(AbstractPdfEngine|array|string|null $name = null): ?AbstractPdfEngine
    {
        if ($name === null) {
            return $this->_engine;
        }
        $config = [];
        if (is_array($name)) {
            $config = $name;
            $name = $name['className'];
        }

        if (is_object($name)) {
            assert(
                is_subclass_of($name, AbstractPdfEngine::class),
                'Pdf engines must extend "AbstractPdfEngine"'
            );

            $this->_engine = $name;
            $this->_engine->setConfig($config);

            return $this->_engine;
        }

        $engineClassName = App::className($name, 'Pdf/Engine', 'Engine');
        if ($engineClassName === null) {
            throw new CakeException(sprintf('Pdf engine "%s" not found', $name));
        }
        assert(
            is_subclass_of($engineClassName, AbstractPdfEngine::class, true),
            'Pdf engines must extend "AbstractPdfEngine"'
        );
        $this->_engine = new $engineClassName($this);
        $this->_engine->setConfig($config);

        return $this->_engine;
    }

    /**
     * Load PdfCrypto
     *
     * @param array|string $name Classname of crypto engine without `Crypto` suffix. For example `CakePdf.Pdftk`
     * @throws \Cake\Core\Exception\CakeException
     * @return \CakePdf\Pdf\Crypto\AbstractPdfCrypto
     */
    public function crypto(string|array|null $name = null): AbstractPdfCrypto
    {
        if ($name === null) {
            return $this->_crypto ?? throw new CakeException('Crypto is not loaded');
        }

        $config = [];
        if (is_array($name)) {
            $config = $name;
            $name = $name['className'];
        }

        $engineClassName = App::className($name, 'Pdf/Crypto', 'Crypto');
        if ($engineClassName === null || !class_exists($engineClassName)) {
            throw new CakeException(sprintf('Pdf crypto `%s` not found', $name));
        }
        assert(
            is_subclass_of($engineClassName, AbstractPdfCrypto::class),
            'Crypto engine must extend `AbstractPdfCrypto`'
        );
        $this->_crypto = new $engineClassName($this);
        $this->_crypto->config($config);

        return $this->_crypto;
    }

    /**
     * Get/Set Page size.
     *
     * @param string|null $pageSize Page size to set
     * @return $this|string
     * @psalm-return ($pageSize is null ? string : $this)
     */
    public function pageSize(?string $pageSize = null): static|string
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
     * @param string|null $orientation orientation to set
     * @return $this|string
     * @psalm-return ($orientation is null ? string : $this)
     */
    public function orientation(?string $orientation = null): static|string
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
     * @param string|null $encoding encoding to set
     * @return $this|string
     * @psalm-return ($encoding is null ? string : $this)
     */
    public function encoding(?string $encoding = null): static|string
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
     * @param array|string|null $left left side footer
     * @param string|null $center center footer
     * @param string|null $right right side footer
     * @return $this|array
     * @psalm-return ($left is null && $center is null && $right is null ? array : $this)
     */
    public function footer(string|array|null $left = null, ?string $center = null, ?string $right = null): static|array
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
     * @param array|string|null $left left side header
     * @param string|null $center center header
     * @param string|null $right right side header
     * @return $this|array
     * @psalm-return ($left is null && $center is null && $right is null ? array : $this)
     */
    public function header(string|array|null $left = null, ?string $center = null, ?string $right = null): static|array
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
     * @param array|string|int|null $bottom bottom margin, or array of margins
     * @param string|null $left left margin
     * @param string|null $right right margin
     * @param string|null $top top margin
     * @return $this|array
     * @psalm-return ($bottom is null ? array : $this)
     */
    public function margin(
        string|int|array|null $bottom = null,
        string|int|null $left = null,
        string|int|null $right = null,
        string|int|null $top = null
    ): static|array {
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

        $this->marginBottom($bottom); // @phpstan-ignore argument.type
        $this->marginLeft($left); // @phpstan-ignore argument.type
        $this->marginRight($right); // @phpstan-ignore argument.type
        $this->marginTop($top); // @phpstan-ignore argument.type

        return $this;
    }

    /**
     * Get/Set bottom margin.
     *
     * @param string|int|null $margin margin to set
     * @return $this|string|int|null
     * @psalm-return ($margin is null ? string|int|null : $this)
     */
    public function marginBottom(string|int|null $margin = null): static|string|int|null
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
     * @param string|int|null $margin margin to set
     * @return $this|string|int|null
     * @psalm-return ($margin is null ? string|int|null : $this)
     */
    public function marginLeft(string|int|null $margin = null): static|string|int|null
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
     * @param string|int|null $margin margin to set
     * @return $this|string|int|null
     * @psalm-return ($margin is null ? string|int|null : $this)
     */
    public function marginRight(string|int|null $margin = null): static|string|int|null
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
     * @param string|int|null $margin margin to set
     * @return $this|string|int|null
     * @psalm-return ($margin is null ? string|int|null : $this)
     */
    public function marginTop(string|int|null $margin = null): static|string|int|null
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
     * @param string|null $title title to set
     * @return $this|string|null
     * @psalm-return ($title is null ? string|null : $this)
     */
    public function title(?string $title = null): static|string|null
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
     * @param int|null $delay delay to set in milliseconds
     * @return $this|int|null
     * @psalm-return ($delay is null ? int|null : $this)
     */
    public function delay(?int $delay = null): static|int|null
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
     * @param string|null $status status to set as string
     * @return $this|string|null
     * @psalm-return ($status is null ? string|null : $this)
     */
    public function windowStatus(?string $status = null): static|string|null
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
     * @param bool|null $protect True or false
     * @return $this|bool
     * @psalm-return ($protect is null ? bool : $this)
     */
    public function protect(?bool $protect = null): static|bool
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
     * @param string|null $password password to set
     * @return $this|string|null
     * @psalm-return ($password is null ? string|null : $this)
     */
    public function userPassword(?string $password = null): static|string|null
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
     * @param string|null $password password to set
     * @return $this|string|null
     * @psalm-return ($password is null ? string|null : $this)
     */
    public function ownerPassword(?string $password = null): static|string|null
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
     * @param array|string|bool|null $permissions Permissions to set
     * @throws \Cake\Core\Exception\CakeException
     * @return $this|array|string|bool|null
     * @psalm-return ($permissions is null ? array|string|bool|null : $this)
     */
    public function permissions(bool|array|string|null $permissions = null): static|array|string|bool|null
    {
        if (!$this->protect()) {
            return $this;
        }

        if ($permissions === null) {
            return $this->_allow;
        }

        if (is_string($permissions)) {
            $permissions = strtolower($permissions);
            $permissions = match ($permissions) {
                'all' => true,
                'none' => false,
                default => throw new CakeException(sprintf('Invalid permission: %s', $permissions)),
            };
        } elseif (is_array($permissions)) {
            foreach ($permissions as $permission) {
                if (!in_array($permission, $this->_availablePermissions)) {
                    throw new CakeException(sprintf('Invalid permission: %s', $permission));
                }

                if (!$this->crypto()->permissionImplemented($permission)) {
                    throw new CakeException(sprintf('Permission not implemented in crypto engine: %s', $permission));
                }
            }
        }

        $this->_allow = $permissions;

        return $this;
    }

    /**
     * Get/Set caching.
     *
     * @param string|bool|null $cache Cache config name to use, If true is passed, 'cake_pdf' will be used.
     * @throws \Cake\Core\Exception\CakeException
     * @return $this|string|false
     * @psalm-return ($cache is null ? string|false : $this)
     */
    public function cache(bool|string|null $cache = null): static|string|false
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
            throw new CakeException(sprintf('CakePdf cache is not configured: %s', $cache));
        }

        $this->_cache = $cache;

        return $this;
    }

    /**
     * Template and layout
     *
     * @param string|false|null $template Template name or null to not use
     * @param ?string $layout Layout name or null to not use
     * @return $this|array
     * @psalm-return ($template is false ? array : $this)
     */
    public function template(string|false|null $template = false, ?string $layout = null): static|array
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
     * @param ?string $templatePath The path of the template to use
     * @return $this|string
     * @psalm-return ($templatePath is null ? string : $this)
     */
    public function templatePath(?string $templatePath = null): static|string
    {
        if ($templatePath === null) {
            return $this->_templatePath;
        }

        $this->_templatePath = $templatePath;

        return $this;
    }

    /**
     * Layout path
     *
     * @param ?string $layoutPath The path of the layout file to use
     * @return $this|string
     * @psalm-return ($layoutPath is null ? string : $this)
     */
    public function layoutPath(?string $layoutPath = null): static|string
    {
        if ($layoutPath === null) {
            return $this->_layoutPath;
        }

        $this->_layoutPath = $layoutPath;

        return $this;
    }

    /**
     * View class for render
     *
     * @param string|null $viewClass name of the view class to use
     * @return $this|string
     * @psalm-return ($viewClass is null ? string : $this)
     */
    public function viewRender(?string $viewClass = null): static|string
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
     * @return $this|array
     * @psalm-return ($viewVars is null ? array : $this)
     */
    public function viewVars(?array $viewVars = null): static|array
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
     * @return $this|string|null
     * @psalm-return ($theme is null ? string|null : $this)
     */
    public function theme(?string $theme = null): static|string|null
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
     * @return $this|array
     * @psalm-return ($helpers is null ? array : $this)
     */
    public function helpers(?array $helpers = null): static|array
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

        /** @var class-string<\Cake\View\View> $viewClass */
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
