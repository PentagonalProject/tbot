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

namespace Pentagonal\TBot;

use Composer\Autoload\ClassLoader;

/**
 * @var ClassLoader $autoloader
 */
$autoloader = require __DIR__ .'/../vendor/autoload.php';
$exist      = false;
$prefix     = __NAMESPACE__ . '\\Base\\';
$baseDir    = __DIR__ . DIRECTORY_SEPARATOR . 'Base';
$psr4       = $autoloader->getPrefixesPsr4();

if (isset($psr4[$prefix])) {
    foreach ($psr4[$prefix] as $dir) {
        $spl = new \SplFileInfo($dir);
        if ($spl->getRealPath() === $baseDir) {
            $exist = true;
            break;
        }
    }
}

if (!$exist) {
    $autoloader->addPsr4($prefix, $baseDir);
    $autoloader->register();
}

return $autoloader;
