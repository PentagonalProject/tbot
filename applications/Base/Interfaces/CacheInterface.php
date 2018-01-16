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

namespace Pentagonal\TBot\Base\Interfaces;

/**
 * Interface CacheInterface
 * @package Pentagonal\TBot\Base\Interfaces
 */
interface CacheInterface
{
    /**
     * Check if cache exists
     *
     * @param string $name
     *
     * @return boolean
     */
    public function exist(string $name) : bool;

    /**
     * Put data into cache
     *
     * @param string $identifier
     * @param mixed      $data
     * @param int        $timeout default timeout is 3600 (1 hour)
     *
     * @return mixed|void
     */
    public function put(string $identifier, $data, $timeout = 3600);

    /**
     * Get cache data
     *
     * @param string $identifier the cache key identifier
     * @param mixed $default returning default if cache does not exists
     *
     * @return mixed
     */
    public function get(string $identifier, $default = null);

    /**
     * Delete cache
     *
     * @param string|int $identifier
     *
     * @return mixed
     */
    public function delete(string $identifier);
}
