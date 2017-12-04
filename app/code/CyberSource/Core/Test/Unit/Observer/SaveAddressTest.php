<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Test\Unit\Observer;

/**
 * Class SaveAddressTest
 * @codingStandardsIgnoreStart
 */
class SaveAddressTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Cybersource\Core\Observer\SaveAddress
     */
    protected $_model;

    /**
     * @var \Magento\Sales\Model\Order\Address
     */
    protected $address;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_observerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_eventMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_customerSessionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_addressDataFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_addressRepositoryInterfaceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_loggerMock;

    protected function setUp()
    {
        $this->_customerSessionMock = $this->getMock('Magento\Customer\Model\Session', ['getId', 'setData'], [], '', false);
        $this->_addressDataFactoryMock = $this->getMock('Magento\Customer\Api\Data\AddressInterfaceFactory', ['create'], [], '', false);
        $this->_addressRepositoryInterfaceMock = $this->getMock('Magento\Customer\Api\AddressRepositoryInterface', [], [], '', false);
        $this->_loggerMock = $this->getMock('Psr\Log\LoggerInterface', [], [], '', false);

        $this->_observerMock = $this->getMock('Magento\Framework\Event\Observer', [], [], '', false);
        $this->_eventMock = $this->getMock(
            'Magento\Framework\Event',
            ['getData'],
            [],
            '',
            false
        );

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->address = $objectManager->getObject(
            '\Magento\Customer\Model\Data\Address',
            []
        );

        $this->_observerMock->expects($this->any())->method('getEvent')->will($this->returnValue($this->_eventMock));
        $this->_model = new \CyberSource\Core\Observer\SaveAddress(
            $this->_customerSessionMock,
            $this->_addressDataFactoryMock,
            $this->_addressRepositoryInterfaceMock,
            $this->_loggerMock
        );
    }

    public function testSaveAddress()
    {
        $addressMockData = [
            'firstname' => 'firstname',
            'middlename' => 'middlename',
            'lastname' => 'lastname'
        ];

        $this->address->setId(1);

        $this->_observerMock->expects($this->once())->method('getData')->with('addresses')->willReturn($addressMockData);
        $this->_addressDataFactoryMock->expects($this->once())->method('create')->willReturn($this->address);
        $this->_addressRepositoryInterfaceMock->expects($this->once())->method('save')->willReturn($this->address);
        $this->_customerSessionMock->expects($this->once())->method('getId')->willReturn($this->returnValue(1));
        $this->_customerSessionMock->expects($this->once())->method('setData')->withAnyParameters()->willReturnSelf();
        $this->_model->execute($this->_observerMock);
    }

    public function testSaveAddressFail()
    {
        $addressMockData = [
            'firstname' => 'firstname',
            'middlename' => 'middlename',
            'lastname' => 'lastname'
        ];

        $this->_observerMock->expects($this->once())->method('getData')->with('addresses')->willReturn($addressMockData);
        $this->_addressDataFactoryMock->expects($this->once())->method('create')->willReturn($this->address);
        $this->_addressRepositoryInterfaceMock->expects($this->once())->method('save')->willThrowException(new \Exception());

        $this->_loggerMock->expects($this->once())->method('error');

        $this->_model->execute($this->_observerMock);
    }
}
