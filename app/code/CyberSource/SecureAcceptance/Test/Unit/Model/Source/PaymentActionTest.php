<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */
namespace CyberSource\SecureAcceptance\Test\Unit\Model\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class PaymentActionTest extends \PHPUnit_Framework_TestCase
{
    
    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;
    
    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProviderMock;
    
    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentAction;
    
    public function setUp()
    {
        $helper = new ObjectManager($this);
        $this->paymentAction = $helper->getObject(
            \CyberSource\SecureAcceptance\Model\Source\PaymentAction::class
        );
    }
    
    public function testToOptionArray()
    {
        
        $data = [
            [
                'value' => \CyberSource\SecureAcceptance\Model\Payment::ACTION_AUTHORIZE,
                'label' => __('Authorize Only'),
            ],
            [
                'value' => \CyberSource\SecureAcceptance\Model\Payment::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorize and Capture')
            ]
        ];
        
        $this->assertEquals($data, $this->paymentAction->toOptionArray());
    }
}
