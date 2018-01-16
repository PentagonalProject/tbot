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

namespace Pentagonal\TBot\Base;

use Apatis;
use Apatis\Container\Container;
use Pentagonal\TBot\Base\Interfaces\ModuleInterface;
use Composer\Autoload\ClassLoader;
use Psr\Container\ContainerInterface;

/**
 * Class AbstractModule
 * @package Pentagonal\TBot\Base
 */
abstract class AbstractModule implements ModuleInterface
{
    /**
     * @var bool
     */
    private $hasInitialize = false;

    /**
     * @var bool
     */
    private $hasProcessed  = false;

    /**
     * Module Identifier
     *
     * @var string
     */
    private $identifier;

    /**
     * Module Name
     *
     * @var string
     */
    protected $moduleName = '';

    /**
     * Module Version
     *
     * @var string
     */
    protected $moduleVersion = '';

    /**
     * Module Description
     *
     * @var string
     */
    protected $moduleDescription = '';

    /**
     * @var string
     */
    protected $moduleURL = '';

    /**
     * @var string
     */
    protected $moduleFormerName = '';

    /**
     * AbstractModule constructor.
     *
     * @param string $identifier
     */
    final private function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    final public function getModuleIdentifier() : string
    {
        return $this->identifier;
    }

    /**
     * {@inheritdoc}
     */
    final public function hasInitializedModule() : bool
    {
        return $this->hasInitialize;
    }

    /**
     * @param \ReflectionType $ref
     *
     * @return AbstractModule|array|int|null|ContainerInterface|mixed
     */
    final private function determineArgumentRefType(\ReflectionType $ref)
    {
        $refType = (string) $ref;
        switch ($refType) {
            case ContainerInterface::class:
            case Container::class:
            case \Pimple\Container::class:
                return \Apatis\Container();
            case ModuleInterface::class:
            case get_class($this):
            case AbstractModule::class:
                return $this;
            default:
                if ($ref->isBuiltin()) {
                    $type = null;
                    if ($refType && $refType !== 'array') {
                        $type = 1;
                        settype($type, $refType);
                    } elseif ($refType === 'array') {
                        $type = [];
                    }
                    return $type;
                }
        }

        return null;
    }

    /**
     * Register NameSpace on Module
     *
     * @param bool $allowEmptyNameSpace
     */
    protected function registerModuleDirectoryNameSpaceAutoloadFactory(bool $allowEmptyNameSpace = false)
    {
        $ref  = new \ReflectionClass($this);
        $nameSpace = $ref->getNamespaceName();
        if (! $allowEmptyNameSpace && (!$nameSpace || $nameSpace === '\\')) {
            return;
        }

        $nameSpace = rtrim($nameSpace, '\\') . '\\';
        /**
         * @var ClassLoader $autoloader
         */
        $autoloader = Apatis\Container()['autoload'];
        $psr4 = $autoloader->getPrefixesPsr4();
        $path = dirname($ref->getFileName());
        if (isset($psr4[$nameSpace]) && is_array($psr4[$nameSpace])) {
            foreach ($psr4[$nameSpace] as $dir) {
                $spl = new \SplFileInfo($dir);
                if ($spl->getRealPath() === $path) {
                    $exist = true;
                    break;
                }
            }
        }

        if (!isset($exist)) {
            $autoloader->addPsr4($nameSpace, $path);
            $autoloader->register();
        }
    }

    /**
     * @param string $method method to be pass
     *
     * @return ModuleInterface
     */
    private function callMethodModuleInstance(string $method) : ModuleInterface
    {
        if (!method_exists($this, $method)) {
            return $this;
        }

        $ref                 = new \ReflectionClass($this);
        if ($ref->hasMethod($method) && ! $ref->getMethod($method)->isPrivate()) {
            $objectMethod = $ref->getMethod($method);
            unset($ref);
            /**
             * @var \ReflectionParameter[] $methodParams
             */
            $methodParams = $objectMethod->getParameters();
            if (count($methodParams) === 0) {
                $this->$method($this);
                return $this;
            }
            if (count($methodParams) === 2) {
                $args0 = $methodParams[0];
                $args1 = $methodParams[1];
                $param0 = null;
                if ($args0->hasType()) {
                    $param0 = $this->determineArgumentRefType($args0->getType());
                }
                $param1 = null;
                if ($args1->hasType()) {
                    $param1 = $this->determineArgumentRefType($args1->getType());
                }
                if ($param0 === null) {
                    if (!$args0->allowsNull()) {
                        return $this;
                    }
                }
                if ($param1 === null) {
                    if (!$args1->allowsNull() && !$args1->isOptional()) {
                        return $this;
                    }
                }

                if ($param0 !== null && $param1 !== null) {
                    $this->$method($param0, $param1);
                    return $this;
                }

                if ($args1->allowsNull() || $param1 !== null) {
                    $this->$method($param0, $param1);
                } elseif ($args1->isOptional()) {
                    $this->$method($param0);
                }
                unset($param1, $param0, $args1, $args0);
                return $this;
            }

            if (count($methodParams) > 1) {
                return $this;
            }

            $methodParams = $methodParams[0];
            $hasInit = false;
            if ($methodParams->hasType()) {
                $type = $this->determineArgumentRefType($methodParams->getType());
                if ($type !== null || $methodParams->allowsNull()) {
                    $hasInit = true;
                    $this->$method($type);
                } elseif ($methodParams->isOptional()) {
                    $hasInit = true;
                    $this->$method();
                }
            }

            if (!$hasInit) {
                if ($methodParams->allowsNull()) {
                    $null = null;
                    $this->$method($null);
                } elseif ($methodParams->isOptional()) {
                    $this->$method();
                }
            }

            unset($methodParams);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    final public function initializeModule() : ModuleInterface
    {
        if ($this->hasInitialize) {
            return $this;
        }
        $result = $this->callMethodModuleInstance('onInit');
        $this->hasInitialize = true;
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    final public function processModule()
    {
        $this->initializeModule();

        if ($this->hasProcessed) {
            return $this;
        }

        $result = $this->callMethodModuleInstance('onProcess');
        $this->hasProcessed = true;
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    final public function hasProcessedModule(): bool
    {
        return (bool) $this->hasProcessed;
    }

    /**
     * @param string $identifier
     *
     * @return ModuleInterface
     */
    final public static function create(string $identifier) : ModuleInterface
    {
        $object = new static($identifier);
        return $object;
    }

    /**
     * @return string
     */
    public function getModuleURL() : string
    {
        return $this->moduleURL;
    }

    /**
     * Module Former Name
     * This suitable for API Response
     *
     * @return string
     */
    public function getModuleFormerName() : string
    {
        return $this->moduleFormerName;
    }

    /**
     * {@inheritdoc]
     */
    public function getModuleName() : string
    {
        if ($this->moduleName === '') {
            $this->moduleName = preg_replace_callback(
                '/[A-Z\\\_]/',
                function ($c) {
                    return strtoupper($c[0]) . ' ';
                },
                get_class($this)
            );
        }

        return $this->moduleName;
    }

    /**
     * {@inheritdoc]
     */
    public function getModuleDescription() : string
    {
        return $this->moduleDescription;
    }

    /**
     * {@inheritdoc]
     */
    public function getVersion()
    {
        return $this->moduleVersion;
    }
}
