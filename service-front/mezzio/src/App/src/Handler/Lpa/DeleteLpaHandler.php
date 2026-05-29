<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\RedirectResponse;
use App\View\Twig\FlashMessenger;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DeleteLpaHandler implements RequestHandlerInterface
{
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
            /** @var FlashMessagesInterface $flash */
            $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
            $flash->flash(FlashMessenger::ERROR, ['LPA could not be deleted']);
        }

        if (is_numeric($page)) {
            return new RedirectResponse(sprintf('/user/dashboard/page/%s', $page));
        }

        return new RedirectResponse('/user/dashboard');
    }
}
