<?php

namespace Application\Library\Hal;

use Application\Model\Rest\EntityInterface;
use Application\Model\Rest\Applications\Entity as ApplicationsEntity;
use Application\Model\Rest\Stats\Entity as StatsEntity;
use Application\Model\Rest\Users\Entity as UsersEntity;
use RuntimeException;

/**
 * Hal document representing a Application\Model\Rest\EntityInterface
 *
 * Class Entity
 * @package Application\Library\Hal
 */
class Entity extends Hal
{
    protected $entity;

    private $linksSet = false;

    public function __construct(EntityInterface $entity)
    {
        $this->setEntity($entity);
    }

    public function setEntity(EntityInterface $entity)
    {
        $this->linksSet = false;
        $this->entity = $entity;
        $this->setData($entity->toArray());
    }

    public function getLinks()
    {
        if (!$this->linksSet) {
            throw new RuntimeException('Cannot return links until they have been set.');
        }

        return parent::getLinks();
    }

    /**
     * Apply the links using the passed route generator.
     *
     * @param callable $routeCallback
     */
    public function setLinks(callable $routeCallback)
    {
        if (!$this->entity instanceof StatsEntity) {
            if ($this->entity instanceof UsersEntity) {
                $callbackParam = 'api-v1/user';
            } elseif ($this->entity instanceof ApplicationsEntity) {
                $callbackParam = 'api-v1/user/level-1';
                $this->addLink('user', call_user_func($routeCallback, 'api-v1/user', $this->entity));
            } else {
                $callbackParam = 'api-v1/user/level-2';
                $this->addLink('user', call_user_func($routeCallback, 'api-v1/user', $this->entity));
                $this->addLink('application', call_user_func($routeCallback, 'api-v1/user/level-1', $this->entity));
            }

            $this->setUri(call_user_func($routeCallback, $callbackParam, $this->entity));
        }

        $this->linksSet = true;
    }
}
