<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use CyberSource\ECheck\Gateway\Response\ReasonCodeHandler;

class ReasonCodeHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testHandle()
    {
        $response = [
            ReasonCodeHandler::REQUEST_ID => "123",
            ReasonCodeHandler::MERCHANT_REFERENCE_CODE => "0000123",
            ReasonCodeHandler::DECISION => "APPROVED",
            ReasonCodeHandler::REASON_CODE => "100",
            ReasonCodeHandler::REQUEST_TOKEN => "123xyz",
            ReasonCodeHandler::REQUEST_ID => "321"
        ];

        $paymentDO = $this->getMock(PaymentDataObjectInterface::class);
        $paymentModel = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentDO->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentModel);

        $paymentModel->expects(static::once())
            ->method('setTransactionId')
            ->with($response[ReasonCodeHandler::REQUEST_ID]);

        $paymentModel->expects($this->exactly(5))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                [
                    ReasonCodeHandler::MERCHANT_REFERENCE_CODE,
                    $response[ReasonCodeHandler::MERCHANT_REFERENCE_CODE]
                ],
                [
                    ReasonCodeHandler::DECISION,
                    $response[ReasonCodeHandler::DECISION]
                ],
                [
                    ReasonCodeHandler::REASON_CODE,
                    $response[ReasonCodeHandler::REASON_CODE]
                ],
                [
                    ReasonCodeHandler::REQUEST_TOKEN,
                    $response[ReasonCodeHandler::REQUEST_TOKEN]
                ],
                [
                    ReasonCodeHandler::REQUEST_ID,
                    $response[ReasonCodeHandler::REQUEST_ID]
                ]
            );

        $paymentModel->expects(static::once())
            ->method('setShouldCloseParentTransaction')
            ->with(false);
        $paymentModel->expects(static::once())
            ->method('setIsTransactionClosed')
            ->with(false);

        $request = new ReasonCodeHandler();
        $request->handle(['payment' => $paymentDO], $response);
        try {
            $request->handle([], $response);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Payment data object should be provided', $e->getMessage());
        }
    }
}
