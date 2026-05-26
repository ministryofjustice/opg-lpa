<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CreateLpaHandler implements RequestHandlerInterface
{
    private const SESSION_KEY_CLONE_DATA = 'clone_data';
    private const FLASH_KEY_ERROR = 'flash_error';
    private const FLASH_KEY_WARNING = 'flash_warning';

    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        assert($session instanceof SessionInterface);

        $seedId = $request->getAttribute('lpa-id');

        if ($seedId !== null) {
            $seedId = (string) $seedId;

            $lpa = $this->lpaApplicationService->createApplication();

            if (!$lpa instanceof Lpa) {
                $session->set(self::FLASH_KEY_ERROR, ['Error creating a new LPA. Please try again.']);
                return new RedirectResponse('/user/dashboard');
            }

            $result = $this->lpaApplicationService->setSeed($lpa, $seedId);

            $this->resetSessionCloneData($session, $seedId);

            if ($result !== true) {
                $session->set(self::FLASH_KEY_WARNING, ['LPA created but could not set seed']);
            }

            return new RedirectResponse(sprintf('/lpa/%s/type', $lpa->id));
        }

        return new RedirectResponse('/lpa/type');
    }

    private function resetSessionCloneData(SessionInterface $session, string $seedId): void
    {
        $cloneData = $session->get(self::SESSION_KEY_CLONE_DATA);

        if (is_array($cloneData) && isset($cloneData[$seedId])) {
            unset($cloneData[$seedId]);
            $session->set(self::SESSION_KEY_CLONE_DATA, $cloneData);
        }
    }
}
