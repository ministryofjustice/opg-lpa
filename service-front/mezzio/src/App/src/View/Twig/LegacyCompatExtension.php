<?php

declare(strict_types=1);

namespace App\View\Twig;

use App\Form\Error\FormLinkedErrors;
use App\Model\FlashMessagesHolder;
use App\Model\FormFlowChecker;
use App\Model\Service\Session\PersistentSessionDetails;
use App\Model\UserDetailsHolder;
use App\Service\AccordionService;
use App\Storage\MezzioSessionStorage;
use App\View\Twig\Traits\ConcatNamesTrait;
use App\View\Twig\Traits\MoneyFormatterTrait;
use App\Model\Service\Authentication\Identity\User;
use Mezzio\Helper\UrlHelper;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\MultiCheckbox;
use Laminas\Form\Element\Radio;
use Laminas\Form\Element\Textarea;
use Laminas\Form\ElementInterface;
use Laminas\Form\FormInterface;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Formatter;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class LegacyCompatExtension extends AbstractExtension
{
    use ConcatNamesTrait;
    use MoneyFormatterTrait;

    public function __construct(
        private readonly array $config,
        private readonly FormLinkedErrors $formLinkedErrors,
        private readonly PersistentSessionDetails $persistentSessionDetails,
        private readonly AccordionService $accordionService,
        private readonly MezzioSessionStorage $sessionStorage,
        private readonly UserDetailsHolder $userDetailsHolder,
        private readonly UrlHelper $urlHelper,
        private readonly FlashMessagesHolder $flashMessagesHolder,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('asset_path', [$this, 'assetPath']),
            // Ported from AppFiltersExtension
            new TwigFilter('ordinal_suffix', [$this, 'ordinalSuffix']),
            new TwigFilter('format_lpa_id', [$this, 'formatLpaId']),
            new TwigFilter('concat_names', [$this, 'concatListOfNames']),
            new TwigFilter('money_format', [$this, 'moneyFormat']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            // Returns the current authenticated User identity, or null if not logged in
            new TwigFunction('identity', fn () => $this->sessionStorage->read()),
            new TwigFunction('url', [$this, 'url']),
            new TwigFunction('flashMessenger', fn () => new FlashMessenger($this->flashMessagesHolder)),
            new TwigFunction('renderNavigation', [$this, 'renderNavigation'], ['is_safe' => ['html'], 'needs_environment' => true]),
            // TODO: stub — always returns empty string; wire up SystemMessage service when available
            new TwigFunction('systemMessage', fn () => '', ['is_safe' => ['html']]),
            // FormRendererStub provides openTag(form) and closeTag — covers all (currently ported) template usage
            new TwigFunction('form', fn () => new FormRendererStub(), ['is_safe' => ['html']]),
            // Laminas view helper equivalent — Twig has no built-in escapeHtmlAttr function
            new TwigFunction('escapeHtmlAttr', fn (string $val) => htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE)),
            new TwigFunction('formElement', [$this, 'formElement'], ['is_safe' => ['html']]),
            new TwigFunction('formHidden', [$this, 'formHidden'], ['is_safe' => ['html']]),
            new TwigFunction('formInput', [$this, 'formInput'], ['is_safe' => ['html']]),
            new TwigFunction('formText', [$this, 'formInput'], ['is_safe' => ['html']]),
            new TwigFunction('formCheckbox', [$this, 'formCheckbox'], ['is_safe' => ['html']]),
            new TwigFunction('formRadio', [$this, 'formRadio'], ['is_safe' => ['html']]),
            new TwigFunction('formRadioOption', [$this, 'formRadioOption'], ['is_safe' => ['html']]),
            // Ported from AppFunctionsExtension — renders via layout/partials/form-element-errors.twig
            new TwigFunction('formElementErrorsV2', [$this, 'formElementErrorsV2'], ['is_safe' => ['html']]),
            new TwigFunction('formErrorTextExchange', [$this, 'formErrorTextExchange']),
            // Ported from AppFunctionsExtension — uses FormLinkedErrors::fromForm()
            new TwigFunction('form_linked_errors', [$this, 'formLinkedErrors']),
            new TwigFunction('serverUrl', [$this, 'serverUrl'], ['is_safe' => ['html']]),
            // Ported from AppFunctionsExtension
            new TwigFunction('final_check_accessible', [$this, 'finalCheckAccessible']),
            new TwigFunction('applicant_names', [$this, 'applicantNames']),
            // Route name tracking backed by PersistentSessionDetails (refreshed per-request by PersistentSessionDetailsMiddleware)
            new TwigFunction('routeName', fn () => [
                'current' => $this->persistentSessionDetails->getCurrentRoute(),
                'previous' => $this->persistentSessionDetails->getPreviousRoute(),
            ]),
            // Ported from AppFunctionsExtension — delegates to AccordionService
            new TwigFunction('accordionTop', [$this, 'accordionTop']),
            new TwigFunction('accordionBottom', [$this, 'accordionBottom']),
        ];
    }

    // -------------------------------------------------------------------------
    // Filters — ported from AppFiltersExtension
    // -------------------------------------------------------------------------

    public function assetPath(string $path, array $options = []): string
    {
        $cache = $this->config['version']['cache'] ?? '';

        if ($cache !== '') {
            $path = str_replace('/assets/', "/assets/{$cache}/", $path);
        }

        if (isset($options['minify']) && $options['minify'] === true) {
            $lastDot = strrpos($path, '.');
            if ($lastDot !== false) {
                $path = substr($path, 0, $lastDot) . '.min' . substr($path, $lastDot);
            }
        }

        return $path;
    }

    public function ordinalSuffix(int $number): string
    {
        $num = $number % 100;

        if ($num < 11 || $num > 13) {
            switch ($num % 10) {
                case 1:
                    return $number . 'st';
                case 2:
                    return $number . 'nd';
                case 3:
                    return $number . 'rd';
            }
        }

        return $number . 'th';
    }

    public function formatLpaId(int $id): string
    {
        return Formatter::id($id);
    }

    public function concatListOfNames(array $nameList): ?string
    {
        return $this->concatNames($nameList);
    }

    public function moneyFormat(mixed $amount): string
    {
        return $this->formatMoney($amount);
    }

    // -------------------------------------------------------------------------
    // Functions
    // -------------------------------------------------------------------------

    public function url(string $routeName, array $params = []): string
    {
        try {
            return $this->urlHelper->generate($routeName, $params);
        } catch (\Throwable) {
            // Route not yet registered in Mezzio — fall back to treating the
            // route name as a path so legacy templates don't break.
            return '/' . ltrim($routeName, '/');
        }
    }

    /**
     * Renders the service navigation partial.
     *
     * userLoggedIn and lastLoginAt are derived from the session identity.
     * name and hasOneOrMoreLPAs are populated from UserDetailsHolder, which is
     * set per-request by UserDetailsMiddleware after fetching from the API.
     */
    public function renderNavigation(Environment $env, string $currentRoute = ''): string
    {
        $identity = $this->sessionStorage->read();
        $userLoggedIn = $identity instanceof User;
        $lastLoginAt = $userLoggedIn ? $identity->lastLogin() : null;

        $name = '';
        $hasOneOrMoreLPAs = false;

        $userDetails = $this->userDetailsHolder->get();
        if ($userDetails !== null) {
            $sessionUserName = $userDetails->getName();
            if ($sessionUserName !== null) {
                $name = trim($sessionUserName->getFirst() . ' ' . $sessionUserName->getLast());
            }
            $hasOneOrMoreLPAs = $userDetails->getNumberOfLpas() > 0;
        }

        return $env->render('application/partials/nav.twig', [
            'nav' => (object) [
                'userLoggedIn'     => $userLoggedIn,
                'name'             => $name,
                'lastLoginAt'      => $lastLoginAt,
                'route'            => $currentRoute,
                'hasOneOrMoreLPAs' => $hasOneOrMoreLPAs,
            ],
        ]);
    }

    /**
     * Ported from AppFunctionsExtension::formElementErrorsV2().
     * Inlined from layout/partials/form-element-errors.twig — no renderer needed.
     *
     * @param array|string|object|null $errors
     */
    public function formElementErrorsV2(array|string|object|null $errors): string
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

        // Inlined from layout/partials/form-element-errors.twig rather than calling the Twig
        // renderer here. Injecting the TemplateRendererInterface into this extension creates
        // a circular dependency (the renderer depends on its extensions to be built first).
        // The template is trivial enough that inlining is the cleanest solution.
        $escaped = implode('<br>', array_map(
            static fn (string $m) => htmlspecialchars($m, ENT_QUOTES),
            $messages,
        ));

        return sprintf(
            '<span class="govuk-error-message text" data-cy="form-error">'
            . '<span class="govuk-visually-hidden">Error:</span> %s'
            . '</span>',
            $escaped,
        );
    }

    /**
     * Ported from AppFunctionsExtension — delegates to FormLinkedErrors::fromForm().
     */
    public function formLinkedErrors(FormInterface $form): array
    {
        return $this->formLinkedErrors->fromForm($form);
    }

    /**
     * Applies error text replacements to form elements in-place and returns the form.
     */
    public function formErrorTextExchange(FormInterface $form, array $mapping): FormInterface
    {
        foreach ($mapping as $elementName => $replacements) {
            if (!$form->has($elementName)) {
                continue;
            }

            $element     = $form->get($elementName);
            $messages    = $element->getMessages();
            $newMessages = [];

            foreach ($messages as $key => $message) {
                // $message is the raw validation key (e.g. 'cannot-be-empty'),
                // $replacements maps that key to a human-readable string.
                $newMessages[$key] = $replacements[$message] ?? $message;
            }

            $element->setMessages($newMessages);
        }

        return $form;
    }

    /**
     * Returns the current server URL (scheme + host), optionally with the request URI.
     */
    public function serverUrl(bool $withRequestUri = false): string
    {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base   = $scheme . '://' . $host;

        if ($withRequestUri) {
            $base .= $_SERVER['REQUEST_URI'] ?? '/';
        }

        return $base;
    }

    /**
     * Renders a form element based on its type.
     *
     * Returns an empty string for null — this occurs when a form's getCsrf() returns null
     * because the MVC CsrfBuilder was never called (e.g. in the Mezzio context where MVC
     * CSRF is not wired up). CSRF will be handled separately in Mezzio.
     */
    public function formElement(?ElementInterface $element): string
    {
        if ($element === null) {
            return '';
        }

        // Radio must be checked before Checkbox/MultiCheckbox because
        // Radio extends MultiCheckbox which extends Checkbox.
        if ($element instanceof Radio) {
            return $this->formRadio($element);
        }

        if ($element instanceof Checkbox || $element instanceof MultiCheckbox) {
            return $this->formCheckbox($element);
        }

        if ($element instanceof Textarea) {
            return $this->formTextarea($element);
        }

        $type = $element->getAttribute('type') ?? 'text';

        if ($type === 'hidden') {
            return $this->formHidden($element);
        }

        return $this->formInput($element);
    }

    /**
     * Renders a textarea element, with the current value as text content.
     */
    public function formTextarea(ElementInterface $element): string
    {
        $attrs = $element->getAttributes();
        unset($attrs['type']); // textarea has no type attribute

        $attrString = $this->buildAttributeString($attrs);
        $value      = htmlspecialchars((string) ($element->getValue() ?? ''), ENT_QUOTES);

        return sprintf('<textarea %s>%s</textarea>', $attrString, $value);
    }

    /**
     * Renders a hidden input element.
     */
    public function formHidden(ElementInterface $element): string
    {
        $name  = htmlspecialchars((string) $element->getAttribute('name'), ENT_QUOTES);
        $value = htmlspecialchars((string) $element->getValue(), ENT_QUOTES);

        return sprintf('<input type="hidden" name="%s" value="%s">', $name, $value);
    }

    /**
     * Renders a text/email/password input element.
     */
    public function formInput(ElementInterface $element): string
    {
        return sprintf('<input %s>', $this->buildInputAttributes($element));
    }

    /**
     * Renders a checkbox input element.
     *
     * For MultiCheckbox elements, renders one wrapped checkbox item per value
     * option (matching the legacy FormMultiCheckbox view helper behaviour).
     * For plain Checkbox elements, renders a single input.
     */
    public function formCheckbox(ElementInterface $element): string
    {
        // MultiCheckbox — render one wrapped item per value option.
        // Radio extends MultiCheckbox, but Radio is handled by formRadio() before
        // this method is ever called (see formElement()), so we can safely
        // treat any MultiCheckbox here as a real multi-checkbox.
        if ($element instanceof MultiCheckbox) {
            return $this->renderMultiCheckbox($element);
        }

        // Plain Checkbox — single input.
        if ($element instanceof Checkbox) {
            $value   = $element->getCheckedValue();
            $checked = $element->isChecked();
        } else {
            $value   = (string) $element->getValue();
            $checked = (bool) $element->getValue();
        }

        $attrs = array_merge($element->getAttributes(), [
            'type'  => 'checkbox',
            'value' => $value,
            'class' => $element->getAttribute('class') ?? 'govuk-checkboxes__input',
        ]);

        $attrString = $this->buildAttributeString($attrs);

        return sprintf('<input %s%s>', $attrString, $checked ? ' checked' : '');
    }

    /**
     * Renders all options of a MultiCheckbox element as individual checkbox items.
     * Each option is wrapped in a <div class="govuk-checkboxes__item"> with a <label>.
     *
     * The legacy Laminas FormMultiCheckbox view helper set `$inputAttributes['checked']`
     * as a boolean value inside the attributes array and passed it to
     * createAttributesString().  That meant `value` and `checked` were always the last
     * two attributes in the rendered string, producing `value="X" checked`.
     *
     * We replicate that ordering here by explicitly appending `value` and `checked` last
     * to the $attrs array before handing it to buildAttributeString().  `checked = false`
     * is skipped by buildAttributeString(), so unchecked options emit no `checked` token.
     */
    private function renderMultiCheckbox(MultiCheckbox $element): string
    {
        $name           = (string) ($element->getName() ?? '');
        $inputName      = $name . '[]';
        $valueOptions   = $element->getValueOptions();
        $selectedValues = array_map('strval', (array) ($element->getValue() ?? []));
        $html           = '';

        foreach ($valueOptions as $optKey => $optSpec) {
            if (is_scalar($optSpec)) {
                $optSpec = ['label' => (string) $optSpec, 'value' => $optKey];
            }

            $optValue   = (string) ($optSpec['value'] ?? $optKey);
            $label      = (string) ($optSpec['label'] ?? $optValue);
            $extraAttrs = $optSpec['attributes'] ?? [];
            $labelAttrs = $optSpec['label_attributes'] ?? [];

            // Strip internal structure hints that are not valid HTML attributes.
            unset($extraAttrs['div-attributes']);

            $inputId = (string) ($extraAttrs['id'] ?? ($name . '-' . $optValue));
            $checked = in_array($optValue, $selectedValues, true);

            // Build core attributes first.  value and checked are appended last so
            // buildAttributeString() produces `... value="X" checked` — matching the
            // legacy FormMultiCheckbox helper and keeping `value` immediately adjacent
            // to `checked` in the output.
            $attrs = [
                'id'    => $inputId,
                'type'  => 'checkbox',
                'name'  => $inputName,
                'class' => $extraAttrs['class'] ?? 'govuk-checkboxes__input',
            ];

            // Carry through any extra per-option attributes (excluding those we set).
            foreach ($extraAttrs as $k => $v) {
                if (!array_key_exists($k, $attrs)) {
                    $attrs[$k] = $v;
                }
            }

            // value and checked must be last — see docblock above.
            $attrs['value']   = $optValue;
            $attrs['checked'] = $checked; // false → skipped by buildAttributeString

            $attrString = $this->buildAttributeString($attrs);
            $labelFor   = htmlspecialchars($inputId, ENT_QUOTES);
            $labelClass = htmlspecialchars($labelAttrs['class'] ?? 'govuk-label govuk-checkboxes__label', ENT_QUOTES);
            $labelText  = htmlspecialchars((string) $label, ENT_QUOTES);

            $html .= sprintf(
                '<div class="govuk-checkboxes__item%s">'
                . '<input %s>'
                . '<label class="%s" for="%s">%s</label>'
                . '</div>',
                $checked ? ' selected' : '',
                $attrString,
                $labelClass,
                $labelFor,
                $labelText,
            );
        }

        return $html;
    }

    /**
     * Renders radio buttons for a Radio element.
     */
    public function formRadio(ElementInterface $element): string
    {
        $name         = (string) ($element->getAttribute('name') ?? '');
        $valueOptions = method_exists($element, 'getValueOptions') ? $element->getValueOptions() : [];
        $currentValue = $element->getValue();
        $html         = '';

        // Determine the wrapper div class from element-level div-attributes, or default
        $divAttributes     = $element->getAttribute('div-attributes') ?? [];
        $defaultDivClass   = 'govuk-radios__item';
        $defaultLabelClass = 'govuk-label govuk-radios__label';

        // Base attributes from the element (excluding value/type/id which vary per option,
        // and div-attributes which are used for the wrapper div, not the input)
        $baseAttrs = array_diff_key($element->getAttributes(), array_flip(['value', 'type', 'id', 'div-attributes']));
        $baseAttrs['type']  = 'radio';
        $baseAttrs['class'] = $baseAttrs['class'] ?? 'govuk-radios__input';

        foreach ($valueOptions as $optValue => $optSpec) {
            $optionAttributes = [];
            $labelAttributes  = [];
            if (is_array($optSpec)) {
                $optionAttributes = $optSpec['attributes'] ?? [];
                $labelAttributes  = $optSpec['label_attributes'] ?? [];
                $optValue         = $optSpec['value'] ?? $optValue;
                $optLabel         = $optSpec['label'] ?? (string) $optValue;
            } else {
                $optLabel = (string) $optSpec;
            }

            $optAttrs            = array_merge($baseAttrs, $optionAttributes);
            $optAttrs['id']      = $name . '-' . $optValue;
            $optAttrs['data-cy'] = $optAttrs['id'];
            $optAttrs['value']   = (string) $optValue;

            // Per-option div-attributes override the element-level ones
            $thisDivAttrs = $optSpec['div-attributes'] ?? $divAttributes;
            $divClass     = $thisDivAttrs['class'] ?? $defaultDivClass;
            $divAttrStr   = $divClass !== '' ? sprintf(' class="%s"', htmlspecialchars($divClass, ENT_QUOTES)) : '';

            // Build label class from label_attributes, falling back to default
            $labelClass    = $labelAttributes['class'] ?? $defaultLabelClass;
            $labelAttrStr  = $this->buildAttributeString(array_merge(
                ['class' => $labelClass, 'for' => $optAttrs['id']],
                array_diff_key($labelAttributes, array_flip(['class', 'for'])),
            ));

            $attrString = $this->buildAttributeString($optAttrs);
            $checked    = ($currentValue == $optValue) ? ' checked' : '';

            $html .= sprintf(
                '<div%s>'
                . '<input %s%s>'
                . '<label %s>%s</label>'
                . '</div>',
                $divAttrStr,
                $attrString,
                $checked,
                $labelAttrStr,
                htmlspecialchars((string) $optLabel, ENT_QUOTES),
            );
        }

        return $html;
    }

    /**
     * Renders a single named option from a Radio element.
     *
     * Root-cause context
     * ------------------
     * The legacy FormRadio view helper exposed an outputOption(Radio $element, $option)
     * method which templates called as formRadio().outputOption(element, 'key').
     * In Twig, functions return values not objects, so that chained-call pattern is a
     * SyntaxError.  This function replicates outputOption() faithfully: it clones the
     * element, restricts its value options to the single requested key, then delegates
     * to formRadio() — producing identical HTML to the legacy helper.
     *
     * @param ElementInterface $element   A Radio element with value_options
     * @param string           $optionKey The key in value_options to render
     */
    public function formRadioOption(ElementInterface $element, string $optionKey): string
    {
        $valueOptions = method_exists($element, 'getValueOptions') ? $element->getValueOptions() : [];

        if (!isset($valueOptions[$optionKey])) {
            return '';
        }

        // Clone to avoid mutating the original element (matches outputOption's clone).
        $single = clone $element;
        if (method_exists($single, 'setValueOptions')) {
            $single->setValueOptions([$optionKey => $valueOptions[$optionKey]]);
        }

        return $this->formRadio($single);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    public function accordionTop(?Lpa $lpa, string $currentRoute): array
    {
        return $this->accordionService->getTopBars($lpa, $currentRoute);
    }

    public function accordionBottom(?Lpa $lpa, string $currentRoute): array
    {
        return $this->accordionService->getBottomBars($lpa, $currentRoute);
    }

    public function finalCheckAccessible(Lpa $lpa): bool
    {
        return FormFlowChecker::isFinalCheckAccessible($lpa);
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

    private function buildInputAttributes(ElementInterface $element): string
    {
        $attrs = $element->getAttributes();

        $attrs['type']  = $attrs['type'] ?? 'text';
        $attrs['class'] = $attrs['class'] ?? 'govuk-input';

        $value = $element->getValue();
        if ($value !== null && $value !== '') {
            $attrs['value'] = $value;
        }

        if (!empty($element->getMessages()) && strpos((string) $attrs['class'], 'govuk-input--error') === false) {
            $attrs['class'] .= ' govuk-input--error';
        }

        return $this->buildAttributeString($attrs);
    }

    /**
     * Renders an associative array of HTML attributes into a string.
     * Boolean true renders as a standalone attribute; null/false values are skipped.
     */
    private function buildAttributeString(array $attrs): string
    {
        $parts = [];
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === false || is_array($value)) {
                continue;
            }
            if ($value === true) {
                $parts[] = htmlspecialchars((string) $key, ENT_QUOTES);
            } else {
                $parts[] = sprintf(
                    '%s="%s"',
                    htmlspecialchars((string) $key, ENT_QUOTES),
                    htmlspecialchars((string) $value, ENT_QUOTES),
                );
            }
        }
        return implode(' ', $parts);
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
}
