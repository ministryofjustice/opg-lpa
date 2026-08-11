<?php

declare(strict_types=1);

namespace App\Handler;

use App\Authentication\AuthenticationService;
use App\Form\SharedSpace\JoinSharedSpaceForm;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\ApiClient\Exception\ApiException;
use App\Service\SharedSpace\SharedSpaceService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JoinSharedSpaceHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly SharedSpaceService $sharedSpaceService,
        private readonly AuthenticationService $authenticationService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var JoinSharedSpaceForm $form */
        $form = $this->formElementManager->get(JoinSharedSpaceForm::class);

        $csrfToken = $request->getAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE);
        $joinError = null;

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $data = $request->getParsedBody() ?? [];
            if (!is_array($data)) {
                $data = [];
            }

            $form->setData($data);

            if ($form->isValid()) {
                try {
                    $sharedSpaceId = $this->sharedSpaceService->join(
                        $form->get('sharedSpaceName')->getValue(),
                        $form->get('sharedSpaceAccessCode')->getValue(),
                    );

                    $this->authenticationService->refreshSharedSpaceId($sharedSpaceId);

                    return new RedirectResponse('/shared-space/dashboard');
                } catch (ApiException $e) {
                    switch ($e->getBody('detail')) {
                        case 'user-already-in-shared-space':
                            $this->authenticationService->refreshSharedSpaceId($e->getBody('sharedSpaceId'));
                            return new RedirectResponse('/shared-space/dashboard');

                        case 'invite-not-found':
                            $joinError = 'The shared space name and/or access code are incorrect';
                            break;

                        default:
                            $joinError = 'Something unexpected happened';
                    };
                }
            }
        }

        $html = $this->renderer->render(
            'application/authenticated/shared-space/join.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form'      => $form,
                    'csrfToken' => $csrfToken,
                    'joinError' => $joinError,
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
