<?php
namespace MinistryOfJustice\PostcodeInfo;

class Address
{
    /**
     * @var string
     */
    private $uprn;
    
    /**
     * @var string
     */
    private $organisationName;
    
    /**
     * @var string
     */
    private $departmentName;
    
    /**
     * @var string
     */
    private $poBoxNumber;
    
    /**
     * @var string
     */
    private $buildingName;
    
    /**
     * @var string
     */
    private $subBuildingName;
    
    /**
     * @var string
     */
    private $buildingNumber;
    
    /**
     * @var string
     */
    private $thoroughfareName;
    
    /**
     * @var string
     */
    private $dependentLocality;
    
    /**
     * @var string
     */
    private $doubleDependentLocality;
    
    /**
     * @var string
     */
    private $postTown;
    
    /**
     * @var string
     */
    private $postcode;
    
    /**
     * @var string
     */
    private $postcodeType;
    
    /**
     * @var string
     */
    private $formattedAddress;
    
    /**
     * @var Point
     */
    private $point;
    
    /**
     * @return the $uprn
     */
    public function getUprn()
    {
        return $this->uprn;
    }

    /**
     * @param string $uprn
     */
    public function setUprn($uprn)
    {
        $this->uprn = $uprn;
    }

    /**
     * @return the $organisationName
     */
    public function getOrganisationName()
    {
        return $this->organisationName;
    }

    /**
     * @param string $organisationName
     */
    public function setOrganisationName($organisationName)
    {
        $this->organisationName = $organisationName;
    }

    /**
     * @return the $departmentName
     */
    public function getDepartmentName()
    {
        return $this->departmentName;
    }

    /**
     * @param string $departmentName
     */
    public function setDepartmentName($departmentName)
    {
        $this->departmentName = $departmentName;
    }
    
    /**
     * @param string $poBoxNumber
     */
    public function setPoBoxNumber($poBoxNumber)
    {
        $this->poBoxNumber = $poBoxNumber;
    }

    /**
     * @return the $poBoxNumber
     */
    public function getPoBoxNumber()
    {
        return $this->poBoxNumber;
    }

    /**
     * @return the $buildingName
     */
    public function getBuildingName()
    {
        return $this->buildingName;
    }

    /**
     * @param string $buildingName
     */
    public function setBuildingName($buildingName)
    {
        $this->buildingName = $buildingName;
    }

    /**
     * @return the $subBuildingName
     */
    public function getSubBuildingName()
    {
        return $this->subBuildingName;
    }

    /**
     * @param string $subBuildingName
     */
    public function setSubBuildingName($subBuildingName)
    {
        $this->subBuildingName = $subBuildingName;
    }

    /**
     * @return the $thoroughfareName
     */
    public function getThoroughfareName()
    {
        return $this->thoroughfareName;
    }

    /**
     * @param string $thoroughfareName
     */
    public function setThoroughfareName($thoroughfareName)
    {
        $this->thoroughfareName = $thoroughfareName;
    }

    /**
     * @return the $dependentLocality
     */
    public function getDependentLocality()
    {
        return $this->dependentLocality;
    }

    /**
     * @param string $dependentLocality
     */
    public function setDependentLocality($dependentLocality)
    {
        $this->dependentLocality = $dependentLocality;
    }

    /**
     * @return the $doubleDependentLocality
     */
    public function getDoubleDependentLocality()
    {
        return $this->doubleDependentLocality;
    }

    /**
     * @param string $doubleDependentLocality
     */
    public function setDoubleDependentLocality($doubleDependentLocality)
    {
        $this->doubleDependentLocality = $doubleDependentLocality;
    }

    /**
     * @return the $postTown
     */
    public function getPostTown()
    {
        return $this->postTown;
    }

    /**
     * @param string $postTown
     */
    public function setPostTown($postTown)
    {
        $this->postTown = $postTown;
    }

    /**
     * @return the $postcode
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * @param string $postcode
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;
    }

    /**
     * @return the $postcodeType
     */
    public function getPostcodeType()
    {
        return $this->postcodeType;
    }

    /**
     * @param string $postcodeType
     */
    public function setPostcodeType($postcodeType)
    {
        $this->postcodeType = $postcodeType;
    }

    /**
     * @return the $formattedAddress
     */
    public function getFormattedAddress()
    {
        return $this->formattedAddress;
    }

    /**
     * @param string $formattedAddress
     */
    public function setFormattedAddress($formattedAddress)
    {
        $this->formattedAddress = $formattedAddress;
    }

    /**
     * @return the $point
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * @param Point $point
     */
    public function setPoint($point)
    {
        $this->point = $point;
    }

    /**
     * @return the $buildingNumber
     */
    public function getBuildingNumber()
    {
        return $this->buildingNumber;
    }

    /**
     * @param string $buildingNumber
     */
    public function setBuildingNumber($buildingNumber)
    {
        $this->buildingNumber = $buildingNumber;
    }

    public function exchangeArray($array)
    {
        $this->setUprn($array['uprn']);
        $this->setThoroughfareName($array['thoroughfare_name']);
        $this->setOrganisationName($array['organisation_name']);
        $this->setDepartmentName($array['department_name']);
        $this->setPoBoxNumber($array['po_box_number']);
        $this->setBuildingName($array['building_name']);
        $this->setSubBuildingName($array['sub_building_name']);
        $this->setBuildingNumber($array['building_number']);
        $this->setDependentLocality($array['dependent_locality']);
        $this->setDoubleDependentLocality($array['double_dependent_locality']);
        $this->setPostTown($array['post_town']);
        $this->setPostcode($array['postcode']);
        $this->setPostcodeType($array['postcode_type']);
        $this->setFormattedAddress($array['formatted_address']);
        
        $point = new Point();
        $point->setType($array['point']['type']);
        $point->setLongitude($array['point']['coordinates'][0]);
        $point->setLatitude($array['point']['coordinates'][1]);
        
        $this->setPoint($point);
    }
}
