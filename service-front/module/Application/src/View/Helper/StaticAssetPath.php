<?php

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class StaticAssetPath extends AbstractHelper
{
    private $assetsVersion;

    public function __construct($assetsVersion)
    {
        $this->assetsVersion = $assetsVersion;
    }

    public function __invoke($path, array $options = [])
    {

        $path = str_replace('/assets/', "/assets/{$this->assetsVersion}/", $path);

        // Should '.min' be include before the file extension.
        if (isset($options['minify']) && $options['minify'] === true) {
            $lastDot = strrpos($path, '.');
            $path = substr($path, 0, $lastDot) . '.min' . substr($path, $lastDot);
        }

        return $path;
    }
}
