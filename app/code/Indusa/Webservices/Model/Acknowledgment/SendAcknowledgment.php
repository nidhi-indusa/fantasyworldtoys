<?php
	
	namespace Indusa\Webservices\Model\Acknowledgment;
	
	use Magento\Backend\App\Area\FrontNameResolver;
	use Magento\Framework\App\ObjectManager\ConfigLoader;
	use Magento\Framework\App\ObjectManagerFactory;
	use Magento\Framework\App\State;
	use Magento\ImportExport\Model\Import;
	use Magento\Store\Model\Store;
	use Magento\Store\Model\StoreManager;
	
	
	class SendAcknowledgment extends \Magento\Framework\Model\AbstractModel {      
		
		protected $logger;
		
		public function __construct(\Indusa\Webservices\Logger\Logger $loggerInterface) 		
		{
			$this->logger = $loggerInterface;		
		}
		/**
			* @param request xml data $processData
			* @param process id $processId
			* 
			* @return Boolean
			* send Acknowledgment to AX
		*/
		public function sendAcknowledgmentToAX($ackXMLResponse = null,$processId)
		{		
			
			$ackResponse = array();
			if(!$ackXMLResponse) return false;
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();       
			$scopeConfig = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');      
			
			$configPath = 'ack_webservice/ack_credential/username';
			$username =  $scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			$configPath = 'ack_webservice/ack_credential/password';
			$password =  $scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
			
			$configPath = 'ack_webservice/ack_credential/ack_url';
			$ackUrl =  $scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);		
			
			$headers = 
			array("Content-type: application/json","password:$password","username:$username");
			$ch = curl_init(); 
			$ch = curl_init($ackUrl); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POST, true);		
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ackXMLResponse));
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			$res = curl_exec($ch);
			
			$ackResponse = json_decode(curl_exec($ch),true);
			$this->logger->info(json_encode($ackResponse));
			if($ackResponse != null){
				if (array_key_exists("Status",$ackResponse))
				{ 
					if($ackResponse['Status'] == 'Success')
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
			return false;
		}
		
		/**
			* @param xml data $data
			* @param Type of formate to convert $formatType
			* 
			* @return requestData
		*/
		public function convertFormatToArray($data,$formatType = 'json')
		{
			if($formatType == 'xml')
			{
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
