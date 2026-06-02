<?php

declare(strict_types=1);

namespace AppTest\View\Twig;

use App\Form\Error\FormLinkedErrors;
use App\Model\FlashMessagesHolder;
use App\Model\FormFlowChecker;
use App\Model\Service\Session\PersistentSessionDetails;
use App\Model\UserDetailsHolder;
use App\Service\AccordionService;
use App\Storage\MezzioSessionStorage;
use App\View\Twig\LegacyCompatExtension;
use Laminas\Form\Element;
use Laminas\Form\Element\Checkbox;
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

    protected function setUp(): void
    {
        $this->urlHelper           = $this->createMock(UrlHelper::class);
        $this->sessionStorage      = $this->createMock(MezzioSessionStorage::class);
        $this->formLinkedErrors    = $this->createMock(FormLinkedErrors::class);
        $this->accordionService    = $this->createMock(AccordionService::class);
        $this->userDetailsHolder   = $this->createMock(UserDetailsHolder::class);
        $this->flashMessagesHolder = $this->createMock(FlashMessagesHolder::class);

        $this->extension = new LegacyCompatExtension(
            ['version' => ['cache' => '', 'tag' => 'dev']],
            $this->formLinkedErrors,
            new PersistentSessionDetails(),
            $this->accordionService,
            $this->sessionStorage,
            $this->userDetailsHolder,
            $this->urlHelper,
            $this->flashMessagesHolder,
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
        $this->assertStringContainsString('value="health-welfare" checked', $html);
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

    // -------------------------------------------------------------------------
    // formInput error class
    // -------------------------------------------------------------------------

    public function testFormInputAddsErrorClassWhenElementHasMessages(): void
    {
        $el = new Element\Text('email');
        $el->setAttributes(['id' => 'email', 'name' => 'email']);
        $el->setMessages(['isEmpty' => 'Required']);

        $html = $this->extension->formInput($el);

        $this->assertStringContainsString('govuk-input--error', $html);
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
