<?php

namespace Indusa\Deliverymethod\Block;

class Comment extends \Magento\Framework\View\Element\Template {
    
     /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            array $data = [],
            \Magento\Framework\Registry $registry
            
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }
    
    // ----Your Block Method----
    
     public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }
    
     /**
     * @return void
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Order # %1', $this->getOrder()->getRealOrderId()));
        //$infoBlock = $this->_paymentHelper->getInfoBlock($this->getOrder()->getPayment(), $this->getLayout());
        //$this->setChild('payment_info', $infoBlock);
    }
}
