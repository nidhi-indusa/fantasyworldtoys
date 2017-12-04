<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Gateway\Validator;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use CyberSource\ECheck\Gateway\Response\ReasonCodeHandler;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;

class ReportResponseValidatorTest extends \PHPUnit_Framework_TestCase
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
        $this->resultInterfaceMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Validator\ResultInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->validator = $helper->getObject(
            \CyberSource\ECheck\Gateway\Validator\ReportResponseValidator::class,
            [
                'resultInterfaceFactory' => $this->resultInterfaceMock,
            ]
        );
    }
    
    public function testValidate()
    {
        $response1 = [
            'Requests' => json_decode(json_encode([
                'Request' => ['PaymentData' => ['EventType' => 1]]
            ]))
        ];
        $response2 = [
            'Requests' => json_decode(json_encode([
                'Request' => ['PaymentData' => ['EventType' => 'TRANSMITTED']]
            ]))
        ];
        $this->assertEquals(null, $this->validator->validate(['response' => $response1]));
        $this->assertEquals(null, $this->validator->validate(['response' => $response2]));
        try {
            $this->validator->validate([]);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Response does not exist', $e->getMessage());
        }
    }
}
