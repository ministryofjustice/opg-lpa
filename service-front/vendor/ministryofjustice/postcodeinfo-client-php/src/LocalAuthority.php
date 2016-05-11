<?php
namespace MinistryOfJustice\PostcodeInfo;

class LocalAuthority
{
    /**
     * @var string
     */
    private $name;
    
    /**
     * @var string
     */
    private $gssCode;
    
    /**
     * @return the $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return the $gssCode
     */
    public function getGssCode()
    {
        return $this->gssCode;
    }

    /**
     * @param string $gssCode
     */
    public function setGssCode($gssCode)
    {
        $this->gssCode = $gssCode;
    }

}
