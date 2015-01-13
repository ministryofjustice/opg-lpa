<?php
namespace Opg\Lpa\Api\Client\Traits;

/**
 * 
 * @author Chris Moreton
 * 
 * Wraps the information received from the auth server
 *
 */
trait JsonSerializer
{
    /**
     * Populate the member variables from a JSON structure
     * Convert underscore_field_names to be camelCase
     *
     * @param string The JSON
     */
    public function exchangeJson($json)
    {
        $this->exchangeArray(
            json_decode($json, true)
        );
    }
    
    /**
     * Return the object as JSON
     *
     * @return string
     */
    public function getJsonCopy()
    {
        return json_encode(
            $this->getArrayCopy()
        );
    }
}
