<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Model\Order;

class Creditmemo extends \Magento\Sales\Model\Order\Creditmemo
{
    /**
     * @return bool
     */
    public function canRefund()
    {
        if ($this->getState() != self::STATE_CANCELED &&
            $this->getState() != self::STATE_REFUNDED &&
            (
                $this->getOrder()->getStatus() != 'payment_review' ||
                $this->getOrder()->getStatus() != 'fraud'
            ) && $this->getOrder()->getPayment()->canRefund()
        ) {
            return true;
        }
        return false;
    }

    public function getBaseGrandTotal()
    {
        if ($this->getTaxAmount() != null && $this->getTaxAmount() > 0) {
            return parent::getGrandTotal();
        }

        return parent::getBaseGrandTotal();
    }
}
