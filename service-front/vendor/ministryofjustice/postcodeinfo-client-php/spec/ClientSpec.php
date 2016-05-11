<?php
namespace spec\MinistryOfJustice\PostcodeInfo;

use PhpSpec\ObjectBehavior;
use MinistryOfJustice\PostcodeInfo\Postcode;

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

    function it_will_get_a_postcode_object_from_a_lookup()
    {
        $this->lookupPostcode('SW195AL')->shouldBeAValidPostcodeObject();
    }
    
    function it_will_know_if_the_postcode_is_valid()
    {
        $this->lookupPostcode('SW195AL')->isValid()->shouldBe(true);
    }
    
    function it_will_know_if_the_postcode_is_not_valid()
    {
        $this->lookupPostcode('MADEUP')->isValid()->shouldBe(false);
    }
    
    function it_will_get_the_postcode_centrepoint_type()
    {
        $this->lookupPostcode('AB124YA')->getCentrePoint()->getType()->shouldBe('Point');
    }
    
    function it_will_get_the_postcode_centrepoint_latitude()
    {
        $this->lookupPostcode('AB124YA')->getCentrePoint()->getLatitude()->shouldBeCoordinate(57.06892522314932);
    }
    
    function it_will_get_the_postcode_centrepoint_longitude()
    {
        $this->lookupPostcode('AB124YA')->getCentrePoint()->getLongitude()->shouldBeCoordinate(-2.148964422536167);
    }
    
    function it_will_get_the_local_authority_gss_code()
    {
        $this->lookupPostcode('sw1y4jh')->getLocalAuthority()->getGssCode()->shouldBe('E09000033');
    }
    
    function it_will_get_the_local_authority_name()
    {
        $this->lookupPostcode('sw1y4 jh')->getLocalAuthority()->getName()->shouldBe('Westminster');
    }
    
    function it_will_get_the_uprn()
    {
        $this->lookupPostcode('DL3 0UR')->getAddresses()[0]->getUprn()->shouldBe('10013312514');
    }
    
    function it_will_get_the_organisation_name()
    {
        $this->lookupPostcode('DL3 0UR')->getAddresses()[0]->getOrganisationName()->shouldBe('ARGOS LTD');
    }
    
    function it_will_get_the_po_box_number()
    {
        $this->lookupPostcode('M5 0DN')->getAddresses()[0]->getPoBoxNumber()->shouldBe('1234');
    }
    
    function it_will_get_the_building_name()
    {
        $this->lookupPostcode('SW19 7nb')->getAddresses()[1]->getBuildingName()->shouldBe('WIMBLEDON REFERENCE LIBRARY');
    }
    
    function it_will_get_the_sub_building_name()
    {
        $this->lookupPostcode('BH65AL')->getAddresses()[1]->getSubBuildingName()->shouldBe('FLAT 10');
    }
    
    function it_will_get_the_building_number()
    {
        $this->lookupPostcode('BH6 5AL')->getAddresses()[5]->getBuildingNumber()->shouldBe(2);
    }

    function it_will_get_the_thoroughfare_name()
    {
        $this->lookupPostcode('SW195AL')->getAddresses()[0]->getThoroughfareName()->shouldBe('CHURCH ROAD');
    }
    
    function it_will_get_the_dependent_locality()
    {
        $this->lookupPostcode('hd97ry')->getAddresses()[3]->getDependentLocality()->shouldBe('THONGSBRIDGE');
    }
    
    function it_will_get_the_double_dependent_locality()
    {
        $this->lookupPostcode('AB12 4YA')->getAddresses()[0]->getDoubleDependentLocality()->shouldBe('BADENTOY INDUSTRIAL ESTATE');
    }
    
    function it_will_get_the_post_town()
    {
        $this->lookupPostcode('AB12 4YA')->getAddresses()[0]->getPostTown()->shouldBe('ABERDEEN');
    }
    
    function it_will_get_the_postcode()
    {
        $this->lookupPostcode('aB124YA')->getAddresses()[0]->getPostcode()->shouldBe('AB12 4YA');
    }
    
    function it_will_get_the_postcode_type()
    {
        $this->lookupPostcode('AB124YA')->getAddresses()[0]->getPostcodeType()->shouldBe('S');
    }

    function it_will_get_the_formatted_address()
    {
        $this->lookupPostcode('AB124YA')->getAddresses()[1]->getFormattedAddress()->shouldBe(
            "Downhole Engineering\nBadentoy Road\nBadentoy Industrial Estate\nPortlethen\nAberdeen\nAB12 4YA"
        );
    }
    
    function it_will_get_the_point_type()
    {
        $this->lookupPostcode('AB124YA')->getAddresses()[1]->getPoint()->getType()->shouldBe('Point');
    }
    
    function it_will_get_the_latitude()
    {
        $this->lookupPostcode('AB124YA')->getAddresses()[0]->getPoint()->getLatitude()->shouldBeCoordinate(57.06970637985032);
    }

    function it_will_get_the_longitude()
    {
        $this->lookupPostcode('AB124YA')->getAddresses()[0]->getPoint()->getLongitude()->shouldBeCoordinate(-2.150890950596414);
    }
    
    public function getMatchers()
    {
        return [
            'beAValidPostcodeObject' => function($subject) {
                return $subject instanceof Postcode;
            },
            'beCoordinate' => function ($subject, $value) {
                // Round to 3 to allow a little flexibility.
                return round($subject, 3) == round($value, 3);
            },
        ];
    }
}
