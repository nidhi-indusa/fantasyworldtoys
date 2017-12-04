<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use CyberSource\ECheck\Observer\DataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignObserverTest extends \PHPUnit_Framework_TestCase
{
    private $counter = 0;
    public function testExecute()
    {
        $observerContainer = $this->getMockBuilder(Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMethodFacade = $this->getMock(MethodInterface::class);
        $this->paymentInfoModel = $this->getMock(InfoInterface::class);
        $this->dataObject = new DataObject(
            [
                PaymentInterface::KEY_ADDITIONAL_DATA =>
                [
                    'check_bank_transit_number' => '071923284',
                    'check_account_number' => '4100'
                ]
            ]
        );
        $this->dataObjectEmpty = new DataObject(
            [
                PaymentInterface::KEY_ADDITIONAL_DATA => 'test'
            ]
        );

        $observerContainer->expects(static::atLeastOnce())
            ->method('getEvent')
            ->willReturn($event);
        $event
            ->method('getDataByKey')
            ->will($this->returnCallback(function ($key) {
                $this->counter++;
                $data = [
                    AbstractDataAssignObserver::METHOD_CODE => $this->paymentMethodFacade,
                    AbstractDataAssignObserver::DATA_CODE => $this->dataObject
                ];
                return ($this->counter == 3 && $key == 'data') ? $this->dataObjectEmpty : $data[$key];
            }));
        
        $this->paymentMethodFacade
            ->method('getInfoInstance')
            ->will($this->returnCallback(function () {
                return ($this->counter == 6) ? null : $this->paymentInfoModel;
            }));

        $this->paymentInfoModel->expects($this->at(0))
            ->method('setAdditionalInformation')
            ->with(
                'check_bank_transit_number',
                '071923284'
            );

        $this->paymentInfoModel->expects($this->at(1))
            ->method('setAdditionalInformation')
            ->with(
                'check_account_number',
                '4100'
            );

        $observer = new DataAssignObserver();
        $this->assertEquals(null, $observer->execute($observerContainer));
        $this->assertEquals(null, $observer->execute($observerContainer));
        try {
            $observer->execute($observerContainer);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->assertEquals('Payment model does not provided.', $e->getMessage());
        }
    }
}
