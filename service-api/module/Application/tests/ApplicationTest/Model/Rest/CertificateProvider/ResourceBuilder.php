<?php

namespace ApplicationTest\Model\Rest\CertificateProvider;

use Application\Model\Rest\CertificateProvider\Resource as CertificateProviderResource;
use ApplicationTest\AbstractResourceBuilder;

class ResourceBuilder extends AbstractResourceBuilder
{

    /**
     * @return CertificateProviderResource
     */
    public function build()
    {
        /** @var CertificateProviderResource $resource */
        $resource = parent::buildMocks(CertificateProviderResource::class);
        return $resource;
    }
}