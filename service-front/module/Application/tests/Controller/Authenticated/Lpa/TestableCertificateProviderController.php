<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CertificateProviderController;

class TestableCertificateProviderController extends CertificateProviderController
{
    public function testGetActorReuseDetails($includeTrusts = true, $forCorrespondent = false)
    {
        return parent::getActorReuseDetails($includeTrusts, $forCorrespondent);
    }
}
