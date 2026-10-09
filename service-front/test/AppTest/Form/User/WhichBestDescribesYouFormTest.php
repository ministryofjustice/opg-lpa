<?php

declare(strict_types=1);

namespace AppTest\Form\User;

use App\Form\User\WhichBestDescribesYouForm;
use Laminas\Form\Element\Radio;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WhichBestDescribesYouFormTest extends TestCase
{
    private WhichBestDescribesYouForm $form;

    protected function setUp(): void
    {
        $this->form = new WhichBestDescribesYouForm();
        $this->form->init();
    }

    public function testHasTheTwoUserTypeOptions(): void
    {
        $userType = $this->form->get('userType');

        $this->assertInstanceOf(Radio::class, $userType);
        $this->assertSame(['lay', 'professional'], array_keys($userType->getValueOptions()));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function userTypeProvider(): array
    {
        return ['lay' => ['lay'], 'professional' => ['professional']];
    }

    #[DataProvider('userTypeProvider')]
    public function testEachOptionIsValid(string $userType): void
    {
        $this->form->setData(['userType' => $userType]);

        $this->assertTrue($this->form->isValid());
    }

    public function testAnAnswerIsRequired(): void
    {
        $this->form->setData([]);

        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('isEmpty', $this->form->get('userType')->getMessages());
    }

    public function testAnUnknownAnswerIsRejected(): void
    {
        $this->form->setData(['userType' => 'admin']);

        $this->assertFalse($this->form->isValid());
    }
}
