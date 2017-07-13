<?php

namespace ApplicationTest\Model\Rest\AttorneyDecisionsPrimary;

use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\AttorneyDecisionsPrimary\Entity;
use Application\Model\Rest\AttorneyDecisionsPrimary\Resource;
use OpgTest\Lpa\DataModel\FixturesData;

class ResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testGetType()
    {
        $resource = new Resource();
        $this->assertEquals(AbstractResource::TYPE_SINGULAR, $resource->getType());
    }

    public function testFetch()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();
        /** @var Entity $primaryAttorneyDecisionsEntity */
        $primaryAttorneyDecisionsEntity = $resource->fetch();
        $this->assertEquals(new Entity($lpa->document->primaryAttorneyDecisions, $lpa), $primaryAttorneyDecisionsEntity);
    }
}