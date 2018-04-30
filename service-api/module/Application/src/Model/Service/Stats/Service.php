<?php

namespace Application\Model\Service\Stats;

use MongoDB\Collection;
use MongoDB\Driver\ReadPreference;

class Service
{
    /**
     * @var Collection
     */
    protected $collection = null;

    /**
     * @param Collection $collection
     */
    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @param $type
     * @return array|null|object
     */
    public function fetch($type)
    {
        // Return all the cached data.// Stats can (ideally) be processed on a secondary.
        $readPreference = [
            'readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        ];

        // Stats can (ideally) be pulled from a secondary.
        $stats = $this->collection->findOne([], $readPreference);

        if (!isset($stats['generated']) || !is_string($stats['generated'])) {
            return [
                'generated' => false
            ];
        }

        // Return specific subset of stats if requested
        switch ($type) {
            case 'lpas':
                $stats = $stats['lpas'];
                break;
            case 'lpasperuser':
                $stats = $stats['lpasPerUser'];
                break;
            case 'whoareyou':
                $stats = $stats['who'];
                break;
            case 'correspondence':
                $stats = $stats['correspondence'];
                break;
            case 'preferencesinstructions':
                $stats = $stats['preferencesInstructions'];
                break;
        }

        return $stats;
    }
}
