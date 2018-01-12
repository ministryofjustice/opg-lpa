<?php

namespace ApplicationTest\Model\Rest\Status;

use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Status\Entity;
use Application\Model\Rest\Status\Resource as StatusResource;
use ApplicationTest\AbstractResourceTest;
use OpgTest\Lpa\DataModel\FixturesData;

class ResourceTest extends AbstractResourceTest
{
    /**
     * @var StatusResource
     */
    private $resource;

    protected function setUp()
    {
        parent::setUp();

        $this->resource = new StatusResource($this->lpaCollection);

        $this->resource->setLogger($this->logger);

        $this->resource->setAuthorizationService($this->authorizationService);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('resourceId', $this->resource->getIdentifier());
    }

    public function testGetName()
    {
        $this->assertEquals('status', $this->resource->getName());
    }

    public function testGetType()
    {
        $this->assertEquals(AbstractResource::TYPE_SINGULAR, $this->resource->getType());
    }

    public function testFetchCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->fetch();
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