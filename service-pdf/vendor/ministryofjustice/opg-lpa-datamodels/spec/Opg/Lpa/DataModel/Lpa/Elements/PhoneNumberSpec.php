<?php

namespace spec\Opg\Lpa\DataModel\Lpa\Elements;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PhoneNumberSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Opg\Lpa\DataModel\Lpa\Elements\PhoneNumber');
    }
    
    function it_will_accept_various_valid_formats()
    {
        $numbers = [
            '01224 12344',
            '0122412344',
            '0122 412344',
            '023 80 899 299',
            '0044 7813 123456',
            '+447813 728889',
            '012345678911',
        ];
        
        foreach ($numbers as $number) {
            $this->number = $number;
            $this->validate(['number'])->getArrayCopy()->shouldBe([]);
        }
        
    }
    
    function it_will_reject_various_invalid_formats()
    {
        $numbers = [
            'z01224 12344',
            '012241!2344',
            '023 8o 899299',
            'O12345678911',
            '(123.123123123',
            '0123456789012888834',
        ];
        
        foreach ($numbers as $number) {
            echo $number . PHP_EOL;
            $this->number = $number;
            $this->validate(['number'])->getArrayCopy()->shouldHaveCount(1);
        }
    }

}
