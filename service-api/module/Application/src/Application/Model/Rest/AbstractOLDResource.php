<?php

namespace Application\Model\Rest;

abstract class AbstractOLDResource extends AbstractResource
{
    const TYPE_SINGULAR = 'singular';
    const TYPE_COLLECTION = 'collections';

    /**
     * Resource name
     *
     * @var string
     */
    protected $name;

    /**
     * Resource identifier
     *
     * @var string
     */
    protected $identifier;

    /**
     * Resource type
     *
     * @var string
     */
    protected $type;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getType()
    {
        return $this->type;
    }
}
