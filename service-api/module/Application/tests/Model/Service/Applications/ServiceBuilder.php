<?php

namespace ApplicationTest\Model\Service\Applications;

use ApplicationTest\Model\Service\AbstractServiceBuilder;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Mockery;
use Mockery\MockInterface;
use Traversable;

class ServiceBuilder extends AbstractServiceBuilder
{
    private $insert = false;
    private $toDelete;
    private $lpas;

    /**
     * @return TestableService
     */
    public function build()
    {
        /** @var TestableService $service */
        $service = parent::buildMocks(TestableService::class, false);

        if ($this->user !== null) {
            if ($this->toDelete === null) {
                $this->apiLpaCollection->shouldNotReceive('replaceOne');
            } else {
                $this->apiLpaCollection->shouldReceive('findOne')
                    ->withArgs([
                        ['_id' => (int)$this->toDelete->id, 'user' => $this->user->id],
                        ['projection' => ['_id'=>true]]
                    ])
                    ->andReturn(['_id' => $this->toDelete->id]);
                $this->apiLpaCollection->shouldReceive('find')
                    ->withArgs([['user' => $this->user->id], [ '_id'=>true ]])
                    ->andReturn([['_id' => $this->toDelete->id]]);
                $this->apiLpaCollection->shouldReceive('replaceOne');
            }

            if ($this->lpas === null) {
                $this->apiLpaCollection->shouldReceive('count')
                    ->withArgs([['user' => $this->user->id]])
                    ->andReturn(0);
                $this->apiLpaCollection->shouldReceive('find')
                    ->withArgs([['user' => $this->user->id]])
                    ->andReturn($this->getDefaultCursor());
            } else {
                $options = ['sort' => ['updatedAt' => -1], 'skip' => 0, 'limit' => 250];

                $this->apiLpaCollection->shouldReceive('count')
                    ->withArgs([['user' => $this->user->id]])
                    ->andReturn(count($this->lpas));
                $this->apiLpaCollection->shouldReceive('find')
                    ->withArgs([['user' => $this->user->id], $options])
                    ->andReturn(new DummyLpaMongoCursor($this->lpas));

                foreach ($this->lpas as $lpa) {
                    $this->apiLpaCollection->shouldReceive('count')
                        ->withArgs([['user' => $this->user->id, '_id' => $lpa->id]])
                        ->andReturn(count($this->lpas));
                    $this->apiLpaCollection->shouldReceive('find')
                        ->withArgs([['user' => $this->user->id, '_id' => $lpa->id], $options])
                        ->andReturn(new DummyLpaMongoCursor([$lpa]));
                }

                //Defaults
                $this->apiLpaCollection->shouldReceive('count')
                    ->andReturn(0);
                $this->apiLpaCollection->shouldReceive('find')
                    ->andReturn($this->getDefaultCursor());
            }
        }

        if ($this->insert) {
            $this->apiLpaCollection->shouldReceive('insertOne')->once();
        } else {
            $this->apiLpaCollection->shouldNotReceive('insertOne');
        }

        $this->apiLpaCollection->shouldReceive('findOne')->andReturn(null);

        return $service;
    }

    /**
     * @return MockInterface
     */
    private function getDefaultCursor()
    {
        $defaultCursor = Mockery::mock(Traversable::class);
        $defaultCursor->shouldReceive('toArray')->andReturn([]);
        return $defaultCursor;
    }

    /**
     * @param bool $insert
     * @return ServiceBuilder
     */
    public function withInsert($insert)
    {
        $this->insert = $insert;
        return $this;
    }

    /**
     * @param Lpa $toDelete
     * @return ServiceBuilder
     */
    public function withToDelete($toDelete)
    {
        $this->toDelete = $toDelete;
        return $this;
    }

    /**
     * @param Lpa[] $lpas
     * @return ServiceBuilder
     */
    public function withLpas($lpas)
    {
        $this->lpas = $lpas;
        return $this;
    }
}
