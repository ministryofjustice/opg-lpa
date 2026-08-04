<?php

declare(strict_types=1);

namespace AppTest\Service\OneLogin;

use App\Service\OneLogin\OneLoginSessionManager;
use App\Service\OneLogin\PendingLink;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OneLoginSessionManagerTest extends TestCase
{
    private const string SESSION_KEY = 'onelogin_pending_link';

    private SessionInterface&MockObject $session;
    private OneLoginSessionManager $manager;

    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
        $this->manager = new OneLoginSessionManager();
    }

    public function testSetPendingLinkStoresSubAndEmailUnderTheOwnedKey(): void
    {
        $this->session
            ->expects($this->once())
            ->method('set')
            ->with(self::SESSION_KEY, [
                'sub'   => 'urn:fdc:gov.uk:2022:newuser',
                'email' => 'newuser@example.com',
            ]);

        $this->manager->setPendingLink($this->session, 'urn:fdc:gov.uk:2022:newuser', 'newuser@example.com');
    }

    public function testGetPendingLinkReturnsDtoFromStoredData(): void
    {
        $this->session
            ->method('get')
            ->with(self::SESSION_KEY)
            ->willReturn(['sub' => 'urn:fdc:gov.uk:2022:newuser', 'email' => 'newuser@example.com']);

        $pendingLink = $this->manager->getPendingLink($this->session);

        $this->assertInstanceOf(PendingLink::class, $pendingLink);
        $this->assertSame('urn:fdc:gov.uk:2022:newuser', $pendingLink->sub);
        $this->assertSame('newuser@example.com', $pendingLink->email);
    }

    public function testGetPendingLinkDefaultsEmailToEmptyStringWhenMissing(): void
    {
        $this->session
            ->method('get')
            ->willReturn(['sub' => 'urn:fdc:gov.uk:2022:newuser']);

        $pendingLink = $this->manager->getPendingLink($this->session);

        $this->assertInstanceOf(PendingLink::class, $pendingLink);
        $this->assertSame('', $pendingLink->email);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidPendingLinkProvider(): array
    {
        return [
            'nothing stored' => [null],
            'not an array'   => ['a string'],
            'missing sub'    => [['email' => 'newuser@example.com']],
            'empty sub'      => [['sub' => '', 'email' => 'newuser@example.com']],
            'non-string sub' => [['sub' => 123, 'email' => 'newuser@example.com']],
        ];
    }

    /**
     * @param mixed $stored
     */
    #[DataProvider('invalidPendingLinkProvider')]
    public function testGetPendingLinkReturnsNullForInvalidData($stored): void
    {
        $this->session->method('get')->willReturn($stored);

        $this->assertNull($this->manager->getPendingLink($this->session));
    }

    public function testClearPendingLinkUnsetsTheOwnedKey(): void
    {
        $this->session
            ->expects($this->once())
            ->method('unset')
            ->with(self::SESSION_KEY);

        $this->manager->clearPendingLink($this->session);
    }
}
