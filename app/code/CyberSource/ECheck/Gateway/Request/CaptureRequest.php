<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Gateway\Request;

use CyberSource\ECheck\Gateway\Config\Config;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class CaptureRequest extends AbstractRequest implements BuilderInterface
{
    const TRANSACTION_TYPE = 'transaction';
    const TRANSACTION_SUBTYPE = 'transactionDetail';
    const TRANSACTION_VERSION_NUMBER = '1.5';

    /**
     * Builds request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof OrderPaymentInterface
        ) {
            throw new \InvalidArgumentException('OrderPaymentInterface should be provided');
        }

        $payment = $buildSubject['payment'];

        $request['merchantID'] = $this->config->getMerchantId();
        $request['type'] = self::TRANSACTION_TYPE;
        $request['subtype'] = self::TRANSACTION_SUBTYPE;
        $request['versionNumber'] = self::TRANSACTION_VERSION_NUMBER;
        $request['requestID'] = $payment->getLastTransId();

        return $request;
    }
}
