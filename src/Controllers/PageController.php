<?php
namespace Frogsystem\Legacy\Bridge\Controllers;

use Frogsystem\Legacy\Bridge\Services\Config;
use Frogsystem\Metamorphosis\Response\View;
use Psr\Http\Message\ResponseInterface;

/**
 * Class PageController
 * @package Frogsystem\Legacy\Bridge\Controllers
 */
abstract class PageController
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $pagePath;

    /**
     * PageController constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Display a Page file
     * @param View $view
     * @return ResponseInterface
     */
    public function page(View $view)
    {
        return $this->display($view, $this->getPageContent($this->config->cfg('goto')));
    }

    /**
     * Getter for page path.
     * @return string
     */
    public function getPagePath()
    {
        return $this->pagePath;
    }

    /**
     * Get content from a legacy page file.
     * @param $page
     * @return string
     */
    protected function getPageContent($page)
    {
        // Display Content
        $template = '';

        // Page file
        global $FD;
        include($this->getPagePath() . DIRECTORY_SEPARATOR . $page . ".php");

        // Return Content
        return $template;
    }

    /**
     * @param View $view
     * @param string $content Content to be displayed
     * @return ResponseInterface
     */
    protected function display(View $view, $content)
    {
        // Display Page
        return $view->render('0_main.tpl/MAIN', [
            'content' => $content,
        ]);
    }
}
