<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Jframeworks\Addressvalidator\Block\Checkout;


/**
 * Multishipping checkout shipping
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Shipping extends \Magento\Framework\View\Element\Template
{
	
	protected $_customerSession;
    /**
     * @var \Magento\Framework\Filter\DataObject\GridFactory
     */
   
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Filter\DataObject\GridFactory $filterGridFactory
     * @param \Magento\Multishipping\Model\Checkout\Type\Multishipping $multishipping
     * @param \Magento\Tax\Helper\Data $taxHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		\Magento\Customer\Model\Session $customerSession,
		array $data = []
	){
		$this->_customerSession = $customerSession;
		parent::__construct($context, $data);
	}
	
	public function getJframeworksResult() 
    {
        return $this->_customerSession->getJframeworksResult();
    }


    /**
     * @return Address[]
     */
    /* public function getResult()
    {
		$result = "custom result";
        return $result;
    } */
    
}
