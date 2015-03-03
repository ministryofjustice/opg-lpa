<?php
namespace Application\Model\Service\Payment;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Omnipay\Omnipay;
use Omnipay\Common\CreditCard;
use Opg\Lpa\DataModel\Lpa\Payment\Payment as PaymentEntity;
use Zend\Session\Container;

class Payment implements ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    /**
     * Update the LPA with the successful payment information
     *
     * @param array $params
     * @param Lpa $lpa
     */
    public function updateLpa($params, $lpa)
    {
        $client = $this->getServiceLocator()->get('ApiClient');
        $config = $this->getServiceLocator()->get('config')['worldpay'];
        $prefix = $config['administration_code'] . '^' . $config['merchant_code'] . '^';
        
        $payment = $lpa->payment;
        $payment->reference = str_replace($prefix, '', $params['orderKey']);
        $payment->date = new \DateTime('today');
        
        //Payment amount and method has been set on fee page
        //$payment->amount = intval($params['paymentAmount']);
        //$payment->method = PaymentEntity::PAYMENT_TYPE_CARD;
        
        $result = $client->setPayment($lpa->id, $payment);
        
        if ($result === false) {
            throw new \Exception(
                'Unable to update LPA with all payment information: ' .
                'API status code: ' . $client->getLastStatusCode() . ' ' .
                'API returned content: ' . print_r($client->getLastContent(), true) 
            );
        }
    }
    
    /**
     * Helper function to verify the order key returned by Worldpay
     *
     * @param array $params
     * @params string $lpaId
     */
    public function verifyOrderKey($params, $lpaId)
    {
        $config = $this->getServiceLocator()->get('config')['worldpay'];
    
        $regexString = "/^" . $config['administration_code'] . '\^'. $config['merchant_code'] . '\^' . "(.+)-(.+)$/";
    
        if (preg_match($regexString, $params['orderKey'], $matches)) {
            if ($matches[1] != $lpaId) {
                throw new \Exception(
                    'Invalid Worldpay orderKey received: ' . $params['orderKey'] . ', ' .
                    'LPA ID ' . $matches[1] . ' does not match session LPA ' . $lpaId
                );
            }
        } else {
            throw new \Exception(
                'Invalid Worldpay orderKey received: ' . $params['orderKey'] . ', ' .
                'expected match with regex ' . $regexString
            );
        }
    }
    
    /**
     * Helper function to verify the MAC string returned from Worldpay
     *
     * @param array $params
     */
    public function verifyMacString($params)
    {
        $config = $this->getServiceLocator()->get('config')['worldpay'];
    
        $macString =
            $params['orderKey'] .
            $params['paymentAmount'] .
            $params['paymentCurrency'] .
            $params['paymentStatus'] .
            $config['mac_secret'];
    
        $md5Mac = md5($macString);
    
        if ($params['mac'] != $md5Mac) {
            throw new \Exception(
                'Worldpay MAC string not verified: ' . $params['mac'] . ' expected ' . $md5Mac
            );
        }
    }
    
    /**
     * Helper function to create and configure the Omnipay gateway object
     */
    public function getGateway()
    {
        $config = $this->getServiceLocator()->get('config')['worldpay'];
    
        $gateway = Omnipay::create('WorldPayXML');
    
        $gateway->setInstallation($config['installation_id']);
        $gateway->setMerchant($config['merchant_code']);
        $gateway->setPassword($config['xml_password']);
        $gateway->setTestMode($config['test_mode']);
    
        return $gateway;
    }
    
    /**
     * Helper function to construct the options use to create
     * the XML for the initial request to Worldpay.
     *
     * @param Lpa $lpa
     * @return array
     */
    public function getOptions($lpa)
    {
        $config = $this->getServiceLocator()->get('config')['worldpay'];
        
        $container = new Container('paymentEmail');
    
        $donorName = $lpa->document->donor->name;
    
        $options = [
            'amount' => $lpa->payment->amount,
            'currency' => $config['currency'],
            'description' => 'LPA for ' . $donorName->__toString(),
            'transactionId' => $lpa->id . '-' . time(),
            'card' => new CreditCard([
                'email' => $container->email,
            ]),
            'token' => $config['api_token_secret'],
        ];
    
        return $options;  
    }
}
