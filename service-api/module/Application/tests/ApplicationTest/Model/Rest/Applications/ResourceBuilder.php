<?php

namespace ApplicationTest\Model\Rest\Applications;

use ApplicationTest\AbstractResourceBuilder;
use Mockery;
use MongoDB\Driver\Query;
use Opg\Lpa\DataModel\Lpa\Lpa;

class ResourceBuilder extends AbstractResourceBuilder
{
    private $insert = false;
    private $toDelete;
    private $lpas;

    /**
     * @return TestableResource
     */
    public function build()
    {
        /** @var TestableResource $resource */
        $resource = parent::buildMocks(TestableResource::class, false);
        //$resource = new TestableResource($this->lpaCollection);

        if ($this->user !== null) {
            if ($this->toDelete === null) {
                $this->lpaCollection->shouldNotReceive('replaceOne');
            } else {
                $this->lpaCollection->shouldReceive('findOne')
                    ->withArgs([
                        ['_id' => (int)$this->toDelete->id, 'user' => $this->user->id],
                        ['projection' => ['_id'=>true]]
                    ])
                    ->andReturn(['_id' => $this->toDelete->id]);
                $this->lpaCollection->shouldReceive('find')
                    ->withArgs([['user' => $this->user->id], [ '_id'=>true ]])
                    ->andReturn([['_id' => $this->toDelete->id]]);
                $this->lpaCollection->shouldReceive('replaceOne');
            }

            if ($this->lpas === null) {
                $this->lpaCollection->shouldReceive('count')
                    ->withArgs([['user' => $this->user->id]])
                    ->andReturn(0);
                $this->lpaCollection->shouldReceive('find')
                    ->withArgs([['user' => $this->user->id]])
                    ->andReturn($this->getDefaultCursor());
            } else {
                $options = ['sort' => ['updatedAt' => -1], 'skip' => 0, 'limit' => 250];

                $this->lpaCollection->shouldReceive('count')
                    ->withArgs([['user' => $this->user->id]])
                    ->andReturn(count($this->lpas));
                $this->lpaCollection->shouldReceive('find')
                    ->withArgs([['user' => $this->user->id], $options])
                    ->andReturn(new DummyLpaMongoCursor($this->lpas));

                foreach ($this->lpas as $lpa) {
                    $this->lpaCollection->shouldReceive('count')
                        ->withArgs([['user' => $this->user->id, '_id' => $lpa->id]])
                        ->andReturn(count($this->lpas));
                    $this->lpaCollection->shouldReceive('find')
                        ->withArgs([['user' => $this->user->id, '_id' => $lpa->id], $options])
                        ->andReturn(new DummyLpaMongoCursor([$lpa]));
                }

                //Defaults
                $this->lpaCollection->shouldReceive('count')
                    ->andReturn(0);
                $this->lpaCollection->shouldReceive('find')
                    ->andReturn($this->getDefaultCursor());
            }
        }

        if ($this->insert) {
            $this->lpaCollection->shouldReceive('insertOne')->once();
        } else {
            $this->lpaCollection->shouldNotReceive('insertOne');
        }

        $this->lpaCollection->shouldReceive('findOne')->andReturn(null);

        return $resource;
    }

    /**
     * @param bool $insert
     * @return ResourceBuilder
     */
    public function withInsert($insert)
    {
        $this->insert = $insert;
        return $this;
    }

    /**
     * @param Lpa $toDelete
     * @return ResourceBuilder
     */
    public function withToDelete($toDelete)
    {
        $this->toDelete = $toDelete;
        return $this;
    }

    /**
     * @param Lpa[] $lpas
     * @return ResourceBuilder
     */
    public function withLpas($lpas)
    {
        $this->lpas = $lpas;
        return $this;
    }
}
