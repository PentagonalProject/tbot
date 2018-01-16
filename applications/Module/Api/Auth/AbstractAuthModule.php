<?php
namespace Pentagonal\TBot\Module\Api\Auth;

use Apatis\Prima\Service;
use Pentagonal\TBot\Module\Core\Rest\ProviderModule;

/**
 * Class AbstractAuthModule
 * @package Pentagonal\TBot\Module\Api\Auth
 */
abstract class AbstractAuthModule extends ProviderModule
{
    /**
     * Route Mapping
     *
     * @param Service $service
     *
     * @return Service
     */
    abstract protected function routeAdder(Service $service) : Service;

    /**
     * {@inheritdoc}
     */
    final public function __invoke(
        Service $service
    ): Service {
        return $this->routeAdder($service);
    }
}
