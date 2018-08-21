<?php

namespace ApplicationTest\Model\Service\System;

use Application\Model\DataAccess\Mongo\Collection\ApiLpaCollection;
use Application\Model\DataAccess\Mongo\Collection\ApiStatsLpasCollection;
use Application\Model\DataAccess\Repository\Application\WhoRepositoryInterface;
use ApplicationTest\Model\Service\AbstractServiceTest;
use Mockery;

class StatsTest extends AbstractServiceTest
{
    public function testGenerate()
    {
        $apiLpaCollection = Mockery::mock(ApiLpaCollection::class);

        //Return 1 for all counts to aid mocking mongo calls
        $apiLpaCollection->shouldReceive('countBetween')->andReturn(1);
        $apiLpaCollection->shouldReceive('countStartedForType')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCreatedForType')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedForType')->andReturn(1);
        $apiLpaCollection->shouldReceive('countDeleted')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetween')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenCorrespondentEmail')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenCorrespondentPhone')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenCorrespondentPost')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenCorrespondentEnglish')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenCorrespondentWelsh')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenWithPreferences')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenWithInstructions')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenByType')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenByCanSign')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenHasActors')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenHasNoActors')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenHasMultipleActors')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenDonorRegistering')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenAttorneyRegistering')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenCaseNumber')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenFeeType')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenPaymentType')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenWithAttorneyDecisions')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenWithTrust')->andReturn(1);
        $apiLpaCollection->shouldReceive('countCompletedBetweenCertificateProviderSkipped')->andReturn(1);
        $apiLpaCollection->shouldReceive('getLpasPerUser')->andReturn([]);
        /** @var ApiLpaCollection $apiLpaCollection */

        $apiStatsLpasCollection = Mockery::mock(ApiStatsLpasCollection::class);
        $apiStatsLpasCollection->shouldReceive('delete')->once();
        $apiStatsLpasCollection->shouldReceive('insert')->withArgs(function ($stats) {
            return isset($stats['generated'])
                && isset($stats['lpas'])
                && isset($stats['lpasPerUser'])
                && isset($stats['who'])
                && isset($stats['correspondence'])
                && isset($stats['preferencesInstructions']);
        })->once();
        /** @var ApiStatsLpasCollection $apiStatsLpasCollection */

        $whoRepository = Mockery::mock(WhoRepositoryInterface::class);
        $whoRepository->shouldReceive('getStatsForTimeRange')->andReturn([]);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiLpaCollection($apiLpaCollection)
            ->withApiStatsLpasCollection($apiStatsLpasCollection)
            ->withWhoRepository($whoRepository)
            ->build();

        $result = $service->generate();

        $this->assertTrue($result);
    }
}