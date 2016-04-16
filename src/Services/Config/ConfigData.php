<?php
namespace Frogsystem\Legacy\Bridge\Services\Config;

/**
 * Class ConfigData
 * @package Frogsystem\Legacy\Bridge\Services\Config
 */
class ConfigData
{
    /**
     * config data
     * @var array
     */
    protected $config = array();

    /**
     * create config object
     * DO NOT OVERRIDE
     * use startup() for your code
     * @param array $data
     * @param bool $json
     */
    final public function __construct($data = array(), $json = false)
    {
        // set start data
        if ($json) {
            $this->config = json_array_decode($data);
        } else {
            $this->config = $data;
        }

        // call startup method
        $this->startup();
    }

    /**
     * method called on object init
     * override this method
     */
    protected function startup()
    {
        // do something here if you want
    }

    /**
     * set specific config entry to value in local copy
     * does not change any database data
     * @param $name
     * @param $value
     * @return $this
     */
    public function setConfig($name, $value)
    {
        $this->config[$name] = $value;
        return $this;
    }

    /**
     * set multiple config entries to value in local copy
     * does not change any database data
     * @param $config
     * @return $this
     */
    public function setConfigByArray($config)
    {
        $this->config += $config;
        return $this;
    }

    /**
     * set multiple config entries from a config file
     * does not change any database data
     * @param $name
     * @return ConfigData
     */
    public function setConfigByFile($name)
    {
        $file = "/{$name}.cfg.php";
        if ('production' !== FS2_ENV) {
            $file_env = "/{$name}-{FS2_ENV}.cfg.php";
            if (file_exists(FS2CONFIG . $file_env)) {
                $file = $file_env;
            }
        }

        $config = array();
        if (file_exists(FS2CONFIG . "/" . $file)) {
            include(FS2CONFIG . "/" . $file);
        }
        return $this->setConfigByArray($config);
    }

    /**
     * return config as array
     * @return array
     */
    public function getConfigArray()
    {
        return $this->config;
    }

    /**
     * return config as json
     * @return mixed
     */
    public function getConfigJson()
    {
        return json_array_encode($this->config);
    }

    /**
     * get config entry
     * @param $name
     * @return mixed
     */
    public function get($name)
    {
        return $this->config[$name];
    }

    /**
     * isset config entry
     * @param $name
     * @return bool
     */
    public function exists($name)
    {
        return isset($this->config[$name]);
    }
}

