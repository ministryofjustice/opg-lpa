<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DeleteLpaHandler implements RequestHandlerInterface
{
    private const FLASH_KEY_ERROR = 'flash_error';

    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        $page = $queryParams['page'] ?? null;
        $lpaId = $request->getAttribute('lpa-id');

        if ($this->lpaApplicationService->deleteApplication($lpaId) !== true) {
            $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
            if ($session instanceof SessionInterface) {
                $session->set(self::FLASH_KEY_ERROR, ['LPA could not be deleted']);
            }
        }

        if (is_numeric($page)) {
            return new RedirectResponse(sprintf('/user/dashboard/page/%s', $page));
        }

        return new RedirectResponse('/user/dashboard');
    }
}
