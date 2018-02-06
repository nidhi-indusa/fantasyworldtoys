<?php
	
	namespace Indusa\Webservices\Cron;
	use Indusa\Webservices\Model\Service;
	use Indusa\Webservices\Logger\Logger;
	
	class ProcessPrice {
		
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
			$resultFactory = $this->_requestQueueFactory->create()->getCollection()->addFieldToFilter('processed', array('eq' => 0))->addFieldToFilter('request_type', array('eq' =>'priceUpdates'))->setOrder('id', 'ASC');
			
			$requestData = $resultFactory->getData();			
			
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$processmodel = $objectManager->create('\Indusa\Webservices\Model\Acknowledgment\SendAcknowledgment');	
			
			foreach ($requestData as $_request) {
				
				$finalErrorList = array();	
				$finalProcessList = array();				
				
				$process = array();
				$requestXML = $_request['request_xml'];
				$xmlProducts = array();
				$requestDataArray = $this->convertFormatToArray($requestXML, 'xml');			
				
				if(array_key_exists(0,$requestDataArray['products']['product']))
				{
					$xmlProducts = $requestDataArray['products']['product'];
				}
				else
				{
					$xmlProducts[] = $requestDataArray['products']['product'];
				}	
				foreach($xmlProducts as $_productData){					
					
					$errorList = array();
					$processList = array();
					
					$priceData = array();
					$priceData['axProductID'] = $_productData['axProductID'];
					
					$_productData['price'] = str_replace(',', '', $_productData['price']);					
					$_productData['specialPrice'] = str_replace(',', '', $_productData['specialPrice']);
					
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
							if($_productData['specialPrice'] > $_productData['price'] || $_productData['specialPrice'] == 0)
							{
								$product->setPrice($_productData['price']);								
								$product->save();	
							}
							elseif($_productData['specialPrice'] == $_productData['price'])
							{
								$product->setPrice($_productData['price']);
								$product->setSpecialPrice($_productData['specialPrice']);								
								$product->save();	
							}
							else
							{
								$product->setPrice($_productData['price']);
								$product->setSpecialPrice($_productData['specialPrice']);
								$product->save();
							}
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
						
						$processList['axProductID'] = $Sku;	
						$finalProcessList[] = $processList;							
					}
					else{							
						$errorList['axProductID'] = $Sku;	
						$errorList['message'] ="Requested SKU doesn't exist";
						$finalErrorList[] = $errorList;
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
				$ackXMLResponse .= "<requestType>" .Service::PRICE_UPDATES. "</requestType>";
				
				if(count($finalProcessList) == 0){
					$ackXMLResponse .= "<processedList/>";
				}
				else{
					$ackXMLResponse .= "<processedList>";
					foreach($finalProcessList as $_product)
					{						
						$ackXMLResponse .="<axProductID>".$_product['axProductID']."</axProductID>";						
					}
					$ackXMLResponse .= "</processedList>";
				}
				
				if(count($finalErrorList) == 0){
					$ackXMLResponse .= "<errorList/>";
				}
				else{
					$ackXMLResponse .= "<errorList>";
					foreach($finalErrorList as $_product)
					{						
						$ackXMLResponse .="<error><axProductID>".$_product['axProductID']."</axProductID>";
						$ackXMLResponse .="<errorMessage>".$_product['message']."</errorMessage></error>";
					}
					$ackXMLResponse .= "</errorList>";
				}				
				$ackXMLResponse .= "</eCommerceAPI>";
				
				//SendAcknowledgmentToAX
				
				$successData = array("acknowledgeXML" => $ackXMLResponse,"requestId" =>$request_id);
				$processmodel = $objectManager->create('\Indusa\Webservices\Model\Acknowledgment\SendAcknowledgment');			
				
				$this->logger->info("Price Updates ack XML log for Req id ".$request_id.": ".$ackXMLResponse);
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
				
				$this->logger->info("Price Updates ack response for Req id ".$request_id.": ".json_encode($ackResponse));
				if($ackResponse != null){
					if (array_key_exists("Status",$ackResponse))
					{ 
						if($ackResponse['Status'] = 'Success')
						{
							$Ackprocess['acknowledgment'] = 1;
							$Ackprocess['ack_datetime'] = date('Y-m-d H:i:s');
							$Ackprocess['id'] = $processId;
							$model = $objectManager->create('Indusa\Webservices\Model\RequestQueue');
						}
						$requestsave = $model->updateProcessQueue($Ackprocess);
						return true;    
					}
				}
				return true;	
				
			}		
		}
	}
?>
