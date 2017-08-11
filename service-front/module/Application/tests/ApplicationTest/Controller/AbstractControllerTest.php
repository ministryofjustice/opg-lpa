<?php

namespace ApplicationTest\Controller;

use PHPUnit_Framework_Error_Deprecated;

abstract class AbstractControllerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        //Required to suppress the deprecated error received when calling getServiceLocator()
        //Calling and using the service locator directly in code could be considered a IoC/DI anti pattern
        //Ideally we would be injecting dependencies via constructor args or setters via the IoC container
        PHPUnit_Framework_Error_Deprecated::$enabled = false;
    }
}