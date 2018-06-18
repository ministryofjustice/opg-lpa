<?php

namespace ApplicationTest\Model\Service;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use MongoDB\Collection;
use Opg\Lpa\Logger\Logger;

abstract class AbstractServiceTest extends MockeryTestCase
{
    /**
     * @var MockInterface|Collection
     */
    protected $lpaCollection;

    /**
     * @var MockInterface|Logger
     */
    protected $logger;

    protected function setUp()
    {
        $this->lpaCollection = Mockery::mock(Collection::class);

        $this->logger = Mockery::mock(Logger::class);
    }
}
