<?php

namespace ApplicationTest\Model\Service\Authentication;

use Application\Model\Service\Authentication\Adapter\AdapterInterface as LpaAdapterInterface;
use Application\Model\Service\Authentication\AuthenticationService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use RuntimeException;
use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\Result;
use Zend\Authentication\Storage\StorageInterface;

class AuthenticationServiceTest extends MockeryTestCase
{
    /**
     * @var $storageInterface StorageInterface|MockInterface
     */
    private $storageInterface;

    /**
     * @var $adapterInterface AdapterInterface|MockInterface
     */
    private $adapterInterface;

    /**
     * @var $service AuthenticationService
     */
    private $service;

    public function setUp()
    {
        parent::setUp();

        $this->storageInterface = Mockery::mock(StorageInterface::class);

        $this->adapterInterface = Mockery::mock(LpaAdapterInterface::class);

        $this->service = new AuthenticationService($this->storageInterface, $this->adapterInterface);
    }

    public function testConstructor() : void
    {
        $this->assertEquals($this->storageInterface, $this->service->getStorage());
        $this->assertEquals($this->adapterInterface, $this->service->getAdapter());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testConstructorRequiresLpaAdapterInterface() : void
    {
        /** @var AdapterInterface $adapterInterface */
        $adapterInterface = Mockery::mock(AdapterInterface::class);

        // Setting the expected message in code, as splitting the string didn't work in the annotation
        $this->expectExceptionMessage('An Application\Model\Service\Authentication\Adapter\AdapterInterface' .
            ' authentication adapter must be injected into' .
            ' Application\Model\Service\Authentication\AuthenticationService at instantiation');
        $this->service = new AuthenticationService($this->storageInterface, $adapterInterface);
    }

    public function testVerify() : void
    {
        $authenticated = Mockery::mock(Result::class);
        $authenticated->shouldReceive('isValid')->times(2)->andReturn(true);
        $authenticated->shouldReceive('getIdentity')->once()->andReturn('identity');

        $this->adapterInterface->shouldReceive('authenticate')->once()->andReturn($authenticated);

        $this->storageInterface->shouldReceive('write')->withArgs(['identity'])->once();

        $result = $this->service->verify();

        $this->assertTrue($result);
    }

    public function testVerifyInvalid() : void
    {
        $authenticated = Mockery::mock(Result::class);
        $authenticated->shouldReceive('isValid')->times(2)->andReturn(false);

        $this->adapterInterface->shouldReceive('authenticate')->once()->andReturn($authenticated);

        $result = $this->service->verify();

        $this->assertFalse($result);
    }

    public function testSetEmail() : void
    {
        $this->adapterInterface->shouldReceive('setEmail')->withArgs(['test@email.com'])->once();

        $result = $this->service->setEmail('test@email.com');

        $this->assertEquals($this->service, $result);
    }

    public function testSetPassword() : void
    {
        $this->adapterInterface->shouldReceive('setPassword')->withArgs(['test-password'])->once();

        $result = $this->service->setPassword('test-password');

        $this->assertEquals($this->service, $result);
    }

    public function testGetSessionExpiry() : void
    {
        $identity = Mockery::mock();
        $identity->shouldReceive('token')->andReturn('4321');

        $this->storageInterface->shouldReceive('isEmpty')->once()->andReturn(false);
        $this->storageInterface->shouldReceive('read')->once()->andReturn($identity);

        $this->adapterInterface->shouldReceive('getSessionExpiry')
            ->withArgs(['4321'])
            ->once()
            ->andReturn(['valid' => true, 'remainingSeconds' => 1234]);

        $result = $this->service->getSessionExpiry();

        $this->assertEquals(1234, $result);
    }

    public function testGetSessionExpiryNoIdentity() : void
    {
        $this->storageInterface->shouldReceive('isEmpty')->once()->andReturn(false);
        $this->storageInterface->shouldReceive('read')->once()->andReturn(null);

        $result = $this->service->getSessionExpiry();

        $this->assertEquals(null, $result);
    }

    public function testGetSessionExpiryNoToken() : void
    {
        $identity = Mockery::mock();
        $identity->shouldReceive('token')->andReturn(null);

        $this->storageInterface->shouldReceive('isEmpty')->once()->andReturn(false);
        $this->storageInterface->shouldReceive('read')->once()->andReturn($identity);

        $result = $this->service->getSessionExpiry();

        $this->assertEquals(null, $result);
    }

    public function testGetSessionExpirySessionNotValid() : void
    {
        $identity = Mockery::mock();
        $identity->shouldReceive('token')->andReturn('4321');

        $this->storageInterface->shouldReceive('isEmpty')->once()->andReturn(false);
        $this->storageInterface->shouldReceive('read')->once()->andReturn($identity);

        $this->adapterInterface->shouldReceive('getSessionExpiry')
            ->withArgs(['4321'])
            ->once()
            ->andReturn(['valid' => false]);

        $result = $this->service->getSessionExpiry();

        $this->assertEquals(null, $result);
    }

    public function testGetSessionExpirySessionReturnsUnexpectedValues() : void
    {
        $identity = Mockery::mock();
        $identity->shouldReceive('token')->andReturn('4321');

        $this->storageInterface->shouldReceive('isEmpty')->once()->andReturn(false);
        $this->storageInterface->shouldReceive('read')->once()->andReturn($identity);

        $this->adapterInterface->shouldReceive('getSessionExpiry')
            ->withArgs(['4321'])
            ->once()
            ->andReturn(1);

        $result = $this->service->getSessionExpiry();

        $this->assertEquals(null, $result);
    }
}
