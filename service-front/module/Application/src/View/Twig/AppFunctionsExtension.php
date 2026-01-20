<?php

declare(strict_types=1);

namespace Application\View\Twig;

use Application\Form\Error\FormLinkedErrors;
use Application\Model\FormFlowChecker;
use Application\Service\AccordionService;
use Application\Service\NavigationViewModelHelper;
use Application\Service\SystemMessage;
use Laminas\Form\Form;
use MakeShared\DataModel\Lpa\Lpa;
use Application\View\Helper\Traits\ConcatNamesTrait;
use Mezzio\Template\TemplateRendererInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppFunctionsExtension extends AbstractExtension
{
    use ConcatNamesTrait;

    public function __construct(
        private readonly array $config,
        private readonly FormLinkedErrors $formLinkedErrors,
        private readonly TemplateRendererInterface $renderer,
        private readonly SystemMessage $systemMessage,
        private readonly AccordionService $accordionService,
        private readonly NavigationViewModelHelper $navigationViewModelHelper,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('applicant_names', [$this, 'applicantNames']),
            new TwigFunction('final_check_accessible', [$this, 'finalCheckAccessible']),
            new TwigFunction('form_linked_errors', fn (Form $form): array => $this->formLinkedErrors->fromForm($form)),
            new TwigFunction('systemMessage', [$this, 'systemMessage'], ['is_safe' => ['html']]),
            new TwigFunction('formElementErrorsV2', [$this, 'formElementErrorsV2'], ['is_safe' => ['html']]),
            new TwigFunction('accordionTop', [$this, 'getTopBars']),
            new TwigFunction('accordionBottom', [$this, 'getBottomBars']),
            new TwigFunction('renderNavigation', [$this, 'renderNavigation'], ['is_safe' => ['html']]),
        ];
    }

    public function applicantNames(Lpa $lpa): ?string
    {
        if (!isset($lpa->document->whoIsRegistering)) {
            return null;
        }

        if ($lpa->document->whoIsRegistering === 'donor') {
            return 'the donor';
        }

        if (is_array($lpa->document->whoIsRegistering) && is_array($lpa->document->primaryAttorneys)) {
            $humans = [];

            foreach ($lpa->document->primaryAttorneys as $attorney) {
                if (in_array($attorney->id, $lpa->document->whoIsRegistering)) {
                    $humans[] = $attorney;
                }
            }

            return $this->concatNames($humans);
        }

        return null;
    }

    public function finalCheckAccessible(Lpa $lpa): bool
    {
        return FormFlowChecker::isFinalCheckAccessible($lpa);
    }

    public function systemMessage(): string
    {
        $message = $this->systemMessage->fetchSanitised();

        if ($message === null) {
            return '';
        }

        return $this->renderer->render('application/partials/system-message.twig', [
            'message' => $message,
        ]);
    }

    public function formElementErrorsV2($errors): string
    {
        if ($errors === null) {
            return '';
        }

        if (is_object($errors) && method_exists($errors, 'getMessages')) {
            $errors = $errors->getMessages();
        }

        if (!is_array($errors) || $errors === []) {
            return '';
        }

        $messages = $this->flattenMessages($errors);

        if ($messages === []) {
            return '';
        }

        return $this->renderer->render(
            'layout/partials/form-element-errors.twig',
            [
                'messages' => $messages,
            ]
        );
    }

    private function flattenMessages(array $errors): array
    {
        $messages = [];

        foreach ($errors as $error) {
            if (is_array($error)) {
                $messages = array_merge($messages, $this->flattenMessages($error));
            } else {
                $messages[] = (string) $error;
            }
        }

        return $messages;
    }

    public function getTopBars(?Lpa $lpa, string $currentRoute): array
    {
        return $this->accordionService->getTopBars($lpa, $currentRoute);
    }

    public function getBottomBars(?Lpa $lpa, string $currentRoute): array
    {
        return $this->accordionService->getBottomBars($lpa, $currentRoute);
    }

    public function renderNavigation(string $currentRoute): string
    {
        return $this->renderer->render(
            'application/partials/nav.twig',
            ['nav' => $this->navigationViewModelHelper->build($currentRoute)]
        );
    }
}
