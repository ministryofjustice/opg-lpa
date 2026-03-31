<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Form\Lpa\WhoAreYouForm;
use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\Element;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\WhoAreYou\WhoAreYou;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class WhoAreYouHandler implements RequestHandlerInterface
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

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE_NAME);

        if ($lpa->whoAreYouAnswered == true) {
            $nextRoute = $flowChecker->nextRoute($currentRoute);

            $html = $this->renderer->render(
                'application/authenticated/lpa/who-are-you/index.twig',
                array_merge(
                    $this->getTemplateVariables($request),
                    [
                        'nextUrl' => $this->urlHelper->generate(
                            $nextRoute,
                            ['lpa-id' => $lpa->id],
                            $flowChecker->getRouteOptions($nextRoute)
                        ),
                    ]
                )
            );

            return new HtmlResponse($html);
        }

        /** @var WhoAreYouForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\WhoAreYouForm');
        $form->setAttribute(
            'action',
            $this->urlHelper->generate($currentRoute, ['lpa-id' => $lpa->id])
        );

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                $whoAreYou = new WhoAreYou($form->getModelDataFromValidatedForm());

                if (!$this->lpaApplicationService->setWhoAreYou($lpa, $whoAreYou)) {
                    throw new RuntimeException(
                        'API client failed to set Who Are You for id: ' . $lpa->id
                    );
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
        }

        $who = $form->get('who');
        $whoValueOptions = $who->getOptions()['value_options'];

        $whoOptions = [];

        $whoOptions['donor'] = new Element('who', [
            'label' => "The donor used this online service with little or no help",
        ]);
        $whoOptions['donor']->setAttributes([
            'type'    => 'radio',
            'id'      => 'who',
            'data-cy' => 'who',
            'value'   => $whoValueOptions['donor']['value'],
            'checked' => (($who->getValue() == 'donor') ? 'checked' : null),
        ]);

        $whoOptions['friend-or-family'] = new Element('who', [
            'label' => "A friend or family member (who may also be the attorney)" .
                " helped the donor use this online service",
        ]);
        $whoOptions['friend-or-family']->setAttributes([
            'type'    => 'radio',
            'id'      => 'who-friend-or-family',
            'data-cy' => 'who-friend-or-family',
            'value'   => $whoValueOptions['friendOrFamily']['value'],
            'checked' => (($who->getValue() == 'friendOrFamily') ? 'checked' : null),
        ]);

        $whoOptions['finance-professional'] = new Element('who', [
            'label' => "A paid finance professional made the LPA on the donor's behalf",
        ]);
        $whoOptions['finance-professional']->setAttributes([
            'type'    => 'radio',
            'id'      => 'who-finance-professional',
            'data-cy' => 'who-finance-professional',
            'value'   => $whoValueOptions['financeProfessional']['value'],
            'checked' => (($who->getValue() == 'financeProfessional') ? 'checked' : null),
        ]);

        $whoOptions['legal-professional'] = new Element('who', [
            'label' => "A paid legal professional made the LPA on the donor's behalf",
        ]);
        $whoOptions['legal-professional']->setAttributes([
            'type'    => 'radio',
            'id'      => 'who-legal-professional',
            'data-cy' => 'who-legal-professional',
            'value'   => $whoValueOptions['legalProfessional']['value'],
            'checked' => (($who->getValue() == 'legalProfessional') ? 'checked' : null),
        ]);

        $whoOptions['estate-planning-professional'] = new Element('who', [
            'label' => "A paid estate planning professional made the LPA on the donor's behalf",
        ]);
        $whoOptions['estate-planning-professional']->setAttributes([
            'type'    => 'radio',
            'id'      => 'who-estate-planning-professional',
            'data-cy' => 'who-estate-planning-professional',
            'value'   => $whoValueOptions['estatePlanningProfessional']['value'],
            'checked' => (($who->getValue() == 'estatePlanningProfessional') ? 'checked' : null),
        ]);

        $whoOptions['digital-partner'] = new Element('who', [
            'label' => "OPG's Assisted Digital Service helped the donor",
        ]);
        $whoOptions['digital-partner']->setAttributes([
            'type'    => 'radio',
            'id'      => 'who-digital-partner',
            'data-cy' => 'who-digital-partner',
            'value'   => $whoValueOptions['digitalPartner']['value'],
            'checked' => (($who->getValue() == 'digitalPartner') ? 'checked' : null),
        ]);

        $whoOptions['charity'] = new Element('who', [
            'label' => "A charity made the LPA on the donor's behalf",
        ]);
        $whoOptions['charity']->setAttributes([
            'type'    => 'radio',
            'id'      => 'who-charity',
            'data-cy' => 'who-charity',
            'value'   => $whoValueOptions['charity']['value'],
            'checked' => (($who->getValue() == 'charity') ? 'checked' : null),
        ]);

        $whoOptions['organisation'] = new Element('who', [
            'label' => "Another organisation, such as a council or community group, helped the donor",
        ]);
        $whoOptions['organisation']->setAttributes([
            'type'    => 'radio',
            'id'      => 'who-organisation',
            'data-cy' => 'who-organisation',
            'value'   => $whoValueOptions['organisation']['value'],
            'checked' => (($who->getValue() == 'organisation') ? 'checked' : null),
        ]);

        $whoOptions['other'] = new Element('who', [
            'label' => "Other",
        ]);
        $whoOptions['other']->setAttributes([
            'type'             => 'radio',
            'id'               => 'who-other',
            'data-cy'          => 'who-other',
            'data-aria-controls' => 'other-input',
            'value'            => $whoValueOptions['other']['value'],
            'checked'          => (($who->getValue() == 'other') ? 'checked' : null),
        ]);

        $whoOptions['notSaid'] = new Element('who', [
            'label' => "I'd prefer not to say",
        ]);
        $whoOptions['notSaid']->setAttributes([
            'type'    => 'radio',
            'id'      => 'who-notSaid',
            'data-cy' => 'who-notSaid',
            'value'   => $whoValueOptions['notSaid']['value'],
            'checked' => (($who->getValue() == 'notSaid') ? 'checked' : null),
        ]);

        $html = $this->renderer->render(
            'application/authenticated/lpa/who-are-you/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form'       => $form,
                    'whoOptions' => $whoOptions,
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
