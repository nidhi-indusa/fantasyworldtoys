<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Quickview\Plugin;

class BlockProductView
{
    /**
     *
     * @var  \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @param Magento\Framework\App\Request\Http $request
     */
    public function __construct(
            \Magento\Framework\App\Request\Http $request)
    {
        $this->request = $request;
    }

   /**
    *
    * @param \Magento\Catalog\Block\Product\View $subject
    * @param bool $result
    * @return bool
    */
    public function afterShouldRenderQuantity(
        \Magento\Catalog\Block\Product\View $subject, $result)
    {
        return $result;
    }

}
