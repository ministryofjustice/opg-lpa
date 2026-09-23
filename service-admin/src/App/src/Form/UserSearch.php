<?php

declare(strict_types=1);

namespace App\Form;

use App\Validator;
use App\Filter\StandardInputFilterChain;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Text;
use Laminas\InputFilter\Input;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */
class UserSearch extends AbstractForm
{
    public const array SEARCH_TYPE_OPTIONS = [
        'email'           => 'Exact or partial email',
        'sharedSpaceName' => 'Exact or partial shared space name',
        'userId'          => 'Exact user ID',
        'aReference'      => 'Exact a-reference',
    ];

    /**
     * UserSearch constructor
     *
     * @param array<string, mixed> $options
     */
    public function __construct($options = [])
    {
        parent::__construct(self::class, $options);

        $inputFilter = $this->getInputFilter();

        // Search type select
        $select = new Select('searchType');
        $select->setValueOptions(self::SEARCH_TYPE_OPTIONS);

        $selectInput = new Input($select->getName());
        $selectInput->setRequired(true);

        $this->add($select);
        $inputFilter->add($selectInput);

        // Search term field
        $field = new Text('searchTerm');
        $input = new Input($field->getName());

        $input->getFilterChain()
            ->attach(StandardInputFilterChain::create());

        $input->getValidatorChain()
            ->attach(new Validator\NotEmpty(), true);

        $input->setRequired(true);

        $this->add($field);
        $inputFilter->add($input);

        // page field
        $page = new Hidden('page');
        $pageInput = new Input($page->getName());

        $pageInput->getFilterChain()
            ->attach(StandardInputFilterChain::create());

        $pageInput->getValidatorChain()
            ->attach(new Validator\Digits(), true);

        $this->add($page);
        $inputFilter->add($pageInput);

        // Csrf field
        $this->addCsrfElement();
    }
}
