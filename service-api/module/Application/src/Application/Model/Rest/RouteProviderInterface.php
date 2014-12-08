<?php
namespace Application\Model\Rest;

/**
 * Ensure an object supplies data need for URL generation.
 *
 * Interface RouteProviderInterface
 * @package Application\Model\Rest
 */
interface RouteProviderInterface {

    /**
     * @return int The ID of the current LPA to use in the route.
     */
    public function lpaId();

    /**
     * @return int The ID of the current resource (sub-lpa).
     */
    public function resourceId();

} // interface
