<?php
namespace Indusa\Deliverymethod\Block;

class Success extends \Magento\Checkout\Block\Onepage\Success {

    public function getOrder() {
        return $this->_checkoutSession->getLastRealOrder();
    }

}
