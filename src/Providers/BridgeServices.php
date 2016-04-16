<?php
namespace Frogsystem\Legacy\Bridge\Providers;

use Frogsystem\Legacy\Bridge\Renderer\Page;
use Frogsystem\Legacy\Bridge\Services\Config;
use Frogsystem\Legacy\Bridge\Services\Database;
use Frogsystem\Legacy\Bridge\Services\GlobalData;
use Frogsystem\Legacy\Bridge\Services\Session;
use Frogsystem\Legacy\Bridge\Services\Text;
use Frogsystem\Metamorphosis\Contracts\RendererInterface;
use Frogsystem\Metamorphosis\Providers\ServiceProvider;
use Frogsystem\Spawn\Container;

/**
 * Class BridgeServices
 * @package Frogsystem\Legacy\Bridge\Providers
 */
class BridgeServices extends ServiceProvider
{
    /**
     * Registers entries with the container.
     * @param Container $app
     */
    public function register(Container $app)
    {
        // old session
        $app[Session::class] = $app->make(Session::class);

        // old config
        $app[Config::class] = $app->one(Config::class);

        // old text system
        $app[Text::class] = $app->once(function (Config $config) use ($app) {
            $args = [];
            if ($config->configExists('main', 'language_text')) {
                $args[] = $config->config('language_text');
            }
            return $app->make(Text::class, $args);
        });

        // Old database system
        $app[Database::class] = $app->one(Database::class);

        // Default style renderer
        $app[RendererInterface::class] = $app->one(Page::class);

        // Global Data
        $app[GlobalData::class] = new GlobalData($app);
    }
}
