<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);
        $paymentMethod = $this->readMethodArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $additionalData = new DataObject($additionalData);
        $paymentInfo = $paymentMethod->getInfoInstance();

        if (!$paymentInfo instanceof InfoInterface) {
            throw new LocalizedException(__('Payment model does not provided.'));
        }

        $paymentInfo->setAdditionalInformation('check_bank_transit_number', $additionalData->getDataByKey('check_bank_transit_number'));
        $paymentInfo->setAdditionalInformation('check_account_number', $additionalData->getDataByKey('check_account_number'));
    }
}
