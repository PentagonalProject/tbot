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

use Apatis\Config;
use Pentagonal\TBot\Base\Constant;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Api
 * @package Pentagonal\TBot\Module\Core\Rest
 *
 * Api generator For Response
 *
 * Add Api::class to data that means it will be set as static::MESSAGE_KEY key
 */
class Api
{
    const DATA_KEY              = Constant::NAME_DATA_KEY;
    const STATUS_KEY            = Constant::NAME_STATUS_KEY;
    const ERROR_KEY             = Constant::NAME_ERROR_KEY;
    const MESSAGE_KEY           = Constant::NAME_MESSAGE_KEY;
    const ENDPOINT_KEY          = Constant::NAME_ENDPOINT_KEY;
    const FORMER_NAME_KEY       = Constant::NAME_FORMER_NAME_KEY;
    const OFFICIAL_URL_KEY      = Constant::NAME_OFFICIAL_URL_KEY;
    const BASE_URL_KEY          = Constant::NAME_BASE_URL_KEY;
    const URL_KEY               = Constant::NAME_URL_KEY;

    /**
     * Application URI Official
     */
    const APP_URI               = 'https://Pentagonal\TBot.co.id/';

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var int
     */
    protected $options = JSON_UNESCAPED_SLASHES;


    /**
     * Sanitize Identifier, this only use trim
     *
     * @param string $marketIdentifier
     *
     * @return bool|string
     */
    public static function normalizeApiIdentifier(string $marketIdentifier)
    {
        $marketIdentifier = trim($marketIdentifier);
        if ($marketIdentifier === '') {
            return false;
        }

        return $marketIdentifier;
    }

    /**
     * Api constructor.
     *
     * @param ResponseInterface $response
     * @param $data
     */
    public function __construct(ResponseInterface $response, $data = null)
    {
        if (Config\Get('prettify')) {
            $this->prettify();
        }

        // if config contains escapeSlash or escape with value
        // convert boolean will be true, JSON will be use escape
        // otherwise unescaped slash
        if (Config\Get('escapeSlash') || Config\Get('escape')) {
            $this->escapeSlash();
        } else {
            $this->unEscapeSlash();
        }

        $this->response = $response->withHeader('Content-Type', 'application/json;charset=utf-8');
        $this->data = $data;
    }

    /**
     * To array Generate
     *
     * @return array
     */
    public function toArray() : array
    {
        $statusCode = $this->response->getStatusCode();
        $statusSuccess = substr((string) $statusCode, 0, 1);
        $method = 'generateSuccessMessage';
        if (abs($statusSuccess) !== 2 && ! in_array($statusCode, [302, 304])) {
            $method = 'generateErrorMessage';
        }

        return $this->$method($this->response, $this->data);
    }

    /**
     * Generate Message Standard for API Service - Error
     *
     * @param ResponseInterface $response
     * @param mixed $data
     *
     * @return array
     */
    public static function generateErrorMessage(ResponseInterface $response, $data) : array
    {
        if ($response->getStatusCode() === 404) {
            $statusMessage = '404 Page Not Found';
        } else {
            $statusMessage = $response->getStatusCode() . ' ' . $response->getReasonPhrase();
        }
        // if has array key `message`
        if ((is_array($data) || is_object($data) && $data instanceof \ArrayAccess) && isset($data[self::class])) {
            $statusMessage = $data[self::class];
            unset($data[self::class]);
        }
        $response = [
            static::ERROR_KEY => [
                static::MESSAGE_KEY  => $statusMessage,
            ]
        ];

        if (!empty($data)) {
            $response[static::ERROR_KEY][static::DATA_KEY] = $data;
        }

        return $response;
    }

    /**
     * Generate Message Standard for API Service - Success
     *
     * @param ResponseInterface $response
     * @param mixed $data
     *
     * @return array
     */
    public static function generateSuccessMessage(ResponseInterface $response, $data) : array
    {
        $statusMessage = $response->getStatusCode() . ' ' . $response->getReasonPhrase();
        $response = [static::STATUS_KEY => $statusMessage];
        // if has array key `message`
        if ((is_array($data) || is_object($data) && $data instanceof \ArrayAccess) && isset($data[self::class])) {
            $response[static::MESSAGE_KEY] = $data[self::class];
            unset($data[self::class]);
        }

        $response[static::DATA_KEY] = $data;
        return $response;
    }

    /**
     * @param ResponseInterface $response
     * @param mixed $data
     *
     * @return Api
     */
    public static function generate(ResponseInterface $response, $data = null) : Api
    {
        return new static($response, $data);
    }

    /**
     * @return ResponseInterface
     */
    public function toResponse() : ResponseInterface
    {
        $response = clone $this->response;
        $response->getBody()->write($this->toJson());
        return $response;
    }

    /**
     * @return string
     */
    public function toJson() : string
    {
        return json_encode($this->toArray(), $this->options);
    }

    /**
     * @return Api
     */
    public function prettify() : Api
    {
        $this->options |= JSON_PRETTY_PRINT;
        return $this;
    }

    /**
     * @return Api
     */
    public function unPrettify() : Api
    {
        $this->options |= ~JSON_PRETTY_PRINT;
        return $this;
    }

    /**
     * Make UnSlashed
     *
     * @return Api
     */
    public function unEscapeSlash() : Api
    {
        $this->options |= JSON_UNESCAPED_SLASHES;
        return $this;
    }

    /**
     * Make Slash
     *
     * @return Api
     */
    public function escapeSlash() : Api
    {
        $this->options |= ~JSON_UNESCAPED_SLASHES;
        return $this;
    }

    /**
     * Magic Method
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->toJson();
    }
}
