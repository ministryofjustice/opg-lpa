<?php
namespace Opg\Lpa\DataModel\Lpa\Elements;

use Opg\Lpa\DataModel\Lpa\AbstractData;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

/**
 * Represents a person's name.
 *
 * Class Name
 * @package Opg\Lpa\DataModel\Lpa\Elements
 */
class Name extends AbstractData {

    /**
     * @var string A person's title. E.g. Mr, Miss, Mrs, etc.
     */
    protected $title;

    /**
     * @var string A person's first name (or names).
     */
    protected $first;

    /**
     * @var string A person's last name.
     */
    protected $last;

    public function extractForPdf(){

        throw new \Exception( 'This method ('.__METHOD__.') has been deprecated.' );

        return [
            'title' => $this->title,
            'first-names' => $this->first,
            'last-name' => $this->last,
        ];

    }

    public function __construct( $data = null ){

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['title'] = function(){
            return (new Validator)->addRule((new Rules\OneOf)->addRules([
                (new Rules\AllOf)->addRules([
                    new Rules\String,
                    new Rules\NotEmpty,
                    new Rules\Length( 1, 5, true ),
                ]),
                new Rules\NullValue,
            ]));
        };

        $this->validators['first'] = function(){
            return (new Validator)->addRules([
                new Rules\String,
                new Rules\NotEmpty,
                new Rules\Length( 1, 50, true ),
            ]);
        };

        $this->validators['last'] = function(){
            return (new Validator)->addRules([
                new Rules\String,
                new Rules\NotEmpty,
                new Rules\Length( 1, 50, false ),
            ]);
        };

        //---

        parent::__construct( $data );

    } // function

} // class
