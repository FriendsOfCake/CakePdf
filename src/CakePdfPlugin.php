<?php
declare(strict_types=1);

namespace CakePdf;

use Cake\Core\BasePlugin;

class CakePdfPlugin extends BasePlugin
{
    /**
     * Do bootstrap or not
     *
     * @var bool
     */
    protected bool $bootstrapEnabled = false;

    /**
     * Load routes or not
     *
     * @var bool
     */
    protected bool $routesEnabled = false;

    /**
     * Enable middleware
     *
     * @var bool
     */
    protected bool $middlewareEnabled = false;

    /**
     * Console middleware
     *
     * @var bool
     */
    protected bool $consoleEnabled = false;
}
