<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Router\RouteMatch;
use MakeShared\DataModel\Lpa\Lpa;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CreateLpaHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly FlashMessenger $flashMessenger,
        private readonly SessionManagerSupport $sessionManagerSupport,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeMatch = $request->getAttribute(RouteMatch::class);
        $seedId = $routeMatch?->getParam('lpa-id');

        if ($seedId !== null) {
            $seedId = (string) $seedId;

            $lpa = $this->lpaApplicationService->createApplication();

            if (!$lpa instanceof Lpa) {
                $this->flashMessenger->addErrorMessage(
                    'Error creating a new LPA. Please try again.'
                );

                return new RedirectResponse('/user/dashboard');
            }

            $result = $this->lpaApplicationService->setSeed($lpa, $seedId);

            $this->resetSessionCloneData($seedId);

            if ($result !== true) {
                $this->flashMessenger->addWarningMessage(
                    'LPA created but could not set seed'
                );
            }

            return new RedirectResponse(sprintf('/lpa/%s/type', $lpa->id));
        }

        return new RedirectResponse('/lpa/type');
    }

    private function resetSessionCloneData(string $seedId): void
    {
        $session = $this->sessionManagerSupport->getSessionManager()->getStorage();

        if (isset($session['cloneData'][$seedId])) {
            unset($session['cloneData'][$seedId]);
        }
    }
}
