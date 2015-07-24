<?php
namespace Application\Adapter;

use Zend\Cache\Storage\StorageInterface;

class DynamoDbKeyValueStore implements StorageInterface
{
    
    /**
     * Constructor
     * 
     * @param array $config
     */
    public function __construct(array $config)
    {
    }
    
     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::addItem()
     */
    public function addItem($key, $value)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::addItems()
     */
    public function addItems(array $keyValuePairs)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::checkAndSetItem()
     */
    public function checkAndSetItem($token, $key, $value)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::decrementItem()
     */
    public function decrementItem($key, $value)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::decrementItems()
     */
    public function decrementItems(array $keyValuePairs)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::getCapabilities()
     */
    public function getCapabilities()
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::getItem()
     */
    public function getItem($key, & $success = null, & $casToken = null)
    {
        return 'I am a test.';
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::getItems()
     */
    public function getItems(array $keys)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::getMetadata()
     */
    public function getMetadata($key)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::getMetadatas()
     */
    public function getMetadatas(array $keys)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::getOptions()
     */
    public function getOptions()
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::hasItem()
     */
    public function hasItem($key)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::hasItems()
     */
    public function hasItems(array $keys)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::incrementItem()
     */
    public function incrementItem($key, $value)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::incrementItems()
     */
    public function incrementItems(array $keyValuePairs)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::removeItem()
     */
    public function removeItem($key)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::removeItems()
     */
    public function removeItems(array $keys)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::replaceItem()
     */
    public function replaceItem($key, $value)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::replaceItems()
     */
    public function replaceItems(array $keyValuePairs)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::setItem()
     */
    public function setItem($key, $value)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::setItems()
     */
    public function setItems(array $keyValuePairs)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::setOptions()
     */
    public function setOptions($options)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::touchItem()
     */
    public function touchItem($key)
    {
        // TODO Auto-generated method stub
        
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::touchItems()
     */
    public function touchItems(array $keys)
    {
        // TODO Auto-generated method stub
        
    }

}
