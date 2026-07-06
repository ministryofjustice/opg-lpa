<?php

declare(strict_types=1);

namespace AppTest\Service\Payment\GovPay\Response;

use App\Service\Payment\GovPay\Exception\UnexpectedValueException;
use App\Service\Payment\GovPay\Response\Event;
use App\Service\Payment\GovPay\Response\Events;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Events::class)]
class EventsTest extends TestCase
{
    private function makeEventData(): array
    {
        return ['payment_id' => 'pay-1', 'updated' => '2024-01-01T00:00:00Z', 'state' => []];
    }

    public function testConstructorMapsEventsToEventObjects(): void
    {
        $events = new Events([
            'payment_id' => 'pay-1',
            'events'     => [$this->makeEventData(), $this->makeEventData()],
        ]);

        $this->assertCount(2, $events);

        foreach ($events as $event) {
            $this->assertInstanceOf(Event::class, $event);
        }
    }

    public function testConstructorSetsPaymentId(): void
    {
        $events = new Events([
            'payment_id' => 'pay-abc',
            'events'     => [],
        ]);

        $this->assertSame('pay-abc', $events->payment_id);
    }

    public function testConstructorDefaultsPaymentIdToEmptyString(): void
    {
        $events = new Events(['events' => []]);

        $this->assertSame('', $events->payment_id);
    }

    public function testConstructorThrowsWhenEventsKeyMissing(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Events response missing 'events' key");

        new Events(['payment_id' => 'pay-1']);
    }

    public function testConstructorThrowsWhenEventsIsNotAnArray(): void
    {
        $this->expectException(UnexpectedValueException::class);

        new Events(['payment_id' => 'pay-1', 'events' => 'not-an-array']);
    }

    public function testConstructorHandlesEmptyEventsArray(): void
    {
        $events = new Events(['payment_id' => 'pay-1', 'events' => []]);
        $this->assertCount(0, $events);
    }

    public function testBuildFromResponseProducesEventsInstance(): void
    {
        $body = json_encode([
            'payment_id' => 'pay-1',
            'events'     => [$this->makeEventData()],
        ]);

        $events = Events::buildFromResponse(new GuzzleResponse(200, [], $body));

        $this->assertInstanceOf(Events::class, $events);
        $this->assertCount(1, $events);
    }
}
