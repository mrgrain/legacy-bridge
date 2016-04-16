<?php
namespace Frogsystem\Legacy\Bridge\Renderer;

use Frogsystem\Legacy\Bridge\Services\Config;
use Frogsystem\Legacy\Bridge\Services\Database;
use Frogsystem\Legacy\Bridge\Services\Lang;

/**
 * Class AdminPage
 * @package Frogsystem\Legacy\Bridge\Renderer
 */
class AdminPage extends Page
{
    /**
     * Applet file path
     */
    const APPLET_PATH = FS2APPLETS;

    /**
     * @var Database
     */
    protected $db;
    
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \adminpage[]
     */
    protected $template = [];

    /**
     * @var array
     */
    public $applets = [];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * AdminPage constructor.
     * @param Database $db
     * @param Config $config
     */
    function __construct(Database $db, Config $config)
    {
        // resources
        $this->db = $db;
        $this->config = $config;

        // Load applets
        $this->applets = $this->loadApplets();
    }

    /**
     * Renders a view with the given data.
     * @param $view
     * @param array $data
     * @param array $conditions
     * @return string
     */
    public function render($view, array $data = [], $conditions = [])
    {
        // view parts
        $file = strtok($view, '/');
        $section = strtok('/');

        // Get Body-Template
        $template = $this->getTemplate($file);

        // extend with default data
        $data += $this->data;

        // set tags
        foreach ($data as $name => $value) {
            $template->addText($name, $value);
        }
        foreach ($conditions as $name => $value) {
            $template->addCond($name, $value);
        }

        // Render Page
        return $template->get($section);
    }

    /**
     * @param $view
     * @param bool $force
     * @return \adminpage
     */
    public function getTemplate($view, $force = false)
    {
        if (!isset($this->template[$view]) || $force) {
            // lang
            $lang = new Lang($this->config->config('language_text'), 'admin/' . $view);
            $common = new Lang($this->config->config('language_text'), 'admin');

            $this->template[$view] = new \adminpage($view, $lang, $common);
        }
        return $this->template[$view];
    }
}
