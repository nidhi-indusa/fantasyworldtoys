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

class ReportRequest extends AbstractRequest
{
    const TRANSACTION_TYPE = 'transaction';
    const TRANSACTION_SUBTYPE = 'transactionDetail';
    const TRANSACTION_VERSION_NUMBER = '1.5';

    /**
     * Builds request
     *
     * @param OrderPaymentInterface $payment
     * @return array
     */
    public function build()
    {
//        $request['merchantID'] = $this->config->getMerchantId();
//        $request['type'] = self::TRANSACTION_TYPE;
//        $request['subtype'] = self::TRANSACTION_SUBTYPE;
//        $request['versionNumber'] = self::TRANSACTION_VERSION_NUMBER;
//        $request['requestID'] = $payment->getLastTransId();
        $request['merchantID'] = $this->config->getMerchantId();
        $request['report_name'] = 'PaymentEventsReport';
        $request['report_date'] = '2017-05-03';
        $request['versionNumber'] = self::TRANSACTION_VERSION_NUMBER;
        return $request;
    }
}
