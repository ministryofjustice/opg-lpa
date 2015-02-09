<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class StaticAssetPath extends AbstractHelper {

    private $assetsVersion;

    public function __construct( $assetsVersion ){
        $this->assetsVersion = $assetsVersion;
    }

    public function __invoke($path){

        $path = str_replace( '/assets/', "/assets/{$this->assetsVersion}/", $path );

        return $path;

    }
}