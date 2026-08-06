<?php

declare(strict_types=1);

namespace AppTest\Form\SharedSpace;

use App\Form\AbstractForm;
use App\Form\SharedSpace\SharedSpaceMemberForm;
use Laminas\Form\Element\Checkbox;
use PHPUnit\Framework\TestCase;

final class SharedSpaceMemberFormTest extends TestCase
{
    private SharedSpaceMemberForm $form;

    protected function setUp(): void
    {
        $this->form = new SharedSpaceMemberForm();
        $this->form->init();
    }

    public function testFormName(): void
    {
        $this->assertSame('sharedSpaceMember', $this->form->getName());
    }

    public function testIsAbstractForm(): void
    {
        $this->assertInstanceOf(AbstractForm::class, $this->form);
    }

    public function testHasPermissionsCheckboxElement(): void
    {
        $this->assertTrue($this->form->has('permissions'));
        $this->assertInstanceOf(Checkbox::class, $this->form->get('permissions'));
    }

    public function testIsValidWhenCheckboxIsChecked(): void
    {
        $this->form->setData(['permissions' => '1']);
        $this->assertTrue($this->form->isValid());

        $data = $this->form->getData();
        $this->assertIsArray($data);
        $this->assertSame('1', $data['permissions']);
    }

    public function testIsValidWhenCheckboxIsUncheckedAndSubmittedAsZero(): void
    {
        $this->form->setData(['permissions' => '0']);

        $this->assertTrue($this->form->isValid());

        $data = $this->form->getData();
        $this->assertIsArray($data);
        $this->assertSame('0', $data['permissions']);
    }

    public function testIsValidWhenCheckboxFieldIsMissingEntirely(): void
    {
        $this->form->setData([]);

        $this->assertTrue($this->form->isValid());
    }
}
