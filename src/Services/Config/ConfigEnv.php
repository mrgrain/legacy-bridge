<?php
namespace Frogsystem\Legacy\Bridge\Services\Config;

/**
 * Class ConfigEnv
 * @package Frogsystem\Legacy\Bridge\Services\Config
 */
class ConfigEnv extends ConfigData
{
    /**
     * startup
     */
    protected function startup()
    {
        // Load env config
        $this->setConfigByFile('env');

        // set env data
        $this->setConfig('date', time());
        $this->setConfig('time', $this->get('date'));
        $this->setConfig('year', date('Y', $this->get('date')));
        $this->setConfig('month', date('m', $this->get('date')));
        $this->setConfig('day', date('d', $this->get('date')));
        $this->setConfig('hour', date('H', $this->get('date')));
        $this->setConfig('minute', date('i', $this->get('date')));
        $this->setConfig('path', FS2CONTENT . '/');
    }
    
    /**
     * get config entry
     * @param $name
     * @return mixed
     */
    public function get($name)
    {
        if (oneof($name, 'pref', 'spam', 'data', 'min')) {
            trigger_error("Usage of config value env/{$name} is deprecated.", E_USER_DEPRECATED);
        }
        return $this->config[$name];
    }
}
