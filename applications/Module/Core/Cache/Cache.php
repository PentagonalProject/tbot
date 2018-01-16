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

namespace Pentagonal\TBot\Module\Core\Cache;

use Apatis\Config;
use Apatis\Container;
use Pentagonal\TBot\Base\AbstractModule;
use Pentagonal\TBot\Base\Constant;
use Pentagonal\TBot\Base\Path;
use Pentagonal\TBot\Module\Core\Cache\Psr\InvalidArgumentException;
use Pentagonal\TBot\Module\Core\Cache\Psr\SimpleCache;
use phpFastCache\CacheManager;
use phpFastCache\Core\Item\ExtendedCacheItemInterface;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;

/**
 * Class Cache
 * @package Pentagonal\TBot\Module\Core\Cache
 *
 * @method static mixed get(string $key, mixed $default = null)
 * @method static ExtendedCacheItemInterface getItem(string $key)
 * @method static bool set(string $key, mixed $value, int|\DateInterval|null $ttl = null)
 * @method static bool delete(string $key)
 * @method static bool clear()
 * @method static mixed getMultiple(iterable $keys, mixed $default = null)
 * @method static bool setMultiple(iterable $values, int|\DateInterval|null $ttl = null)
 * @method static bool deleteMultiple(iterable $keys)
 * @method static bool has(string $key)
 * @method static void setItemPool(ExtendedCacheItemPoolInterface $cacheManager)
 * @method static ExtendedCacheItemPoolInterface getItemPool()
 */
class Cache extends AbstractModule
{
    /**
     * Default Driver is Files of not exists Redis
     */
    const DEFAULT_DRIVER = 'Files';

    /**
     * Prefix For Unique Key
     */
    const PREFIX_KEY = 'tbot_';

    /**
     * @var string
     */
    protected $moduleName  = 'Cache';

    /**
     * @var string
     */
    protected $moduleVersion = '1.0';

    /**
     * @var string
     */
    protected $moduleURL = 'https://www.pentagonal.org/';

    /**
     * @var string
     */
    protected $moduleDescription = 'Module for cache handler';

    /**
     * @var string
     */
    protected $moduleFormerName = 'Cache Handler';

    /**
     * @var string
     */
    protected $selectedDriver;

    /**
     * @var SimpleCache
     */
    private $simpleCache;

    /**
     * @var Config\ConfigInterface
     */
    protected $config;

    /**
     * @var Cache
     */
    private static $instance;

    /**
     * @var bool
     */
    protected $useCache;

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
     * Helper To Create Unique
     *
     * @param string|mixed $key
     * @param string $prefix
     *
     * @return string
     */
    public static function createUniqueKey($key, string $prefix = null) : string
    {
        $prefix = $prefix === null ? static::PREFIX_KEY : $prefix;
        if (!is_string($key)) {
            $key = serialize($key);
        }

        return $prefix . sha1((string) $key);
    }

    /**
     * @return bool
     */
    public function isUseCache() : bool
    {
        if (is_bool($this->useCache)) {
            return $this->useCache;
        }
        $this->useCache = !(Config\Get('useCache') === false);
        return $this->useCache;
    }

    /**
     * @return Config\ConfigInterface
     * @throws InvalidArgumentException
     */
    public function getConfig() : Config\ConfigInterface
    {
        if (isset($this->config)) {
            return $this->config;
        }

        $config = Config\Get('cache');
        if (! $config instanceof Config\ConfigInterface
            || ! is_string($config->get('driver'))
        ) {
            if (extension_loaded('redis') && class_exists('Redis')) {
                $configTemp = Config\FromArray([
                    'driver' => 'Redis',
                    'host' => '127.0.0.1'
                ]);
                $config = $config ?: Config\FromArray([]);
                $configTemp->merge($config);
                try {
                    $r = new \Redis();
                    if ($r->connect($configTemp['host'])) {
                        $this->selectedDriver = 'Redis';
                        $r->close();
                        Config\Merge($configTemp);
                        $this->config = $configTemp;
                        $this->selectedDriver = $this->config['driver'];
                        return $this->config;
                    }
                } catch (InvalidArgumentException $e) {
                    // test
                }
            }
        }

        if (!$config instanceof Config\ConfigInterface) {
            $config = Config\FromArray([]);
        }
        $driver = !isset($config['driver']) || !is_string($config['driver'])
            ? self::DEFAULT_DRIVER
            : $config['driver'];
        $hasConfig = isset($config['driver']);
        $config['driver'] = self::resolveDriver($driver);
        $this->config = $config;
        if ($config['driver'] === 'Files') {
            // default cache path
            $cachePath = Path::fromRoot('storage/cache/');
            $path  = $config['path'];
            if (is_string($path)) {
                // if windows and is not a full path
                if (strpos(PHP_OS, 'win') && ! preg_match('/^[A-Z]+\:/i', Path::resolveSeparator($path))
                    || substr($path, 0) !== '/'
                ) {
                    $path = Path::fromRoot($path);
                }
            }

            $path = !is_string($path) ? $cachePath : $path;
            if (file_exists($path)) {
                if (!is_dir($path)) {
                    throw new InvalidArgumentException(
                        'Cache path can not a directory'
                    );
                }
                if (!is_writable($path)) {
                    if (!$hasConfig) {
                        $config->clear();
                        $config['driver'] = self::resolveDriver('Devnull');
                    } else {
                        throw new InvalidArgumentException(
                            'Cache path is not writable'
                        );
                    }
                }
            } elseif (!is_writable(dirname($path))) {
                throw new InvalidArgumentException(
                    'Parent cache path directory is not writable'
                );
            }

            if ($config['driver'] === 'Files') {
                $config = $config->set('path', $path);
            }

            $this->selectedDriver = $this->config['driver'];
            $this->config         = $config;
        }

        return $this->config;
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \RedisException
     * @throws \phpFastCache\Exceptions\phpFastCacheDriverCheckException
     * @throws \phpFastCache\Exceptions\phpFastCacheInvalidConfigurationException
     */
    protected function initCache()
    {
        if (!isset($this->simpleCache)) {
            $config = $this->getConfig();
            /**
             * @var ExtendedCacheItemPoolInterface $cachePool
             */
            $cachePool = CacheManager::getInstance(
                $config['driver'],
                $config->toArray()
            );

            if ($this->isUseCache()) {
                try {
                    // test item
                    $cachePool->detachItem($cachePool->getItem('test'));
                } catch (\RedisException $e) {
                    throw $e;
                }
            }

            $this->simpleCache = new SimpleCache($cachePool);
            self::$instance =& $this;
        }
    }

    /**
     * @return SimpleCache
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \RedisException
     * @throws \phpFastCache\Exceptions\phpFastCacheDriverCheckException
     * @throws \phpFastCache\Exceptions\phpFastCacheInvalidConfigurationException
     */
    public function getSimpleCache() : SimpleCache
    {
        if (!isset($this->simpleCache)) {
            $this->initCache();
        }
        return $this->simpleCache;
    }

    /**
     * @param string $driverName
     * @param string $default
     *
     * @return int|string
     */
    public static function resolveDriver(
        string $driverName,
        string $default = self::DEFAULT_DRIVER
    ) {
        preg_match('`
            (?P<Predis>Predis)
            |(?P<Redis>Redis)
            |(?P<Apcu>Apcu)
            |(?P<Apc>Apc)
            |(?P<Cassandra>Cas?san)
            |(?P<Couchbase>Couchbase)
            |(?P<Couchdb>Couchdb)
            |(?P<Devnull>Dev)
            |(?P<Files>File)
            |(?P<Leveldb>level)
            |(?P<Memcached>|Memcached)
            |(?P<Memcache>|Memcache)
            |(?P<Memstatic>|Memstatic)
            |(?P<Mongodb>mongo)
            |(?P<Ssdb>ssd)
            |(?P<Sqlite>sql)
            |(?P<Wincache>|Winc)
            |(?P<Xcache>Xc)
            |(?P<Zenddisk>Zendd?)
            |(?P<Zendshm>Zensh)
        `sixm', $driverName, $match);
        foreach ($match as $key => $val) {
            if (is_string($key) && !empty($val)) {
                return $key;
            }
        }

        return $default;
    }

    /**
     * @return Cache
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function &getInstance() : Cache
    {
        if (isset(self::$instance)) {
            return self::$instance;
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        self::$instance = Container\Get(Constant::MODULE_CORE);
        $name = self::$instance->normalizeModuleIdentifier(basename(__DIR__));
        self::$instance = self::$instance->getModule($name);
        /** @noinspection PhpUndefinedMethodInspection */
        self::$instance->processModule();
        return self::$instance;
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \RedisException
     * @throws \phpFastCache\Exceptions\phpFastCacheDriverCheckException
     * @throws \phpFastCache\Exceptions\phpFastCacheInvalidConfigurationException
     */
    protected function onProcess()
    {
        $this->initCache();
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \RedisException
     * @throws \phpFastCache\Exceptions\phpFastCacheDriverCheckException
     * @throws \phpFastCache\Exceptions\phpFastCacheInvalidConfigurationException
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return call_user_func_array(
            [self::getInstance()->getSimpleCache(), $name],
            $arguments
        );
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \RedisException
     * @throws \phpFastCache\Exceptions\phpFastCacheDriverCheckException
     * @throws \phpFastCache\Exceptions\phpFastCacheInvalidConfigurationException
     */
    public function __call(string $name, array $arguments)
    {
        return call_user_func_array([$this->getSimpleCache(), $name], $arguments);
    }
}
