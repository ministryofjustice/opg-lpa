<?php

namespace ApplicationTest\Model\Service;

use Application\Model\Service\ApiClient\Exception\ApiException;
use DateInterval;
use DateTime;
use Exception;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;

class ServiceTestHelper
{
    public static function createApiException(
        string $message = 'Test error',
        int $status = 500,
        $body = '{}'
    ) : ApiException {
        /** @var ResponseInterface|MockInterface $response */
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->andReturn($body);
        $response->shouldReceive('getStatusCode')->andReturn($status);

        return new ApiException($response, $message);
    }

    /**
     * @param DateTime $expected
     * @param DateTime $actual
     * @param int $secondsOut
     * @throws Exception
     */
    public static function assertTimeNear(DateTime $expected, DateTime $actual, int $secondsOut = 1) : void
    {
        $upperLimit = (clone $expected)->add(new DateInterval('PT' . $secondsOut . 'S'));
        Assert::assertLessThanOrEqual($upperLimit, $actual);

        $lowerLimit = (clone $expected)->sub(new DateInterval('PT' . $secondsOut . 'S'));
        Assert::assertGreaterThanOrEqual($lowerLimit, $actual);
    }
}
