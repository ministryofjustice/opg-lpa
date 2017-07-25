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
        $resource = new TestableResource();
        parent::buildMocks($resource, false);

        if ($this->user !== null) {
            if ($this->toDelete === null) {
                $this->lpaCollection->shouldNotReceive('save');
            } else {
                $this->lpaCollection->shouldReceive('findOne')
                    ->with(['_id' => (int)$this->toDelete->id, 'user' => $this->user->id], [ '_id'=>true ])
                    ->andReturn(['_id' => $this->toDelete->id]);
                $this->lpaCollection->shouldReceive('find')
                    ->with(['user' => $this->user->id], [ '_id'=>true ])
                    ->andReturn([['_id' => $this->toDelete->id]]);
                $this->lpaCollection->shouldReceive('save');
            }

            if ($this->lpas === null) {
                $this->lpaCollection->shouldReceive('find')
                    ->with(['user' => $this->user->id])
                    ->andReturn($this->getDefaultCursor());
            } else {
                $this->lpaCollection->shouldReceive('count')
                    ->with(['user' => $this->user->id])
                    ->andReturn(count($this->lpas));
                $this->lpaCollection->shouldReceive('find')
                    ->with(['user' => $this->user->id], ['sort' => [ 'updatedAt' => -1 ], 'skip' => 0, 'limit' => 250])
                    ->andReturn(new DummyLpaMongoCursor($this->lpas));

                foreach ($this->lpas as $lpa) {
                    $this->lpaCollection->shouldReceive('count')
                        ->with(['user' => $this->user->id, '_id' => $lpa->id])
                        ->andReturn(count($this->lpas));
                    $this->lpaCollection->shouldReceive('find')
                        ->with(['user' => $this->user->id, '_id' => $lpa->id])
                        ->andReturn(new DummyLpaMongoCursor([$lpa]));
                }

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