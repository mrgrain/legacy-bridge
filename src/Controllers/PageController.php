<?php
namespace Frogsystem\Legacy\Bridge\Controllers;

use Frogsystem\Metamorphosis\Response\View;
use Psr\Http\Message\ResponseInterface;

/**
 * Class PageController
 * @package Frogsystem\Legacy\Bridge\Controllers
 */
abstract class PageController
{
    /**
     * @param View $view
     * @param string $content Content to be displayed
     * @return ResponseInterface
     */
    function display(View $view, $content)
    {
        // Display Page
        return $view->render('0_main.tpl/MAIN', [
            'content' => $content,
        ]);
    }
}
