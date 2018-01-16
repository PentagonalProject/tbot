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

/**
 * Class Path
 * @package Pentagonal\TBot\Base
 */
class Path
{
    private static $rootPath;

    /**
     * @return string
     */
    public static function getRoot() : string
    {
        if (!isset(self::$rootPath)) {
            $spl      = new \SplFileInfo(__DIR__ . '/../../');
            self::$rootPath = $spl->getRealPath();
        }

        return self::$rootPath;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public static function resolveSeparator(string $path) : string
    {
        return preg_replace('/(\\\|\/)+/', DIRECTORY_SEPARATOR, $path);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public static function fromRoot(string $path) : string
    {
        $rootPath = self::getRoot();
        $path = trim(self::resolveSeparator($path));
        if ($path == '') {
            return $rootPath;
        }
        if ($path == DIRECTORY_SEPARATOR) {
            return $rootPath . $path;
        }

        if ($path[0] == DIRECTORY_SEPARATOR) {
            $path = substr($path, 1);
        }

        $endPath = substr($path, -1) === DIRECTORY_SEPARATOR ?  DIRECTORY_SEPARATOR : '';
        $splPath = new \SplFileInfo($rootPath . DIRECTORY_SEPARATOR . $path);
        $realPath = $path;
        if ($splPath->getRealPath()) {
            $realPath = $splPath->getRealPath() . $endPath;
        } elseif ($splPath->getPathInfo()->getRealPath()) {
            $realPath = $splPath->getRealPath()
                        . DIRECTORY_SEPARATOR
                        . $splPath->getBasename()
                        . $endPath;
        }

        return $realPath;
    }
}
