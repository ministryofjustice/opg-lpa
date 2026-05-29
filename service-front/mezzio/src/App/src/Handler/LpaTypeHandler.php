<?php

declare(strict_types=1);

namespace App\Handler;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\CsrfValidationMiddleware;
use App\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Handles the LPA type form when no LPA yet exists (/lpa/type, route "lpa-type-no-id").
 * Creates a new LPA application then sets its type.
 */
class LpaTypeHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    private const string ROUTE_NAME = 'lpa-type-no-id';

    private const FLASH_KEY_ERROR = 'flash_error';

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $csrfToken = $request->getAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE);

        $form = $this->formElementManager->get('Application\Form\Lpa\TypeForm');

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $data = $request->getParsedBody() ?? [];
            if (!is_array($data)) {
                $data = [];
            }

            $form->setData($data);

            if ($form->isValid()) {
                $lpa = $this->lpaApplicationService->createApplication();

                if (!$lpa instanceof Lpa) {
                    $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
                    if ($session instanceof SessionInterface) {
                        $session->set(self::FLASH_KEY_ERROR, ['Error creating a new LPA. Please try again.']);
                    }
                    return new RedirectResponse('/user/dashboard');
                }

                $formData = $form->getData();
                $lpaType = is_array($formData) ? ($formData['type'] ?? '') : '';

                if (!$this->lpaApplicationService->setType($lpa, $lpaType)) {
                    throw new RuntimeException('API client failed to set LPA type for id: ' . $lpa->id);
                }

                $flowChecker = new FormFlowChecker($lpa);
                $nextRoute = $flowChecker->nextRoute(self::ROUTE_NAME);

                return new RedirectResponse(
                    $this->urlHelper->generate(
                        $nextRoute,
                        ['lpa-id' => $lpa->id],
                        $flowChecker->getRouteOptions($nextRoute)
                    )
                );
            }
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/type/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form'             => $form,
                    'isChangeAllowed'  => true,
                    'currentRouteName' => self::ROUTE_NAME,
                    'csrfToken'        => $csrfToken,
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
