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

use Pentagonal\TBot\Base\Exceptions\ModuleDoesNotExistsException;
use Pentagonal\TBot\Base\Interfaces\ModuleInterface;

/**
 * Class ModuleReader
 * @package Pentagonal\TBot\Base
 */
class ModuleReader implements \ArrayAccess
{
    /**
     * @var array
     */
    private static $cachedModules = [];

    /**
     * @var string
     */
    protected $modulePath;

    /**
     * ModuleReader constructor.
     *
     * @param string $modulePath
     */
    public function __construct(string $modulePath)
    {
        $spl = new \SplFileInfo($modulePath);
        if (!$spl->isDir()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Module path %s is not directory',
                    $modulePath
                )
            );
        }

        $this->modulePath = $spl->getRealPath();
    }

    /**
     * @return string
     */
    public function getModulePath() : string
    {
        return $this->modulePath;
    }

    /**
     * @return array[]
     */
    public function getAllCachedModulesData() : array
    {
        $this->scan();
        return self::$cachedModules;
    }

    /**
     * @return array[]
     */
    public function getModulesData() : array
    {
        return $this->getAllCachedModulesData()[$this->modulePath];
    }

    /**
     * @return array|ModuleInterface[]|AbstractModule[]
     */
    public function getModules() : array
    {
        return $this->getModulesData()['modules'];
    }

    /**
     * Scan Module Directory
     * @return ModuleReader
     */
    final public function scan() : ModuleReader
    {
        if (isset(self::$cachedModules[$this->modulePath])
            && is_array(self::$cachedModules[$this->modulePath])
            && isset(self::$cachedModules[$this->modulePath]['modules'])
            && is_array(self::$cachedModules[$this->modulePath])
        ) {
            return $this;
        }

        $modules = [
            'ignored'    => [],
            'identifier' => [],
            'modules'    => [],
        ];

        foreach (new \DirectoryIterator($this->modulePath) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            $path = $fileInfo->getRealPath();
            $name = $fileInfo->getBasename();
            $moduleName = $this->normalizeModuleIdentifier($name);
            $moduleFile = $path . DIRECTORY_SEPARATOR . $name . '.php';
            if (!$fileInfo->isDir() || !file_exists($moduleFile)) {
                $modules['ignored'][$name] = $moduleName;
                continue;
            }

            $spl = new \SplFileInfo($moduleFile);
            if (!$spl->isReadable() || $spl->getSize() < 10 || $spl->getSize() > 102400) {
                $modules['ignored'][$name] = $moduleName;
                continue;
            }

            // ignore same directory
            if (isset($modules['modules'][$moduleName])) {
                $modules['ignored'][$name] = $moduleName;
                continue;
            }

            $content = php_strip_whitespace($moduleFile);
            if (!preg_match('~^\<\?php~', $content)) {
                $modules['ignored'][$name] = $moduleName;
                continue;
            }

            preg_match_all(
                '`
                (?:namespace\s+(?P<namespace>[^\;]+));
                | (?:class\s+(?P<class>[^\s+]+))
                `xmsi',
                $content,
                $match
            );

            $match = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);
            $match = array_map('reset', array_map('array_filter', $match));
            if (empty($match['class'])) {
                $modules['ignored'][$name] = $moduleName;
                continue;
            }
            $nameSpace = '';
            if (!empty($match['namespace'])) {
                $nameSpace = ltrim($match['namespace'], '\\');
            }

            $class = $nameSpace . '\\'. $match['class'];
            if (@class_exists($class)) {
                $ref = new \ReflectionClass($class);
                if ($ref->getFileName() !== $spl->getRealPath()) {
                    $modules['ignored'][$name] = $moduleName;
                    continue;
                }
                if (!$ref->isSubclassOf(ModuleInterface::class)
                    // module never instantiable
                    || $ref->isInstantiable()
                ) {
                    $modules['ignored'][$name] = $moduleName;
                    continue;
                }

                $modules['identifier'][$moduleName] = $name;
                $modules['modules'][$moduleName] = call_user_func_array(
                    [$class, 'create'],
                    [$moduleName]
                );

                continue;
            }

            try {
                set_error_handler(function () {
                    throw new \RuntimeException();
                });
                /** @noinspection PhpIncludeInspection */
                include_once $moduleFile;
            } catch (\Throwable $e) {
                restore_error_handler();
                $modules['ignored'][$name] = $moduleName;
                continue;
            }

            restore_error_handler();
            if (!@class_exists($class)) {
                $modules['ignored'][$name] = $moduleName;
                continue;
            }

            $ref = new \ReflectionClass($class);
            if ($ref->getFileName() !== $spl->getRealPath()) {
                $modules['ignored'][$name] = $moduleName;
                continue;
            }
            if (!$ref->isSubclassOf(ModuleInterface::class)
                // module never instantiable
                || $ref->isInstantiable()
            ) {
                $modules['ignored'][$name] = $moduleName;
                continue;
            }

            $modules['identifier'][$moduleName] = $name;
            $modules['modules'][$moduleName] = call_user_func_array(
                [$class, 'create'],
                [$moduleName]
            );
        }

        self::$cachedModules[$this->modulePath] = $modules;
        return $this;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function normalizeModuleIdentifier(string $name) : string
    {
        return strtolower($name);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasModule(string $name) : bool
    {
        $modules = $this->getModules();
        return isset($modules[$this->normalizeModuleIdentifier($name)]);
    }

    /**
     * @param string $name
     *
     * @return ModuleInterface|AbstractModule
     */
    public function getModule(string $name) : ModuleInterface
    {
        if (!$this->hasModule($name)) {
            throw new ModuleDoesNotExistsException(
                sprintf(
                    'Module %s has not found',
                    $name
                )
            );
        }

        return $this->getModules()[$this->normalizeModuleIdentifier($name)];
    }

    /**
     * Load all Modules
     */
    public function loadAllModules()
    {
        /**
         * @var ModuleInterface $module
         */
        foreach ($this->getModules() as $key => $module) {
            if (!$module->hasInitializedModule()) {
                $module->initializeModule();
            }
        }
    }

    /**
     * Process All Modules
     */
    public function processAllModules()
    {
        /**
         * @var ModuleInterface $module
         */
        foreach ($this->getModules() as $key => $module) {
            if (!$module->hasProcessedModule()) {
                $module->processModule();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset) : bool
    {
        return $this->hasModule($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->getModule($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        // pass
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        // pass
    }
}
