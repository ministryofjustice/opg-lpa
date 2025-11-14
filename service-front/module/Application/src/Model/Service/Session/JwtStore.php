<?php

namespace Application\Model\Service\Session;

use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class JwtStore
{
    private const SESSION_KEY = 'jwt-payload';

    private function __construct(private SessionInterface $session)
    {
        if (!is_array($session->get(self::SESSION_KEY))) {
            $session->set(self::SESSION_KEY, []);
        }
    }

    public static function fromRequest(ServerRequestInterface $request): self
    {
        /** @var SessionInterface|null $session */
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        if (!$session instanceof SessionInterface) {
            throw new RuntimeException('SessionInterface not available on request');
        }
        return new self($session);
    }

    public function get(string $key): mixed
    {
        $jwtPayload = $this->session->get(self::SESSION_KEY) ?? [];
        return $jwtPayload[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $jwtPayload = $this->session->get(self::SESSION_KEY) ?? [];
        $jwtPayload[$key] = $value;
        $this->session->set(self::SESSION_KEY, $jwtPayload);
    }

    public function clear(): void
    {
        $this->session->set(self::SESSION_KEY, []);
    }

    public function has(string $key): bool
    {
        $jwtPayload = $this->session->get(self::SESSION_KEY) ?? [];
        return array_key_exists($key, $jwtPayload);
    }
}
