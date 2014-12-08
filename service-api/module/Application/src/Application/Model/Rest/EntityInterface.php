<?php
namespace Application\Model\Rest;

interface EntityInterface extends RouteProviderInterface {

    /**
     * @return \Application\Library\Hal\Hal
     */
    public function getHal( callable $routeCallback );

} // interface
