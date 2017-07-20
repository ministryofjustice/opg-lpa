<?php

namespace ApplicationTest\Model\Rest\Status;

use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Status\Entity;
use Application\Model\Rest\Status\Resource;
use ApplicationTest\Model\AbstractResourceTest;
use OpgTest\Lpa\DataModel\FixturesData;

class ResourceTest extends AbstractResourceTest
{
    public function testGetType()
    {
        $resource = new Resource();
        $this->assertEquals(AbstractResource::TYPE_SINGULAR, $resource->getType());
    }

    public function testFetchCheckAccess()
    {
        /** @var StatusResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->fetch();
    }

    public function testFetch()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();

        $entity = $resource->fetch();

        $status = [
            'started' => true,
            'created' => false,
            'completed' => false,
        ];
        $this->assertEquals(new Entity($status, $lpa), $entity);
        $resourceBuilder->verify();
    }
}