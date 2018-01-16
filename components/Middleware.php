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

namespace Pentagonal\TBot;

use Apatis;
use Apatis\Config;
use Apatis\Service;
use Apatis\Container;
use Pentagonal\DatabaseDBAL\Database;
use Pentagonal\TBot\Base\ModuleReader;
use Pentagonal\TBot\Base\Constant;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

if (!function_exists('Apatis\Config\Set')) {
    return;
}

// enable Shutdown Handler
Config\Set('handleShutdown', true);
// sorting Middleware
Config\Set('sortMiddleware', true);
// fix proxy
Config\Set('fixProxy', true);
// disable dispatch route Before Middleware
Config\Set('dispatchRouteBeforeMiddleware', false);

/* -------------------------------------------------------
 * Start Middleware
 * -------------------------------------------------------
 */

/**
 * Middleware to default conversion as JSON content type Response
 */
Service\AddMiddleware(function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {
    if (preg_match('/^\/api(?:\/(?:.+)?)?$/', $request->getUri()->getPath())) {
        /**
         * Set Default Response
         */
        $jsonContentType = 'application/json;charset=utf-8';
        Service\GetNotFoundHandler()->setContentType($jsonContentType);
        Service\GetNotAllowedHandler()->setContentType($jsonContentType);
        Service\GetErrorHandler()->setContentType($jsonContentType);
        Service\GetExceptionHandler()->setContentType($jsonContentType);
        // set Content-Type Header
        $response = $response->withHeader('Content-Type', $jsonContentType);
    }

    return $next($request, Apatis\Response($response));
});

/**
 * Middleware
 *
 * 2. load & process module
 */
Service\AddMiddleware(function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {
    Container\Set('database', function(ContainerInterface $c) {
        $config = Config\Get('database');
        $config = $config ? $config->toArray() : [];
        return new Database($config);
    });

    // set cookie container
    Container\Set('cookie', function() use ($request) {
        return new Apatis\Http\Cookie\Cookies($request->getCookieParams());
    });

    /**
     * Modules Loader
     */
    Container\Set(Constant::MODULE_CORE, function () : ModuleReader {
        $module = new ModuleReader(__DIR__ .'/../applications/Module/Core');
        return $module;
    });
    Container\Set(Constant::MODULE_API, function () : ModuleReader {
        $module = new ModuleReader(__DIR__ .'/../applications/Module/Api');
        return $module;
    });

    /**
     * @var ModuleReader $coreModule
     * @var ModuleReader $moduleAPI
     */
    $coreModule = Container\Get(Constant::MODULE_CORE);
    $moduleAPI = Container\Get(Constant::MODULE_API);
    // scan & load core module & then load API
    $coreModule->scan()->loadAllModules();
    $moduleAPI->scan()->loadAllModules();

    // process core Module
    $coreModule->processAllModules();
    // process API Module
    $moduleAPI->processAllModules();

    return $next($request, Apatis\Response($response));
});
