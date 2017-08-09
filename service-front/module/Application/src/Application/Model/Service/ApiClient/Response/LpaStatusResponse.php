<?php

namespace Application\Model\Service\ApiClient\Response;

use Application\Model\Service\ApiClient\Traits\JsonSerializer;

/**
 * LpaStatusResponse
 *
 * @author Chris Moreton
 */
class LpaStatusResponse
{
    use JsonSerializer;

    /**
     * Return an array representation of the object
     *
     * @return array
     */
    public function getArrayCopy()
    {
        return [];
    }

    /**
     * Populate the member variables from an array
     *
     * @param array $array
     */
    public function exchangeArray(array $array)
    {}
}
