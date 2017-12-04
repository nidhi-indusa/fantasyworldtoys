<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferInterface;
use CyberSource\ECheck\Gateway\Http\TransferFactory;
use Psr\Log\LoggerInterface;

class TransferFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $request = $this->buildRequest();

        $transferBuilder = $this->getMockBuilder(TransferBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transferObject = $this->getMock(TransferInterface::class);

        $transferBuilder->expects(static::once())
            ->method('setBody')
            ->with($request)
            ->willReturnSelf();
        $transferBuilder->expects(static::once())
            ->method('setMethod')
            ->with('POST')
            ->willReturnSelf();

        $transferBuilder->expects(static::once())
            ->method('build')
            ->willReturn($transferObject);

        $logger = $this->getMock(LoggerInterface::class);
        $transferFactory = new TransferFactory($transferBuilder, $logger);

        static::assertSame(
            $transferObject,
            $transferFactory->create($request)
        );
    }

    /**
     * @return array
     */
    private function buildRequest()
    {
        return [
            'merchantID' => 'chtest',
            'merchantReferenceCode' => '000000135',
            'ecDebitService' => [
                'run' => 'true',
            ],
            'billTo' =>
                [
                    'firstName' => 'FirstName',
                    'lastName' => 'LastName',
                    'street1' => '2741 Jade St',
                    'city' => 'Vancouver',
                    'state' => 'BC',
                    'postalCode' => 'V7V 1Y8',
                    'country' => 'CA',
                    'phoneNumber' => '1231231231',
                    'email' => 'test@collinsharper.com',
                ],
            'purchaseTotals' =>
                [
                    'currency' => 'USD',
                    'grandTotalAmount' => '41.00',
                ],
            'check' =>
                [
                    'accountNumber' => '4100',
                    'accountType' => 'C',
                    'bankTransitNumber' => '071923284',
                    'secCode' => 'WEB',
                ],
            'item_0_unitPrice' => 36,
        ];
    }
}
