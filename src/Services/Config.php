<?php
namespace Frogsystem\Legacy\Bridge\Services;

use Exception;
use Frogsystem\Legacy\Bridge\BridgeApplication;
use Psr\Log\InvalidArgumentException;

/**
 * Class Config
 * @package Frogsystem\Legacy\Bridge\Services
 */
class Config
{
    /**
     * @var Legacy
     */
    protected $app;
    /**
     * @var
     */
    protected $db;
    /**
     * @var array
     */
    private $config = [];

    /**
     * Config constructor.
     * @param BridgeApplication $app
     */
    function __construct(BridgeApplication $app)
    {
        $this->app = $app;
        $this->config['env'] = new Config\ConfigEnv();
    }

    /**
     * load config
     * use reloadConfig if you want to get the data fresh from the database
     * @param $name
     */
    public function loadConfig($name)
    {
        // only if config not yet exists
        if (!$this->configExists($name))
            $this->config[$name] = $this->getConfigObjectFromDatabase($name);
    }

    /**
     * reload config from database
     * @param $name
     * @param null $data
     * @param bool $json
     */
    private function reloadConfig($name, $data = null, $json = false)
    {
        // get from DB
        if (empty($data)) {
            $this->config[$name] = $this->getConfigObjectFromDatabase($name);

            // set data from input
        } else {
            $this->config[$name] = $this->createConfigObject($name, $data, $json);
        }
    }

    /**
     * load configs by hook
     * @param $hook
     * @param bool $reload
     */
    public function loadConfigsByHook($hook, $reload = false)
    {
        // Load configs from DB
        $data = $this->app->db->conn()->prepare(
            'SELECT * FROM ' . $this->app->db->getPrefix() . 'config
                         WHERE `config_loadhook` = ?');
        $data->execute(array($hook));
        $data = $data->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($data as $config) {
            // Load corresponding class and get config array
            if ($reload || !$this->configExists($config['config_name'])) {
                $this->config[$config['config_name']] = $this->createConfigObject($config['config_name'], $config['config_data'], true);
            }
        }
    }

    /**
     * create config object
     * @param $name
     * @param $data
     * @param $json
     * @return mixed
     */
    private function createConfigObject($name, $data, $json)
    {
        // Load corresponding class and get config array
        $class_name = self::class . '\\Config' . ucfirst(strtolower($name));

        if (!class_exists($class_name)) {
            $class_name = self::class . '\\ConfigData';
        }

        return new $class_name($data, $json);
    }

    /**
     * create config object from db
     * @param $name
     * @return mixed
     */
    private function getConfigObjectFromDatabase($name)
    {
        // Load config from DB
        $config = $this->app->db->conn()->prepare(
            'SELECT * FROM ' . $this->app->db->getPrefix() . 'config
                          WHERE `config_name` = ? LIMIT 1');
        $config->execute(array($name));
        $config = $config->fetch(\PDO::FETCH_ASSOC);

        // Load corresponding class and get config array
        return $this->createConfigObject($config['config_name'], $config['config_data'], true);
    }

    /**
     * get access on a config object
     * @param $name
     * @return mixed
     */
    public function configObject($name)
    {
        // make sure we always get the config object
        if (!$this->configExists($name)) {
            $this->reloadConfig($name);
        }

        // Load corresponding class and get config array
        return $this->config[$name];
    }

    /**
     * set config
     * @throws Exception
     */
    public function setConfig()
    {
        // error
        if (func_num_args() < 2 || func_num_args() > 3) {
            throw new InvalidArgumentException('Invalid Call of method "config"');
        }

        // default main config
        if (func_num_args() == 2) {
            return $this->setConfig('main', func_get_arg(0), func_get_arg(1));
        }

        // default global config
        return $this->configObject(func_get_arg(0))->setConfig(func_get_arg(1), func_get_arg(2));
    }

    /**
     * set config
     * @param $name
     * @param $new_data
     * @throws \Exception
     */
    public function saveConfig($name, $new_data)
    {
        try {
            //get original data from db
            $original_data = $this->app->db->getField('config', 'config_data', array('W' => "`config_name` = '" . $name . "'"));
            if (!empty($original_data))
                $original_data = json_array_decode($original_data);
            else {
                $original_data = array();
            }


            // update data
            foreach ($new_data as $key => $value) {
                $original_data[$key] = $value;
            }

            // convert back to json
            $new_data = array(
                'config_name' => $name,
                'config_data' => json_array_encode($original_data),
            );

            // save to db
            $this->app->db->save('config', $new_data, 'config_name', false);

            // Reload Data
            $this->reloadConfig($name, $new_data['config_data'], true);

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * get config
     * @return mixed
     * @throws
     */
    public function config()
    {
        // error
        if (func_num_args() < 1 || func_num_args() > 2) {
            throw new InvalidArgumentException('Invalid Call of method "config"');
        }

        // default main config
        if (func_num_args() == 1) {
            return $this->config('main', func_get_arg(0));
        }

        // return configs
        return $this->configObject(func_get_arg(0))->get(func_get_arg(1));
    }

    /**
     * Alias for config
     * @return mixed
     */
    public function cfg()
    {
        return call_user_func_array(array($this, 'config'), func_get_args());
    }

    /**
     * Alias for env config
     * @param $arg
     * @return mixed
     * @throws
     */
    public function env($arg)
    {
        return $this->config('env', $arg);
    }

    /**
     * Alias for system config
     * @param $arg
     * @return mixed
     * @throws
     */
    public function system($arg)
    {
        return $this->config('system', $arg);
    }

    /**
     * Alias for info config
     * @param $arg
     * @return mixed
     * @throws
     */
    public function info($arg)
    {
        return $this->config('info', $arg);
    }

    /**
     * config and/or key exists
     * @return bool
     */
    public function configExists()
    {
        // check for config
        if (func_num_args() == 1) {
            return isset($this->config[func_get_arg(0)]);
        }

        return isset($this->config[func_get_arg(0)]) && $this->config[func_get_arg(0)]->exists(func_get_arg(1));
    }
}
