<?php

namespace ApplicationTest\Model\Service;

use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\User\User;

abstract class AbstractServiceTest extends MockeryTestCase
{
    /**
     * Convenience function to get a pre-mocked ApiLpaCollection
     *
     * @param Lpa $lpa
     * @param User $user
     * @param bool $isModified
     * @param bool $addDefaults
     * @return ApplicationRepositoryInterface|MockInterface
     */
    public function getApplicationRepository(Lpa $lpa, User $user, $isModified = false, $addDefaults = true)
    {
        $apiLpaCollection = Mockery::mock(ApplicationRepositoryInterface::class);

        if ($user !== null) {
            if ($lpa !== null) {
                $apiLpaCollection->shouldReceive('getById')
                    ->withArgs([(int)$lpa->getId(), $user->getId()])
                    ->andReturn($lpa->toArray());
                $apiLpaCollection->shouldReceive('getById')
                    ->withArgs([$lpa->getId()])
                    ->andReturn($lpa->toArray());
            }
        }

        if ($lpa === null) {
            $apiLpaCollection->shouldNotReceive('getById');
            $apiLpaCollection->shouldNotReceive('fetch');
        }

        if ($addDefaults) {
            $apiLpaCollection->shouldReceive('getById')->andReturn(null);
        }

        if ($isModified === true) {
            $apiLpaCollection->shouldReceive('update');
        } else {
            $apiLpaCollection->shouldNotReceive('update');
        }

        /** @var ApplicationRepositoryInterface $apiLpaCollection */
        return $apiLpaCollection;
    }
}
