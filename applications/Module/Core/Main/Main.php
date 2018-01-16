<?php
namespace Pentagonal\TBot\Module\Core\Main;

use Apatis\Route;
use Pentagonal\TBot\Base\AbstractModule;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Main
 * @package Pentagonal\TBot\Module\Core
 */
class Main extends AbstractModule
{
    /**
     * @var View
     */
    protected $view;

    /**
     * On init
     */
    public function onInit()
    {
        static $init;
        if (!isset($init)) {
            $this->registerModuleDirectoryNameSpaceAutoloadFactory();
            $init = true;
        }
    }

    /**
     * @return View
     */
    public function getView() : View
    {
        if (!isset($this->view)) {
            $this->view = new View(__DIR__ .'/Views');
        }
        return $this->view;
    }
    /**
     * On Process
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function onProcess()
    {
        if (!$this->hasProcessedModule()) {
            $c = $this;
            Route\Any('[/]', function (
                ServerRequestInterface $request,
                ResponseInterface $response
            ) use ($c) {
                return $c
                    ->getView()
                    ->render(
                        'Main.phtml',
                        $request,
                        $response
                    );
            });
        }
    }
}
