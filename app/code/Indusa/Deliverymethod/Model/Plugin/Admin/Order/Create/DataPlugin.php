<?php

namespace Indusa\Deliverymethod\Model\Plugin\Admin\Order\Create;

 class DataPlugin extends \Magento\Backend\Block\Template
 {

    public function beforegetChildHtml($subject, $alias = '', $useCache = true){
     if($alias=='totals'){
        echo $this->getLayout()
        ->createBlock('\Magento\Backend\Block\Template')
        ->setTemplate('Indusa_Deliverymethod::sales/order/create/delivery_method.phtml')
        ->toHtml();
     }

    }
 }