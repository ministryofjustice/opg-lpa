<?php
namespace Application\Model\DataAccess\Mongo\Collection;

use DateTime;
use MongoDB\BSON\UTCDateTime;

abstract class AbstractCollection {

    /**
     * Prepares data to be sent to MongoDB.
     *
     * Specifically:
     *  - Maps id -> _id
     *  - Changes all DateTimes to Mongo DateTimes.
     *
     * @param array $data
     * @return array
     */
    protected function prepare(array $data) : array
    {
        // Rename 'id' to '_id' (keeping it at the beginning of the array)
        if (isset($data['id'])) {
            $data = ['_id' => $data['id']] + $data;
            unset($data['id']);
        }

        // Function to recursively map DateTime -> MongoDB\BSON\UTCDateTime
        $map = function(array $input) use (&$map){
            //var_dump($input); die;
            $output = [];
            foreach ($input as $key => $value) {
                if (is_array($value)) {
                    $output[$key] = $map($value);
                    $output[$key] = $value;
                } elseif ($value instanceof DateTime) {
                    $output[$key] = new UTCDateTime($value);
                } else {
                    $output[$key] = $value;
                }
            }
            return $output;
        };

        $data = $map($data);

        return $data;
    }

}
