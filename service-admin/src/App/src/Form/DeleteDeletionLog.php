<?php

declare(strict_types=1);

namespace App\Form;

use App\Filter\StandardInputFilterChain;
use App\Validator;
use Laminas\Form\Element\Hidden;
use Laminas\InputFilter\Input;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */
class DeleteDeletionLog extends AbstractForm
{
    public function __construct(array $options = [])
    {
        parent::__construct(self::class, $options);

        $inputFilter = $this->getInputFilter();

        $field = new Hidden('email');
        $input = new Input($field->getName());

        $input->getFilterChain()
            ->attach(StandardInputFilterChain::create());

        $input->getValidatorChain()
            ->attach(new Validator\NotEmpty(), true);

        $input->setRequired(true);

        $this->add($field);
        $inputFilter->add($input);

        $this->addCsrfElement();
    }
}
