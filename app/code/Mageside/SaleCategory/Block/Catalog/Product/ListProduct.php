<?php
/**
 * Copyright © 2017 Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\SaleCategory\Block\Catalog\Product;

class ListProduct extends \Magento\Catalog\Block\Product\ListProduct
{
    /**
     * Product collection factory
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var \Mageside\SaleCategory\Helper\Config
     */
    protected $_configHelper;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Mageside\SaleCategory\Helper\Config $configHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Mageside\SaleCategory\Helper\Config $configHelper,
        array $data = []
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_configHelper = $configHelper;
        parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $data
        );
    }

    protected function _getProductCollection()
    {
        if ($this->_configHelper->isSaleCategory()) {
            return $this->_getSaleCategoryCollection();
        }
        return parent::_getProductCollection();
    }

    protected function _beforeToHtml()
    {
        if ($this->_configHelper->isSaleCategory()) {
            $this->setSortBy('name');
        }
        return parent::_beforeToHtml();
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function _getSaleCategoryCollection()
    {
        if ($this->_productCollection === null) {
            /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
            $collection = $this->_productCollectionFactory->create();
            $this->_catalogLayer->prepareProductCollection($collection);

            $todayStart = $this->_localeDate->date()->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            $todayEnd = $this->_localeDate->date()->setTime(23, 59, 59)->format('Y-m-d H:i:s');

            $collection
                ->addStoreFilter()
                ->addAttributeToFilter(
                    'special_from_date',
                    [
                        'or' => [
                            ['date' => true, 'to' => $todayEnd],
                            ['is' => new \Zend_Db_Expr('null')]
                        ]
                    ],
                    'left'
                )->addAttributeToFilter(
                    'special_to_date',
                    [
                        'or' => [
                            ['date' => true, 'from' => $todayStart],
                            ['is' => new \Zend_Db_Expr('null')]
                        ]
                    ],
                    'left'
                )->addAttributeToFilter(
                    [
                        ['attribute' => 'special_price', 'is' => new \Zend_Db_Expr('not null')]
                    ]
                );

            if ($this->_configHelper->getConfigModule('show_with_images')) {
                $collection->addAttributeToFilter(
                    'small_image',
                    [
                        'or' => [
                            ['neq' => 'no_selection'],
                            ['is' => new \Zend_Db_Expr('null')]
                        ]
                    ]
                );
            }

            $collection->getSelect()
                ->where('price_index.final_price < price_index.price');

            $this->_productCollection = $collection;
        }

        return $this->_productCollection;
    }
}
