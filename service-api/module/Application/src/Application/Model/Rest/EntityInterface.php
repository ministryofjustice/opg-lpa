<?php
namespace Application\Model\Rest;

interface EntityInterface extends RouteProviderInterface {

    /**
     * @return array The Entity's data as an array.
     */
    public function toArray();

} // interface
