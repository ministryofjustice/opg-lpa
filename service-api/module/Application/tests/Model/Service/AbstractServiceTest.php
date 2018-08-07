<?php

namespace ApplicationTest\Model\Service;

use Application\Model\DataAccess\Mongo\Collection\ApiLpaCollection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Opg\Lpa\Logger\Logger;

abstract class AbstractServiceTest extends MockeryTestCase
{
    /**
     * @var MockInterface|ApiLpaCollection
     */
    protected $apiLpaCollection;

    /**
     * @var MockInterface|Logger
     */
    protected $logger;

    protected function setUp()
    {
        $this->apiLpaCollection = Mockery::mock(ApiLpaCollection::class);

        $this->logger = Mockery::mock(Logger::class);
    }
}
