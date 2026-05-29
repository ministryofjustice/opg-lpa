<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CreateLpaHandler implements RequestHandlerInterface
{
    private const SESSION_KEY_CLONE_DATA = 'clone_data';

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
                /** @var FlashMessagesInterface $flash */
                $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
                $flash->flash('flash_error', ['Error creating a new LPA. Please try again.']);
                return new RedirectResponse('/user/dashboard');
            }

            $result = $this->lpaApplicationService->setSeed($lpa, $seedId);

            $this->resetSessionCloneData($session, $seedId);

            if ($result !== true) {
                /** @var FlashMessagesInterface $flash */
                $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
                $flash->flash('flash_warning', ['LPA created but could not set seed']);
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
