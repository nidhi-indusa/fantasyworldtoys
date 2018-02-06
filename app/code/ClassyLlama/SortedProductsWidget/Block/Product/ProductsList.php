<?php
	namespace ClassyLlama\SortedProductsWidget\Block\Product;
	
	class ProductsList extends \Magento\CatalogWidget\Block\Product\ProductsList
	{
		
		const DEFAULT_SORT_BY = 'id';
		
		const DEFAULT_SORT_ORDER = 'asc';
		public function createCollection()
		{
			$productCollection = $this->productCollectionFactory->create();
			
			$productCollection
			->setVisibility($this->catalogProductVisibility->getVisibleInCatalogIds())
			->addMinimalPrice()
			->addFinalPrice()
			->addTaxPercents()
			->addUrlRewrite()
			->addStoreFilter()
			->addAttributeToSort(
			'minimal_price',
			'asc'
			);
			
			$productCollection->getSelect()->where(
			'price_index.final_price < price_index.price'
			);
			
			return $productCollection;
		}
		public function getSortBy()
		{
			if (!$this->hasData('products_sort_by')) {
				$this->setData('products_sort_by', self::DEFAULT_SORT_BY);
			}
			return $this->getData('products_sort_by');
		}
		
		public function getSortOrder()
		{
			if (!$this->hasData('products_sort_order')) {
				$this->setData('products_sort_order', self::DEFAULT_SORT_ORDER);
			}
			return $this->getData('products_sort_order');
		}
	}		