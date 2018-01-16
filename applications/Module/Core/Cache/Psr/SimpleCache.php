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

namespace Pentagonal\TBot\Module\Core\Cache\Psr;

use phpFastCache\Core\Item\ExtendedCacheItemInterface;
use phpFastCache\Core\Pool\ExtendedCacheItemPoolInterface;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

/**
 * Class SimpleCache
 * @package Pentagonal\TBot\Module\Core\Cache\Psr
 */
class SimpleCache implements CacheInterface
{
    /**
     * @var ExtendedCacheItemPoolInterface
     */
    protected $cacheManager;

    /**
     * SimpleCache constructor.
     *
     * @param ExtendedCacheItemPoolInterface $cachePool
     */
    public function __construct(ExtendedCacheItemPoolInterface $cachePool)
    {
        $this->setItemPool($cachePool);
    }

    /**
     * @param ExtendedCacheItemPoolInterface $cacheManager
     */
    public function setItemPool(ExtendedCacheItemPoolInterface $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * @return ExtendedCacheItemPoolInterface
     */
    public function getItemPool() : ExtendedCacheItemPoolInterface
    {
        return $this->cacheManager;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {

        if ($this->has($key)) {
            return $this->getItem($key)->get();
        }

        return $default;
    }

    /**
     * @param $key
     *
     * @return ExtendedCacheItemInterface
     */
    public function getItem($key)
    {
        try {
            return $this->getItemPool()->getItem($key);
        } catch (phpFastCacheInvalidArgumentException $e) {
            throw new InvalidArgumentException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null) : bool
    {
        try {
            $save = $this
                ->getItem($key)
                ->set($value);
            $is2Args = func_num_args() > 1;
            if ($is2Args && (is_numeric($ttl) || $ttl instanceof \DateInterval)) {
                $save->expiresAfter($ttl);
            }

            /**
             * @var ExtendedCacheItemInterface $item
             */
            return $this
                ->getItemPool()
                ->save($save) && $this->has($key);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key) : bool
    {
        try {
            return $this
                ->getItemPool()
                ->deleteItem($key);
        } catch (\Psr\Cache\InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear() : bool
    {
        return $this->getItemPool()->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        if (!is_iterable($keys)) {
            throw new InvalidArgumentException(
                'Params keys must be iterable'
            );
        }

        $notFound = true;
        foreach ($keys as $key) {
            if ($this->has($key)) {
                $notFound = false;
                break;
            }
        }

        if ($notFound) {
            return $default;
        }

        try {
            $result = [];
            foreach ($this->getItemPool()->getItems($keys) as $key => $item) {
                $result[$key] = $item->get();
            }
            return $result;
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null) : bool
    {
        if (!is_iterable($values)) {
            throw new InvalidArgumentException(
                'Values must be iterable'
            );
        }

        try {
            $is2Args = func_num_args() > 1;
            $count = 0;
            foreach ($values as $key => $value) {
                $count++;
                if ($value instanceof ExtendedCacheItemPoolInterface) {
                    continue;
                }
                $value = $this
                    ->getItemPool()
                    ->getItem($key)
                    ->set($value);

                if ($is2Args && (is_numeric($ttl) || $ttl instanceof \DateInterval)) {
                    $value->expiresAfter($ttl);
                }

                $values[$key] = $value;
            }
            if ($count === 0) {
                throw new InvalidArgumentException(
                    'Values to save could not be empty'
                );
            }

            return call_user_func_array([$this->getItemPool(), 'saveMultiple'], $values);
        } catch (phpFastCacheInvalidArgumentException $e) {
            throw new InvalidArgumentException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys)
    {
        if (!is_iterable($keys)) {
            throw new InvalidArgumentException(
                'Parameter keys must be iterable'
            );
        }
        try {
            return $this->getItemPool()->deleteItems($keys);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has($key) : bool
    {
        try {
            return $this->getItemPool()->hasItem($key);
        } catch (\Psr\Cache\InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
