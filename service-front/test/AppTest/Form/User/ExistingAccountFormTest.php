<?php

declare(strict_types=1);

namespace AppTest\Form\User;

use App\Form\User\ExistingAccountForm;
use Laminas\Form\Element\Radio;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExistingAccountFormTest extends TestCase
{
    private ExistingAccountForm $form;

    protected function setUp(): void
    {
        $this->form = new ExistingAccountForm();
        $this->form->init();
    }

    public function testOffersNewToTheServiceFirstThenAnExistingAccount(): void
    {
        $hasExistingAccount = $this->form->get('hasExistingAccount');

        $this->assertInstanceOf(Radio::class, $hasExistingAccount);
        $this->assertSame(['no', 'yes'], array_keys($hasExistingAccount->getValueOptions()));
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
        $this->form->setData(['hasExistingAccount' => $answer]);

        $this->assertTrue($this->form->isValid());
    }

    public function testAnAnswerIsRequired(): void
    {
        $this->form->setData([]);

        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('isEmpty', $this->form->get('hasExistingAccount')->getMessages());
    }

    public function testAnUnknownAnswerIsRejected(): void
    {
        $this->form->setData(['hasExistingAccount' => 'maybe']);

        $this->assertFalse($this->form->isValid());
    }
}
