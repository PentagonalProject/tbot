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

namespace Pentagonal\TBot\Base;

/**
 * Class CacheRecord
 * @package Pentagonal\TBot\Base
 *
 * Class Helper to save non string value data
 */
class CacheRecord implements \Serializable
{
    const SELECTOR = 'btid';

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var bool
     */
    protected $dataChange = false;

    /**
     * @var bool
     */
    protected $isJson;

    /**
     * CacheRecord constructor.
     *
     * @param $data
     */
    public function __construct($data)
    {
        if (isset($this->data)) {
            $this->dataChange = true;
        }
        $this->data = $data;
    }

    /**
     * Get Data
     *
     * @return mixed
     */
    public function value()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     *
     * @return CacheRecord
     */
    public static function create($data) : CacheRecord
    {
        return new static($data);
    }

    /**
     * @var bool
     */
    protected $isHandle = false;

    /**
     * Handler
     */
    protected function setHandle()
    {
        if ($this->isHandle) {
            return;
        }
        $this->isHandle = true;
        set_error_handler(function () {
            error_clear_last();
        });
    }

    /**
     * Restore
     */
    protected function restoreHandle()
    {
        if ($this->isHandle) {
            return;
        }
        $this->isHandle = false;
        restore_error_handler();
    }

    /**
     * @return string
     */
    public function serialize() : string
    {
        return serialize([static::SELECTOR => $this->data]);
    }

    /**
     * @param mixed|CacheRecord $instance
     *
     * @return CacheRecord
     */
    public static function with($instance) : CacheRecord
    {
        if (!self::valid($instance)) {
            $instance = new static($instance);
        }

        return $instance;
    }

    /**
     * @param mixed $cacheRecord
     *
     * @return CacheRecord|false false if invalid
     */
    public static function valid($cacheRecord)
    {
        return $cacheRecord instanceof CacheRecord ? $cacheRecord : false;
    }

    /**
     * @return bool
     */
    public function isNull() : bool
    {
        return $this->data === null;
    }

    /**
     * @return bool
     */
    public function isBoolean() : bool
    {
        return is_bool($this->data);
    }

    /**
     * @return bool
     */
    public function isNumeric() : bool
    {
        return is_numeric($this->data);
    }

    public function isJson() : bool
    {
        if (!is_string($this->data)) {
            return false;
        }

        if (isset($this->isJson) && ! $this->dataChange) {
            return $this->isJson;
        }

        $this->dataChange = false;
        json_decode($this->data);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Magic Method UnSerialize
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        if (!is_string($serialized)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Argument must be as a string %s given',
                    gettype($serialized)
                )
            );
        }

        $this->setHandle();
        $data = @unserialize($serialized);
        $this->restoreHandle();
        if (!is_array($data) || ! array_key_exists(static::SELECTOR, $data)) {
            return;
        }

        $this->data = $data[static::SELECTOR];
        unset($data);
    }
}
