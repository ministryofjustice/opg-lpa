<?php

declare(strict_types=1);

namespace AppTest\Form\User;

use App\Form\User\KeepYourLpasForm;
use Laminas\Form\Element\Radio;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class KeepYourLpasFormTest extends TestCase
{
    private KeepYourLpasForm $form;

    protected function setUp(): void
    {
        $this->form = new KeepYourLpasForm();
        $this->form->init();
    }

    public function testOffersYesThenNo(): void
    {
        $keepExistingAccount = $this->form->get('keepExistingAccount');

        $this->assertInstanceOf(Radio::class, $keepExistingAccount);
        $this->assertSame(['yes', 'no'], array_keys($keepExistingAccount->getValueOptions()));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function answerProvider(): array
    {
        return ['no' => ['no'], 'yes' => ['yes']];
    }

    #[DataProvider('answerProvider')]
    public function testEachOptionIsValid(string $answer): void
    {
        $this->form->setData(['keepExistingAccount' => $answer]);

        $this->assertTrue($this->form->isValid());
    }

    public function testAnAnswerIsRequired(): void
    {
        $this->form->setData([]);

        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('isEmpty', $this->form->get('keepExistingAccount')->getMessages());
    }

    public function testAnUnknownAnswerIsRejected(): void
    {
        $this->form->setData(['keepExistingAccount' => 'maybe']);

        $this->assertFalse($this->form->isValid());
    }
}
