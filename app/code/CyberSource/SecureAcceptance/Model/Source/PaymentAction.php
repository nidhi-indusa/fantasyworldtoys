<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class PaymentAction
 * @package CyberSource\SecureAcceptance\Model\Source
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
                'value' => \CyberSource\SecureAcceptance\Model\Payment::ACTION_AUTHORIZE,
                'label' => __('Authorize Only'),
            ],
            [
                'value' => \CyberSource\SecureAcceptance\Model\Payment::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorize and Capture')
            ]
        ];
    }
}
