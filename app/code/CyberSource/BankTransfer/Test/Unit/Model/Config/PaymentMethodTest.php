<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Test\Unit\Model\Config;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class PlaceTest
 * @codingStandardsIgnoreStart
 */
class PaymentMethodTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;
    
    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethod;
    
    public function setUp()
    {
        $helper = new ObjectManager($this);
        $this->paymentMethod = $helper->getObject(
            \CyberSource\BankTransfer\Model\Config\PaymentMethod::class
        );
        
    }
    
    public function testGetConfig()
    {       
        $data = [
            [
                'value' => 'ideal',
                'label' => __('iDeal'),
            ],
            [
                'value' => 'sofort',
                'label' => __('Sofort')
            ],
            [
                'value' => 'bancontact',
                'label' => __('Bancontact')
            ]
        ];
        $this->assertEquals($data, $this->paymentMethod->toOptionArray());
    }
    
}