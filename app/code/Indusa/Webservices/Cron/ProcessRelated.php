<?php
	
	namespace Indusa\Webservices\Cron;
	use Indusa\Webservices\Model\Service;
	use Indusa\Webservices\Logger\Logger;
	
	class ProcessRelated {
		
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
			$resultFactory = $this->_requestQueueFactory->create()->getCollection()->addFieldToFilter('processed', array('eq' => 0))->addFieldToFilter('request_type', array('eq' =>'relatedProducts'))->setOrder('id', 'DESC');
			
			$requestData = $resultFactory->getData();
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$processmodel = $objectManager->create('\Indusa\Webservices\Model\Acknowledgment\SendAcknowledgment');	
			
			foreach ($requestData as $_request) {
				
				$process = array();				
				$finalProcessList = array();		
				$finalErrorList = array();	
				$xmlProducts = array();
				
				$requestXML = $_request['request_xml'];
				$requestDataArray = $this->convertFormatToArray($requestXML, 'xml');				
				
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
					
					$processList = array();
					$_products = array();
					$Sku = $_productData['axProductID'];
					
					//check product exists
					$productModel = $objectManager->get('Magento\Catalog\Model\Product')
					->getCollection()->addFieldToSelect('entity_id')
					->addAttributeToFilter('sku',$Sku)->getFirstItem();										
					
					if($productModel->getEntityId()){
						
						//delete all related products of product
						
						$resource   = $objectManager->get('Magento\Framework\App\ResourceConnection');
						$connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION'); 
						$tableName = $resource->getTableName('catalog_product_link');						
						$connection->query("Delete FROM ".$tableName." where product_id =".$productModel->getEntityId()." and link_type_id = 1");		
						
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
						
						
						if(count($_products)>1)
						{
						$this->logger->info("Inside if".json_encode($_products));
					
							foreach($_products as $_product){
								
								$errorList = array();
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
									->setLinkType('related');
									
									$productLinks[] = $newLink;
									
									$productModel
									->setProductLinks($productLinks)
									->save(); 								
								}	
								else {							
									$errorList['axProductID'] = $Sku;	
									$errorList['error_list'] = $_product." Requested SKU doesn't exist";
									$finalErrorList[] = $errorList;
								}
							}
							if(count($errorList) == 0) $finalProcessList[] = $Sku;
						}
						else{
						$this->logger->info("Inside else".json_encode($_products));
							$finalProcessList[] = $Sku;
						}
					}
					else{
						$errorList['axProductID'] = $Sku;	
						$errorList['error_list'] = "Requested SKU - ".$Sku." doesn't exist";
						$finalErrorList[] = $errorList;
					}
				}
				$finalProcessList = array_unique($finalProcessList);	
				//Updating the Request table when processing done
				$process['id'] = $_request['id'];
				
				$process['processed']=1;
				$process['processed_at'] = date('Y-m-d H:i:s');
				
				if(is_array($finalErrorList)) { 				
					$process['error_list'] = json_encode($finalErrorList);
				}
				if(is_array($finalProcessList)) { 
					$process['processed_list'] = json_encode($finalProcessList);
				}				
				
				$model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
				$requestsave = $model->updateProcessQueue($process);			
				
				$this->sendAcknowledgmentToAx($requestsave,$finalProcessList,$finalErrorList,$_request['id'],$_request['request_id']);
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
		
		public function sendAcknowledgmentToAx($processed,$finalProcessList,$finalErrorList,$processId,$request_id){
			
			$ackResponse = array();
			if($processed  === true){   
				
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
				
				$scopeConfig = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');      
				$configPath = 'ack_webservice/ack_credential/username';
				$username =  $scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
				$configPath = 'ack_webservice/ack_credential/password';
				$password =  $scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
				
				$configPath = 'ack_webservice/ack_credential/ack_url';
				$ackUrl =  $scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
				
				$ackXMLResponse = "<eCommerceAPI>";
				$ackXMLResponse .= "<username>" .$username. "</username>";
				$ackXMLResponse .= "<password>" .$password. "</password>";
				$ackXMLResponse .= "<serviceName>" .Service::SEND_ACKNOWLEDGEMENT_TO_AX. "</serviceName>";
				$ackXMLResponse .= "<requestID>" .$request_id. "</requestID>";
				//$ackXMLResponse .= "<status>Success</status>";
				$ackXMLResponse .= "<requestType>" .Service::RELATED_PRODUCTS. "</requestType>";
				
				if(count($finalProcessList) == 0){
					$ackXMLResponse .= "<processedList/>";
				}
				else{
					$ackXMLResponse .= "<processedList>";
					foreach($finalProcessList as $_product)
					{						
						$ackXMLResponse .="<axProductID>".$_product."</axProductID>";
					}
					$ackXMLResponse .= "</processedList>";
				}
				
				if(count($finalErrorList) == 0){
					$ackXMLResponse .= "<errorList/>";
				}
				else{	
					$ackXMLResponse .= "<errorList>";
					foreach($finalErrorList as $_errorProduct)
					{						
						$ackXMLResponse .="<error><axProductID>".$_errorProduct['axProductID']."</axProductID>";
						$ackXMLResponse .="<errorMessage>".$_errorProduct['error_list']."</errorMessage></error>";
					}
					$ackXMLResponse .= "</errorList>";
				}
				
				$ackXMLResponse .= "</eCommerceAPI>";
				
				//SendAcknowledgmentToAX
				
				$successData = array("acknowledgeXML" => $ackXMLResponse,"requestId" =>$request_id);
				$processmodel = $objectManager->create('\Indusa\Webservices\Model\Acknowledgment\SendAcknowledgment');			
				
				$this->logger->info("Related Product Updates ack XML log for Req id ".$request_id.": ".$ackXMLResponse);
				$headers = 
				array("Content-type: application/json","password:$password","username:$username");
				$ch = curl_init(); 
				$ch = curl_init($ackUrl); 
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_POST, true);		
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($successData));
				curl_setopt($ch, CURLOPT_VERBOSE, true);
				$res = curl_exec($ch);
				
				$ackResponse = json_decode(curl_exec($ch),true);
				
				$this->logger->info("Related Products Updates ack response for Req id ".$request_id.": ".json_encode($ackResponse));
				if($ackResponse != null){
					if (array_key_exists("Status",$ackResponse))
					{ 
						if($ackResponse['Status'] = 'Success')
						{
							$Ackprocess['acknowledgment'] = 1;
							$Ackprocess['ack_datetime'] = date('Y-m-d H:i:s');
							$Ackprocess['id'] = $processId;
							$model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
							$requestsave = $model->updateProcessQueue($Ackprocess);
						}
						return true;    
					} 
				}
				return true;	
				
			}		
		}		
	}
?>
