<?php

declare(strict_types=1);

namespace AppTest\Form\Lpa;

use App\Form\Lpa\ReuseDetailsForm;
use Laminas\Form\Element\Radio;
use Laminas\Form\Element\Submit;
use PHPUnit\Framework\TestCase;

final class ReuseDetailsFormTest extends TestCase
{
    public function testFormName(): void
    {
        $form = new ReuseDetailsForm();
        $form->init();
        $this->assertSame('form-reuse-details', $form->getName());
    }

    public function testHasReuseDetailsRadioElement(): void
    {
        $form = new ReuseDetailsForm();
        $form->init();
        $this->assertTrue($form->has('reuse-details'));
        $this->assertInstanceOf(Radio::class, $form->get('reuse-details'));
    }

    public function testHasSubmitElement(): void
    {
        $form = new ReuseDetailsForm();
        $form->init();
        $this->assertTrue($form->has('submit'));
        $this->assertInstanceOf(Submit::class, $form->get('submit'));
    }

    public function testSingleActorOptionDoesNotAddNoneOfTheAboveChoice(): void
    {
        $form = new ReuseDetailsForm([
            'actorReuseDetails' => [
                ['label' => 'John Doe (Donor)'],
            ],
        ]);
        $form->init();

        $options = $form->get('reuse-details')->getValueOptions();
        $labels  = array_column($options, 'label');
        $this->assertNotContains('None of the above - I want to add a new person', $labels);
        $this->assertCount(1, $options);
    }

    public function testMultipleActorOptionsAddNoneOfTheAboveChoice(): void
    {
        $form = new ReuseDetailsForm([
            'actorReuseDetails' => [
                ['label' => 'John Doe (Donor)'],
                ['label' => 'Jane Smith (Attorney)'],
            ],
        ]);
        $form->init();

        $options = $form->get('reuse-details')->getValueOptions();
        $labels  = array_column($options, 'label');
        $this->assertContains('None of the above - I want to add a new person', $labels);
        $this->assertCount(3, $options); // 2 actors + None of the above
    }

    public function testNoneOfTheAboveOptionHasValueOfMinusOne(): void
    {
        $form = new ReuseDetailsForm([
            'actorReuseDetails' => [
                ['label' => 'A'],
                ['label' => 'B'],
            ],
        ]);
        $form->init();

        $options = $form->get('reuse-details')->getValueOptions();
        $noneOption = array_filter($options, fn($o) => $o['value'] === '-1');
        $this->assertCount(1, $noneOption);
    }

    public function testNoActorDetailsResultsInEmptyOptions(): void
    {
        $form = new ReuseDetailsForm();
        $form->init();

        $options = $form->get('reuse-details')->getValueOptions();
        $this->assertEmpty($options);
    }

    public function testActorDetailsPassedAsFirstArgumentWork(): void
    {
        // Laminas InvokableFactory passes options as first arg
        $form = new ReuseDetailsForm([
            'actorReuseDetails' => [
                ['label' => 'Someone'],
            ],
        ]);
        $form->init();

        $options = $form->get('reuse-details')->getValueOptions();
        $this->assertCount(1, $options);
        $this->assertSame('Someone', $options[0]['label']);
    }

    public function testFormDataCyAttributeIsSet(): void
    {
        $form = new ReuseDetailsForm();
        $form->init();
        $this->assertSame('form-reuse-details', $form->getAttribute('data-cy'));
    }
}
