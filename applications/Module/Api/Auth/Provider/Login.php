<?php
namespace Pentagonal\TBot\Module\Api\Auth\Provider;

use Apatis\Container;
use Apatis\Http\Cookie\Cookies;
use Apatis\Prima\Service;
use Pentagonal\TBot\Module\Api\Auth\AbstractAuthModule;
use Pentagonal\TBot\Module\Core\Rest\Api;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Login
 * @package Pentagonal\TBot\Module\Api\Auth\Provider
 */
class Login extends AbstractAuthModule
{
    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function cookieLoginCheck()
    {
        /**
         * @var Cookies $cookie
         */
        $cookie = Container\Get('cookie');
        return [
            'username' => 'admin'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function routeAdder(Service $service) : Service
    {
        $c = $this;
        $service->get('[/]', function (
            ServerRequestInterface $request,
            ResponseInterface $response
        ) use($c) {
            $status = $c->cookieLoginCheck();
            if (is_array($status) && !empty($status)) {
                return Api::generate(
                    $response->withStatus(200),
                    $status
                )->toResponse();
            }

            return Api::generate($response->withStatus(401))->toResponse();
        });

        return $service;
    }
}