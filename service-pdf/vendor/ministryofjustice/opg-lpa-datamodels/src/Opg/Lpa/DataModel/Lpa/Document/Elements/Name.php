<?php
namespace Opg\Lpa\DataModel\Lpa\Document\Elements;

use Opg\Lpa\DataModel\Lpa\AbstractData;

use Respect\Validation\Rules;
use Opg\Lpa\DataModel\Validator\Validator;

class Name extends AbstractData {

    protected $title;
    protected $first;
    protected $last;

    public function __construct(){
        parent::__construct();

        # TEMPORARY TEST DATA ------------

        $this->title = 'Mr';
        $this->first = 'Bob';
        $this->last = 'Sanders';

        //-----------------------------------------------------
        // Validators (wrapped in Closures for lazy loading)

        $this->validators['title'] = function(){
            return (new Validator)->addRules([
                new Rules\String,
                new Rules\NotEmpty,
                new Rules\Length( 1, 20, true ),
            ]);
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

    } // function

} // class
