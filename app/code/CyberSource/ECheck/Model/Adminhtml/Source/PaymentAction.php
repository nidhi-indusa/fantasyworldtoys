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
class PaymentAction implements ArrayInterface
{
    const ACTION_AUTHORIZE = 'authorize';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::ACTION_AUTHORIZE,
                'label' => __('Authorize Only'),
            ]
        ];
    }
}
