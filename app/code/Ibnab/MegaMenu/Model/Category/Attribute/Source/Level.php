<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Ibnab\MegaMenu\Model\Category\Attribute\Source;

/**
 * Catalog product landing page attribute source
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Level extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{

    /**
     * @return array
     */
    public function getAllOptions()
    {

        $option[] = ['value' => '1column', 'label' => __('1 column')];
        $option[] = ['value' => '2column', 'label' => __('2 column')];
        $option[] = ['value' => '3column', 'label' => __('3 column')];

        return $option;
    }
}
