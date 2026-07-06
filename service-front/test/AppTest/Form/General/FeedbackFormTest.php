<?php

declare(strict_types=1);

namespace AppTest\Form\General;

use App\Form\AbstractForm;
use App\Form\General\FeedbackForm;
use Laminas\Form\Element\Radio;
use Laminas\Form\Element\Textarea;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Text;
use PHPUnit\Framework\TestCase;

final class FeedbackFormTest extends TestCase
{
    private FeedbackForm $form;

    protected function setUp(): void
    {
        $this->form = new FeedbackForm();
        $this->form->init();
    }

    public function testFormName(): void
    {
        $this->assertSame('send-feedback', $this->form->getName());
    }

    public function testIsAbstractForm(): void
    {
        $this->assertInstanceOf(AbstractForm::class, $this->form);
    }

    public function testHasRatingRadioElement(): void
    {
        $this->assertTrue($this->form->has('rating'));
        $this->assertInstanceOf(Radio::class, $this->form->get('rating'));
    }

    public function testHasDetailsTextarea(): void
    {
        $this->assertTrue($this->form->has('details'));
        $this->assertInstanceOf(Textarea::class, $this->form->get('details'));
    }

    public function testHasEmailElement(): void
    {
        $this->assertTrue($this->form->has('email'));
        $this->assertInstanceOf(Email::class, $this->form->get('email'));
    }

    public function testHasPhoneElement(): void
    {
        $this->assertTrue($this->form->has('phone'));
        $this->assertInstanceOf(Text::class, $this->form->get('phone'));
    }

    public function testValidMinimalDataIsValid(): void
    {
        $this->form->setData([
            'rating'  => 'satisfied',
            'details' => 'Great service',
            'email'   => '',
            'phone'   => '',
        ]);
        $this->assertTrue($this->form->isValid());
    }

    public function testAllRatingsAreAccepted(): void
    {
        foreach (['very-satisfied', 'satisfied', 'neither-satisfied-or-dissatisfied', 'dissatisfied', 'very-dissatisfied'] as $rating) {
            $this->form->setData([
                'rating'  => $rating,
                'details' => 'test',
                'email'   => '',
                'phone'   => '',
            ]);
            $this->assertTrue($this->form->isValid(), "Rating '$rating' should be valid");
        }
    }

    public function testMissingRatingIsInvalid(): void
    {
        $this->form->setData([
            'rating'  => '',
            'details' => 'Great',
            'email'   => '',
            'phone'   => '',
        ]);
        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('rating', $this->form->getMessages());
    }

    public function testMissingDetailsIsInvalid(): void
    {
        $this->form->setData([
            'rating'  => 'satisfied',
            'details' => '',
            'email'   => '',
            'phone'   => '',
        ]);
        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('details', $this->form->getMessages());
    }

    public function testDetailsTooLongIsInvalid(): void
    {
        $this->form->setData([
            'rating'  => 'satisfied',
            'details' => str_repeat('a', 2001),
            'email'   => '',
            'phone'   => '',
        ]);
        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('details', $this->form->getMessages());
    }

    public function testDetailsExactlyMaxLengthIsValid(): void
    {
        $this->form->setData([
            'rating'  => 'satisfied',
            'details' => str_repeat('a', 2000),
            'email'   => '',
            'phone'   => '',
        ]);
        $this->assertTrue($this->form->isValid());
    }

    public function testValidEmailIsAccepted(): void
    {
        $this->form->setData([
            'rating'  => 'satisfied',
            'details' => 'Good',
            'email'   => 'user@example.com',
            'phone'   => '',
        ]);
        $this->assertTrue($this->form->isValid());
    }

    public function testInvalidEmailIsRejected(): void
    {
        $this->form->setData([
            'rating'  => 'satisfied',
            'details' => 'Good',
            'email'   => 'not-an-email',
            'phone'   => '',
        ]);
        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('email', $this->form->getMessages());
    }

    public function testEmailIsLowercasedAfterValidation(): void
    {
        $this->form->setData([
            'rating'  => 'satisfied',
            'details' => 'Good',
            'email'   => 'USER@EXAMPLE.COM',
            'phone'   => '',
        ]);
        $this->form->isValid();
        $this->assertSame('user@example.com', $this->form->getData()['email']);
    }

    public function testValidPhoneIsAccepted(): void
    {
        $this->form->setData([
            'rating'  => 'satisfied',
            'details' => 'Good',
            'email'   => '',
            'phone'   => '01234567890',
        ]);
        $this->assertTrue($this->form->isValid());
    }

    public function testHtmlTagsInDetailsAreStripped(): void
    {
        $this->form->setData([
            'rating'  => 'satisfied',
            'details' => '<b>Good service</b>',
            'email'   => '',
            'phone'   => '',
        ]);
        $this->form->isValid();
        $this->assertStringNotContainsString('<b>', $this->form->getData()['details']);
        $this->assertStringContainsString('Good service', $this->form->getData()['details']);
    }
}
