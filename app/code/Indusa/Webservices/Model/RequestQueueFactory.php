<?php

namespace Indusa\Webservices\Model;

class RequestQueueFactory
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
        return $this->_objectManager->create('Indusa\Webservices\Model\RequestQueue', $arguments, false);
    }
}