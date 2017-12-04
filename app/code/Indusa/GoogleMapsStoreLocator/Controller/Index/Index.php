<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Indusa\GoogleMapsStoreLocator\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
    
    protected $resultForwardFactory;
        
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context);
    }

    public function execute()
    {
    
         $this->_view->loadLayout();

         $this->_view->renderLayout();
    }
}
