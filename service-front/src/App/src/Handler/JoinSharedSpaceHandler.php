<?php

declare(strict_types=1);

namespace App\Handler;

use App\Authentication\AuthenticationService;
use App\Form\SharedSpace\JoinSharedSpaceForm;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Service\ApiClient\Exception\ApiException;
use App\Service\SharedSpace\SharedSpaceService;
use DateTime;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\User\User;
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

        $joinError = null;

        /** @var User|null $userDetails */
        $userDetails = $request->getAttribute(RequestAttribute::USER_DETAILS);

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
                        $userDetails?->getEmail()->getAddress()
                    );

                    $this->authenticationService->refreshSharedSpaceId($sharedSpaceId);

                    return new RedirectResponse('/shared-space/dashboard?joined=success');
                } catch (ApiException $e) {
                    switch ($e->getBody('detail')) {
                        case 'user-already-in-shared-space':
                            $this->authenticationService->refreshSharedSpaceId($e->getBody('sharedSpaceId'));
                            return new RedirectResponse('/shared-space/dashboard');

                        case 'invite-not-matched':
                            $joinError = 'The shared space name and joining code must be entered exactly as they appear in the email inviting you to join.';
                            break;

                        case 'invite-not-found':
                            return new HtmlResponse(
                                $this->renderer->render(
                                    'application/authenticated/shared-space/invite-not-found.twig',
                                    array_merge(
                                        $this->getTemplateVariables($request),
                                        ['email' => $userDetails->getEmail()->getAddress()],
                                    ),
                                ),
                            );
                            break;

                        case 'invite-expired':
                            return new HtmlResponse(
                                $this->renderer->render(
                                    'application/authenticated/shared-space/invite-expired.twig',
                                    array_merge(
                                        $this->getTemplateVariables($request),
                                        [
                                            'inviteExpiry' => (new DateTime($e->getBody('expires')))->format('j F Y'),
                                            'sharedSpaceName' => $form->get('sharedSpaceName')->getValue(),
                                        ],
                                    ),
                                ),
                            );
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
                    'form'           => $form,
                    'joinError'      => $joinError,
                    'isProfessional' => $userDetails->getIsProfessional(),
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
