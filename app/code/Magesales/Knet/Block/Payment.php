<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magesales\Knet\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;


/**
 * Class Payment
 */
class Payment extends Template
{
    /**
     * @var ConfigInterface
     */
	protected $_checkoutSession;
	
	protected $_orderCollectionFactory;
    /**
     * Constructor
     *
     * @param Context $context
     * @param ConfigInterface $config
     * @param array $data
     */
    public function __construct(
        Context $context,
       \Magento\Checkout\Model\Session $checkoutSession,
	   \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
    }
	
	public function getOrderId()
	{
    	$orderId = $this->_checkoutSession->getLastRealOrderId();
		if(!$orderId)
		{
			$orderId = $this->_checkoutSession->getKnetOrder();
		}
    	return $orderId;
	}
	
	public function getSorder()
	{
		$this->orders = $this->_orderCollectionFactory->create()->getLastItem()->getIncrementId();
		return $this->orders;
	}
}
