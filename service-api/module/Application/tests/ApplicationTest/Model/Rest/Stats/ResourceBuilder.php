<?php

namespace ApplicationTest\Model\Rest\Stats;

use Application\Model\Rest\Stats\Resource as StatsResource;
use ApplicationTest\AbstractResourceBuilder;
use Mockery;
use Mockery\MockInterface;
use MongoDB\Collection as MongoCollection;

class ResourceBuilder extends AbstractResourceBuilder
{
    /**
     * @var MockInterface|MongoCollection
     */
    private $statsLpasCollection = null;

    /**
     * @return StatsResource
     */
    public function build()
    {
        $this->lpaCollection = $this->lpaCollection ?: Mockery::mock(MongoCollection::class);

        $resource = new StatsResource($this->lpaCollection, $this->statsLpasCollection);

        return $resource;
    }

    /**
     * @param $statsLpasCollection
     * @return $this
     */
    public function withStatsLpasCollection($statsLpasCollection)
    {
        $this->statsLpasCollection = $statsLpasCollection;
        return $this;
    }
}