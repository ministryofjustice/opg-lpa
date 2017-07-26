<?php

namespace ApplicationTest\Model\Rest\Stats;

use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Stats\Entity;
use Application\Model\Rest\Stats\Resource;
use ApplicationTest\Model\AbstractResourceTest;
use Mockery;
use MongoCollection;
use MongoCursor;

class ResourceTest extends AbstractResourceTest
{
    public function testGetType()
    {
        $resource = new Resource();
        $this->assertEquals(AbstractResource::TYPE_COLLECTION, $resource->getType());
    }

    public function testFetchTypeNotFound()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->build();

        $entity = $resource->fetch('unknown');

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(404, $entity->status);
        $this->assertEquals('Stats type not found.', $entity->detail);

        $resourceBuilder->verify();
    }

    public function testFetchTypeLpa()
    {
        $lpaCollection = Mockery::mock(MongoCollection::class);
        $lpaCollection->shouldReceive('count')->andReturn(1);

        $start = new \DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new \DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        $expectedByMonth = array();
        for ($i = 1; $i <=4; $i++) {
            $expectedByMonth[date('Y-m', $start)] = [
                'started' => 1,
                'created' => 1,
                'completed' => 1
            ];

            $start->modify("first day of -1 month");
            $end->modify("last day of -1 month");
        }

        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withLpaCollection($lpaCollection)->build();

        $entity = $resource->fetch('lpas');

        $expectedStats = [
            'all' => [
                'started' => 2,
                'created' => 2,
                'completed' => 2,
                'deleted' => 1
            ],
            'health-and-welfare' => [
                'started' => 1,
                'created' => 1,
                'completed' => 1
            ],
            'property-and-finance' => [
                'started' => 1,
                'created' => 1,
                'completed' => 1
            ],
            'by-month' => $expectedByMonth
        ];

        $this->assertEquals(new Entity($expectedStats), $entity);

        $resourceBuilder->verify();
    }

    public function testFetchWhoAreYou()
    {
        $statsWhoCollection = Mockery::mock(MongoCollection::class);
        $statsWhoCollection->shouldReceive('count')->andReturn(1);

        $start = new \DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new \DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        $expectedByMonth = array();
        for ($i = 1; $i <=4; $i++) {
            $expectedByMonth[date('Y-m', $start)] = [
                'professional' => [
                    'count' => 1,
                    'subquestions' => [
                        'solicitor' => 1,
                        'will-writer' => 1,
                        'other' => 1
                    ]
                ],
                'digitalPartner' => [
                    'count' => 1,
                    'subquestions' => []
                ],
                'organisation' => [
                    'count' => 1,
                    'subquestions' => []
                ],
                'donor' => [
                    'count' => 1,
                    'subquestions' => []
                ],
                'friendOrFamily' => [
                    'count' => 1,
                    'subquestions' => []
                ],
                'notSaid' => [
                    'count' => 1,
                    'subquestions' => []
                ],
            ];

            $start->modify("first day of -1 month");
            $end->modify("last day of -1 month");
        }

        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withStatsWhoCollection($statsWhoCollection)->build();

        $entity = $resource->fetch('whoareyou');

        $expectedStats = [
            'all' => [
                'professional' => [
                    'count' => 1,
                    'subquestions' => [
                        'solicitor' => 1,
                        'will-writer' => 1,
                        'other' => 1
                    ]
                ],
                'digitalPartner' => [
                    'count' => 1,
                    'subquestions' => []
                ],
                'organisation' => [
                    'count' => 1,
                    'subquestions' => []
                ],
                'donor' => [
                    'count' => 1,
                    'subquestions' => []
                ],
                'friendOrFamily' => [
                    'count' => 1,
                    'subquestions' => []
                ],
                'notSaid' => [
                    'count' => 1,
                    'subquestions' => []
                ],
            ],
            'by-month' => $expectedByMonth
        ];

        $this->assertEquals(new Entity($expectedStats), $entity);

        $resourceBuilder->verify();
    }

    public function testFetchLpasPerUser()
    {
        $statMongoCursor = new DummyStatMongoCursor([['_id' => 1, 'count' => 2]]);
        $statsLpasCollection = Mockery::mock(MongoCollection::class);
        $statsLpasCollection->shouldReceive('setReadPreference');
        $statsLpasCollection->shouldReceive('find')->andReturn($statMongoCursor);
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withStatsLpasCollection($statsLpasCollection)->build();

        $entity = $resource->fetch('lpasperuser');

        $expectedStats = [
            'byLpaCount' => [1 => 2]
        ];

        $this->assertEquals(new Entity($expectedStats), $entity);

        $resourceBuilder->verify();
    }

    public function testFetchWelshLanguage()
    {
        $lpaCollection = Mockery::mock(MongoCollection::class);
        $lpaCollection->shouldReceive('setReadPreference');
        $lpaCollection->shouldReceive('count')->andReturn(1);
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withLpaCollection($lpaCollection)->build();

        $entity = $resource->fetch('welshlanguage');

        $start = new \DateTime('first day of this month');
        $start->setTime(0, 0, 0);

        $end = new \DateTime('last day of this month');
        $end->setTime(23, 59, 59);

        $expectedStats = array();
        for ($i = 1; $i <=4; $i++) {
            $expectedStats[date('Y-m', $start->getTimestamp())] = [
                'completed' => 1,
                'contactInEnglish' => 1,
                'contactInWelsh' => 1
            ];

            $start->modify("first day of -1 month");
            $end->modify("last day of -1 month");
        }

        $this->assertEquals(new Entity($expectedStats), $entity);

        $resourceBuilder->verify();
    }
}