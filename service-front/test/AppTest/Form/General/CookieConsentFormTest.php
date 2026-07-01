<?php

declare(strict_types=1);

namespace AppTest\Form\General;

use App\Form\AbstractForm;
use App\Form\General\CookieConsentForm;
use Laminas\Form\Element\Radio;
use PHPUnit\Framework\TestCase;

final class CookieConsentFormTest extends TestCase
{
    private CookieConsentForm $form;

    protected function setUp(): void
    {
        $this->form = new CookieConsentForm();
        $this->form->init();
    }

    public function testFormName(): void
    {
        $this->assertSame('cookieConsent', $this->form->getName());
    }

    public function testIsAbstractForm(): void
    {
        $this->assertInstanceOf(AbstractForm::class, $this->form);
    }

    public function testFormMethodIsPost(): void
    {
        $this->assertSame('post', $this->form->getAttribute('method'));
    }

    public function testFormHasNovalidateAttribute(): void
    {
        $this->assertSame('novalidate', $this->form->getAttribute('novalidate'));
    }

    public function testHasUsageCookiesRadioElement(): void
    {
        $this->assertTrue($this->form->has('usageCookies'));
        $this->assertInstanceOf(Radio::class, $this->form->get('usageCookies'));
    }

    public function testYesIsValid(): void
    {
        $this->form->setData(['usageCookies' => 'yes']);
        $this->assertTrue($this->form->isValid());
    }

    public function testNoIsValid(): void
    {
        $this->form->setData(['usageCookies' => 'no']);
        $this->assertTrue($this->form->isValid());
    }

    public function testEmptyIsInvalid(): void
    {
        $this->form->setData(['usageCookies' => '']);
        $this->assertFalse($this->form->isValid());
    }
}
