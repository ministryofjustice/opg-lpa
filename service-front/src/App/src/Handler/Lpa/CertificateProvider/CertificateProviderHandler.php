<?php

declare(strict_types=1);

namespace App\Handler\Lpa\CertificateProvider;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\ApiClient\Exception\ConflictException;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\Metadata;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CertificateProviderHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly UrlHelper $urlHelper,
        private readonly Metadata $metadata,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE_NAME);

        /** @var \App\Form\Lpa\BlankMainFlowForm $form */
        $form = $this->formElementManager->get('App\Form\Lpa\BlankMainFlowForm', [
            'lpa' => $lpa,
        ]);

        $conflictError = null;
        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                $ifMatchVersion = (int)$postData['version'];
                try {
                    $this->metadata->setCertificateProviderSkipped($lpa, $ifMatchVersion);

                    $nextRoute = $flowChecker->nextRoute($currentRoute);

                    return new RedirectResponse(
                        $this->urlHelper->generate(
                            $nextRoute,
                            ['lpa-id' => $lpa->id],
                            $flowChecker->getRouteOptions($nextRoute)
                        )
                    );
                } catch (ConflictException $e) {
                    $conflictError = $e;
                }
            }
        }

        $nextRoute = $flowChecker->nextRoute($currentRoute);

        $html = $this->renderer->render(
            'application/authenticated/lpa/certificate-provider/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'nextRoute' => $nextRoute,
                    'form' => $form,
                    'conflictError' => $conflictError,
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
