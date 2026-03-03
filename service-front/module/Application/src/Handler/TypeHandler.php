<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Form\Lpa\TypeForm;
use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Lpa;
use Application\Helper\MvcUrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Handles the LPA type form for an existing LPA (/lpa/:lpa-id/type, route "lpa/form-type").
 * The LPA and FlowChecker are injected as request attributes by LpaLoaderListener.
 */
class TypeHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly MvcUrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE);

        /** @var TypeForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\TypeForm');

        $isChangeAllowed = true;

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $data = $request->getParsedBody() ?? [];
            if (!is_array($data)) {
                $data = [];
            }

            $form->setData($data);

            if ($form->isValid()) {
                $formData = $form->getData();
                $lpaType = is_array($formData) ? ($formData['type'] ?? '') : '';

                if ($lpaType !== $lpa->document->type) {
                    if (!$this->lpaApplicationService->setType($lpa, $lpaType)) {
                        throw new RuntimeException(
                            'API client failed to set LPA type for id: ' . $lpa->id
                        );
                    }
                }

                $nextRoute = $flowChecker->nextRoute($currentRoute);

                return new RedirectResponse(
                    $this->urlHelper->generate(
                        $nextRoute,
                        ['lpa-id' => $lpa->id],
                        $flowChecker->getRouteOptions($nextRoute)
                    )
                );
            }
        } elseif ($lpa->document instanceof Document) {
            $form->bind($lpa->document->flatten());

            if ($lpa->document->donor instanceof Donor) {
                $isChangeAllowed = false;
            }
        }

        $nextRoute = $flowChecker->nextRoute($currentRoute);

        $nextUrl = $this->urlHelper->generate(
            $nextRoute,
            ['lpa-id' => $lpa->id],
            $flowChecker->getRouteOptions($nextRoute)
        );

        $cloneUrl = $this->urlHelper->generate(
            'user/dashboard/create-lpa',
            ['lpa-id' => $lpa->id]
        );

        $html = $this->renderer->render(
            'application/authenticated/lpa/type/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form'            => $form,
                    'cloneUrl'        => $cloneUrl,
                    'nextUrl'         => $nextUrl,
                    'isChangeAllowed' => $isChangeAllowed,
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
