<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Alphagov\Pay\Client as GovPayClient;
use Application\Handler\Lpa\Traits\CheckoutTrait;
use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Communication;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Note: Alphagov\Pay\Response\Payment uses magic methods to
 * convert the response from gov pay to an array object whose
 * contents are accessible as properties. Psalm doesn't
 * understand this (it can't tell what the gov pay response
 * looks like), and raises UndefinedPropertyFetch errors.
 * That's why they are suppressed throughout this class.
 *
 * @psalm-suppress UndefinedPropertyFetch
 */
class CheckoutPayResponseHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use CheckoutTrait;

    public function __construct(
        private readonly FormElementManager $formElementManager,
        LpaApplicationService $lpaApplicationService,
        Communication $communicationService,
        private readonly GovPayClient $paymentClient,
        MvcUrlHelper $urlHelper,
        private readonly TemplateRendererInterface $renderer,
    ) {
        $this->lpaApplicationService = $lpaApplicationService;
        $this->communicationService = $communicationService;
        $this->urlHelper = $urlHelper;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        if (is_null($lpa->payment->gatewayReference)) {
            throw new RuntimeException('Payment id needed');
        }

        $paymentResponse = $this->paymentClient->getPayment($lpa->payment->gatewayReference);

        if (!$paymentResponse->isSuccess()) {
            /** @var \Application\Form\Lpa\BlankMainFlowForm $form */
            $form = $this->formElementManager->get('Application\Form\Lpa\BlankMainFlowForm', [
                'lpa' => $lpa,
            ]);

            $form->setAttribute(
                'action',
                $this->urlHelper->generate('lpa/checkout/pay', ['lpa-id' => $lpa->id])
            );
            $form->setAttribute('class', 'js-single-use');
            $form->get('submit')->setAttribute('value', 'Retry online payment');

            $template = $paymentResponse->state->code === 'P0030'
                ? 'application/authenticated/lpa/checkout/govpay-cancel.twig'
                : 'application/authenticated/lpa/checkout/govpay-failure.twig';

            $html = $this->renderer->render(
                $template,
                array_merge(
                    $this->getTemplateVariables($request),
                    ['form' => $form]
                )
            );

            return new HtmlResponse($html);
        }

        $lpa->payment->method    = Payment::PAYMENT_TYPE_CARD;
        $lpa->payment->reference = $paymentResponse->reference;
        $lpa->payment->date      = new \DateTime();
        $lpa->payment->email     = new EmailAddress(['address' => strtolower($paymentResponse->email)]);

        $this->lpaApplicationService->updateApplication($lpa->id, ['payment' => $lpa->payment->toArray()]);

        return $this->finishCheckout($lpa);
    }
}
