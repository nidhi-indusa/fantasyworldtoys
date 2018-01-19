<?php
/**
 * Copyright © 2015 Jframeworks . All rights reserved.
 */
namespace Jframeworks\Addressvalidator\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
	const XML_CONFIG_ENABLE = 'addressvalidator_section/general/enable_in_frontend';
    const XML_USER_KEY = 'addressvalidator_section/general/addressvalidator_user_key';
	const XML_SNAPCX_API_URL = 'addressvalidator_section/general/addressvalidator_jframeworks_api_url';
	const XML_CONFIG_GLOBAL_ENABLE = 'addressvalidator_section/general/enable_global_in_frontend';
	const XML_SNAPCX_GLOBAL_API_URL = 'addressvalidator_section/general/addressvalidator_jframeworks_global_api_url';
	
	protected $_scopeConfig;
    protected $enable_config;
    protected $userkey;
	protected $jframeworksapiurl;
	protected $enable_global_config;
	protected $jframeworks_global_api_url;
	protected $_countryCollectionFactory;

	/**
     * @param \Magento\Framework\App\Helper\Context $context
     */
	public function __construct(
	\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
	\Magento\Directory\Model\Region $countryCollectionFactory
	) {
		$this->_scopeConfig = $scopeConfig;
        $this->enable_config =  $this->_scopeConfig->getValue(self::XML_CONFIG_ENABLE);
        $this->userkey =  $this->_scopeConfig->getValue(self::XML_USER_KEY);
		$this->jframeworksapiurl = $this->_scopeConfig->getValue(self::XML_SNAPCX_API_URL);
		$this->enable_global_config =  $this->_scopeConfig->getValue(self::XML_CONFIG_GLOBAL_ENABLE);
		$this->jframeworks_global_api_url = $this->_scopeConfig->getValue(self::XML_SNAPCX_GLOBAL_API_URL);
		$this->_countryCollectionFactory = $countryCollectionFactory;
	}
	
	public function isenable()
    {
        return  $this->enable_config;
    }
	
	public function isglobalenable()
    {
        return  $this->enable_global_config;
    }
	
	public function userkey()
    {
      return  empty($this->userkey)?'':$this->userkey;
    }
	
	public function jframeworksapiurl()
    {
      return  empty($this->jframeworksapiurl)?'':$this->jframeworksapiurl;
    }
	
	public function jframeworksglobalapiurl()
    {
      return  empty($this->jframeworks_global_api_url)?'':$this->jframeworks_global_api_url;
    }
	
	public function callApi($url){
    
		//ok now lets call our API
		
		$user_key = $this->userkey();
		
		// Start cURL
		$curl = curl_init();
		// Headers
		$headers = array();
		$headers[] = 'user_key:'.$user_key;
		//$headers[] = 'Accept: application/json';
		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $curl, CURLOPT_HEADER, false);
	
		// Get response
		$response = curl_exec($curl);
	
		// Get HTTP status code
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		//TODO put status check for "200". 
		// Close cURL
		curl_close($curl);

		if($response!=''){
			$response = json_decode($response);	
			return $response;
		} else {
			return false;
		}
		
    }
	
	/**
	 * Evaluate the response from api
	 * 
	 * @param Varien_Event_Observer $observer
	 * @return Varien_Event_Observer $observer
	 */
	public function evaluateResponse($response, $transient,$zip){
		
		$return = false;
		if(is_object($response) && isset( $response->header) && isset( $response->header->status) && $response->header->status == 'SUCCESS'){
			if(isset($response->addressRecord[0]) && isset($response->addressRecord[0]->addressSummary) && isset($response->addressRecord[0]->addressSummary->matchCode)){
				switch($response->addressRecord[0]->addressSummary->matchCode){
					case 'AVS_01':
						if($response->addressRecord[0]->address[0]->zipCode != $zip){
							//loop thru the matching addrs
							$transient['corrected'] = array();
								
							for($i=0; $i<count($response->addressRecord[0]->address); $i++){
							
								//save on typing store in temp!!!!
								$temp = $response->addressRecord[0]->address[$i];
								
								$region = $this->_countryCollectionFactory->loadByCode($temp->state, $transient['orig']['country']);
								if($region->getId())
								{
									$state_id = $region->getId();
								}
								else{
									$state_id = 0;
								}
	
								$transient['corrected'][$i]['addr1'] =  is_null($temp->addressLine1) ? "" : $temp->addressLine1 ;
								$transient['corrected'][$i]['addr2'] = is_null($temp->addressLine2) ? "" : $temp->addressLine2 ;
								$transient['corrected'][$i]['city'] = is_null($temp->city) ? "" : $temp->city ;
								$transient['corrected'][$i]['state'] = is_null($temp->state) ? "" : $temp->state;
								$transient['corrected'][$i]['region_id'] = is_null($state_id) ? "" : $state_id;
								$transient['corrected'][$i]['zip'] = is_null($temp->zipCode) ? "" : $temp->zipCode;
								$transient['corrected'][$i]['country'] = $transient['orig']['country'];
							}
							$result['validate'] = true;
							$result['error'] = true;
							break;	
						}
						else{
							$result['validate'] = true;
							$result['error'] = false;
						}
					break;
					case 'AVS_02':
						//OK we should get a bunch of returned addr's - lets
						//add them to the transient
						
						//loop thru the matching addrs
						$transient['corrected'] = array();
						
						for($i=0; $i<count($response->addressRecord[0]->address); $i++){
							
							//save on typing store in temp!!!!
							$temp = $response->addressRecord[0]->address[$i];
								
							$region = $this->_countryCollectionFactory->loadByCode($temp->state, $transient['orig']['country']);
							if($region->getId())
							{
								$state_id = $region->getId();
							}
							else{
								$state_id = 0;
							}
	
							$transient['corrected'][$i]['addr1'] =  is_null($temp->addressLine1) ? "" : $temp->addressLine1 ;
							$transient['corrected'][$i]['addr2'] = is_null($temp->addressLine2) ? "" : $temp->addressLine2 ;
							$transient['corrected'][$i]['city'] = is_null($temp->city) ? "" : $temp->city ;
							$transient['corrected'][$i]['state'] = is_null($temp->state) ? "" : $temp->state;
							$transient['corrected'][$i]['region_id'] = is_null($state_id) ? "" : $state_id;
							$transient['corrected'][$i]['zip'] = is_null($temp->zipCode) ? "" : $temp->zipCode;
							$transient['corrected'][$i]['country'] = $transient['orig']['country'];
						}
						$result['validate'] = true;
						$result['error'] = true;
					break;
					case 'AVS_03':
						//we just show the original
						//but it is invalid!!!! Need to make sure the user corrects it
						$result['validate'] = true;
						$result['error'] = true;
					break;
					default:
						$result['validate'] = false;
            $result['error'] = false;
				}
				$result['data'] = $transient;
        $result['message'] = $response->addressRecord[0]->addressSummary->message;
				$return = $result;
			}	
		}
		return $return;
	}
	
	
}


