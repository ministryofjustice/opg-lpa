<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\Lpa\WhoAreYouForm;
use MakeShared\DataModel\WhoAreYou\WhoAreYou;
use Laminas\Form\Element\Radio;
use Laminas\Form\Element\Text;
use PHPUnit\Framework\TestCase;

final class WhoAreYouFormTest extends TestCase
{
    private WhoAreYouForm $form;

    protected function setUp(): void
    {
        $this->form = new WhoAreYouForm();
        $this->form->init();
    }

    public function testFormName(): void
    {
        $this->assertSame('form-who-are-you', $this->form->getName());
    }

    public function testHasWhoRadioElement(): void
    {
        $this->assertTrue($this->form->has('who'));
        $this->assertInstanceOf(Radio::class, $this->form->get('who'));
    }

    public function testHasOtherTextField(): void
    {
        $this->assertTrue($this->form->has('other'));
        $this->assertInstanceOf(Text::class, $this->form->get('other'));
    }

    public function testValidDonorWhoIsValid(): void
    {
        $this->form->setData(['who' => 'donor', 'other' => '']);
        $this->assertTrue($this->form->isValid());
    }

    public function testValidFriendOrFamilyWhoIsValid(): void
    {
        $this->form->setData(['who' => 'friendOrFamily', 'other' => '']);
        $this->assertTrue($this->form->isValid());
    }

    public function testValidNotSaidWhoIsValid(): void
    {
        $this->form->setData(['who' => 'notSaid', 'other' => '']);
        $this->assertTrue($this->form->isValid());
    }

    public function testAllValidWhoOptionsAreAccepted(): void
    {
        foreach (WhoAreYou::options() as $option => $_label) {
            if ($option === 'other') {
                continue; // other is tested separately with qualifier
            }
            $this->form->setData(['who' => $option, 'other' => '']);
            $this->assertTrue($this->form->isValid(), "Option '$option' should be valid");
        }
    }

    public function testMissingWhoIsInvalid(): void
    {
        $this->form->setData(['who' => '', 'other' => '']);
        $this->assertFalse($this->form->isValid());
    }

    public function testConvertFormDataForModelWithValidWho(): void
    {
        $this->form->setData(['who' => 'donor', 'other' => '']);
        $this->form->isValid();
        $data = $this->form->getModelDataFromValidatedForm();
        $this->assertSame('donor', $data['who']);
        $this->assertNull($data['qualifier']);
    }

    public function testConvertFormDataForModelWithOtherAndQualifier(): void
    {
        $this->form->setData(['who' => 'other', 'other' => 'My organisation']);
        $this->form->isValid();
        $data = $this->form->getModelDataFromValidatedForm();
        $this->assertSame('other', $data['who']);
        $this->assertSame('My organisation', $data['qualifier']);
    }

    public function testConvertFormDataForModelWithInvalidWhoReturnsEmpty(): void
    {
        $this->form->setData(['who' => 'unknown', 'other' => '']);
        $this->form->isValid();
        $data = $this->form->getModelDataFromValidatedForm();
        $this->assertSame([], $data);
    }
}
