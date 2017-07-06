<?php

namespace spec\Opg\Lpa\DataModel\Common;

use PhpSpec\ObjectBehavior;

class AddressSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Opg\Lpa\DataModel\Common\Address');
    }
    
    function it_will_reject_a_postcode_with_more_than_eight_chars()
    {
        $this->beConstructedWith([
            'address1' => 'Address One',
            'address2' => '',
            'address3' => '',
            'postcode' => '123456789'
        ]);

        $this->validate(['postcode'])->getArrayCopy()->shouldBe([
            "postcode" =>
                [
                    "value" => "123456789",
                    "messages" => [ 0 => "must-be-less-than-or-equal:8" ],
                ]
        ]);
    }
    
    function it_will_allow_an_empty_postcode()
    {
        $this->beConstructedWith([
            'address1' => 'Address One',
            'address2' => '',
            'address3' => '',
            'postcode' => ''
        ]);
    
        $this->validate(['postcode'])->getArrayCopy()->shouldBe([]);
    }
    
    function it_will_allow_a_postcode_with_one_character()
    {
        $this->beConstructedWith([
            'address1' => 'Address One',
            'address2' => '',
            'address3' => '',
            'postcode' => 'A'
        ]);
    
        $this->validate(['postcode'])->getArrayCopy()->shouldBe([]);
    }
}
