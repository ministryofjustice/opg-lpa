<?php

namespace Application\Model\Rest;

interface EntityInterface extends RouteProviderInterface
{
    public function toArray();
}
