<?php

namespace ApplicationTest\Model\Rest\Stats;

use Application\Model\Rest\Stats\Resource as StatsResource;
use ApplicationTest\AbstractResourceBuilder;
use Mockery;
use MongoCollection;
use Zend\ServiceManager\ServiceLocatorInterface;

class ResourceBuilder extends AbstractResourceBuilder
{
    private $statsLpasCollection = null;

    /**
     * @return StatsResource
     */
    public function build()
    {
        $resource = new StatsResource();

        $this->lpaCollection = $this->lpaCollection ?: Mockery::mock(MongoCollection::class);

        $this->serviceLocatorMock = Mockery::mock(ServiceLocatorInterface::class);
        $this->serviceLocatorMock->shouldReceive('get')->with('MongoDB-Default-lpa')->andReturn($this->lpaCollection);
        $this->serviceLocatorMock->shouldReceive('get')->with('MongoDB-Default-stats-who')->andReturn($this->statsWhoCollection);

        if ($this->statsLpasCollection !== null) {
            $this->serviceLocatorMock->shouldReceive('get')->with('MongoDB-Default-stats-lpas')->andReturn($this->statsLpasCollection);
        }

        $resource->setServiceLocator($this->serviceLocatorMock);

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