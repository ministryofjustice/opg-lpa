<?php
namespace MinistryOfJustice\PostcodeInfo;

class Point
{
    
    /**
     * @var string type
     */
    private $type;
    
    /**
     * @var string
     */
    private $latitude;
    
    /**
     * @var string
     */
    private $longitude;
    
    /**
     * @return the $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return the $latitude
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param string $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * @return the $longitude
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param string $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }
    
}
