<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Test\Unit\Model\Cart;

class CartTotalRepositoryTest extends \PHPUnit_Framework_TestCase
{
    private $repository;
    private $helper;

    public function setUp()
    {
        $this->helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $quoteRepository = $this->getMockBuilder(\Magento\Quote\Api\CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quoteModel = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $addressMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quoteItem = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->getMock();

        $addressMock->expects($this->any())
            ->method('getData')
            ->willReturn(['test']);

        $addressMock->expects($this->any())
            ->method('getTotals')
            ->willReturn(1);

        $quoteModel->expects($this->any())
            ->method('isVirtual')
            ->willReturn(true);

        $quoteModel->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($addressMock);

        $quoteModel->expects($this->any())
            ->method('getAllVisibleItems')
            ->willReturn([$quoteItem]);

        $quoteModel->expects($this->any())
            ->method('getItemsQty')
            ->willReturn(1);

        $quoteModel->expects($this->any())
            ->method('getBaseCurrencyCode')
            ->willReturn("CAD");

        $quoteModel->expects($this->any())
            ->method('getQuoteCurrencyCode')
            ->willReturn("CAD");

        $quoteRepository->expects($this->any())
            ->method('getActive')
            ->with('1')
            ->willReturn($quoteModel);

        $totalsFactory = $this->getMockBuilder('\Magento\Quote\Api\Data\TotalsInterfaceFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $totalsInterface = $this->getMockBuilder('\Magento\Quote\Api\Data\TotalsInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $totalsInterface->expects($this->any())
            ->method('getGrandTotal')
            ->willReturn(10);

        $totalsInterface->expects($this->any())
            ->method('getTaxAmount')
            ->willReturn(5);

        $totalsInterface->expects($this->any())
            ->method('setGrandTotal')
            ->withAnyParameters()
            ->willReturnSelf();

        $totalsInterface->expects($this->any())
            ->method('setItems')
            ->withAnyParameters()
            ->willReturnSelf();

        $totalsInterface->expects($this->any())
            ->method('setItemsQty')
            ->withAnyParameters()
            ->willReturnSelf();

        $totalsInterface->expects($this->once())
            ->method('setBaseCurrencyCode')
            ->withAnyParameters()
            ->willReturnSelf();

        $totalsInterface->expects($this->any())
            ->method('getBaseCurrencyCode')
            ->willReturn("CAD");

        $totalsInterface->expects($this->once())
            ->method('setQuoteCurrencyCode')
            ->withAnyParameters()
            ->willReturnSelf();

        $totalsFactory->expects($this->any())
            ->method('create')
            ->willReturn($totalsInterface);

        $this->repository = $this->helper->getObject(
            'CyberSource\Core\Model\Cart\CartTotalRepository',
            [
                'quoteRepository' => $quoteRepository,
                'totalsFactory' => $totalsFactory
            ]
        );
    }

    public function testGet()
    {
        $totals = $this->repository->get('1');

        $this->assertEquals("CAD", $totals->getBaseCurrencyCode());
        $this->assertInstanceOf('\Magento\Quote\Api\Data\TotalsInterface', $totals);
    }
}
