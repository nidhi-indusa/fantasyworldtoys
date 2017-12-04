<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Cron;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use CyberSource\ECheck\Gateway\Response\ReasonCodeHandler;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;

class StatusTest extends \PHPUnit_Framework_TestCase
{
    
    private $counter = 0;
    
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
        $this->loggerMock = $this
            ->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentCollectionFactoryMock = $this
            ->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->paymentCollectionMock = $this
            ->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\Payment\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->commandPoolMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Command\CommandPoolInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->selectMock = $this
            ->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transferBuilderMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Http\TransferBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transferMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Http\TransferInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->clientMock = $this
            ->getMockBuilder(\CyberSource\ECheck\Gateway\Http\Client\HTTPClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock = $this
            ->getMockBuilder(\CyberSource\ECheck\Gateway\Config\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transportBuilderMock = $this
            ->getMockBuilder(\Magento\Framework\Mail\Template\TransportBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transportMock = $this
            ->getMockBuilder(\Magento\Framework\Mail\TransportInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->cron = $helper->getObject(
            \CyberSource\ECheck\Cron\Status::class,
            [
                'logger' => $this->loggerMock,
                'paymentCollectionFactory' => $this->paymentCollectionFactoryMock,
                'commandPool' => $this->commandPoolMock,
                'transferBuilder' => $this->transferBuilderMock,
                'client' => $this->clientMock,
                'config' => $this->configMock,
                'transportBuilder' => $this->transportBuilderMock
            ]
        );
    }
    
    public function testExecute()
    {
        $this->transportBuilderMock
             ->method('setTemplateIdentifier')
             ->will($this->returnValue($this->transportBuilderMock));
        $this->transportBuilderMock
             ->method('setTemplateOptions')
             ->will($this->returnValue($this->transportBuilderMock));
        $this->transportBuilderMock
             ->method('setTemplateVars')
             ->will($this->returnValue($this->transportBuilderMock));
        $this->transportBuilderMock
             ->method('setFrom')
             ->will($this->returnValue($this->transportBuilderMock));
        $this->transportBuilderMock
             ->method('addTo')
             ->will($this->returnValue($this->transportBuilderMock));
        $this->transportBuilderMock
             ->method('setReplyTo')
             ->will($this->returnValue($this->transportBuilderMock));
        $this->transportBuilderMock
             ->method('getTransport')
             ->will($this->returnValue($this->transportMock));
        $this->paymentCollectionFactoryMock
             ->method('create')
             ->will($this->returnValue($this->paymentCollectionMock));
        $this->paymentCollectionMock
             ->method('getSelect')
             ->will($this->returnValue($this->selectMock));
        $this->paymentMock
             ->method('getOrder')
             ->will($this->returnValue($this->orderMock));
        $items = new \ArrayObject;
        $items->append($this->paymentMock);
        $this->paymentCollectionMock
            ->method('getIterator')
            ->will($this->returnValue($items));
        $this->transferBuilderMock
            ->method('setBody')
            ->will($this->returnValue($this->transferBuilderMock));
        $this->transferBuilderMock
            ->method('setMethod')
            ->will($this->returnValue($this->transferBuilderMock));
        $this->transferBuilderMock
            ->method('build')
            ->will($this->returnValue($this->transferMock));
        $this->configMock
            ->method('isTestMode')
            ->will($this->returnCallback(function () {
                return ($this->counter == 2);
            }));
        $this->configMock
            ->method('getAcceptEventType')
            ->will($this->returnCallback(function () {
                switch ($this->counter) {
                    case 3:
                        $data = ['double'];
                        break;
                    case 4:
                        $data = ['accept'];
                        break;
                    default:
                        $data = ['Confirm'];
                }
                return $data;
            }));
        $this->configMock
            ->method('getRejectEventType')
            ->will($this->returnCallback(function () {
                switch ($this->counter) {
                    case 3:
                        $data = ['double'];
                        break;
                    case 5:
                        $data = ['reject'];
                        break;
                    default:
                        $data = ['Confirm'];
                }
                return $data;
            }));
        $this->configMock
            ->method('getPendingEventType')
            ->will($this->returnCallback(function () {
                switch ($this->counter) {
                    case 3:
                        $data = ['double'];
                        break;
                    default:
                        $data = ['Confirm'];
                }
                return $data;
            }));
        $this->configMock
            ->method('getTestEventType')
            ->will($this->returnCallback(function () {
                $data = 'ok';
                return $data;
            }));
        $this->clientMock
            ->method('placeRequest')
            ->will($this->returnCallback(function () {
                switch ($this->counter) {
                    case 2:
                        $type = 'ok';
                        break;
                    case 3:
                        $type = 'double';
                        break;
                    case 4:
                        $type = 'accept';
                        break;
                    case 5:
                        $type = 'reject';
                        break;
                    default:
                        $type = 'Confirm';
                }
                $data = [
                'Requests' => json_decode(json_encode(
                    [
                            'Request' => [
                                'PaymentData' => [
                                    'EventType' => $type
                                ]
                            ]
                        ]
                ))
                ];
                return $data;
            }));
        $this->paymentCollectionMock
            ->method('load')
            ->will($this->returnCallback(function () {
                $this->counter++;
                if ($this->counter == 1) {
                    throw new \Exception(__('error'));
                }
            }));
        $this->assertEquals($this->cron, $this->cron->execute());
        $this->assertEquals($this->cron, $this->cron->execute());
        $this->assertEquals($this->cron, $this->cron->execute());
        $this->assertEquals($this->cron, $this->cron->execute());
        $this->assertEquals($this->cron, $this->cron->execute());
        $this->assertEquals($this->cron, $this->cron->execute());
    }
}
