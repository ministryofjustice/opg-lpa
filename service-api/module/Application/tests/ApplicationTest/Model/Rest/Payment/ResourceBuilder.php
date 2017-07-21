<?php

namespace ApplicationTest\Model\Rest\Payment;

use Application\Model\Rest\Payment\Resource as PaymentResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{

    /**
     * @return PaymentResource
     */
    public function build()
    {
        $resource = new PaymentResource();
        parent::buildMocks($resource);
        return $resource;
    }
}