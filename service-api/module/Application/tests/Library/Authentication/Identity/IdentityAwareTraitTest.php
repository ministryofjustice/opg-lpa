<?php

namespace ApplicationTest\Library\Authentication\Identity;

use RuntimeException;
use Application\Library\Authentication\Identity\IdentityInterface;
use Library\Authentication\Identity\TestableIdentityAwareTrait;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class IdentityAwareTraitTest extends MockeryTestCase
{
    public function testGetIdentityNotSet(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No identity set');

        $identityAwareInstance = new TestableIdentityAwareTrait(null);
        $identityAwareInstance->getIdentity();
    }

    public function testGetIdentitySet(): void
    {
        $identityInterface = Mockery::mock(IdentityInterface::class);

        $identityAwareInstance = new TestableIdentityAwareTrait($identityInterface);
        $this->assertEquals($identityInterface, $identityAwareInstance->getIdentity());
    }

    public function testSetIdentity(): void
    {
        $identityInterface = Mockery::mock(IdentityInterface::class);

        $identityAwareInstance = new TestableIdentityAwareTrait(null);
        $identityAwareInstance->setIdentity($identityInterface);

        $this->assertEquals($identityInterface, $identityAwareInstance->getIdentity());
    }
}
