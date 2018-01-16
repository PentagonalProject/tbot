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

use Apatis\Prima\Service;
use Pentagonal\TBot\Base\CacheRecord;
use Pentagonal\TBot\Module\Core\Cache\Cache;
use phpFastCache\Core\Item\ExtendedCacheItemInterface;

/**
 * Class ProviderModule
 * @package Pentagonal\TBot\Module\Core\Rest\CryptoCurrency
 */
abstract class ProviderModule
{
    /**
     * @var AbstractModuleAPI
     */
    protected $object;

    /**
     * Default Cache Time for Balance
     *  default is 10
     *
     * @type integer
     */
    const DEFAULT_EXPIRE = 30;

    /**
     * @type string tags of cache
     */
    const DEFAULT_CACHE_TAG    = Cache::PREFIX_KEY;

    /**
     * @var bool
     */
    protected $stopQueue = false;

    /**
     * @var int
     */
    protected $cacheExpiredAfter = self::DEFAULT_EXPIRE;

    /**
     * AbstractMarketModule constructor.
     *
     * @param AbstractModuleAPI $object
     */
    public function __construct(AbstractModuleAPI $object)
    {
        $this->setObject($object);
    }

    /**
     * @param AbstractModuleAPI $object
     *
     * @return static
     */
    public function setObject(AbstractModuleAPI $object) : ProviderModule
    {
        $this->object = $object;
        return $this;
    }

    /**
     * Get Module API
     *
     * @return AbstractModuleAPI
     */
    public function getObject() : AbstractModuleAPI
    {
        return $this->object;
    }

    /**
     * @param Service $service
     *
     * @return Service
     */
    abstract public function __invoke(
        Service $service
    ) : Service;

    /**
     * @return int
     */
    public function getCacheExpired() : int
    {
        return $this->cacheExpiredAfter;
    }

    /**
     * @param int $second
     */
    public function setCacheExpired(int $second)
    {
        $this->cacheExpiredAfter = $second;
    }

    /**
     * Delete Cache
     *
     * @param string $keyCache
     *
     * @return mixed
     */
    public function deleteCache(string $keyCache)
    {
        return Cache::delete($keyCache);
    }

    /**
     * Getting cache value if valid
     *
     * @param string $keyCache
     * @param mixed $default
     *
     * @return mixed
     */
    public function getCache(string $keyCache, $default = null)
    {
        if ($record = CacheRecord::valid(Cache::get($keyCache))) {
            return $record->value();
        }

        return $default;
    }

    /**
     * @param string $keyCache
     *
     * @return ExtendedCacheItemInterface
     */
    public function getCacheItem(string $keyCache) : ExtendedCacheItemInterface
    {
        return Cache::getItem($keyCache);
    }

    /**
     * @param $keyCache
     * @param $value
     *
     * @return bool
     * @throws \phpFastCache\Exceptions\phpFastCacheInvalidArgumentException
     */
    protected function saveCache($keyCache, $value) : bool
    {
        $pool = Cache::getItemPool();
        return $pool->save(
            $pool
                ->getItem($keyCache)
                ->set(CacheRecord::create($value))
                ->addTag(static::DEFAULT_CACHE_TAG)
                ->expiresAfter($this->getCacheExpired())
        );
    }

    /**
     * @return string
     */
    protected function getProcessCacheKey() : string
    {
        return Cache::createUniqueKey(get_class($this) . '_inProcess');
    }

    /**
     * Create Cache Process
     */
    protected function createProcessCache()
    {
        $cacheKeyProcess = $this->getProcessCacheKey();
        Cache::set($cacheKeyProcess, CacheRecord::create(true), 30);
    }

    /**
     * Delete process Cache
     */
    protected function deleteProcessCache()
    {
        $cacheKeyProcess = $this->getProcessCacheKey();
        Cache::delete($cacheKeyProcess);
        $this->stopQueue = true;
    }

    /**
     * @return bool
     */
    protected function inProcessCache() : bool
    {
        $cacheKeyProcess = $this->getProcessCacheKey();
        return CacheRecord::valid(Cache::get($cacheKeyProcess)) !== false;
    }

    /**
     * Waiting to Get
     * @param int $until
     * @param \closure|null $callback
     */
    protected function waitQueue(int $until, \closure $callback)
    {
        if ($callback() === true) {
            $this->stopQueue = true;
        } else {
            $inProcess       = true;
            $c               = 0;
            while ($inProcess) {
                if ($this->stopQueue) {
                    $this->stopQueue = false;
                    $this->deleteProcessCache();
                    break;
                }
                if ($callback() === true) {
                    $this->stopQueue = true;
                    $this->deleteProcessCache();
                    break;
                }
                try {
                    $inProcess = $c > $until && $this->inProcessCache();
                } catch (\Exception $e) {
                    // pass
                }
                $c++;
                if (! $inProcess) {
                    break;
                }

                sleep(0.3);
            }
        }
    }
}
