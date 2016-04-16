<?php
namespace Frogsystem\Legacy\Bridge\Services\Config;

/**
 * Class ConfigInfo
 * @package Frogsystem\Legacy\Bridge\Services\Config
 */
class ConfigInfo extends ConfigData
{
    /**
     * startup
     */
    protected function startup()
    {
        // set canonical parameters default to null (= no parameters)
        $this->setConfig('canonical', null);
    }
}
