<?php
/**
 * Copyright © 2017 Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\SaleCategory\Model\Source;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\DB\Helper as DbHelper;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Backend\Model\Session;

/**
 * Class Categories
 */
class Categories implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var array
     */
    protected $_categoriesTrees = [];

    /**
     * @var CategoryCollectionFactory
     */
    protected $_categoryCollectionFactory;

    /**
     * @var DbHelper
     */
    protected $_dbHelper;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param DbHelper $dbHelper
     * @param Session $session
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        DbHelper $dbHelper,
        Session $session
    ) {
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_dbHelper = $dbHelper;
        $this->_session = $session;
    }

    /**
     * @return array|null
     */
    public function toOptionArray()
    {
        return $this->getOptionsWithoutOptgroup($this->getCategoriesTree());
    }

    protected function getOptionsWithoutOptgroup($optGroupOptions, $parentCategoryLabel = null)
    {
        $options = [];
        foreach ($optGroupOptions as $option) {
            $currentLabel = $parentCategoryLabel ? $parentCategoryLabel . ' / ' . $option['label'] : $option['label'];
            if ($option['level'] > 1) {
                $options[] = [
                    'value' => $option['value'],
                    'label' => $currentLabel
                ];
            }
            if (isset($option['optgroup']) && !empty($option['optgroup'])) {
                $options = array_merge(
                    $options,
                    $this->getOptionsWithoutOptgroup($option['optgroup'], $currentLabel)
                );
            }
        }

        return $options;
    }

    /**
     * Retrieve categories tree
     *
     * @param string|null $filter
     * @return array
     */
    protected function getCategoriesTree($filter = null)
    {
        if (isset($this->_categoriesTrees[$filter])) {
            return $this->_categoriesTrees[$filter];
        }

        $storeId = $this->_session->getStoreId() ? $this->_session->getStoreId() : 0;

        /** @var $categoryCollection \Magento\Catalog\Model\ResourceModel\Category\Collection */
        $categoryCollection = $this->_categoryCollectionFactory->create();

        if ($filter !== null) {
            $categoryCollection->addAttributeToFilter(
                'name',
                ['like' => $this->_dbHelper->addLikeEscape($filter, ['position' => 'any'])]
            );
        }

        $categoryCollection->addAttributeToSelect('path')
            ->addAttributeToFilter('entity_id', ['neq' => CategoryModel::TREE_ROOT_ID])
            ->setStoreId($storeId);

        $shownCategoriesIds = [];

        /** @var \Magento\Catalog\Model\Category $category */
        foreach ($categoryCollection as $category) {
            foreach (explode('/', $category->getPath()) as $parentId) {
                $shownCategoriesIds[$parentId] = 1;
            }
        }

        /** @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
        $collection = $this->_categoryCollectionFactory->create();

        $collection->addAttributeToFilter('entity_id', ['in' => array_keys($shownCategoriesIds)])
            ->addAttributeToSelect(['name', 'is_active', 'parent_id'])
            ->setStoreId($storeId);

        $categoryById = [
            CategoryModel::TREE_ROOT_ID => [
                'value' => CategoryModel::TREE_ROOT_ID,
                'optgroup' => null,
            ],
        ];

        foreach ($collection as $category) {
            foreach ([$category->getId(), $category->getParentId()] as $categoryId) {
                if (!isset($categoryById[$categoryId])) {
                    $categoryById[$categoryId] = ['value' => $categoryId];
                }
            }

            $categoryById[$category->getId()]['is_active'] = $category->getIsActive();
            $categoryById[$category->getId()]['level'] = $category->getLevel();
            $categoryById[$category->getId()]['label'] = $category->getName();
            $categoryById[$category->getParentId()]['optgroup'][] = &$categoryById[$category->getId()];
        }

        $this->_categoriesTrees[$filter] = $categoryById[CategoryModel::TREE_ROOT_ID]['optgroup'];

        return $this->_categoriesTrees[$filter];
    }
}