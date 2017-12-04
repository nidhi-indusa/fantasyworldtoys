<?php
	
	namespace Indusa\Webservices\Cron;
	use Indusa\Webservices\Model\Service;
	use Indusa\Webservices\Logger\Logger;
	
	class ProcessRequest {
		
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
	
			$errorList = array();
			$processList = array();
			$finalErrorList = array();				
			$xmlProducts = array();
			
			//Fetching all the request with "processed" value is '0'
			$resultFactory = $this->_requestQueueFactory->create()->getCollection()->addFieldToFilter('processed', array('eq' => 0))->addFieldToFilter('error_list', array('null' => true))->setOrder('id', 'DESC');
			
			$requestData = $resultFactory->getData();
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$processmodel = $objectManager->create('\Indusa\Webservices\Model\Acknowledgment\SendAcknowledgment');	
			
			foreach ($requestData as $_request) {
				$requestXML = $_request['request_xml'];
				$requestDataArray = $this->convertFormatToArray($requestXML, 'xml');
				if ($_request['request_type'] == Service::MANAGE_PRODUCTS) {
					
					$importProductModel = $objectManager->get('Indusa\Webservices\Model\Import\ImportConfigurable');
					$productResponseInfo = $importProductModel->importProductData($requestDataArray, $_request['id'],$_request['request_id']);
					
					if ($productResponseInfo) {										
						//Magento reindexing after product changes
						$this->_service->reIndexing();								
					}
				}
				else if($_request['request_type'] == Service::PRICE_UPDATES)
				{
					$errorList = array();
					$processList = array();
					$finalErrorList = array();				
					
					if(array_key_exists(0,$requestDataArray['products']['product']))
					{
						$xmlProducts = $requestDataArray['products']['product'];
					}
					else
					{
						$xmlProducts[] = $requestDataArray['products']['product'];
					}	
					foreach($xmlProducts as $_productData){
						
						$priceData = array();
						$priceData['axProductID'] = $_productData['axProductID'];
						$priceData['price'] = $_productData['price'];
						$priceData['specialPrice'] = $_productData['specialPrice'];						
						$Sku = $_productData['axProductID'];
						
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
								if($_productData['specialPrice'] >= $_productData['price'] || $_productData['specialPrice'] == 0)
								{
									$product->setPrice($_productData['price']);
									$product->save();	
								}
								else
								{
									$product->setPrice($_productData['price']);
									$product->setSpecialPrice($_productData['specialPrice']);
									$product->save();
								}
							}
							$processList['Sku'] = $Sku;	
							//$processList['message'] ="Price Updated Successfully";
							$finalProcessList[] = $processList;
						}
						else if($productType == 'simple')
						{							
							//Updating Simple Product Prices	
							$productId=$_product->getId();
							$productFactory = $objectManager->get('\Magento\Catalog\Model\ProductFactory');
							$product = $productFactory->create()->load($productId);
							if($_productData['specialPrice'] >= $_productData['price'] || $_productData['specialPrice'] == 0)
							{
								$product->setPrice($_productData['price']);
								$product->save();	
							}
							else
							{
								$product->setPrice($_productData['price']);
								$product->setSpecialPrice($_productData['specialPrice']);
								$product->save();
							}
							
							$processList['Sku'] = $Sku;	
							$finalProcessList[] = $processList;							
						}
						else{							
							$errorList['Sku'] = $Sku;	
							$errorList['message'] ="Requested SKU doesn't exist";
							$finalErrorList[] = $errorList;
						}
					}
					
					if(isset($finalErrorList)) { 
						$error['error_list'] = json_encode($finalErrorList);
					}
					$error['id'] = $_request['id'];
					//$this->logger->info(json_encode($error));
					$error['processed']=1;
					$error['processed_at'] = date('Y-m-d H:i:s');
					$model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
					$requestsave = $model->updateProcessQueue($error);
					//return true;
					
					if(isset($finalProcessList)) { 
						$process['processed_list'] = json_encode($finalProcessList);
					}
					$process['id'] = $_request['id'];
					//$this->logger->info(json_encode($process));
					$process['processed']=1;
					$process['processed_at'] = date('Y-m-d H:i:s');
					$model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
					$requestsave = $model->updateProcessQueue($process);
						    
					return true;
				}
				else if($_request['request_type'] == Service::RELATED_PRODUCTS)
				{		
					//check if one product data in XML	
					if(array_key_exists(0,$requestDataArray['products']['product']))
					{
						$xmlProducts = $requestDataArray['products']['product'];
					}
					else
					{
						$xmlProducts[] = $requestDataArray['products']['product'];
					}	
					
					foreach($xmlProducts as $_productData){
						$_products = array();
						$Sku = $_productData['axProductID'];
						
						//check if one related product data in XML
						
						if(is_array($_productData['relatedProducts']['axProductID'])){					
							if(array_key_exists(0,$_productData['relatedProducts']['axProductID']))
							{
								$_products = $_productData['relatedProducts']['axProductID'];
							}
							else
							{
								$_products[] = $_productData['relatedProducts']['axProductID'];
							}
						}
						else{
							$_products[] = $_productData['relatedProducts']['axProductID'];
						}	
						
						foreach($_products as $_product){
							
							$productRepository = $objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface');
							$productModel = $productRepository->get($Sku); 
							$productLinks = $productModel->getProductLinks();
							
						//check the related product exists or not
						$productFactory = $this->_productFactory->create();				
						$relatedProductId = $productFactory->getIdBySku($_product);
						
						if($relatedProductId){
							$newLink = $objectManager->create('Magento\Catalog\Model\ProductLink\Link')
							->setSku($Sku)
							->setLinkedProductSku($_product)
							->setPosition(1)
							->setLinkType('related');
							
							$productLinks[] = $newLink;
							
							$productModel
							->setProductLinks($productLinks)
							->save(); 
								
							$processList['Sku'] = $Sku;	
							$finalProcessList[] = $processList;							
						}	
						else {							
							$errorList['Sku'] = $Sku;	
							$errorList['error_list'] = $_product." Requested SKU doesn't exist";
							$finalErrorList[] = $errorList;
						}
					}
				}
				
				//Updating the Request table when processing done
					$process['id'] = $_request['id'];
					
					$process['processed']=1;
					$process['processed_at'] = date('Y-m-d H:i:s');
					if(isset($finalErrorList)) { 
						$process['error_list'] = json_encode($finalErrorList);
						}
					if(isset($finalProcessList)) { 
						$process['processed_list'] = json_encode($finalProcessList);
					}				
					
					$model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
					$requestsave = $model->updateProcessQueue($process);					
					return true;					
				}
			}        
		}
		
		/**
			* @param xml data $data
			* @param Type of formate to convert $formatType
			* 
			* @return requestData
		*/
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