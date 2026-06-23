<?php

declare(strict_types=1);

namespace AppTest\View\Twig;

use App\Form\Error\FormLinkedErrors;
use App\Model\FlashMessagesHolder;
use App\Model\FormFlowChecker;
use App\Model\Service\Session\PersistentSessionDetails;
use App\Model\UserDetailsHolder;
use App\Service\AccordionService;
use App\Service\SystemMessage;
use App\Storage\MezzioSessionStorage;
use App\View\Twig\LegacyCompatExtension;
use Laminas\Form\Element;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\MultiCheckbox;
use Laminas\Form\Element\Radio;
use Laminas\Form\Form;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LegacyCompatExtensionTest extends TestCase
{
    private LegacyCompatExtension $extension;
    private UrlHelper&MockObject $urlHelper;
    private MezzioSessionStorage&MockObject $sessionStorage;
    private FormLinkedErrors&MockObject $formLinkedErrors;
    private AccordionService&MockObject $accordionService;
    private UserDetailsHolder&MockObject $userDetailsHolder;
    private FlashMessagesHolder&MockObject $flashMessagesHolder;
    private SystemMessage&MockObject $systemMessage;

    protected function setUp(): void
    {
        $this->urlHelper           = $this->createMock(UrlHelper::class);
        $this->sessionStorage      = $this->createMock(MezzioSessionStorage::class);
        $this->formLinkedErrors    = $this->createMock(FormLinkedErrors::class);
        $this->accordionService    = $this->createMock(AccordionService::class);
        $this->userDetailsHolder   = $this->createMock(UserDetailsHolder::class);
        $this->flashMessagesHolder = $this->createMock(FlashMessagesHolder::class);
        $this->systemMessage       = $this->createMock(SystemMessage::class);

        $this->extension = new LegacyCompatExtension(
            ['version' => ['cache' => '', 'tag' => 'dev']],
            $this->formLinkedErrors,
            new PersistentSessionDetails(),
            $this->accordionService,
            $this->sessionStorage,
            $this->userDetailsHolder,
            $this->urlHelper,
            $this->flashMessagesHolder,
            $this->systemMessage,
        );
    }

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    public function testRegistersExpectedFilters(): void
    {
        $names = array_map(fn($f) => $f->getName(), $this->extension->getFilters());

        $this->assertContains('asset_path', $names);
        $this->assertContains('ordinal_suffix', $names);
        $this->assertContains('format_lpa_id', $names);
        $this->assertContains('concat_names', $names);
        $this->assertContains('money_format', $names);
    }

    public function testRegistersExpectedFunctions(): void
    {
        $names = array_map(fn($f) => $f->getName(), $this->extension->getFunctions());

        foreach (
            ['url', 'formElement', 'formCheckbox', 'formRadio', 'formElementErrorsV2',
                  'formErrorTextExchange', 'form_linked_errors', 'serverUrl',
                  'final_check_accessible', 'applicant_names', 'routeName',
                  'accordionTop', 'accordionBottom', 'flashMessenger', 'renderNavigation'] as $fn
        ) {
            $this->assertContains($fn, $names, "Missing function: $fn");
        }
    }

    // -------------------------------------------------------------------------
    // assetPath
    // -------------------------------------------------------------------------

    public function testAssetPathReturnsPathUnchangedWhenNoCacheVersion(): void
    {
        $this->assertSame('/assets/app.js', $this->extension->assetPath('/assets/app.js'));
    }

    public function testAssetPathInjectsCacheVersion(): void
    {
        $ext = new LegacyCompatExtension(
            ['version' => ['cache' => 'abc123', 'tag' => 'dev']],
            $this->formLinkedErrors,
            new PersistentSessionDetails(),
            $this->accordionService,
            $this->sessionStorage,
            $this->userDetailsHolder,
            $this->urlHelper,
            $this->flashMessagesHolder,
            $this->systemMessage,
        );

        $this->assertSame('/assets/abc123/app.js', $ext->assetPath('/assets/app.js'));
    }

    public function testAssetPathAddsMinSuffix(): void
    {
        $result = $this->extension->assetPath('/assets/app.js', ['minify' => true]);
        $this->assertSame('/assets/app.min.js', $result);
    }

    // -------------------------------------------------------------------------
    // ordinalSuffix
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('ordinalSuffixProvider')]
    public function testOrdinalSuffix(int $n, string $expected): void
    {
        $this->assertSame($expected, $this->extension->ordinalSuffix($n));
    }

    public static function ordinalSuffixProvider(): array
    {
        return [
            [1, '1st'], [2, '2nd'], [3, '3rd'], [4, '4th'],
            [11, '11th'], [12, '12th'], [13, '13th'],
            [21, '21st'], [22, '22nd'], [23, '23rd'], [24, '24th'],
        ];
    }

    // -------------------------------------------------------------------------
    // url
    // -------------------------------------------------------------------------

    public function testUrlDelegatesToUrlHelper(): void
    {
        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('user/dashboard', [])
            ->willReturn('/user/dashboard');

        $this->assertSame('/user/dashboard', $this->extension->url('user/dashboard'));
    }

    public function testUrlFallsBackToSlashPrefixedRouteNameOnException(): void
    {
        $this->urlHelper->method('generate')->willThrowException(new \RuntimeException('no route'));

        $this->assertSame('/unregistered/route', $this->extension->url('unregistered/route'));
    }

    // -------------------------------------------------------------------------
    // formElementErrorsV2
    // -------------------------------------------------------------------------

    public function testFormElementErrorsV2ReturnsEmptyStringForNull(): void
    {
        $this->assertSame('', $this->extension->formElementErrorsV2(null));
    }

    public function testFormElementErrorsV2ReturnsEmptyStringForEmptyArray(): void
    {
        $this->assertSame('', $this->extension->formElementErrorsV2([]));
    }

    public function testFormElementErrorsV2ReturnsEmptyStringForNonArray(): void
    {
        $this->assertSame('', $this->extension->formElementErrorsV2('not-an-array'));
    }

    public function testFormElementErrorsV2RendersMessagesFromArray(): void
    {
        $result = $this->extension->formElementErrorsV2(['isEmpty' => 'Enter a value']);

        $this->assertStringContainsString('Enter a value', $result);
        $this->assertStringContainsString('data-cy="form-error"', $result);
    }

    public function testFormElementErrorsV2FlattensNestedErrors(): void
    {
        $result = $this->extension->formElementErrorsV2([
            'isEmpty' => 'Required',
            'length'  => ['tooShort' => 'Too short'],
        ]);

        $this->assertStringContainsString('Required', $result);
        $this->assertStringContainsString('Too short', $result);
    }

    public function testFormElementErrorsV2AcceptsFormElement(): void
    {
        $element = $this->createMock(Element::class);
        $element->method('getMessages')->willReturn(['isEmpty' => 'Required']);

        $result = $this->extension->formElementErrorsV2($element);

        $this->assertStringContainsString('Required', $result);
    }

    // -------------------------------------------------------------------------
    // formErrorTextExchange
    // -------------------------------------------------------------------------

    public function testFormErrorTextExchangeReplacesMessageValues(): void
    {
        $form = new Form();
        $form->add(['name' => 'email', 'type' => 'text']);
        $form->get('email')->setMessages(['isEmpty' => 'cannot-be-empty']);

        $this->extension->formErrorTextExchange($form, [
            'email' => ['cannot-be-empty' => 'Enter your email address'],
        ]);

        $this->assertSame(
            ['isEmpty' => 'Enter your email address'],
            $form->get('email')->getMessages(),
        );
    }

    public function testFormErrorTextExchangeLeavesUnmappedMessagesUnchanged(): void
    {
        $form = new Form();
        $form->add(['name' => 'email', 'type' => 'text']);
        $form->get('email')->setMessages(['isEmpty' => 'some-unknown-key']);

        $this->extension->formErrorTextExchange($form, [
            'email' => ['cannot-be-empty' => 'Enter your email address'],
        ]);

        $this->assertSame(
            ['isEmpty' => 'some-unknown-key'],
            $form->get('email')->getMessages(),
        );
    }

    public function testFormErrorTextExchangeSkipsMissingElements(): void
    {
        $form = new Form();
        // 'missing' is not in the form — should not throw
        $result = $this->extension->formErrorTextExchange($form, [
            'missing' => ['cannot-be-empty' => 'Enter a value'],
        ]);

        $this->assertSame($form, $result);
    }

    // -------------------------------------------------------------------------
    // formCheckbox
    // -------------------------------------------------------------------------

    public function testFormCheckboxRendersCheckedValue(): void
    {
        $checkbox = new Checkbox('terms');
        $checkbox->setAttribute('id', 'terms');

        $html = $this->extension->formCheckbox($checkbox);

        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('value="1"', $html);
        $this->assertStringNotContainsString('checked', $html);
    }

    public function testFormCheckboxRendersCheckedWhenChecked(): void
    {
        $checkbox = new Checkbox('terms');
        $checkbox->setAttribute('id', 'terms');
        $checkbox->setChecked(true);

        $html = $this->extension->formCheckbox($checkbox);

        $this->assertStringContainsString('checked', $html);
    }

    public function testFormCheckboxRendersDataCyAttribute(): void
    {
        $checkbox = new Checkbox('terms');
        $checkbox->setAttributes(['id' => 'terms', 'data-cy' => 'signup-terms']);

        $html = $this->extension->formCheckbox($checkbox);

        $this->assertStringContainsString('data-cy="signup-terms"', $html);
    }

    public function testFormCheckboxUsesDefaultClass(): void
    {
        $checkbox = new Checkbox('terms');
        $checkbox->setAttribute('id', 'terms');

        $html = $this->extension->formCheckbox($checkbox);

        $this->assertStringContainsString('govuk-checkboxes__input', $html);
    }

    // -------------------------------------------------------------------------
    // formCheckbox — MultiCheckbox
    // -------------------------------------------------------------------------

    public function testFormCheckboxRendersOneItemPerValueOption(): void
    {
        $multi = new MultiCheckbox('attorneyList');
        $multi->setValueOptions([
            1 => ['label' => 'Amy Wheeler',   'value' => 1, 'attributes' => ['id' => 'attorney-1']],
            2 => ['label' => 'David Wheeler', 'value' => 2, 'attributes' => ['id' => 'attorney-2']],
        ]);

        $html = $this->extension->formCheckbox($multi);

        $this->assertSame(2, substr_count($html, 'type="checkbox"'));
        $this->assertStringContainsString('value="1"', $html);
        $this->assertStringContainsString('value="2"', $html);
        $this->assertStringContainsString('Amy Wheeler', $html);
        $this->assertStringContainsString('David Wheeler', $html);
    }

    public function testFormCheckboxMultiUsesArrayNameForSubmission(): void
    {
        $multi = new MultiCheckbox('attorneyList');
        $multi->setValueOptions([
            1 => ['label' => 'Amy Wheeler', 'value' => 1, 'attributes' => ['id' => 'attorney-1']],
        ]);

        $html = $this->extension->formCheckbox($multi);

        $this->assertStringContainsString('name="attorneyList[]"', $html);
    }

    public function testFormCheckboxMultiWrapsEachOptionInGovukItem(): void
    {
        $multi = new MultiCheckbox('attorneyList');
        $multi->setValueOptions([
            1 => ['label' => 'Amy Wheeler', 'value' => 1, 'attributes' => ['id' => 'attorney-1']],
        ]);

        $html = $this->extension->formCheckbox($multi);

        $this->assertStringContainsString('class="govuk-checkboxes__item"', $html);
        $this->assertStringContainsString('<label', $html);
        $this->assertStringContainsString('for="attorney-1"', $html);
    }

    public function testFormCheckboxMultiMarksSelectedValuesAsChecked(): void
    {
        $multi = new MultiCheckbox('attorneyList');
        $multi->setValueOptions([
            1 => ['label' => 'Amy Wheeler',   'value' => 1, 'attributes' => ['id' => 'attorney-1']],
            2 => ['label' => 'David Wheeler', 'value' => 2, 'attributes' => ['id' => 'attorney-2']],
        ]);
        $multi->setValue([1]); // only attorney 1 selected

        $html = $this->extension->formCheckbox($multi);

        $this->assertSame(1, substr_count($html, ' checked'));
        // value and checked must be adjacent — matching the legacy FormMultiCheckbox
        // view helper which set checked as a boolean in the attributes array so that
        // createAttributesString() produced `value="X" checked` as adjacent tokens.
        $this->assertStringContainsString('value="1" checked', $html);
        $this->assertStringNotContainsString('value="2" checked', $html);
    }

    public function testFormCheckboxMultiAddsSelectedClassWhenChecked(): void
    {
        $multi = new MultiCheckbox('attorneyList');
        $multi->setValueOptions([
            1 => ['label' => 'Amy Wheeler', 'value' => 1, 'attributes' => ['id' => 'attorney-1']],
        ]);
        $multi->setValue([1]);

        $html = $this->extension->formCheckbox($multi);

        $this->assertStringContainsString('govuk-checkboxes__item selected', $html);
    }

    public function testFormCheckboxMultiStripsInternalDivAttributes(): void
    {
        $multi = new MultiCheckbox('attorneyList');
        $multi->setValueOptions([
            1 => ['label' => 'Amy Wheeler', 'value' => 1, 'attributes' => [
                'id' => 'attorney-1',
                'div-attributes' => ['class' => 'multiple-choice'],
            ]],
        ]);

        $html = $this->extension->formCheckbox($multi);

        $this->assertStringNotContainsString('div-attributes', $html);
        $this->assertStringNotContainsString('multiple-choice', $html);
    }

    public function testFormCheckboxMultiEscapesLabelHtmlByDefault(): void
    {
        $multi = new MultiCheckbox('items');
        $multi->setValueOptions([
            'a' => ['label' => '<strong>Option A</strong>', 'value' => 'a', 'attributes' => ['id' => 'items-a']],
        ]);

        $html = $this->extension->formCheckbox($multi);

        $this->assertStringContainsString('&lt;strong&gt;Option A&lt;/strong&gt;', $html);
        $this->assertStringNotContainsString('<strong>Option A</strong>', $html);
    }

    public function testFormCheckboxMultiRendersLabelHtmlWhenDisableHtmlEscapeSetOnElement(): void
    {
        $multi = new MultiCheckbox('items');
        $multi->setValueOptions([
            'a' => ['label' => '<strong>Option A</strong>', 'value' => 'a', 'attributes' => ['id' => 'items-a']],
        ]);
        $multi->setLabelOptions(['disable_html_escape' => true]);

        $html = $this->extension->formCheckbox($multi);

        $this->assertStringContainsString('<strong>Option A</strong>', $html);
        $this->assertStringNotContainsString('&lt;strong&gt;', $html);
    }

    public function testFormCheckboxMultiRendersLabelHtmlWhenDisableHtmlEscapeSetPerOption(): void
    {
        $multi = new MultiCheckbox('items');
        $multi->setValueOptions([
            'a' => [
                'label'               => '<strong>Option A</strong>',
                'value'               => 'a',
                'attributes'          => ['id' => 'items-a'],
                'disable_html_escape' => true,
            ],
            'b' => ['label' => '<em>Option B</em>', 'value' => 'b', 'attributes' => ['id' => 'items-b']],
        ]);

        $html = $this->extension->formCheckbox($multi);

        $this->assertStringContainsString('<strong>Option A</strong>', $html);
        $this->assertStringContainsString('&lt;em&gt;Option B&lt;/em&gt;', $html);
    }

    public function testFormElementDispatchesToFormCheckboxForMultiCheckbox(): void
    {
        $multi = new MultiCheckbox('items');
        $multi->setValueOptions(['a' => ['label' => 'Option A', 'value' => 'a']]);

        $html = $this->extension->formElement($multi);

        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('name="items[]"', $html);
    }

    // -------------------------------------------------------------------------
    // formRadio
    // -------------------------------------------------------------------------

    public function testFormRadioRendersOptionsAsRadioInputs(): void
    {
        $radio = new Radio('lpa-type');
        $radio->setAttributes(['name' => 'lpa-type', 'id' => 'lpa-type']);
        $radio->setValueOptions(['property-finance' => 'Property and finance', 'health-welfare' => 'Health and welfare']);

        $html = $this->extension->formRadio($radio);

        $this->assertStringContainsString('type="radio"', $html);
        $this->assertStringContainsString('value="property-finance"', $html);
        $this->assertStringContainsString('value="health-welfare"', $html);
        $this->assertStringContainsString('Property and finance', $html);
        $this->assertStringContainsString('Health and welfare', $html);
    }

    public function testFormRadioMarksCurrentValueAsChecked(): void
    {
        $radio = new Radio('lpa-type');
        $radio->setAttributes(['name' => 'lpa-type', 'id' => 'lpa-type']);
        $radio->setValueOptions(['property-finance' => 'Property and finance', 'health-welfare' => 'Health and welfare']);
        $radio->setValue('health-welfare');

        $html = $this->extension->formRadio($radio);

        // Should have exactly one checked
        $this->assertSame(1, substr_count($html, 'checked'));
        // checked attribute appears on the health-welfare input (attribute order may vary)
        $this->assertMatchesRegularExpression('/value="health-welfare"[^>]*\bchecked\b/', $html);
    }

    public function testFormRadioEscapesLabelHtmlByDefault(): void
    {
        $radio = new Radio('how');
        $radio->setAttributes(['name' => 'how']);
        $radio->setValueOptions([
            'jointly' => ['label' => '<strong>Jointly</strong>', 'value' => 'jointly'],
        ]);

        $html = $this->extension->formRadio($radio);

        // HTML tags must be escaped when disable_html_escape is not set
        $this->assertStringContainsString('&lt;strong&gt;Jointly&lt;/strong&gt;', $html);
        $this->assertStringNotContainsString('<strong>Jointly</strong>', $html);
    }

    public function testFormRadioRendersLabelHtmlWhenDisableHtmlEscapeSetOnElement(): void
    {
        $radio = new Radio('how');
        $radio->setAttributes(['name' => 'how']);
        $radio->setValueOptions([
            'jointly' => ['label' => '<strong>Jointly</strong>', 'value' => 'jointly'],
            'depends' => ['label' => '<em>Jointly for some</em>', 'value' => 'depends'],
        ]);
        $radio->setLabelOptions(['disable_html_escape' => true]);

        $html = $this->extension->formRadio($radio);

        // HTML tags must be rendered raw when disable_html_escape is true
        $this->assertStringContainsString('<strong>Jointly</strong>', $html);
        $this->assertStringContainsString('<em>Jointly for some</em>', $html);
        $this->assertStringNotContainsString('&lt;strong&gt;', $html);
    }

    public function testFormRadioRendersLabelHtmlWhenDisableHtmlEscapeSetPerOption(): void
    {
        $radio = new Radio('how');
        $radio->setAttributes(['name' => 'how']);
        $radio->setValueOptions([
            'jointly' => [
                'label'              => '<strong>Jointly</strong>',
                'value'              => 'jointly',
                'disable_html_escape' => true,
            ],
            'severally' => ['label' => '<em>Severally</em>', 'value' => 'severally'],
        ]);

        $html = $this->extension->formRadio($radio);

        // Only the option with disable_html_escape=true renders raw HTML
        $this->assertStringContainsString('<strong>Jointly</strong>', $html);
        // The other option is still escaped
        $this->assertStringContainsString('&lt;em&gt;Severally&lt;/em&gt;', $html);
    }

    // -------------------------------------------------------------------------
    // formRadioOption
    // -------------------------------------------------------------------------

    public function testFormRadioOptionRendersOnlyTheRequestedOption(): void
    {
        $radio = new Radio('contactInWelsh');
        $radio->setAttributes(['name' => 'contactInWelsh']);
        $radio->setValueOptions([
            'english' => ['label' => 'English', 'value' => 'false'],
            'welsh'   => ['label' => 'Welsh',   'value' => 'true'],
        ]);

        $html = $this->extension->formRadioOption($radio, 'english');

        $this->assertSame(1, substr_count($html, 'type="radio"'));
        $this->assertStringContainsString('value="false"', $html);
        $this->assertStringContainsString('>English<', $html);
        $this->assertStringNotContainsString('>Welsh<', $html); // Welsh label absent; 'contactInWelsh' name is fine
    }

    public function testFormRadioOptionDoesNotMutateOriginalElement(): void
    {
        $radio = new Radio('when');
        $radio->setAttributes(['name' => 'when']);
        $radio->setValueOptions([
            'now'         => ['label' => 'Now',         'value' => 'now'],
            'no-capacity' => ['label' => 'No capacity', 'value' => 'no-capacity'],
        ]);

        $this->extension->formRadioOption($radio, 'now');

        // Original element still has both options
        $this->assertCount(2, $radio->getValueOptions());
    }

    public function testFormRadioOptionReturnsEmptyStringForUnknownKey(): void
    {
        $radio = new Radio('when');
        $radio->setAttributes(['name' => 'when']);
        $radio->setValueOptions([
            'now' => ['label' => 'Now', 'value' => 'now'],
        ]);

        $this->assertSame('', $this->extension->formRadioOption($radio, 'nonexistent'));
    }

    public function testFormRadioOptionMarksCheckedWhenValueMatches(): void
    {
        $radio = new Radio('contactInWelsh');
        $radio->setAttributes(['name' => 'contactInWelsh']);
        $radio->setValueOptions([
            'english' => ['label' => 'English', 'value' => 'false'],
            'welsh'   => ['label' => 'Welsh',   'value' => 'true'],
        ]);
        $radio->setValue('true'); // Welsh selected

        $htmlWelsh   = $this->extension->formRadioOption($radio, 'welsh');
        $htmlEnglish = $this->extension->formRadioOption($radio, 'english');

        $this->assertStringContainsString('checked', $htmlWelsh);
        $this->assertStringNotContainsString('checked', $htmlEnglish);
    }

    // -------------------------------------------------------------------------
    // formElement dispatch
    // -------------------------------------------------------------------------

    public function testFormElementDispatchesToFormCheckboxForCheckbox(): void
    {
        $checkbox = new Checkbox('terms');
        $checkbox->setAttributes(['id' => 'terms', 'name' => 'terms']);

        $html = $this->extension->formElement($checkbox);

        $this->assertStringContainsString('type="checkbox"', $html);
    }

    public function testFormElementDispatchesToFormRadioForRadio(): void
    {
        $radio = new Radio('type');
        $radio->setAttributes(['name' => 'type', 'id' => 'type']);
        $radio->setValueOptions(['a' => 'A']);

        $html = $this->extension->formElement($radio);

        $this->assertStringContainsString('type="radio"', $html);
    }

    public function testFormElementRendersHiddenForHiddenType(): void
    {
        $el = new Element\Hidden('token');
        $el->setValue('abc123');

        $html = $this->extension->formElement($el);

        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('value="abc123"', $html);
    }

    public function testFormElementReturnsEmptyStringForNull(): void
    {
        $this->assertSame('', $this->extension->formElement(null));
    }

    public function testFormElementRendersTextInput(): void
    {
        $el = new Element\Text('email');
        $el->setAttributes(['id' => 'email', 'name' => 'email']);
        $el->setValue('test@example.com');

        $html = $this->extension->formElement($el);

        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('value="test@example.com"', $html);
    }

    public function testFormElementDispatchesToFormTextareaForTextarea(): void
    {
        $el = new Element\Textarea('instruction');
        $el->setAttributes(['id' => 'instruction', 'name' => 'instruction', 'rows' => '10']);
        $el->setValue('Some instructions');

        $html = $this->extension->formElement($el);

        // Must render as <textarea>, not <input>
        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('</textarea>', $html);
        $this->assertStringNotContainsString('<input', $html);
        // Value must appear as text content, not as an attribute
        $this->assertStringContainsString('Some instructions', $html);
        $this->assertStringNotContainsString('value=', $html);
    }

    public function testFormTextareaRendersValueAsTextContent(): void
    {
        $el = new Element\Textarea('preference');
        $el->setAttributes(['id' => 'preference', 'name' => 'preference', 'rows' => '5']);
        $el->setValue('<b>Bold</b> text');

        $html = $this->extension->formTextarea($el);

        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('</textarea>', $html);
        // Value must be HTML-escaped in text content
        $this->assertStringContainsString('&lt;b&gt;Bold&lt;/b&gt; text', $html);
    }

    public function testFormTextareaWithEmptyValueRendersEmptyContent(): void
    {
        $el = new Element\Textarea('notes');
        $el->setAttributes(['id' => 'notes', 'name' => 'notes']);

        $html = $this->extension->formTextarea($el);

        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('</textarea>', $html);
    }

    // -------------------------------------------------------------------------
    // formInput / buildInputAttributes
    // -------------------------------------------------------------------------

    public function testFormInputAddsErrorClassWhenElementHasMessages(): void
    {
        $el = new Element\Text('email');
        $el->setAttributes(['id' => 'email', 'name' => 'email']);
        $el->setMessages(['isEmpty' => 'Required']);

        $html = $this->extension->formInput($el);

        $this->assertStringContainsString('govuk-input--error', $html);
    }

    public function testFormInputDoesNotDuplicateErrorClassWhenAlreadyPresent(): void
    {
        $el = new Element\Text('email');
        $el->setAttributes(['id' => 'email', 'name' => 'email', 'class' => 'govuk-input govuk-input--error']);
        $el->setMessages(['isEmpty' => 'Required']);

        $html = $this->extension->formInput($el);

        $this->assertSame(1, substr_count($html, 'govuk-input--error'));
    }

    public function testFormInputRendersValueWhenPresent(): void
    {
        $el = new Element\Text('name');
        $el->setAttributes(['id' => 'name', 'name' => 'name']);
        $el->setValue('John');

        $html = $this->extension->formInput($el);

        $this->assertStringContainsString('value="John"', $html);
    }

    public function testFormInputOmitsValueAttributeWhenNull(): void
    {
        $el = new Element\Text('name');
        $el->setAttributes(['id' => 'name', 'name' => 'name']);
        // Value is null by default

        $html = $this->extension->formInput($el);

        $this->assertStringNotContainsString('value=', $html);
    }

    public function testFormInputOmitsValueAttributeWhenEmptyString(): void
    {
        $el = new Element\Text('name');
        $el->setAttributes(['id' => 'name', 'name' => 'name']);
        $el->setValue('');

        $html = $this->extension->formInput($el);

        $this->assertStringNotContainsString('value=', $html);
    }

    public function testFormInputRendersValueZeroString(): void
    {
        $el = new Element\Text('quantity');
        $el->setAttributes(['id' => 'quantity', 'name' => 'quantity']);
        $el->setValue('0');

        $html = $this->extension->formInput($el);

        $this->assertStringContainsString('value="0"', $html);
    }

    public function testFormInputUsesDefaultTypeAndClass(): void
    {
        $el = new Element\Text('field');
        $el->setAttributes(['id' => 'field', 'name' => 'field']);

        $html = $this->extension->formInput($el);

        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('class="govuk-input"', $html);
    }

    public function testFormInputRespectsCustomTypeAndClass(): void
    {
        $el = new Element\Text('email');
        $el->setAttributes(['id' => 'email', 'name' => 'email', 'type' => 'email', 'class' => 'custom-class']);

        $html = $this->extension->formInput($el);

        $this->assertStringContainsString('type="email"', $html);
        $this->assertStringContainsString('class="custom-class"', $html);
        $this->assertStringNotContainsString('govuk-input', $html);
    }

    // -------------------------------------------------------------------------
    // applicantNames
    // -------------------------------------------------------------------------

    public function testApplicantNamesReturnsDonorWhenWhoIsRegisteringIsDonor(): void
    {
        $lpa = new Lpa((string) file_get_contents(__DIR__ . '/../../fixtures/hw.json'));
        $lpa->document->whoIsRegistering = 'donor';

        $this->assertSame('the donor', $this->extension->applicantNames($lpa));
    }

    public function testApplicantNamesReturnsConcatenatedNamesWhenAttorneysRegister(): void
    {
        $lpa = new Lpa((string) file_get_contents(__DIR__ . '/../../fixtures/hw.json'));

        $whoIsRegistering = array_map(fn($a) => $a->id, $lpa->document->primaryAttorneys);
        $lpa->document->whoIsRegistering = $whoIsRegistering;

        $result = $this->extension->applicantNames($lpa);

        $this->assertNotNull($result);
        $this->assertStringContainsString('and', $result);
    }

    public function testApplicantNamesReturnsNullWhenNotSet(): void
    {
        $lpa = new Lpa((string) file_get_contents(__DIR__ . '/../../fixtures/hw.json'));
        $lpa->document->whoIsRegistering = null;

        $this->assertNull($this->extension->applicantNames($lpa));
    }

    // -------------------------------------------------------------------------
    // finalCheckAccessible
    // -------------------------------------------------------------------------

    public function testFinalCheckAccessibleDelegatesToFormFlowChecker(): void
    {
        $lpa = new Lpa((string) file_get_contents(__DIR__ . '/../../fixtures/hw.json'));

        $this->assertSame(
            FormFlowChecker::isFinalCheckAccessible($lpa),
            $this->extension->finalCheckAccessible($lpa),
        );
    }

    // -------------------------------------------------------------------------
    // serverUrl
    // -------------------------------------------------------------------------

    public function testServerUrlReturnsSchemeAndHost(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        unset($_SERVER['HTTPS']);

        $result = $this->extension->serverUrl();

        $this->assertStringStartsWith('http://', $result);
        $this->assertStringContainsString('localhost', $result);
    }

    public function testServerUrlWithRequestUriAppendspath(): void
    {
        $_SERVER['HTTP_HOST']   = 'localhost';
        $_SERVER['REQUEST_URI'] = '/some/path';
        unset($_SERVER['HTTPS']);

        $result = $this->extension->serverUrl(true);

        $this->assertStringContainsString('/some/path', $result);
    }

    // -------------------------------------------------------------------------
    // routeName
    // -------------------------------------------------------------------------

    public function testRouteNameReturnsBothCurrentAndPreviousAsNull(): void
    {
        $details = new PersistentSessionDetails();

        $ext = new LegacyCompatExtension(
            [],
            $this->formLinkedErrors,
            $details,
            $this->accordionService,
            $this->sessionStorage,
            $this->userDetailsHolder,
            $this->urlHelper,
            $this->flashMessagesHolder,
            $this->systemMessage,
        );

        $functions = $ext->getFunctions();
        $routeNameFn = null;
        foreach ($functions as $fn) {
            if ($fn->getName() === 'routeName') {
                $routeNameFn = $fn->getCallable();
            }
        }

        $result = $routeNameFn();

        $this->assertArrayHasKey('current', $result);
        $this->assertArrayHasKey('previous', $result);
        $this->assertSame('', $result['current']);
        $this->assertSame('home', $result['previous']);
    }
}
