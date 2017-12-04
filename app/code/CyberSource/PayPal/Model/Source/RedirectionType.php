<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\PayPal\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class RedirectionType
 * @package CyberSource\PayPal\Model\Source
 * @codeCoverageIgnore
 */
class RedirectionType implements ArrayInterface
{
    const TRADITIONAL = 'traditional';
    const IN_CONTEXT = 'in_context';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::TRADITIONAL,
                'label' => __('Traditional Express Checkout'),
            ],
            [
                'value' => self::IN_CONTEXT,
                'label' => __('In-Context Express Checkout')
            ]
        ];
    }
}
