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

namespace Pentagonal\TBot {

    use Apatis;
    use Apatis\Config;
    use Apatis\Container;
    use Apatis\Http\Message\Request;
    use Apatis\Http\Message\Response;
    use Apatis\Http\Message\Stream;
    use Apatis\Service;
    use Composer\Autoload\ClassLoader;
    use Psr\Container\ContainerExceptionInterface;
    use Psr\Http\Message\ResponseInterface;

    // require vendor
    require __DIR__ . '/../vendor/autoload.php';

    /**
     * @param \Throwable $e
     *
     * @return ResponseInterface
     */
    $errorResponseHandler = function ($e): ResponseInterface {
        $response    = Apatis\Response() ?: new Response(500, [], new Stream(fopen(
            (
            defined('STREAM_USE_MEMORY') && STREAM_USE_MEMORY
                ? 'php://memory'
                : 'php://temp'
            ),
            'r+'
        )));
        $handler     = Service\GetErrorHandler();
        $contentType = $response->getHeaderLine('Content-Type');
        if ($contentType !== '') {
            $handler->setContentType($contentType);
        }

        return Apatis\Service()->serveResponse(
            $handler(Request::createFromGlobalsResolveCLIRequest($_SERVER), $response, $e)
        );
    };

    Apatis\Service()->setConfiguration('displayErrors', true);
    Apatis\Service()->setConfiguration('dispatchRouteBeforeMiddleware', false);
    try {
        Apatis\Service()->setConfig(Apatis\Config(Config\FromFile(__DIR__ . '/../config.yaml')));
        define('STREAM_USE_MEMORY', ! (Config\Get('useMemory', false)));
        // add Service instance
        Container\Set('service', function () {
            return Apatis\Service();
        });
        // add autoload
        Container\Set('autoload', function (): ClassLoader {
            return require __DIR__ . '/../vendor/autoload.php';
        });
        // mutable
        (function () {
            // check API Middleware
            if (! file_exists(__DIR__ . '/../components/Middleware.php')) {
                throw new \RuntimeException(
                    "API Middleware file does not exists!"
                );
            }
            if (file_exists(__DIR__ . '/../components/Containers.php')) {
                require __DIR__ . '/../components/Containers.php';
            }

            // Require Middleware
            require __DIR__ . '/../components/Middleware.php';
        })->bindTo(null)->__invoke();
    } catch (ContainerExceptionInterface $e) {
        return $errorResponseHandler($e);
    } catch (\Exception $e) {
        return $errorResponseHandler($e);
    } catch (\Throwable $e) {
        return $errorResponseHandler($e);
    }

    // serve
    return Service\Serve();
}
