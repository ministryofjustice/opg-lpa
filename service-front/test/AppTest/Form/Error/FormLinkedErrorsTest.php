<?php

declare(strict_types=1);

namespace AppTest\Form\Error;

use App\Form\Error\FormLinkedErrors;
use Laminas\Form\Element\Radio;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use PHPUnit\Framework\TestCase;

final class FormLinkedErrorsTest extends TestCase
{
    private FormLinkedErrors $service;

    protected function setUp(): void
    {
        $this->service = new FormLinkedErrors();
    }

    public function testFromFormUsesFirstRadioValueAndFlattensNestedMessages(): void
    {
        $form = new Form();

        $radio = new Radio('contact');
        $radio->setValueOptions([
            ['value' => 'email', 'label' => 'Email'],
            ['value' => 'phone', 'label' => 'Phone'],
        ]);
        $form->add($radio);
        $form->add(new Text('notes'));

        $form->setMessages([
            'contact' => ['isEmpty' => 'Choose a contact method'],
            'notes' => [
                'required' => ['Please enter more detail', ''],
                'ignored' => 123,
            ],
        ]);

        $this->assertSame(
            [
                ['field' => 'contact-email', 'message' => 'Choose a contact method'],
                ['field' => 'notes', 'message' => 'Please enter more detail'],
            ],
            $this->service->fromForm($form)
        );
    }

    public function testFromFormFallsBackToFieldNameWhenRadioOptionsDoNotProvideAnchorValue(): void
    {
        $form = new Form();

        $radio = new Radio('status');
        $radio->setValueOptions([
            'yes' => 'Yes',
            'no' => 'No',
        ]);
        $form->add($radio);

        $emptyRadio = new Radio('empty-radio');
        $emptyRadio->setValueOptions([]);
        $form->add($emptyRadio);

        $form->setMessages([
            'status' => ['invalid' => 'Choose yes or no'],
            'missing-field' => ['isEmpty' => 'This field is required'],
            'empty-radio' => ['isEmpty' => 'Choose one option'],
        ]);

        $result = $this->service->fromForm($form);

        $this->assertCount(3, $result);
        $this->assertContains(['field' => 'status-yes', 'message' => 'Choose yes or no'], $result);
        $this->assertContains(['field' => 'missing-field', 'message' => 'This field is required'], $result);
        $this->assertContains(['field' => 'empty-radio', 'message' => 'Choose one option'], $result);
    }
}
