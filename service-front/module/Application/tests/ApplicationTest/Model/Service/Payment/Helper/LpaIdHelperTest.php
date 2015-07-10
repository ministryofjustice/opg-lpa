<?php
namespace ApplicationTest\Model\Service\Payment\Helper;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Application\Model\Service\Signatures\DateCheck;
use Application\Model\Service\Payment\Helper\LpaIdHelper;

/**
 * FormFlowChecker test case.
 */
class PaymentTest extends AbstractHttpControllerTestCase
{

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();
    }
    
    public function testConstructLpaIdWithStringWhenZeroesNeeded()
    {
        $this->assertEquals(
            LpaIdHelper::constructWorldPayTransactionId('123'),
            '00000000123'
        );
    }
    
    public function testConstructLpaIdWithIntegerWhenZeroesNeeded()
    {
        $this->assertEquals(
            LpaIdHelper::constructWorldPayTransactionId(123),
            '00000000123'
        );
    }
    
    public function testConstructLpaIdWhenNoZeroesNeeded()
    {
        $this->assertEquals(
            LpaIdHelper::constructWorldPayTransactionId('12345678901'),
            '12345678901'
        );
    }
    
    public function testConstructLpaIdWhenLpaIdIsTooBig()
    {
        $exceptionThrown = false;
        try {
            $paddedId = LpaIdHelper::constructWorldPayTransactionId('123456789011');
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }
        
        $this->assertTrue($exceptionThrown);
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
    }
}

