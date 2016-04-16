<?php
namespace Frogsystem\Legacy\Bridge\Services;

/**
 * Class Lang
 * @package Frogsystem\Legacy\Bridge\Services
 */
class Lang
{
    /**
     * @var null
     */
    private $local = null;
    /**
     * @var null
     */
    private $type = null;

    // other vars
    /**
     * @var bool
     */
    private $loaded = false;
    /**
     * @var array
     */
    private $phrases = array();

    /**
     * Lang constructor.
     * @param $local
     * @param bool $type
     */
    public function __construct($local, $type = false)
    {
        $this->setLocal($local);

        if ($type !== false) {
            $this->setType($type);
        }
    }

    /**
     * destructor
     */
    public function __destruct()
    {
        unset($this->local, $this->type, $this->phrases);
    }

    /**
     * Method to assign phrases to tags
     * @param $tag
     * @param $text
     */
    private function add($tag, $text)
    {
        $this->phrases[$tag] = tpl_functions($text, 1, array('DATE', 'VAR', 'URL'));
    }

    /**
     * load text data
     * @param $data
     */
    private function import(&$data)
    {
        $imports = array();
        preg_match_all('/#@([-a-z0-9\/_]+)\\n/is', $data, $imports, PREG_SET_ORDER);
        foreach ($imports as $import) {
            $importPath = FS2LANG . '/' . $this->local . '/' . $import[1] . '.txt';
            $importData = @file_get_contents($importPath);

            // getting file content ok
            if ($importData != false) {
                $importData = str_replace(array("\r\n", "\r"), "\n", $importData); // unify linebreaks
                $this->import($importData);
                $replace = '/#@' . preg_quote($import[1], '/') . '/i';
                $data = preg_replace($replace, $importData, $data);
                // replace all imports recursive
            }
        }
        unset($imports);
    }

    /**
     * load text data
     * @throws \Exception
     */
    private function load()
    {
        // reset phrases
        $this->phrases = array();
        $this->loaded = true;

        // set file path
        $langDataPath = FS2LANG . '/' . $this->local . '/' . $this->type . '.txt';

        // include language data file
        if (file_exists($langDataPath)) {
            // load file
            $langData = file_get_contents($langDataPath);
            $langData = str_replace(array("\r\n", "\r"), "\n", $langData); // unify linebreaks
            $this->import($langData);

            // get lines
            $langData = preg_replace("/#.*?\n/is", '', $langData);
            $langData = explode("\n", $langData);

            // Run through lines
            foreach ($langData as $line) {
                preg_match("#([a-z0-9_-]+?)\s*?:\s*(.*)#is", $line, $match);
                if (count($match) >= 2) {
                    $this->add($match[1], $match[2]);
                }
                unset($match);
            }
        } else {
            Throw new \Exception('Language File not found: ' . $langDataPath);
        }
    }

    /**
     * set used file
     * @param $type
     */
    public function setType($type)
    {
        $this->type = $type;
        $this->loaded = false;
    }

    /**
     * @param $local
     */
    public function setLocal($local)
    {
        if ($this->local != $local) {
            $this->local = $local;
            $this->loaded = false;
        }
    }
    
    /**
     * method to display phrases
     * @param $tag
     * @return mixed|string
     * @throws \Exception
     */
    public function get($tag)
    {
        // load if necessary
        if (!$this->loaded) {
            $this->load();
        }

        // get tag
        if (!isset($this->phrases[$tag]) || $this->phrases[$tag] == '') {
            return 'LOCALIZE [' . $this->local . ']: ' . $tag;
        } else {
            return $this->phrases[$tag];
        }
    }
}
