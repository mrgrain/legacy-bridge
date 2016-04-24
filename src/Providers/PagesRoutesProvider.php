<?php
namespace Frogsystem\Legacy\Bridge\Providers;

use Frogsystem\Metamorphosis\Middleware\Stack;
use Frogsystem\Metamorphosis\Providers\RoutesProvider;
use Frogsystem\Legacy\Bridge\Services\Config;

/**
 * Class PagesRoutesProvider
 * @package Frogsystem\Legacy\Bridge
 */
abstract class PagesRoutesProvider extends RoutesProvider
{
    /**
     * Helper method for displaying old pages
     * @param $name
     * @param string $controller
     * @param string $method
     * @return \Closure
     */
    public function page($name, $controller, $method = 'page')
    {
        return new Stack(
            $this->controller(function (Config $config) use ($name, $controller, $method) {
                // set old config
                $config->setConfig('goto', $name);
                $config->setConfig('env', 'goto', $name);
            }),
            $this->controller($controller, $method)
        );
    }
}
