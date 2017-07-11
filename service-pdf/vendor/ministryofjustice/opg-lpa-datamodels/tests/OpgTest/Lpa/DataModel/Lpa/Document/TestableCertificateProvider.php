<?php

namespace OpgTest\Lpa\DataModel\Lpa\Document;

use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;

class TestableCertificateProvider extends CertificateProvider
{
    public function testMap($property, $v)
    {
        return parent::map($property, $v);
    }
}
