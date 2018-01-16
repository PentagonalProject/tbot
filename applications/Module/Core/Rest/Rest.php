<?php
/**
 * MIT License
 *
 * Copyright (c) 2018 Block Tech Indonesia
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);

namespace Pentagonal\TBot\Module\Core\Rest;

use Apatis;
use Apatis\Container;
use Pentagonal\TBot\Base\AbstractModule;
use Pentagonal\TBot\Base\Constant;
use Pentagonal\TBot\Base\ModuleReader;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Rest
 * @package Pentagonal\TBot\Module\Core\Rest
 */
class Rest extends AbstractModule
{
    /**
     * @var string
     */
    protected static $prefixApiPattern = '/api';

    /**
     * Module Name
     *
     * @var string
     */
    protected $moduleName = 'Rest Core Module';

    /**
     * Module Version
     *
     * @var string
     */
    protected $moduleVersion = '1.0';

    /**
     * Module Description
     *
     * @var string
     */
    protected $moduleDescription = 'Core Module For Handle API';

    /**
     * @var string
     */
    protected $moduleURL = 'https://Pentagonal\TBot.co.id/';

    /**
     * @var string
     */
    protected $moduleFormerName = 'Rest Module Handler';

    /**
     * @var Rest
     */
    private static $instance;

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

        self::$instance = $this;
    }

    /**
     * @return Rest
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function &getInstance() : Rest
    {
        if (self::$instance instanceof Rest) {
            return self::$instance;
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $module = Container\Get(Constant::MODULE_CORE);
        $name = $module->normalizeModuleIdentifier(basename(__DIR__));
        $module = $module->getModule($name);
        /** @noinspection PhpUndefinedMethodInspection */
        $module->initializeModule();
        self::$instance = $module;
        return $module;
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
            $prefix = static::$prefixApiPattern;
            $c = $this;
            Apatis\Service()->group($prefix, function (Apatis\Prima\Service $service) use ($c) {
                if (!Container\Exist(Constant::MODULE_API)) {
                    return;
                }

                $apiModule    = Container\Get(Constant::MODULE_API);
                if (!$apiModule instanceof ModuleReader) {
                    return;
                }

                foreach ($apiModule->getModules() as $module) {
                    if (!$module instanceof AbstractModuleAPI) {
                        continue;
                    }

                    $module->setService($service);
                    $module->processModule();
                }

                $service->map(
                    $service->getStandardHttpRequestMethods(),
                    '[/]',
                    function (ServerRequestInterface $request, ResponseInterface $response) {
                        return Api::generate($response->withStatus(404))->toResponse();
                    }
                );
            });
        }
    }
}
