<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class PaymentAction
 * @package CyberSource\ECheck\Model\Adminhtml\Source
 * @codeCoverageIgnore
 */
class EventType implements ArrayInterface
{

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'Payment',
                'label' => __('Payment'),
            ],
            [
                'value' => 'Refund',
                'label' => __('Refund'),
            ],
            [
                'value' => 'Completed',
                'label' => __('Completed'),
            ],
            [
                'value' => 'Correction',
                'label' => __('Correction'),
            ],
            [
                'value' => 'Declined',
                'label' => __('Declined'),
            ],
            [
                'value' => 'Error',
                'label' => __('Error'),
            ],
            [
                'value' => 'Failed',
                'label' => __('Failed'),
            ],
            [
                'value' => 'Final NSF',
                'label' => __('Final NSF'),
            ],
            [
                'value' => 'First NSF',
                'label' => __('First NSF'),
            ],
            [
                'value' => 'NSF',
                'label' => __('NSF'),
            ],
            [
                'value' => 'Second NSF',
                'label' => __('Second NSF'),
            ],
            [
                'value' => 'Stop Payment',
                'label' => __('Stop Payment'),
            ],
            [
                'value' => 'Void',
                'label' => __('Void'),
            ],
            //other event types
            [
                'value' => 'BATCH_ERROR',
                'label' => __('BATCH_ERROR'),
            ],
            [
                'value' => 'BATCH_RESET',
                'label' => __('BATCH_RESET'),
            ],
            [
                'value' => 'CANCELLED',
                'label' => __('CANCELLED'),
            ],
            [
                'value' => 'CANCELED_REVERS',
                'label' => __('CANCELED_REVERS'),
            ],
            [
                'value' => 'FAILED',
                'label' => __('FAILED'),
            ],
            [
                'value' => 'FUNDED',
                'label' => __('FUNDED'),
            ],
            [
                'value' => 'MIPS',
                'label' => __('MIPS'),
            ],
            [
                'value' => 'PAYMENT',
                'label' => __('PAYMENT'),
            ],
            [
                'value' => 'PENDING',
                'label' => __('PENDING'),
            ],
            [
                'value' => 'REFUNDED',
                'label' => __('REFUNDED'),
            ],
            [
                'value' => 'REVERSAL',
                'label' => __('REVERSAL'),
            ],
            [
                'value' => 'REVERSING',
                'label' => __('REVERSING'),
            ],
            [
                'value' => 'TRANSMITTED',
                'label' => __('TRANSMITTED'),
            ],
            [
                'value' => 'VOIDED',
                'label' => __('VOIDED'),
            ],
        ];
    }
}
