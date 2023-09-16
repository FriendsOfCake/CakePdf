<?php
declare(strict_types=1);

namespace CakePdf;

use Cake\Core\BasePlugin;

class Plugin extends BasePlugin
{
    /**
     * Load routes or not
     */
    protected bool $routesEnabled = false;

    /**
     * Enable middleware
     */
    protected bool $middlewareEnabled = false;

    /**
     * Console middleware
     */
    protected bool $consoleEnabled = false;
}
