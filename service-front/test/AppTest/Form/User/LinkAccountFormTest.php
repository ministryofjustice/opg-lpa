<?php

declare(strict_types=1);

namespace AppTest\Form\User;

use App\Form\User\LinkAccountForm;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LinkAccountFormTest extends TestCase
{
    private LinkAccountForm $form;

    protected function setUp(): void
    {
        $this->form = new LinkAccountForm();
        $this->form->init();
    }

    public function testAcceptsAnEmailAddressAndPassword(): void
    {
        $this->form->setData(['email' => 'Someone@Example.com', 'password' => 'secret']); // pragma: allowlist secret

        $this->assertTrue($this->form->isValid());
        $data = $this->form->getData();

        $this->assertIsArray($data);
        $this->assertSame('someone@example.com', $data['email']);
    }

    public function testAnEmailAddressInTheWrongFormatGivesOneClearMessage(): void
    {
        $this->form->setData(['email' => 'not-an-email', 'password' => 'secret']); // pragma: allowlist secret
        $this->form->isValid();

        $this->assertSame(['invalidEmailAddress'], array_keys($this->form->get('email')->getMessages()));
    }

    /**
     * @return array<string, array{array<string, string>, string, string}>
     */
    public static function invalidProvider(): array
    {
        return [
            'no email address'        => [['password' => 'secret'], 'email', 'cannot-be-empty'], // pragma: allowlist secret
            'email in the wrong form' => [['email' => 'not-an-email', 'password' => 'secret'], 'email', 'invalidEmailAddress'], // pragma: allowlist secret
            'no password'             => [['email' => 'someone@example.com'], 'password', 'cannot-be-empty'], // pragma: allowlist secret
        ];
    }

    /**
     * @param array<string, string> $data
     */
    #[DataProvider('invalidProvider')]
    public function testOnlyEmptyFieldsAndTheEmailFormatAreChecked(array $data, string $field, string $message): void
    {
        $this->form->setData($data);

        $this->assertFalse($this->form->isValid());

        $messages = $this->form->get($field)->getMessages();
        $this->assertTrue(array_key_exists($message, $messages) || in_array($message, $messages, true));
    }
}
