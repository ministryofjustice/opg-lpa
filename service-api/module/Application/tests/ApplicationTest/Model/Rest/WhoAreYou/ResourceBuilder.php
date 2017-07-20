<?php

namespace ApplicationTest\Model\Rest\WhoAreYou;

use Application\Model\Rest\WhoAreYou\Resource as WhoAreYouResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{
    private $statsWhoCollection = null;

    /**
     * @return WhoAreYouResource
     */
    public function build()
    {
        $resource = new WhoAreYouResource();
        parent::buildMocks($resource);

        if ($this->statsWhoCollection !== null) {
            $this->serviceLocatorMock->shouldReceive('get')->with('MongoDB-Default-stats-who')->andReturn($this->statsWhoCollection);
        }

        return $resource;
    }

    /**
     * @param $statsWhoCollection
     * @return $this
     */
    public function withStatsWhoCollection($statsWhoCollection)
    {
        $this->statsWhoCollection = $statsWhoCollection;
        return $this;
    }
}