<?php

namespace Indusa\AddressValidator\Model;

class CityFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * Create new Request Queue model
     *
     * @param array $arguments
     * @return $object
     */
    public function create(array $arguments = [])
    {
        return $this->_objectManager->create('Indusa\AddressValidator\Model\City', $arguments, false);
    }
    
    public function getCollection(){
         return $this->_objectManager->create()->getCollection();
    }
}