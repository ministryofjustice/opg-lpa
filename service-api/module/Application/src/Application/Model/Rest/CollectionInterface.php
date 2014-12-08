<?php
namespace Application\Model\Rest;

interface CollectionInterface extends RouteProviderInterface {

    /**
     * @return \Application\Library\Hal\Hal
     */
    public function getHalItemsByPage( $pageNumber, callable $routeCallback );

} // interface
