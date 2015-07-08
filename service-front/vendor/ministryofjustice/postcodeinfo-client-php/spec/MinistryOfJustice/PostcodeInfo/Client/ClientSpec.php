<?php
namespace spec\MinistryOfJustice\PostcodeInfo\Client;

include "spec/SpecHelper.php";

use PhpSpec\ObjectBehavior;
use MinistryOfJustice\PostcodeInfo\Client\Postcode;

class ClientSpec extends ObjectBehavior
{
    
    function it_is_initializable()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->shouldHaveType('MinistryOfJustice\PostcodeInfo\Client\Client');
    }

    function it_will_get_a_postcode_object_from_a_lookup()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('SW195AL')->shouldBeAValidPostcodeObject();
    }
    
    function it_will_know_if_the_postcode_is_valid()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('SW195AL')->isValid()->shouldBe(true);
    }
    
    function it_will_know_if_the_postcode_is_not_valid()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('MADEUP')->isValid()->shouldBe(false);
    }
    
    function it_will_get_the_postcode_centrepoint_type()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('AB124YA')->getCentrePoint()->getType()->shouldBe('Point');
    }
    
    function it_will_get_the_postcode_centrepoint_latitude()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('AB124YA')->getCentrePoint()->getLatitude()->shouldBe(-2.148964422536167);
    }
    
    function it_will_get_the_postcode_centrepoint_longitude()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('AB124YA')->getCentrePoint()->getLongitude()->shouldBe(57.06892522314932);
    }
    
    function it_will_get_the_local_authority_gss_code()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('sw1y4jh')->getLocalAuthority()->getGssCode()->shouldBe('E09000033');
    }
    
    function it_will_get_the_local_authority_name()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('sw1y4 jh')->getLocalAuthority()->getName()->shouldBe('Westminster');
    }
    
    function it_will_get_the_uprn()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('DL3 0UR')->getAddresses()[0]->getUprn()->shouldBe('10013312514');
    }
    
    function it_will_get_the_organisation_name()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('DL3 0UR')->getAddresses()[0]->getOrganisationName()->shouldBe('ARGOS LTD');
    }
    
    function it_will_get_the_po_box_number()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('M5 0DN')->getAddresses()[0]->getPoBoxNumber()->shouldBe('1234');
    }
    
    function it_will_get_the_building_name()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('SW19 7nb')->getAddresses()[1]->getBuildingName()->shouldBe('WIMBLEDON REFERENCE LIBRARY');
    }
    
    function it_will_get_the_sub_building_name()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('BH65AL')->getAddresses()[1]->getSubBuildingName()->shouldBe('FLAT 10');
    }
    
    function it_will_get_the_building_number()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('BH6 5AL')->getAddresses()[5]->getBuildingNumber()->shouldBe(2);
    }

    function it_will_get_the_thoroughfare_name()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('SW195AL')->getAddresses()[0]->getThoroughfareName()->shouldBe('CHURCH ROAD');
    }
    
    function it_will_get_the_dependent_locality()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('hd97ry')->getAddresses()[3]->getDependentLocality()->shouldBe('THONGSBRIDGE');
    }
    
    function it_will_get_the_double_dependent_locality()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('AB12 4YA')->getAddresses()[0]->getDoubleDependentLocality()->shouldBe('BADENTOY INDUSTRIAL ESTATE');
    }
    
    function it_will_get_the_post_town()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('AB12 4YA')->getAddresses()[0]->getPostTown()->shouldBe('ABERDEEN');
    }
    
    function it_will_get_the_postcode()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('aB124YA')->getAddresses()[0]->getPostcode()->shouldBe('AB12 4YA');
    }
    
    function it_will_get_the_postcode_type()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('AB124YA')->getAddresses()[0]->getPostcodeType()->shouldBe('S');
    }

    function it_will_get_the_formatted_address()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('AB124YA')->getAddresses()[1]->getFormattedAddress()->shouldBe(
            "Cameron Controls Ltd\nBadentoy Road\nBadentoy Industrial Estate\nPortlethen\nAberdeen\nAB12 4YA"
        );
    }
    
    function it_will_get_the_point_type()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('AB124YA')->getAddresses()[1]->getPoint()->getType()->shouldBe('Point');
    }
    
    function it_will_get_the_latitude()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('AB124YA')->getAddresses()[0]->getPoint()->getLatitude()->shouldBe(-2.150890950596414);
    }

    function it_will_get_the_longitude()
    {
        $this->beConstructedWith(file_get_contents('spec/api_key'), 'https://postcodeinfo-staging.dsd.io/');
        $this->lookupPostcode('AB124YA')->getAddresses()[0]->getPoint()->getLongitude()->shouldBe(57.06970637985032);
    }
    
    public function getMatchers()
    {
        return [
            'beAValidPostcodeObject' => function($subject) {
                return $subject instanceof Postcode;
            },
        ];
    }
}
