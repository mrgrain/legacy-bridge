<?php
namespace Frogsystem\Legacy\Bridge;

use Frogsystem\Legacy\Bridge\Middleware\AliasMiddleware;
use Frogsystem\Legacy\Bridge\Middleware\AnalyticsMiddleware;
use Frogsystem\Legacy\Bridge\Middleware\UrlMiddleware;
use Frogsystem\Legacy\Bridge\Services\Database;
use Frogsystem\Metamorphosis\WebApplication;
use Interop\Container\ContainerInterface;

/**
 * Class BridgeApplication
 * @property Database db
 * @package Frogsystem\Legacy\Bridge
 */
class BridgeApplication extends WebApplication
{
    /**
     * @param ContainerInterface $delegate
     */
    public function __construct(ContainerInterface $delegate)
    {
        // Debugging aka environment
        $this->setDebugMode(defined('FS2_DEBUG') ? FS2_DEBUG : false);

        // Database
        $this->db = $delegate->get(Database::class);

        // call parent and load huggables
        parent::__construct($delegate);

        $this
            ->push(UrlMiddleware::class)
            ->push(AliasMiddleware::class)
            ->push(AnalyticsMiddleware::class);
    }

    /**
     * Set old debug mode
     * @param $debug
     */
    protected function setDebugMode($debug)
    {
        error_reporting(0);
        // Enable error_reporting
        if ($debug) {
            error_reporting(E_ALL);
            ini_set('display_errors', true);
            ini_set('display_startup_errors', true);
        }
    }
}
