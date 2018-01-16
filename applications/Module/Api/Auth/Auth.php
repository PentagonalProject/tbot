<?php
namespace Pentagonal\TBot\Module\Api\Auth;

use Apatis\Prima\Service;
use Pentagonal\TBot\Module\Core\Rest\AbstractModuleAPI;

/**
 * Class Auth
 * @package Pentagonal\TBot\Module\Api\Auth
 */
class Auth extends AbstractModuleAPI
{
    /**
     * Register Route Group
     *
     * @return void
     */
    protected function registerRouteForGroupRoute()
    {
        $this->baseRegisterRouteForGroupRoute(
            __DIR__ . DIRECTORY_SEPARATOR . 'Provider',
            AbstractAuthModule::class,
            __NAMESPACE__ .'\\Provider\\'
        );
    }

    /**
     * @param string $identifier
     * @param $module
     *
     * @return string identifier
     */
    public function registerAPI(string $identifier, $module) : string
    {
        return $this->baseRegisterAPI($identifier, $module, AbstractAuthModule::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupRouteIdentifier() : string
    {
        return $this->getModuleIdentifier();
    }

    /**
     * @param Service $service
     */
    protected function routeForAuth(Service $service)
    {
        // pass
    }

    /**
     * Call On Process
     */
    protected function onProcess()
    {
        if (!$this->hasProcessedModule()) {
            $this->baseOnProcess(null, function (Service $arg) {
                $this->routeForAuth($arg);
            });
        }
    }

    /**
     * Magic Method Destruct
     */
    public function __destruct()
    {
        $this->registeredAPI = [];
    }
}
