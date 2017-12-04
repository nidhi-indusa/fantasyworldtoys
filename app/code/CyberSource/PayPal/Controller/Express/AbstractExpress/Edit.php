<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace CyberSource\PayPal\Controller\Express\AbstractExpress;

class Edit extends \CyberSource\PayPal\Controller\Express\AbstractExpress
{
    /**
     * Dispatch customer back to PayPal for editing payment information
     *
     * @return void
     */
    public function execute()
    {
        try {
            $this->getResponse()->setRedirect($this->gatewayConfig->getExpressCheckoutEditUrl($this->_initToken()));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                $e->getMessage()
            );
            $this->_redirect('*/*/review');
        }
    }
}
