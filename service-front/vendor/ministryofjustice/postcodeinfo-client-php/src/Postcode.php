<?php
namespace MinistryOfJustice\PostcodeInfo;

class Postcode
{
    /**
     * @var boolean
     */
    private $isValid;
    
    /**
     * @var Point
     */
    private $centrePoint;
    
    /**
     * @var LocalAuthority
     */
    private $localAuthority;
    
    /**
     * An array of type Address
     * 
     * @var array
     */
    private $addresses;
    
    /**
     * @return $isValid
     */
    public function isValid()
    {
        return $this->isValid;
    }

    /**
     * @param boolean $isValid
     */
    public function setIsValid($isValid)
    {
        $this->isValid = $isValid;
    }

    /**
     * @return LocalAuthority $localAuthority
     */
    public function getLocalAuthority()
    {
        return $this->localAuthority;
    }

    /**
     * @param LocalAuthority $localAuthority
     */
    public function setLocalAuthority(LocalAuthority $localAuthority)
    {
        $this->localAuthority = $localAuthority;
    }

    /**
     * @return the $centrePoint
     */
    public function getCentrePoint()
    {
        return $this->centrePoint;
    }
    
    /**
     * @param Point $centrePoint
     */
    public function setCentrePoint($centrePoint)
    {
        $this->centrePoint = $centrePoint;
    }
    
    /**
     * @return the $addresses
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * @param array $addresses
     */
    public function setAddresses($addresses)
    {
        $this->addresses = $addresses;
    }
    
    /**
     * @param $address
     */
    public function addAddress($address)
    {
        $this->addresses[] = $address;
    }

}
