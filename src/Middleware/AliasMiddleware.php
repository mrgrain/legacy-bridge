<?php
namespace Frogsystem\Legacy\Bridge\Middleware;

use Frogsystem\Legacy\Bridge\Services\Config;
use Frogsystem\Legacy\Bridge\Services\Database;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class AliasMiddleware
 * @package Frogsystem\Legacy\Bridge\Middleware
 */
class AliasMiddleware
{
    /**
     * @var Database
     */
    protected $db;

    /**
     * @var Config
     */
    protected $config;

    /**
     * AliasMiddleware constructor.
     * @param Database $db
     * @param Config $config
     */
    function __construct(Database $db, Config $config)
    {
        $this->db = $db;
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
        $request = $request->withUri($this->forward_aliases($request->getUri()));
        return $next($request, $response);
    }

    /**
     * @param UriInterface $uri
     * @return UriInterface
     * @throws \ErrorException
     */
    protected function forward_aliases(UriInterface $uri)
    {
        // strip beginning /
        $path = substr($uri->getPath(), 1);

        $aliases = $this->db->conn()->prepare(<<<SQL
          SELECT `alias_go`, `alias_forward_to`
          FROM `{$this->db->getPrefix()}aliases`
          WHERE `alias_active` = 1 AND `alias_go` = ?
SQL
        );
        $aliases->execute(array($path));
        $aliases = $aliases->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($aliases as $alias) {
            if ($path == $alias['alias_go']) {
                $path = $alias['alias_forward_to'];
            }
        }

        return $uri->withPath('/' . $path);
    }
}
