<?php

namespace ApplicationTest\Model\Service;

use Application\Model\DataAccess\Mongo\Collection\ApiLpaCollection;
use Application\Model\DataAccess\Mongo\DateCallback;
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
     * @return ApiLpaCollection|MockInterface
     */
    public function getApiLpaCollection(Lpa $lpa, User $user, $isModified = false, $addDefaults = true)
    {
        $apiLpaCollection = Mockery::mock(ApiLpaCollection::class);

        if ($user !== null) {
            if ($lpa !== null) {
                $apiLpaCollection->shouldReceive('getById')
                    ->withArgs([(int)$lpa->getId(), $user->getId()])
                    ->andReturn($lpa->toArray(new DateCallback()));
                $apiLpaCollection->shouldReceive('getById')
                    ->withArgs([$lpa->getId()])
                    ->andReturn($lpa->toArray(new DateCallback()));
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

        /** @var ApiLpaCollection $apiLpaCollection */
        return $apiLpaCollection;
    }
}
