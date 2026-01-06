<?php

namespace MakeSharedTest\DataModel\Lpa\Document\Attorneys;

use InvalidArgumentException;
use MakeShared\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeSharedTest\DataModel\FixturesData;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class AbstractAttorneyTest extends TestCase
{
    public function testFactoryNotJson()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON passed to constructor');
        $data = 'Not JSON';
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

        $this->assertEquals(3, count($metadata->getConstrainedProperties()));
        $this->assertContains('id', $metadata->getConstrainedProperties());
        $this->assertContains('address', $metadata->getConstrainedProperties());
        $this->assertContains('email', $metadata->getConstrainedProperties());
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
