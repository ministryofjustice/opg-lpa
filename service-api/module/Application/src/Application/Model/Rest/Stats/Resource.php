<?php

namespace Application\Model\Rest\Stats;

use Application\Model\Rest\AbstractResource;
use MongoDB\Driver\ReadPreference;

class Resource extends AbstractResource
{
    //  TODO - FROM OLD ABSTRACT - REMOVE
    /**
     * Resource name
     *
     * @var string
     */
    protected $name = 'stats';

    /**
     * Resource identifier
     *
     * @var string
     */
    protected $identifier = 'type';

    /**
     * Resource type
     *
     * @var string
     */
    const TYPE_COLLECTION = 'collections';
    protected $type = self::TYPE_COLLECTION;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getType()
    {
        return $this->type;
    }
    //  TODO - END FROM OLD ABSTRACT - REMOVE

    public function fetch($type)
    {
        // Return all the cached data.// Stats can (ideally) be processed on a secondary.
        $readPreference = [
            'readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        ];

        // Stats can (ideally) be pulled from a secondary.
        $stats = $this->collection->findOne([], $readPreference);

        if (!isset($stats['generated']) || !is_string($stats['generated'])) {
            return new Entity(['generated' => false]);
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

        return new Entity($stats);
    }
}
