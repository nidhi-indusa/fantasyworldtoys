<?php
/**
 * Copyright © 2017 Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
namespace Mageside\SaleCategory\Helper;

use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\CatalogInventory\Model\ResourceModel\Stock\StatusFactory;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Status;
use Magento\Catalog\Model\Product;
use Mageside\SaleCategory\Helper\Config as SaleHelper;

/**
 * Class Stock
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Stock extends \Magento\CatalogInventory\Helper\Stock
{
    /**
     * @var \Mageside\SaleCategory\Helper\Config
     */
    protected $_configHelper;
    
    /**
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param StatusFactory $stockStatusFactory
     * @param StockRegistryProviderInterface $stockRegistryProvider
     * @param SaleHelper $configHelper
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        StatusFactory $stockStatusFactory,
        StockRegistryProviderInterface $stockRegistryProvider,
        SaleHelper $configHelper
    ) {
        parent::__construct(
            $storeManager,
            $scopeConfig,
            $stockStatusFactory,
            $stockRegistryProvider
        );
        
        $this->_configHelper = $configHelper;
    }

    /**
     * Add only is in stock products filter to product collection
     * Modified method for choosing settings from store or module if current category is "sale category"
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return void
     */
    public function addIsInStockFilterToCollection($collection)
    {
        $stockFlag = 'has_stock_status_filter';
        if (!$collection->hasFlag($stockFlag)) {
            if ($this->_configHelper->isSaleCategory()) {
                $isShowOutOfStock = $this->_configHelper->getConfigModule('show_out_of_stock');
            } else {
                $isShowOutOfStock = $this->scopeConfig->getValue(
                    \Magento\CatalogInventory\Model\Configuration::XML_PATH_SHOW_OUT_OF_STOCK,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
            }
            $resource = $this->getStockStatusResource();
            $resource->addStockDataToCollection($collection, !$isShowOutOfStock);
            $collection->setFlag($stockFlag, true);
        }
    }
}
