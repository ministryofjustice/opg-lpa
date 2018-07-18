<?php

namespace Application\Model\Service\Stats;

use Application\Model\DataAccess\Mongo\Collection\AuthUserCollection;
use MongoDB\Collection;
use MongoDB\Driver\ReadPreference;

class Service
{
    /**
     * @var Collection
     */
    protected $collection = null;

    /**
     * @var AuthUserCollection
     */
    protected $authUserCollection = null;

    /**
     * @param Collection $collection
     * @param AuthUserCollection $authUserCollection
     */
    public function __construct(Collection $collection, AuthUserCollection $authUserCollection)
    {
        $this->collection = $collection;
        $this->authUserCollection = $authUserCollection;
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

        //  Get the user stats
        $stats['users'] = [
            'total'                 => $this->authUserCollection->countAccounts(),
            'activated'             => $this->authUserCollection->countActivatedAccounts(),
            'activated-this-month'  => $this->authUserCollection->countActivatedAccounts(new \DateTime('first day of this month 00:00:00')),
            'deleted'               => $this->authUserCollection->countDeletedAccounts(),
        ];

        // Return specific subset of stats if requested
        switch ($type) {
            case 'lpas':
                $stats = $stats['lpas'];
                break;
            case 'users':
                $stats = $stats['users'];
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
