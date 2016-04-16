<?php
namespace Frogsystem\Legacy\Bridge\Services;

use App\Frogsystem2;

/**
 * Class GlobalData
 * @property Config config
 * @property Text text
 * @package Frogsystem\Legacy\Bridge\Services
 */
class GlobalData
{
    /**
     * @var Frogsystem2
     */
    protected $app;

    /**
     * Request Legacy Container
     * @param Frogsystem2 $app
     */
    public function __construct(Frogsystem2 $app = null)
    {
        $this->app = $app;

        global $FD;
        $FD = $this;
    }

    /**
     * This is a pseudo container and therefore uses it delegate to lookup missing entries.
     * @param string $id
     * @return mixed
     * @throws \Frogsystem\Spawn\Exceptions\NotFoundException
     */
    function __get($id)
    {
        // config
        if ('config' == $id) {
            return $this->app->get(Config::class);
        }

        // text
        if ('text' == $id) {
            return $this->app->get(Text::class);
        }

        // unknown thing
        throw new \InvalidArgumentException;
    }

    /**
     * Executed whenever destroyed, remove global
     * @return mixed
     */
    public function __destruct()
    {
        global $FD;
        unset($FD);
    }

    /**
     * Return the text internal
     * @param $type
     * @param $tag
     * @return null
     */
    public function text($type, $tag)
    {
        $this->deprecate('text');
        if (isset($this->text[$type]))
            return $this->text[$type]->get($tag);

        return null;
    }

    /**
     * Return the database internal
     * @return mixed
     */
    public function db()
    {
        $this->deprecate('db');
        return $this->app->get(Database::class);
    }

    /**
     * Return the old sql internal
     * @return mixed
     */
    public function sql()
    {
        $this->deprecate('sql');
        return $this->db();
    }

    /**
     * config interface
     * @param $name
     * @return mixed
     */
    public function loadConfig($name)
    {
        $this->deprecate('loadConfig');
        $this->config->loadConfig($name);
        return;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function configObject($name)
    {
        $this->deprecate('configObject');
        return $this->config->configObject($name);
    }

    /**
     * @return mixed
     */
    public function setConfig()
    {
        $this->deprecate('setConfig');
        return call_user_func_array(array($this->config, 'setConfig'), func_get_args());
    }

    /**
     * @param $name
     * @param $new_data
     * @return mixed
     */
    public function saveConfig($name, $new_data)
    {
        $this->deprecate('saveConfig');
        $this->config->saveConfig($name, $new_data);
        return;
    }

    /**
     * Return the cold config internal
     * @return mixed
     */
    public function config()
    {
        $this->deprecate('config');
        return call_user_func_array(array($this->config, 'config'), func_get_args());
    }

    /**
     * Alias for config
     * @return mixed
     */
    public function cfg()
    {
        $this->deprecate('cfg');
        return call_user_func_array(array($this->config, 'config'), func_get_args());
    }

    /**
     * Alias for config
     * @param $arg
     * @return mixed
     */
    public function env($arg)
    {
        $this->deprecate('env');
        return $this->config->cfg('env', $arg);
    }

    /**
     * Alias for config
     * @param $arg
     * @return mixed
     */
    public function system($arg)
    {
        $this->deprecate('system');
        return $this->config->cfg('system', $arg);
    }

    /**
     * Alias for config
     * @param $arg
     * @return mixed
     */
    public function info($arg)
    {
        $this->deprecate('info');
        return $this->config->cfg('info', $arg);
    }

    /**
     * Alias for config
     * @return mixed
     */
    public function configExists()
    {
        $this->deprecate('configExists');
        return call_user_func_array(array($this->config, 'configExists'), func_get_args());
    }

    /**
     * @param $method
     */
    private function deprecate($method)
    {
//        trigger_error("Usage of \$FD::{$method} is deprecated.", E_USER_DEPRECATED);
    }
}
