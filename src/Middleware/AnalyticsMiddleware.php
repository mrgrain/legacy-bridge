<?php
namespace Frogsystem\Legacy\Bridge\Middleware;

use Frogsystem\Legacy\Bridge\Services\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class AnalyticsMiddleware
 * @package Frogsystem\Legacy\Bridge\Middleware
 */
class AnalyticsMiddleware
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * AnalyticsMiddleware constructor.
     * @param Config $config
     */
    function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        // get response
        $response =  $next($request, $response);

        // Save statistics after all manipulations
        //count_all($this->config->cfg('goto'));
        save_visitors();
        if (!$this->config->configExists('main', 'count_referers') || $this->config->cfg('main', 'count_referers') == 1) {
            save_referer();
        }

        return $response;
    }
}
