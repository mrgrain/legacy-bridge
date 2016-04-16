<?php
namespace Frogsystem\Legacy\Bridge\Services;

/**
 * Class Text
 * @package Frogsystem\Legacy\Services
 */
class Text implements \ArrayAccess
{
    /**
     * @var array
     */
    private $container = [];

    /**
     * @param string|null $local
     */
    public function __construct($local = null)
    {
        if (!$local) {
            $local = $this->detectLanguage();
        }

        $this->container = array(
            'frontend' => new Lang($local, 'frontend'),
            'admin' => new Lang($local, 'admin'),
            'template' => new Lang($local, 'template'),
            'menu' => new Lang($local, 'menu'),
            'fscode' => new Lang($local, 'fscode'),
        );
    }

    /**
     * @param $local
     */
    public function setLocal($local)
    {
        foreach ($this->container as $lang) {
            /** @var Lang $lang */
            $lang->setLocal($local);
        }
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * @param mixed $offset
     * @return Lang
     */
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }
    
    /**
     * @param string $default
     * @return string
     */
    protected function detectLanguage($default = 'de_DE')
    {
        $langs = array();
        unset($_SESSION['user_lang']);
        if (!isset($_SESSION['user_lang']) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // break up string into pieces (languages and q factors)
            preg_match_all('/([a-z]{1,8}(?:-([a-z]{1,8}))?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);
            //~ var_dump($lang_parse);
            if (count($lang_parse[1])) {
                // create a list like "en" => 0.8
                $langs = array_combine($lang_parse[1], $lang_parse[4]);

                // set default to 1 for any without q factor
                foreach ($langs as $lang => $val) {
                    if ($val === '') $langs[$lang] = 1;
                }

                // sort list based on value
                arsort($langs, SORT_NUMERIC);
            }
        }

        foreach ($langs as $lang => $p) {
            switch ($lang) {
                case 'en':
                    return 'en_US';
                case 'de':
                    return 'de_DE';
            }
        }

        return $default;
    }
}
