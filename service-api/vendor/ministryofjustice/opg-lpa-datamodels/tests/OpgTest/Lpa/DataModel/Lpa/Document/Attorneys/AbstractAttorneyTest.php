<?php

namespace OpgTest\Lpa\DataModel\Lpa\Document\Attorneys;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use OpgTest\Lpa\DataModel\FixturesData;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class AbstractAttorneyTest extends \PHPUnit_Framework_TestCase
{
    public function testFactoryNotJson()
    {
        $data = 'Not JSON';

        $this->setExpectedException(\InvalidArgumentException::class, 'Invalid JSON passed to constructor');

        AbstractAttorney::factory($data);
    }

    public function testFactoryHuman()
    {
        $data = FixturesData::getAttorneyHumanJson();

        $attorney = AbstractAttorney::factory($data);

        $this->assertEquals(Human::class, get_class($attorney));
    }

    public function testFactoryTrust()
    {
        $data = FixturesData::getAttorneyTrustJson();

        $attorney = AbstractAttorney::factory($data);

        $this->assertEquals(TrustCorporation::class, get_class($attorney));
    }

    public function testFactoryTrustByNumber()
    {
        $data = FixturesData::getAttorneyTrustJson(true);

        $attorney = AbstractAttorney::factory($data);

        $this->assertEquals(TrustCorporation::class, get_class($attorney));
    }

    public function testFactoryHumanDefault()
    {
        $data = FixturesData::getAttorneyHumanJson(true);

        $attorney = AbstractAttorney::factory($data);

        $this->assertEquals(Human::class, get_class($attorney));
    }

    public function testLoadValidatorMetadata()
    {
        $metadata = new ClassMetadata(AbstractAttorney::class);

        AbstractAttorney::loadValidatorMetadata($metadata);

        $this->assertEquals(3, count($metadata->properties));
        $this->assertNotNull($metadata->properties['id']);
        $this->assertNotNull($metadata->properties['address']);
        $this->assertNotNull($metadata->properties['email']);
    }

    public function testMap()
    {
        $data = FixturesData::getAttorneyHumanJson();

        $attorney = AbstractAttorney::factory($data);

        $this->assertEquals('opglpademo+WellingtonGastri@gmail.com', $attorney->get('email')->address);

        $this->assertEquals('Severington Lane', $attorney->get('address')->address1);
        $this->assertEquals('Kingston', $attorney->get('address')->address2);
        $this->assertEquals('Burlingtop, Hertfordshire', $attorney->get('address')->address3);
        $this->assertEquals('PL1 9NE', $attorney->get('address')->postcode);
    }
}
