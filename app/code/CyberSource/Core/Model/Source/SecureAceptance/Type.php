<?php
/**
 * Copyright © 2017 CyberSource. All rights reserved.
 * See accompanying License.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Model\Source\SecureAceptance;

/**
 * Class Type
 * @package CyberSource\Core\Model\Source\SecureAceptance
 * @codeCoverageIgnore
 */
class Type implements \Magento\Framework\Option\ArrayInterface
{

    const SA_WEB = 'web';
    const SA_SOP = 'silent';

    /**
     * Generate list of email templates
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
        [
            'value' => self::SA_WEB,
            'label' => __('Web / Mobile')
        ],
        [
            'value' => self::SA_SOP,
            'label' => __('Silent Order Post (SOP)')
        ]
        ];
    }
}
