<?php
	
	namespace Indusa\Webservices\Cron;
	use Indusa\Webservices\Model\Service;
	use Indusa\Webservices\Logger\Logger;
	
	class ProcessDiscounts {
		
		protected $logger;
		protected $_requestQueueFactory;
		protected $_productFactory;
		protected $_service;
		
		public function __construct(
		\Indusa\Webservices\Logger\Logger $loggerInterface, \Indusa\Webservices\Model\RequestQueueFactory $requestQueueFactory , \Magento\Catalog\Model\ProductFactory $productFactory, \Indusa\Webservices\Model\Service $service		
		) {
			$this->_requestQueueFactory = $requestQueueFactory;
			$this->logger = $loggerInterface;			
			$this->_productFactory = $productFactory;
			$this->_service = $service;
		}
		
		/**
			* indusa_webservices_cron_group Cron execution
			*
		*/
		public function execute() {
			
			//Fetching all the request with "processed" value is '0'
			$resultFactory = $this->_requestQueueFactory->create()->getCollection()->addFieldToFilter('processed', array('eq' => 0))->addFieldToFilter('request_type', array('eq' =>'manageDiscounts'))->setOrder('id', 'ASC');
			
			$requestData = $resultFactory->getData();		
			
			$this->logger->info(json_encode($requestData));
			
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$processmodel = $objectManager->create('\Indusa\Webservices\Model\Acknowledgment\SendAcknowledgment');	
			
			foreach ($requestData as $_request) {
				
				$finalErrorList = array();	
				$finalProcessList = array();				
				
				$process = array();
				$requestXML = $_request['request_xml'];
				$xmlProducts = array();
				$requestDataArray = $this->convertFormatToArray($requestXML, 'xml');			
				
				if(array_key_exists(0,$requestDataArray['discounts']['discount']))
				{
					$xmlProducts = $requestDataArray['discounts']['discount'];
				}
				else
				{
					$xmlProducts[] = $requestDataArray['discounts']['discount'];
				}	
				foreach($xmlProducts as $_productData){					
					
					$errorList = array();
					$processList = array();
					
					$discountData = array();
					$lineData = array();
					$discountData['startDate'] = $_productData['validFrom'];
					$discountData['endDate'] = $_productData['validTo'];
					$sDate = str_replace('/', '-', $discountData['startDate']);
					$startDate = date('Y-m-d', strtotime($sDate));
					$eDate = str_replace('/', '-', $discountData['endDate']);
					$endDate = date('Y-m-d', strtotime($eDate));
					
					
					if(array_key_exists(0,$_productData['lines']['line']))
					{
						$lineData = $_productData['lines']['line'];
					}
					else
					{
						$lineData[] = $_productData['lines']['line'];
					}	
					
					foreach($lineData as $_productDiscountData){
					//$pricePercent = "";
						$pricePercent = $_productDiscountData['percentage'];
						$Sku = $_productDiscountData['axProductId'];
						$categoryCode = $_productDiscountData['categoryCode'];
						if(!empty($Sku)){
							
							$this->logger->info('inside product');
							$productFactory = $this->_productFactory->create();				
							$_product = $productFactory->load($productFactory->getIdBySku($Sku));
							
							
							$productType = $_product->getTypeId();
							if($productType == 'configurable'){
								$_children = $_product->getTypeInstance()->getUsedProducts($_product);
								
								//Updating Configurable - Simple Product Prices	
								foreach ($_children as $child){
									
									$productId = $child->getId();
									$productFactory = $objectManager->get('\Magento\Catalog\Model\ProductFactory');
									$product = $productFactory->create()->load($productId);
									
									$productPrice = $product->getPrice();	
									$productSpecialPrice = $productPrice -($productPrice * $pricePercent)/100;
									
									$product->setSpecialPrice($productSpecialPrice);	
									$product->setSpecialFromDate($startDate);
									//$product->setSpecialFromDateIsFormated(true);
									
									// Sets the End Date
									$product->setSpecialToDate($endDate);
									//$product->setSpecialToDateIsFormated(true);
									
									$product->save();	
								}
								$processList['axProductID'] = $Sku;							
								$finalProcessList[] = $processList;
							}
							else if($productType == 'simple')
							{							
								//Updating Simple Product Prices	
								$productId=$_product->getId();
								$productFactory = $objectManager->get('\Magento\Catalog\Model\ProductFactory');
								$product = $productFactory->create()->load($productId);
								$productPrice = $product->getPrice();	
								$productSpecialPrice = $productPrice -($productPrice * $pricePercent)/100;
								$product->setSpecialPrice($productSpecialPrice);
								$product->setSpecialFromDate($startDate);
								//$product->setSpecialFromDateIsFormated(true);
								
								// Sets the End Date
								$product->setSpecialToDate($endDate);
								//$product->setSpecialToDateIsFormated(true);
								
								$product->save();
								
								
								$processList['axProductID'] = $Sku;	
								$finalProcessList[] = $processList;							
							}
							else{							
								$errorList['axProductID'] = $Sku;	
								$errorList['message'] ="Requested SKU doesn't exist";
								$finalErrorList[] = $errorList;
							}
						}
						
						if(!empty($categoryCode)){
							$this->logger->info('inside cat'.$categoryCode);
							
							
							$categorycollection = $objectManager->get('Magento\Catalog\Model\CategoryFactory')->create()->getCollection()
							->addFieldToFilter('ax_category_code', array('in' => $categoryCode));
							
							$categoryId = "";
							
							foreach($categorycollection as $_category)
							{
								
								$categoryId = $_category->getId();
								$this->logger->info($_category->getId());
								
							}
							if($categoryId !="" ){
								//$catId = $categorycollection->getId();
								$categoryFactory = $objectManager->get('\Magento\Catalog\Model\CategoryFactory');
								$category = $categoryFactory->create()->load($categoryId);
								$categoryProducts = $category->getProductCollection()
								->addAttributeToSelect('*');
								foreach ($categoryProducts as $product) {
									$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
									$productStockObj = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem($product->getId());
									$productIds[] = $productStockObj->getProductId();
									foreach ($productIds as $Id) {
										
										$productFactory = $this->_productFactory->create();				
										$_product = $productFactory->load($Id);
										
										
										$productType = $_product->getTypeId();
										if($productType == 'configurable'){
											$_children = $_product->getTypeInstance()->getUsedProducts($_product);
											
											//Updating Configurable - Simple Product Prices	
											foreach ($_children as $child){
												
												$productId = $child->getId();
												$productFactory = $objectManager->get('\Magento\Catalog\Model\ProductFactory');
												$product = $productFactory->create()->load($productId);
												
												$productPrice = $product->getPrice();	
												$productSpecialPrice = $productPrice -($productPrice * $pricePercent)/100;
												
												$product->setSpecialPrice($productSpecialPrice);	
												$product->setSpecialFromDate($startDate);
												//$product->setSpecialFromDateIsFormated(true);
												
												// Sets the End Date
												$product->setSpecialToDate($endDate);
												//$product->setSpecialToDateIsFormated(true);
												
												$product->save();	
											}
											$processList['axProductID'] = $Sku;							
											$finalProcessList[] = $processList;
										}
										else if($productType == 'simple')
										{							
											//Updating Simple Product Prices	
											$productId=$_product->getId();
											$productFactory = $objectManager->get('\Magento\Catalog\Model\ProductFactory');
											$product = $productFactory->create()->load($productId);
											$productPrice = $product->getPrice();	
											$productSpecialPrice = $productPrice -($productPrice * $pricePercent)/100;
											$product->setSpecialPrice($productSpecialPrice);
											$product->setSpecialFromDate($startDate);
											//$product->setSpecialFromDateIsFormated(true);
											
											// Sets the End Date
											$product->setSpecialToDate($endDate);
											//$product->setSpecialToDateIsFormated(true);
											
											$product->save();				
										}
									}
								}
								$processList['categoryCode'] = $categoryCode;	
								$finalProcessList[] = $processList;	
								$this->logger->info($pricePercent);
							}
							else{
								$errorList['categoryCode'] = $categoryCode;	
								$errorList['message'] ="Requested Category doesn't exist";
								$finalErrorList[] = $errorList;
							}
						}
						
					}
				}
				if(is_array($finalErrorList)) { 
					$process['error_list'] = json_encode($finalErrorList);
				}
				
				if(is_array($finalProcessList)) { 
					$process['processed_list'] = json_encode($finalProcessList);
				}
				$process['id'] = $_request['id'];				
				$process['processed']=1;
				$process['processed_at'] = date('Y-m-d H:i:s');
				$model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
				$requestsave = $model->updateProcessQueue($process);
				
			}
		}
		public function convertFormatToArray($data, $formatType = 'json') {
			if ($formatType == 'xml') {
				$requestData = json_decode(json_encode((array) simplexml_load_string($data)), 1);            
			}
			if ($formatType == 'json') {
				$jsondata = json_decode($data, true);
				$requestData = $jsondata['params'];
			}
			return $requestData;
		}
		
	}													