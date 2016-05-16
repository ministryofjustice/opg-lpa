<?php
namespace spec\MinistryOfJustice\PostcodeInfo;

use PhpSpec\ObjectBehavior;

/**
 * Integration tests for the PostcodeInfo API.
 *
 * Note: these tests make assumptions about the data that will be returned from the API.
 *
 * Class ClientSpec
 * @package spec\MinistryOfJustice\PostcodeInfo
 */
class ClientSpec extends ObjectBehavior
{

    function let(){

        $httpClient = new \Http\Client\Curl\Client(
            new \Http\Message\MessageFactory\GuzzleMessageFactory,
            new \Http\Message\StreamFactory\GuzzleStreamFactory
        );

        $this->beConstructedWith([
            'apiKey' => trim(file_get_contents('spec/api_key')),
            'httpClient' => $httpClient,
        ]);

    }
    
    function it_is_initializable()
    {
        $this->shouldHaveType('MinistryOfJustice\PostcodeInfo\Client');
    }

    function it_will_get_an_address_list_object_from_a_lookup()
    {
        $this->lookupPostcodeAddresses('SW195AL')->shouldReturnAnInstanceOf('MinistryOfJustice\PostcodeInfo\Response\AddressList');
    }
    
    function it_will_know_if_the_postcode_is_valid()
    {
        $this->lookupPostcodeAddresses('SW195AL')->count()->shouldNotEqual(0);
    }
    
    function it_will_know_if_the_postcode_is_not_valid()
    {
        $this->lookupPostcodeAddresses('MADEUP')->count()->shouldEqual(0);
    }
    
    function it_will_get_the_postcode_centrepoint_type()
    {
        $this->lookupPostcodeMetadata('AB124YA')->centre->shouldHaveType('MinistryOfJustice\PostcodeInfo\Response\Point');
    }
    
    function it_will_get_the_postcode_centrepoint_latitude()
    {
        $this->lookupPostcodeMetadata('AB124YA')->centre->getLatitude()->shouldBeCoordinate(57.06892522314932);
    }
    
    function it_will_get_the_postcode_centrepoint_longitude()
    {
        $this->lookupPostcodeMetadata('AB124YA')->centre->getLongitude()->shouldBeCoordinate(-2.148964422536167);
    }
    
    function it_will_get_the_local_authority_gss_code()
    {
        $this->lookupPostcodeMetadata('sw1y4jh')->local_authority->gss_code->shouldBe('E09000033');
    }
    
    function it_will_get_the_local_authority_name()
    {
        $this->lookupPostcodeMetadata('sw1y4 jh')->local_authority->name->shouldBe('Westminster');
    }

    function it_will_get_the_country_gss_code()
    {
        $this->lookupPostcodeMetadata('sw1y4jh')->country->gss_code->shouldBe('E92000001');
    }

    function it_will_get_the_country_name()
    {
        $this->lookupPostcodeMetadata('sw1y4 jh')->country->name->shouldBe('England');
    }
    
    function it_will_get_the_uprn()
    {
        $this->lookupPostcodeAddresses('DL3 0UR')->offsetGet(0)->uprn->shouldBe('10013312514');
    }
    
    function it_will_get_the_organisation_name()
    {
        $this->lookupPostcodeAddresses('DL3 0UR')->offsetGet(0)->organisation_name->shouldBe('ARGOS LTD');
    }
    
    function it_will_get_the_po_box_number()
    {
        $this->lookupPostcodeAddresses('M5 0DN')->offsetGet(0)->po_box_number->shouldBe('1234');
    }
    
    function it_will_get_the_building_name()
    {
        $this->lookupPostcodeAddresses('SW19 7nb')->offsetGet(1)->building_name->shouldBe('WIMBLEDON REFERENCE LIBRARY');
    }
    
    function it_will_get_the_sub_building_name()
    {
        $this->lookupPostcodeAddresses('BH65AL')->offsetGet(1)->sub_building_name->shouldBe('FLAT 10');
    }
    
    function it_will_get_the_building_number()
    {
        $this->lookupPostcodeAddresses('BH6 5AL')->offsetGet(5)->building_number->shouldBe(2);
    }

    function it_will_get_the_thoroughfare_name()
    {
        $this->lookupPostcodeAddresses('SW195AL')->offsetGet(0)->thoroughfare_name->shouldBe('CHURCH ROAD');
    }
    
    function it_will_get_the_dependent_locality()
    {
        $this->lookupPostcodeAddresses('hd97ry')->offsetGet(3)->dependent_locality->shouldBe('THONGSBRIDGE');
    }
    
    function it_will_get_the_double_dependent_locality()
    {
        $this->lookupPostcodeAddresses('AB12 4YA')->offsetGet(0)->double_dependent_locality->shouldBe('BADENTOY INDUSTRIAL ESTATE');
    }
    
    function it_will_get_the_post_town()
    {
        $this->lookupPostcodeAddresses('AB12 4YA')->offsetGet(0)->post_town->shouldBe('ABERDEEN');
    }
    
    function it_will_get_the_postcode()
    {
        $this->lookupPostcodeAddresses('aB124YA')->offsetGet(0)->postcode->shouldBe('AB12 4YA');
    }
    
    function it_will_get_the_postcode_type()
    {
        $this->lookupPostcodeAddresses('AB124YA')->offsetGet(0)->postcode_type->shouldBe('S');
    }

    function it_will_get_the_formatted_address()
    {
        $this->lookupPostcodeAddresses('AB124YA')->offsetGet(1)->formatted_address->shouldBe(
            "Downhole Engineering\nBadentoy Road\nBadentoy Industrial Estate\nPortlethen\nAberdeen\nAB12 4YA"
        );
    }
    
    function it_will_get_the_point_type()
    {
        $this->lookupPostcodeAddresses('AB124YA')->offsetGet(1)->point->shouldHaveType('MinistryOfJustice\PostcodeInfo\Response\Point');
    }
    
    function it_will_get_the_latitude()
    {
        $this->lookupPostcodeAddresses('AB124YA')->offsetGet(0)->point->getLatitude()->shouldBeCoordinate(57.06970637985032);
    }

    function it_will_get_the_longitude()
    {
        $this->lookupPostcodeAddresses('AB124YA')->offsetGet(0)->point->getLongitude()->shouldBeCoordinate(-2.150890950596414);
    }
    
    public function getMatchers()
    {
        return [
            'beCoordinate' => function ($subject, $value) {
                // Round to 3 to allow a little flexibility.
                return round($subject, 3) == round($value, 3);
            },
        ];
    }
}
