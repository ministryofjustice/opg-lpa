<?php

namespace Application\Model\Rest\Stats;

use Application\Model\Rest\AbstractResource;
use MongoDB\Driver\ReadPreference;

class Resource extends AbstractResource
{
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
    protected $type = self::TYPE_COLLECTION;

    public function fetch($type)
    {
        $collection = $this->getCollection('stats-lpas');

        // Return all the cached data.// Stats can (ideally) be processed on a secondary.
        $readPreference = [
            'readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)
        ];

        // Stats can (ideally) be pulled from a secondary.
        $stats = $collection->findOne([], $readPreference);

        if (!isset($stats['generated'])) {
            // Regenerate stats as missing or using old format
            $this->getServiceLocator()->get('StatsService')->generate();
        }

        $stats['generated'] = date('d/m/Y H:i:s', $stats['generated']->toDateTime()->getTimestamp());

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
