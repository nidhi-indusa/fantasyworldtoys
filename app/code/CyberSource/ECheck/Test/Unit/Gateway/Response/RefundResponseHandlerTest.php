<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use CyberSource\ECheck\Gateway\Response\ReasonCodeHandler;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;

class RefundResponseHandlerTest extends \PHPUnit_Framework_TestCase
{
    
    protected function setUp()
    {
        Bootstrap::create(BP, $_SERVER)->createApplication(Http::class);
        $this->orderRepoMock = $this
            ->getMockBuilder(\Magento\Sales\Model\OrderRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentDataObjectMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->response = $helper->getObject(
            \CyberSource\ECheck\Gateway\Response\RefundResponseHandler::class,
            [
                '_orderRepository' => $this->orderRepoMock,
            ]
        );
    }
    
    public function testHandle()
    {
        $this->paymentDataObjectMock
             ->method('getPayment')
             ->will($this->returnValue($this->paymentMock));
        $this->assertEquals(null, $this->response->handle(['payment' => $this->paymentDataObjectMock], ['response']));
        try {
            $this->response->handle([], []);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Payment data object should be provided', $e->getMessage());
        }
    }
}
