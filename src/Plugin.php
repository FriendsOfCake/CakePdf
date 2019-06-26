<?php
declare(strict_types=1);

namespace CakePdf;

use Cake\Core\BasePlugin;

class Plugin extends BasePlugin
{
    /**
     * Load routes or not
     *
     * @var bool
     */
    protected $routesEnabled = false;

    /**
     * Enable middleware
     *
     * @var bool
     */
    protected $middlewareEnabled = false;

    /**
     * Console middleware
     *
     * @var bool
     */
    protected $consoleEnabled = false;
}
