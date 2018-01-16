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
use Pentagonal\TBot\Base\AbstractModule;

/**
 * Class AbstractModuleAPI
 * @package Pentagonal\TBot\Module\Core\Rest
 *
 * Base API Helper Module
 */
abstract class AbstractModuleAPI extends AbstractModule
{
    const ENDPOINT_KEY          = Api::ENDPOINT_KEY;
    const FORMER_NAME_KEY       = Api::FORMER_NAME_KEY;
    const OFFICIAL_URL_KEY      = Api::OFFICIAL_URL_KEY;

    /**
     * @var array
     */
    protected $availableAPI = [];

    /**
     * @var array|object[]
     */
    protected $registeredAPI = [];

    /**
     * @var bool
     */
    private $initialize = false;

    /**
     * @var Apatis\Prima\Service
     */
    protected $service;

    /**
     * @param Apatis\Prima\Service $service
     */
    public function setService(Apatis\Prima\Service $service)
    {
        $this->service = $service;
    }

    /**
     * on Init
     */
    protected function onInit()
    {
        static $init;
        if (!isset($init)) {
            $this->registerModuleDirectoryNameSpaceAutoloadFactory();
            $init = true;
        }

        if ($this->initialize) {
            return;
        }

        $this->initialize = true;
        $this->registerRouteForGroupRoute();
    }

    /**
     * Base Helper Register Group Route
     *
     * @param string $directory
     * @param string $abstractName
     * @param string $namespaceClass
     */
    protected function baseRegisterRouteForGroupRoute(string $directory, string $abstractName, string $namespaceClass)
    {
        if (! is_dir($directory)) {
            return;
        }

        $namespaceClass = rtrim($namespaceClass, '\\');
        foreach (new \DirectoryIterator($directory) as $fInfo) {
            if ($fInfo->isDot()
                || ! $fInfo->getFileInfo()->isFile()
                || $fInfo->getExtension() !== 'php'
            ) {
                continue;
            }

            $baseName = pathinfo($fInfo->getBasename(), PATHINFO_FILENAME);
            if (preg_match('/[^a-zA-Z\_]/', $baseName)) {
                continue;
            }

            $className = $namespaceClass .'\\'. $baseName;
            try {
                $ref = new \ReflectionClass($className);
                if (!$ref->isSubclassOf($abstractName)) {
                    continue;
                }
            } catch (\Exception $e) {
                continue;
            }

            $identifier = $this->normalizeIdentifier($baseName);
            if (isset($this->availableAPI[$identifier])) {
                continue;
            }
            $this->availableAPI[$identifier] = $className;
        }

        foreach ($this->availableAPI as $key => $value) {
            if (!$this->isRegistered($key)) {
                $this->registerAPI($key, $value);
            }
        }
    }

    /**
     * Register API Group
     *
     * @return mixed
     */
    abstract protected function registerRouteForGroupRoute();

    /**
     * Sanitize Identifier
     *
     * @param string $identifier
     *
     * @return bool|string
     */
    public function normalizeIdentifier(string $identifier)
    {
        $identifier = strtolower($identifier);
        return Api::normalizeApiIdentifier($identifier);
    }

    /**
     * @param string $identifier
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isRegistered(string $identifier) : bool
    {
        $identifier = $this->normalizeIdentifier($identifier);
        if ($identifier === false) {
            $className = explode('\\', get_class($this));
            throw new \InvalidArgumentException(
                'Invalid identifier detected for object %s',
                end($className)
            );
        }

        return isset($this->registeredAPI[$identifier]);
    }

    /**
     * @return array|object[]|AbstractProviderModule[]
     */
    public function getRegisteredAPI() : array
    {
        return $this->registeredAPI;
    }

    /**
     * @param string $identifier
     * @return mixed|AbstractProviderModule
     */
    public function getAPI(string $identifier)
    {
        if ($this->isRegistered($identifier)) {
            $identifier = $this->normalizeIdentifier($identifier);
            return $this->registeredAPI[$identifier];
        }

        throw new ApiNotFoundException(
            $identifier
        );
    }

    /**
     * @param string $identifier
     * @param $module
     *
     * @return string the identifier
     */
    abstract public function registerAPI(string $identifier, $module) : string;

    /**
     * @param string $identifier
     * @param object|string $module
     * @param string $moduleBaseClass
     *
     * @return string
     *
     * @throws IdentifierExistsException
     * @throws InvalidModuleException
     * @throws \InvalidArgumentException
     */
    public function baseRegisterAPI(string $identifier, $module, string $moduleBaseClass)
    {
        $identifier = $this->normalizeIdentifier($identifier);
        if ($this->isRegistered($identifier)) {
            throw new IdentifierExistsException($identifier);
        }

        if (!is_string($module) && !is_object($module)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Argument 2 must be as a string or object instance of %s and %s given',
                    $moduleBaseClass,
                    gettype($module)
                )
            );
        }

        if (is_string($module)) {
            try {
                $ref = new \ReflectionClass($module);
                if (!$ref->isSubclassOf($moduleBaseClass)) {
                    throw new InvalidModuleException(
                        sprintf(
                            'Module %s is not an instance of %s',
                            $module,
                            $moduleBaseClass
                        )
                    );
                }
            } catch (InvalidModuleException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw new InvalidModuleException(
                    $e->getMessage()
                );
            }
            $module = new $module($this);
        } elseif (!is_subclass_of($module, $moduleBaseClass)) {
            throw new InvalidModuleException(
                sprintf(
                    'Module %s is not an instance of %s',
                    get_class($module),
                    $moduleBaseClass
                )
            );
        }

        if (method_exists($module, 'setObject')) {
            $mod = $module->setObject($this);
            if (is_object($mod) && get_class($mod) === get_class($module)) {
                $module = $mod;
            }
        }

        $this->registeredAPI[$identifier] = $module;
        return $identifier;
    }

    /**
     * @return string
     */
    abstract public function getGroupRouteIdentifier() : string;

    /**
     * Base On Process Helper
     * This must be called once! with same group route
     *
     * @param string $groupRoute
     * @param string $message
     * @param \closure $callBackGroupCreated
     *
     * @return Apatis\Route\RouteGroupInterface
     */
    protected function baseOnProcess(
        string $groupRoute = null,
        \closure $callBackGroupCreated = null
    ) : Apatis\Route\RouteGroupInterface {
        $groupRoute = $groupRoute?: $this->getGroupRouteIdentifier();
        $groupRoute = trim($groupRoute, '//');
        $groupRoute = '/'. $groupRoute;
        $c = $this;
        return $this->service->group(
            $groupRoute,
            function (Apatis\Prima\Service $service) use (
                &$c,
                $groupRoute,
                $callBackGroupCreated
            ) {
                foreach ($c->getRegisteredAPI() as $identifier => $object) {
                    $prefix = '/' . ltrim($identifier, '/');
                    $service->group($prefix, $object);
                }
                if (is_callable($callBackGroupCreated)) {
                    $callBackGroupCreated($this);
                }
            }
        );
    }

    /**
     * @return void
     */
    abstract protected function onProcess();
}
