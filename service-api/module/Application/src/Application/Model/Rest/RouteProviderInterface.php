<?php

namespace Application\Model\Rest;

/**
 * Ensure an object supplies data need for URL generation.
 *
 * Interface RouteProviderInterface
 * @package Application\Model\Rest
 */
interface RouteProviderInterface
{
    public function userId();

    public function lpaId();

    public function resourceId();
}
