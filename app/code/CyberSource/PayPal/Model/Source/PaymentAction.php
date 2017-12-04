<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\PayPal\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class PaymentAction
 * @package CyberSource\PayPal\Model\Source
 * @codeCoverageIgnore
 */
class PaymentAction implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => \CyberSource\PayPal\Model\Payment::ACTION_AUTHORIZE,
                'label' => __('Authorize Only'),
            ],
            [
                'value' => \CyberSource\PayPal\Model\Payment::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorize and Capture')
            ]
        ];
    }
}
