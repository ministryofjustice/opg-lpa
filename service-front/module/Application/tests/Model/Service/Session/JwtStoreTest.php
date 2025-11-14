<?php

namespace ApplicationTest\Model\Service\Session;

use Application\Model\Service\Session\JwtStore;
use Laminas\Diactoros\ServerRequestFactory;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\TestCase;

final class JwtStoreTest extends TestCase
{
    public function testFromRequestThrowsIfNoSession(): void
    {
        $this->expectException(\RuntimeException::class);
        $req = ServerRequestFactory::fromGlobals();
        JwtStore::fromRequest($req);
    }

    public function testSetGetHasClear(): void
    {
        $session = new InMemorySession();
        $req = ServerRequestFactory::fromGlobals()
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session);

        $store = JwtStore::fromRequest($req);

        $this->assertIsArray($session->get('jwt-payload'));

        $this->assertFalse($store->has('token'));
        $this->assertNull($store->get('token'));

        $store->set('token', 'abc');
        $this->assertTrue($store->has('token'));
        $this->assertSame('abc', $store->get('token'));

        $store->set('csrf', 'xyz');
        $this->assertSame(['token' => 'abc', 'csrf' => 'xyz'], $session->get('jwt-payload'));

        $store->clear();
        $this->assertSame([], $session->get('jwt-payload'));
        $this->assertFalse($store->has('token'));
    }
}
