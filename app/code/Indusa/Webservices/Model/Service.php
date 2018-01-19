<?php
	namespace Indusa\Webservices\Model;	
	use Magento\Catalog\Api\CategoryRepositoryInterface;
	
	class Service
	{
		protected $_storeManager;
		protected $categoryRepository;
		
		const MANAGE_PRODUCTS = 'manageProducts';
		
		const INVENTORY_UPDATES = 'inventoryUpdates';
		
		const PRICE_UPDATES = 'priceUpdates';
		
		const RELATED_PRODUCTS = 'relatedProducts';
		
		const CREATE_ORDER_AND_CUSTOMER = 'createOrderAndCustomer';
		
		const ORDER_STATUS_UPDATES = 'orderStatusUpdates';
		
		const RESERVED_INVENOTRY_UPDATES = 'reservedInventoryUpdates';
		
		const DEFAULT_CATEGORY = 'Default Category';
		
		const SEND_ACKNOWLEDGEMENT_TO_MAGENTO = 'sendAcknowledgementToMagento';
		
		const SEND_ACKNOWLEDGEMENT_TO_AX = 'sendAcknowledgementToAx';
		
		const PRODUCT_STATUS_UPDATES = 'productStatusUpdates';
		
		const RESERVED_INVENTORY_UPDATES = 'reservedInventoryUpdates';		
		
		const WAREHOUSE_ID = '999';
		
		const INVOICED = 'Invoiced';
		
		const DELIVERED = 'Delivered';
		
		/**
			* Initialize resource model
			*
			* @return void
		*/
		
		protected function _construct(\Magento\Store\Model\StoreManagerInterface $storeManager, CategoryRepositoryInterface $categoryRepository
		)
		{
			$this->_storeManager = $storeManager;
			$this->categoryRepository = $categoryRepository;			
		}
		
		/**
			* @param array of category codes $categoryIds
			* 
			* @return string of category
		*/
		public function getCategory($categoryIds = null)
		{
			if(!$categoryIds) return;
			$_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$categoryIdArr = $categoryIds;
			if(!is_array($categoryIds)) { $categoryIdArr = explode(",",$categoryIdArr); }
			
			$catTxt = Service::DEFAULT_CATEGORY;
			$newTxt = '';
			
			$categoryInfo = array();
			
			$categorycollection = $_objectManager->get('Magento\Catalog\Model\CategoryFactory')->create()->getCollection()
			->addFieldToFilter('ax_category_code', ['in' => $categoryIds]);
			
			$categories = array();
			
			foreach($categorycollection as $_category)
			{  
				$newTxt = $catTxt;
				$currentCat = $_objectManager->create('Magento\Catalog\Model\Category')->load($_category->getId());
				foreach ($currentCat->getParentCategories() as $parent) {
					if($parent->getId() != 2)
					{   
						$categoryInfo[$currentCat->getName()][$parent->getName()] = $parent->getId();
						$newTxt .= "/".$parent->getName();
					}
				}
				$categories[$currentCat->getName()] = $newTxt;
			}
			
			if($categories)
			{
				$category = implode(",",$categories);
				return $category; 
			}        
			return Service::DEFAULT_CATEGORY;
		}
		
		public function reIndexing(){
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$indexerCollectionFactory = $objectManager->get("\Magento\Indexer\Model\Indexer\CollectionFactory");
			$indexerFactory = $objectManager->get("\Magento\Indexer\Model\IndexerFactory");
			$indexerCollection = $indexerCollectionFactory->create();
			$allIds = $indexerCollection->getAllIds();
			
			foreach ($allIds as $id) {
				$indexer = $indexerFactory->create()->load($id);				
				$indexer->reindexAll(); // this reindexes all
			}
		}
	}
?>