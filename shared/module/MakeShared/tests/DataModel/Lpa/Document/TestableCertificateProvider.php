<?php

namespace MakeSharedTest\DataModel\Lpa\Document;

use MakeShared\DataModel\Lpa\Document\CertificateProvider;

class TestableCertificateProvider extends CertificateProvider
{
    public function testMap($property, $v)
    {
        return parent::map($property, $v);
    }
}
