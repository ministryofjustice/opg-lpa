<?php

declare(strict_types=1);

namespace ApplicationTest\View\Twig;

use Application\Form\Error\FormLinkedErrors;
use Application\Model\FormFlowChecker;
use Application\Service\SystemMessage;
use Application\View\Twig\AppFunctionsExtension;
use Laminas\Form\Element;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;

final class AppFunctionsExtensionTest extends TestCase
{
    private AppFunctionsExtension $extension;
    private TemplateRendererInterface $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $formLinkedErrors = $this->createMock(FormLinkedErrors::class);
        $systemMessage    = $this->createMock(SystemMessage::class);
        $this->renderer         = $this->createMock(TemplateRendererInterface::class);

        $this->extension = new AppFunctionsExtension(
            [],
            $formLinkedErrors,
            $this->renderer,
            $systemMessage,
        );
    }

    public function testRegistersApplicantNamesFunction(): void
    {
        $functions = $this->extension->getFunctions();

        $names = array_map(
            static fn($fn) => $fn->getName(),
            $functions
        );

        $this->assertContains('applicant_names', $names);
    }

    public function testApplicantNamesReturnsTheDonorWhenWhoIsRegisteringIsDonor(): void
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));
        $lpa->document->whoIsRegistering = 'donor';

        $result = $this->extension->applicantNames($lpa);

        $this->assertSame('the donor', $result);
    }

    public function testApplicantNamesReturnsConcatenatedPrimaryAttorneyNamesWhenTheyAreRegistering(): void
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));

        $whoIsRegistering = [];
        foreach ($lpa->document->primaryAttorneys as $attorney) {
            $whoIsRegistering[] = $attorney->id;
        }
        $lpa->document->whoIsRegistering = $whoIsRegistering;

        $expectedHumans = 'Dr Lilly Simpson, Mr Marcel Tanner and Mrs Annabella Collier';

        $result = $this->extension->applicantNames($lpa);

        $this->assertSame($expectedHumans, $result);
    }

    public function testApplicantNamesReturnsNullWhenWhoIsRegisteringIsNotSet(): void
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));

        $lpa->document->whoIsRegistering = null;

        $result = $this->extension->applicantNames($lpa);

        $this->assertNull($result);
    }

    public function testApplicantNamesReturnsNullForUnsupportedWhoIsRegisteringType(): void
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));

        $lpa->document->whoIsRegistering = 12345;

        $result = $this->extension->applicantNames($lpa);

        $this->assertNull($result);
    }

    public function testFinalCheckAccessibleDelegatesToFormFlowChecker(): void
    {
        $lpa = new Lpa(file_get_contents(__DIR__ . '/../../fixtures/hw.json'));

        $expected = FormFlowChecker::isFinalCheckAccessible($lpa);

        $actual = $this->extension->finalCheckAccessible($lpa);

        $this->assertSame($expected, $actual);
    }

    public function testSystemMessageReturnsEmptyStringWhenNoMessage(): void
    {
        $systemMessage = $this->createMock(\Application\Service\SystemMessage::class);
        $systemMessage->expects($this->once())
            ->method('fetchSanitised')
            ->willReturn(null);

        $renderer = $this->createMock(TemplateRendererInterface::class);

        $extension = new AppFunctionsExtension(
            [],
            $this->createMock(FormLinkedErrors::class),
            $renderer,
            $systemMessage,
        );

        $this->assertSame('', $extension->systemMessage());
    }

    public function testSystemMessageRendersPartialWhenMessageExists(): void
    {
        $systemMessage = $this->createMock(\Application\Service\SystemMessage::class);
        $systemMessage->expects($this->once())
            ->method('fetchSanitised')
            ->willReturn('cleaned');

        $renderer = $this->createMock(TemplateRendererInterface::class);
        $renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/partials/system-message.twig',
                ['message' => 'cleaned']
            )
            ->willReturn('<div class="notice"></div>');

        $extension = new AppFunctionsExtension(
            [],
            $this->createMock(FormLinkedErrors::class),
            $renderer,
            $systemMessage,
        );

        $this->assertSame(
            '<div class="notice"></div>',
            $extension->systemMessage()
        );
    }

    public function testReturnsEmptyStringWhenErrorsIsNull(): void
    {
        $this->renderer->expects($this->never())->method('render');

        $result = $this->extension->formElementErrorsV2(null);

        $this->assertSame('', $result);
    }

    public function testReturnsEmptyStringWhenErrorsIsNotArray(): void
    {
        $this->renderer->expects($this->never())->method('render');

        $result = $this->extension->formElementErrorsV2('not-an-array');

        $this->assertSame('', $result);
    }

    public function testReturnsEmptyStringWhenErrorsArrayIsEmpty(): void
    {
        $this->renderer->expects($this->never())->method('render');

        $result = $this->extension->formElementErrorsV2([]);

        $this->assertSame('', $result);
    }

    public function testFlattensNestedErrorArraysAndRendersTemplate(): void
    {
        $errors = [
            'isEmpty' => 'Enter a value',
            'length' => [
                'tooShort' => 'Too short',
            ],
        ];

        $expectedMessages = [
            'Enter a value',
            'Too short',
        ];

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'layout/partials/form-element-errors.twig',
                ['messages' => $expectedMessages]
            )
            ->willReturn('<span>rendered</span>');

        $result = $this->extension->formElementErrorsV2($errors);

        $this->assertSame('<span>rendered</span>', $result);
    }

    public function testAcceptsFormElementAndUsesGetMessages(): void
    {
        $element = $this->createMock(Element::class);

        $element
            ->expects($this->once())
            ->method('getMessages')
            ->willReturn([
                'required' => 'Required',
            ]);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'layout/partials/form-element-errors.twig',
                ['messages' => ['Required']]
            )
            ->willReturn('<span>rendered</span>');

        $result = $this->extension->formElementErrorsV2($element);

        $this->assertSame('<span>rendered</span>', $result);
    }
}
