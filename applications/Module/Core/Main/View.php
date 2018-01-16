<?php
namespace Pentagonal\TBot\Module\Core\Main;

use Pentagonal\TBot\Base\Path;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class View
 * @package Pentagonal\TBot\Module\Core\Main
 */
class View
{
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $viewExtensions = [
        'phtml',
        'php'
    ];

    /**
     * @var string
     */
    protected $path;

    /**
     * View constructor.
     *
     * @param string $directory
     * @param array $attributes
     */
    public function __construct(string $directory, array $attributes = [])
    {
        $directory = Path::resolveSeparator($directory);
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s is not a directory',
                    $directory
                )
            );
        }
        $this->path = new \SplFileInfo($directory);
        $this->path = $this->path->getRealPath();
    }

    /**
     * @return array
     */
    public function getAttributes() : array
    {
        return $this->attributes;
    }

    /**
     * @return string
     */
    public function getPath() : string
    {
        return $this->path;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $fileName
     *
     * @return ResponseInterface
     */
    public function render(
        string $fileName,
        ServerRequestInterface $request,
        ResponseInterface $response
    ) : ResponseInterface {
        $body = clone $response->getBody();
        $body->write(
            $this->includeFileBuffer(
                $fileName,
                clone $request,
                clone $response
            )
        );

        return $response->withBody($body);
    }

    /**
     * @param string $name
     * @param $value
     */
    public function setAttribute(string $name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * @param string $name
     * @param null $default
     *
     * @return mixed|null
     */
    public function getAttribute(string $name, $default = null)
    {
        if ($this->hasAttribute($name)) {
            return $this->attributes[$name];
        }

        return $default;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasAttribute(string $name) : bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $fileName
     *
     * @return string
     */
    protected function includeFileBuffer(
        string $fileName,
        ServerRequestInterface $request,
        ResponseInterface $response
    ) : string {
        $c = clone $this;
        ob_start();
        $fileName = Path::resolveSeparator($fileName);
        $path = $this->getPath() . DIRECTORY_SEPARATOR;
        $file = $path . $fileName;
        if (!file_exists($path . $fileName)) {
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            if ( ! in_array($extension, $this->viewExtensions)) {
                foreach ($this->viewExtensions as $extension) {
                    if (is_file($path . $fileName . $extension)) {
                        $file = $path . $fileName . $extension;
                        break;
                    }
                }
                if (! file_exists($file)) {
                    foreach ($this->viewExtensions as $extension) {
                        if (file_exists($path . $fileName . $extension)) {
                            $file = $path . $fileName . $extension;
                            break;
                        }
                    }
                }
            }
        }
        if (!file_exists($file)) {
            throw new \RuntimeException(
                sprintf(
                    '%s is not exists',
                    $file
                )
            );
        }
        if (!is_file($file)) {
            throw new \RuntimeException(
                sprintf(
                    '%s is not a file',
                    $file
                )
            );
        }

        (function($request, $response, string $fileName) {
            /** @noinspection PhpIncludeInspection */
            include $fileName;
        })->call($c, $request, $response, $file);

        return ob_get_clean();
    }
}
