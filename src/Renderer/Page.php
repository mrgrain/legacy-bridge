<?php
namespace Frogsystem\Legacy\Bridge\Renderer;

use function Frogsystem\Legacy\Bridge\get_copyright;
use Frogsystem\Legacy\Bridge\Services\Config;
use Frogsystem\Legacy\Bridge\Services\Database;
use Frogsystem\Metamorphosis\Contracts\RendererInterface;

/**
 * Class Page
 * @package Frogsystem\Legacy\Bridge\Renderer
 */
class Page implements RendererInterface
{
    /**
     * applet file path
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
     * @var array
     */
    public $applets = [];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Page constructor.
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

        // set default data
        $this->data['copyright'] = get_copyright();
    }

    /**
     * Renders a view with the given data.
     * @param $view
     * @param array $data
     * @return string
     */
    public function render($view, array $data = [])
    {
        // Get Body-Template
        $template = $this->getTemplate($view);

        // extend with default data
        $data += $this->data;

        // set tags
        foreach ($data as $name => $value) {
            $template->tag($name, $value);
        }

        // Render Page
        return $this->applyFunctions(get_maintemplate((string)$template));
    }

    /**
     * @param $view
     * @return \template
     */
    protected function getTemplate($view)
    {
        $template = new \template();
        $template->setFile(strtok($view, '/'));
        $template->load(strtok('/'));
        return $template;
    }

    /**
     * @param $string
     * @return mixed
     */
    function applyFunctions($string)
    {
        global $NAV, $SNP, $APP;

        // init globals
        $NAV = array();
        $SNP = array();
        $APP = $this->applets;

        return tpl_functions($string, $this->config->cfg('system', 'var_loop'), array(), true);
    }

    /**
     * @return array
     * @throws \ErrorException
     */
    protected function loadApplets()
    {
        // Load Applets from DB
        $applet_data = $this->db->conn()->query(<<<SQL
            SELECT `applet_include`, `applet_file`, `applet_output`
            FROM `{$this->db->getPrefix()}applets`
            WHERE `applet_active` = 1
SQL
        );
        $applet_data = $applet_data->fetchAll(\PDO::FETCH_ASSOC);

        // Write Applets into Array & get Applet Template
        $new_applet_data = array();
        foreach ($applet_data as $entry) {
            // prepare data
            $entry['applet_file'] .= '.php';
            settype($entry['applet_output'], 'boolean');

            // include applets & load template
            if ($entry['applet_include'] == 1) {
                $entry['applet_template'] = $this->loadApplet($entry['applet_file'], $entry['applet_output'], array());
            }

            $new_applet_data[$entry['applet_file']] = $entry;
        }

        // Return Content
        return $new_applet_data;
    }

    /**
     * @param $file
     * @param $output
     * @param $args
     * @return string
     */
    public static function loadApplet($file, $output, $args)
    {
        // Setup $SCRIPT Var
        unset($SCRIPT, $template);
        $SCRIPT['argc'] = array_unshift($args, $file);
        $SCRIPT['argv'] = $args;

        // include applet & load template
        ob_start();
        global $FD;
        include(static::APPLET_PATH . DIRECTORY_SEPARATOR . $file);
        $return_data = ob_get_clean();

        // Early no output return
        if (!$output) {
            return '';
        }

        // set empty str
        $template = isset($template) ? $template : '';
        return ($return_data . $template);
    }
}
