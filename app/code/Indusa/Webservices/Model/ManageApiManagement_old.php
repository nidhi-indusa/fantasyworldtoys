<?php
	/**
		* Indusa Webservice handling product add/update
		* Copyright (C) 2017 Indusa
		* 
		* This file included in Indusa/Webservices is licensed under OSL 3.0
		* 
		* http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
		* Please see LICENSE.txt for the full text of the OSL 3.0 license
	*/
	
	namespace Indusa\Webservices\Model;
	use Indusa\Webservices\Model\Service;
	
	class ManageApiManagement
	{    
		public $_availableService = array();
		public $authorization;
		
		protected $date;
		
		public function __construct(\Magento\Framework\Stdlib\DateTime\DateTime $date , \Indusa\Webservices\Logger\Logger $loggerInterface)
		{
			$this->date = $date;
			$this->logger = $loggerInterface;
			$this->_availableService = array(Service::MANAGEPRODUCTS,Service::INVENTORYUPDATES,Service::PRICEUPDATES,Service::RELATEDPRODUCTS);
		}    
		
		/**
			* Save API data in request_queue table
		*/
		public function saveapidata()
		{
			try {
				
				libxml_use_internal_errors(true);
				$postdata = file_get_contents("php://input");
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				
				$responseData = array();
				if($postdata)
				{
					
					$requestData =  $this->convertFormatToArray($postdata,'xml');
					
					if(!in_array($requestData['serviceName'],$this->_availableService))
					{
						throw new \Exception('Servicename with '.$requestData['serviceName'].' does not exist.');
					}				
					
					if(!isset($requestData['requestID']))
					{
						throw new \Exception('Request id is not found');  
					}
					
					$requestId = $requestData['requestID'];
					
					$xml = new \SimpleXMLElement('<'.$requestData['serviceName'].'/>');
					$response = $this->authenticate($requestData);
					$responseStatus = 'Ok';
					$responseData['requestID'] = $requestId;
					$responseData['status'] = 'Ok';
					if($response['ResponseStatus'] == 'Error')
					{
						$responseStatus = 'Error';
						$responseData['status'] = 'Error';
						$responseData['message'] = $response["ResponseMessage"];					 
					}
					else if($requestData['serviceName']=='inventoryUpdates')
					{
						$variantSKU=$requestData['SKU'];
						$products = $objectManager->get('Magento\Catalog\Model\Product')
						->getCollection()
						->addAttributeToFilter('variant_sku',$variantSKU)->getFirstItem();
						
						//$finalarray = $products->getData();
						$Sku= $products['sku'];
						if($Sku){
							$data=array();
							$data['quantity']=$requestData['qty'];
							$data['type']=$requestData['type'];
							$data['storeId']=$requestData['storeID'];
							$InventoryModel = $objectManager->create('\Indusa\Webservices\Model\InventoryStore');
							$requestsave = $InventoryModel->saveInventory($data,$Sku,$requestData['serviceName']);
							
							if($requestData['type']!='Store')
							{
								$productRepository = $objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface');
								$stockRegistry = $objectManager->create('Magento\CatalogInventory\Api\StockRegistryInterface');
								$product = $productRepository->get($Sku);
								$stockItem = $stockRegistry->getStockItem($product->getId());
								$stockItem->setData('qty',$data['quantity']);
								$stockRegistry->updateStockItemBySku($Sku, $stockItem);
								
								$apiResponseInfo['request_id'] = $requestId;
								$apiResponseInfo['request_type'] = $requestData['serviceName'];
								$apiResponseInfo['request_xml'] =  $postdata;
								$apiResponseInfo['request_datetime'] =  date('Y-m-d H:i:s');
								$apiResponseInfo['created_at'] = date('Y-m-d H:i:s');
								$apiResponseInfo['updated_at'] = date('Y-m-d H:i:s');            
								$apiResponseInfo['processed'] = 1;            
								$apiResponseInfo['processed_at'] = date('Y-m-d H:i:s'); 
								$apiResponseInfo['acknowledgment'] = 1;   
								$apiResponseInfo['ack_datetime'] = date('Y-m-d H:i:s');  
								$model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
								$requestsave = $model->saveRequestQueue($apiResponseInfo);
							}
							else
							{
								
								$apiResponseInfo['request_id'] = $requestId;
								$apiResponseInfo['request_type'] = $requestData['serviceName'];
								$apiResponseInfo['request_xml'] =  $postdata;
								$apiResponseInfo['request_datetime'] =  date('Y-m-d H:i:s');
								$apiResponseInfo['created_at'] = date('Y-m-d H:i:s');
								$apiResponseInfo['updated_at'] = date('Y-m-d H:i:s');            
								$apiResponseInfo['processed'] = 1;            
								$apiResponseInfo['processed_at'] = date('Y-m-d H:i:s'); 
								$apiResponseInfo['acknowledgment'] = 1;   
								$apiResponseInfo['ack_datetime'] = date('Y-m-d H:i:s');  
								$model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
								$requestsave = $model->saveRequestQueue($apiResponseInfo);
							}
						}
						else
						{
							//$responseStatus = 'Error';
							$responseData['status'] = 'Error';
							$responseData['message'] = "Requested product doesn't exist";
						}
					}
					
					else
					{
						$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
						$baseUrl = $storeManager->getStore()->getBaseUrl();
						
						$apiResponseInfo['request_id'] = $requestId;
						$apiResponseInfo['request_type'] = $requestData['serviceName'];
						$apiResponseInfo['request_xml'] =  $postdata;
						$apiResponseInfo['request_datetime'] =  date('Y-m-d H:i:s');
						$apiResponseInfo['created_at'] = date('Y-m-d H:i:s');
						$apiResponseInfo['updated_at'] = date('Y-m-d H:i:s');            
						
						$model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
						$requestsave = $model->saveRequestQueue($apiResponseInfo);
						
						if(!$requestsave) {  
							$responseData['Error'] = 'status';
							$responseData['Sync Failed'] = 'message'; 
						}
					}    
				}  
			} catch (\Exception $e)
			{
				if(!isset($requestData['serviceName']))
				{
					$requestData['serviceName'] = 'Error'; 
				}
				$responseData['status'] = 'Error';
				$responseData['message'] = $e->getMessage(); 
			}  
			
			$xmldata = '<'.$requestData['serviceName'].'>';
			foreach($responseData as $key => $value)
			{
				$xmldata .= '<'.$key.'>'.$value.'</'.$key.'>'; 
			}
			$xmldata .= '</'.$requestData['serviceName'].'>';
			echo $xmldata; 
			die();
		}    
		
		/**
			* Function to validate the API credentials
			* 
			* @return Response
		*/
		public function authenticate($requestData)
		{
			$validateJsonData = true;	
			
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$scopeConfig = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');      
			
			$configPath = 'ecommerce_webservice/webservice_credential/username';
			$username =  $scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			$configPath = 'ecommerce_webservice/webservice_credential/password';
			$password =  $scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			
			$response["ResponseStatus"] = 'success';
			
			if (!isset($requestData['username'])) {
				$validateJsonData = false;
				$response["ResponseStatus"] = 'Error';
				$ResponseMessage[] = 'Username is required';			
			}
			
			if (!isset($requestData['password'])) {
				$validateJsonData = false;
				$response["ResponseStatus"] = 'Error';
				$ResponseMessage[] = 'Password cannot be blank';				
			}
			
			if(!$validateJsonData)
			{
				$response["ResponseMessage"] = implode(" and ",$ResponseMessage);
			}
			
			if (isset($requestData['username']) && isset($requestData['password'])) {
				
				if($username != $requestData['username']) { $response["ResponseStatus"] = 'Error';  $validateJsonData = false; }
				if($password != $requestData['password']) { $response["ResponseStatus"] = 'Error'; $validateJsonData = false;  }
				
				if(!$validateJsonData)
				{
					$response["ResponseMessage"] = 'Username or Password is incorrect';
				}	
			}            
			return $response;	
		}
        
		public function convertFormatToArray($data,$formatType = 'json')
		{
			if($formatType == 'xml')
			{
				$xmlData = simplexml_load_string($data);
				
				if ($xmlData === false) {
					throw new \Exception('Invalid request data');
					return $xmlData;
				}           
				$requestData = json_decode(json_encode((array) simplexml_load_string($data)),1);
			}
			
			if($formatType == 'json')
			{
				$jsondata = json_decode($data,true);
				$requestData = $jsondata['params'];
			}        
			return  $requestData ;
		}
	}
