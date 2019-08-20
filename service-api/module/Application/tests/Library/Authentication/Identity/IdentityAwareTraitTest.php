<?php

namespace ApplicationTest\Library\Authentication\Identity;

use Application\Library\Authentication\Identity\IdentityInterface;
use Library\Authentication\Identity\TestableIdentityAwareTrait;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class IdentityAwareTraitTest extends MockeryTestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No identity set
     */
    public function testGetIdentityNotSet() : void
    {
        $identityAwareInstance = new TestableIdentityAwareTrait(null);
        $identityAwareInstance->getIdentity();
    }

    public function testGetIdentitySet() : void
    {
        $identityInterface = Mockery::mock(IdentityInterface::class);

        $identityAwareInstance = new TestableIdentityAwareTrait($identityInterface);
        $this->assertEquals($identityInterface, $identityAwareInstance->getIdentity());
    }

    public function testSetIdentity() {
        $identityInterface = Mockery::mock(IdentityInterface::class);

        $identityAwareInstance = new TestableIdentityAwareTrait(null);
        $identityAwareInstance->setIdentity($identityInterface);

        $this->assertEquals($identityInterface, $identityAwareInstance->getIdentity());
    }
}
