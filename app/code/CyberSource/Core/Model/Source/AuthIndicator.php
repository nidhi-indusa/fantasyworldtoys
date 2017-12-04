<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class AuthIndicator
 * @package CyberSource\Core\Model\Source
 * @codeCoverageIgnore
 */
class AuthIndicator implements ArrayInterface
{

    const PRE_AUTHORIZATION = 0;
    const FINAL_AUTHORIZATION = 1;
    const UNDEFINED = 2;

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => (string) self::PRE_AUTHORIZATION,
                'label' => __('Preauthorization'),
            ],
            [
                'value' => (string) self::FINAL_AUTHORIZATION,
                'label' => __('Final authorization')
            ],
            [
                'value' => (string) self::UNDEFINED,
                'label' => __('Undefined')
            ]
        ];
    }
}
