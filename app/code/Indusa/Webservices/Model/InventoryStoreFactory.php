<?php

namespace Indusa\Webservices\Model;

class InventoryStoreFactory
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
     * Create new InventoryStore model
     *
     * @param array $arguments
     * @return \Indusa\Webservices\Model\InventoryStore
     */
    public function create(array $arguments = [])
    {
        return $this->_objectManager->create('Indusa\Webservices\Model\InventoryStore', $arguments, false);
    }
    
	public function getCollection(){

    return $this->_objectManager->create()->getCollection();

}
    
}