<?php
/**
 * Copyright © 2015 Inchoo d.o.o.
 * created by Zoran Salamun(zoran.salamun@inchoo.net)
 */
namespace Jframeworks\Addressvalidator\Controller\Shipping;
use Magento\Framework\Controller\ResultFactory;
 

class Check extends \Magento\Framework\App\Action\Action
{
	
	protected $_countryCollectionFactory; 
	public function __construct(
	    \Magento\Framework\App\Action\Context $context,
		 \Jframeworks\Addressvalidator\Helper\Data $helper,
		 \Magento\Directory\Model\Region $countryCollectionFactory
    ) {
		parent::__construct($context);
		$this->helper = $helper;
		$this->_countryCollectionFactory = $countryCollectionFactory;
    }
	
    public function execute()
    {
		if($this->helper->isenable() == 1){	
					
		    $post_addr1 = $this->_request->getParam('post_addr1');
			$post_addr2 = $this->_request->getParam('post_addr2');
			$post_city  = $this->_request->getParam('post_city');
			$post_state = $this->_request->getParam('post_state');
			$post_zip 	= $this->_request->getParam('post_zip');
			$selected	= $this->_request->getParam('selected');
			$post_country 	= $this->_request->getParam('post_country');
			
			$address_1 = $this->_request->getParam('strt1');
			$address_2 = $this->_request->getParam('strt2');
			$zip = $this->_request->getParam('pstcde');
			$city = $this->_request->getParam('ct');
			$state = $this->_request->getParam('st');
			$country = $this->_request->getParam('ctry');
			$RegionId = $this->_request->getParam('regid');

			if(strpos($state,"Please select a region") !== FALSE){
				$states = explode('Please select a region',$state);
				$state = $states[0];	
			}
			
			$region = $this->_countryCollectionFactory->loadByName($state,$country);
            If($region->getCode())
			{
				$state = $region->getCode();
			}
				
			if(isset($post_addr1) && isset($post_city) && isset($post_state) && isset($post_zip) && isset($post_country))
			{
				$dirty = false;
			
				($address_1 == $post_addr1) ? $dirty=$dirty : $dirty=true;
				($address_2 == $post_addr2) ? $dirty=$dirty : $dirty=true;
				($city == $post_city) ? $dirty=$dirty : $dirty=true;
				($state == $post_state) ? $dirty=$dirty : $dirty=true;
				($zip == $post_zip) ? $dirty=$dirty : $dirty=true;
				($country == $post_country) ? $dirty=$dirty : $dirty=true;
				
				
				 /* echo $address_1 .'=='. $post_addr1.'<br>'.$address_2 .'=='. $post_addr2.'<br>'.$city .'=='. $post_city.'<br>'.$state .'=='. $post_state.'<br>'.$zip .'=='. $post_zip.'<br>'.$country .'=='.$post_country.'<br>'.$RegionId .'=='.$postRegionId;
				die('hey'); */
				
				//if clean then lets just return the data and we are good to go
				if(!$dirty){
					//TODO for now we return nothing so the order doesnt process
					//faking error on clean!
					$resultArray = json_encode(array('error'=>false));
					$resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
					$resultJson->setData($resultArray);
					return $resultJson;
				}
			}
			
			//generate a unique request id
			$requestId   = 'Magento_'.time();
			$url = '?request_id='.$requestId.'&street='.urlencode($address_1).'&secondary='.urlencode($address_2).'&state='.urlencode($state).'&city='.urlencode($city).'&zipcode='.urlencode($zip).'&country='.urlencode($country);
			

			if($country != 'US' && $this->helper->isglobalenable() == 1 ){
				$request_url = $this->helper->jframeworksglobalapiurl().$url;
			}
			else if ( $country == 'US' ){
				$request_url = $this->helper->jframeworksapiurl().$url;
			}
			else{
				$resultArray = json_encode(array('error'=>false));
				$resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
				$resultJson->setData($resultArray);
				return $resultJson;
			}

			//Call the api via curl
			if(!$response=$this->helper->callApi($request_url)){
				$resultArray = json_encode(array('error'=>false));
				 $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
				 $resultJson->setData($resultArray);
				 return $resultJson;
			}

			
			$transient 							= array();
			$transient['orig'] 					= array();
			$transient['orig']['addr1'] 		= $address_1;
			$transient['orig']['addr2'] 	    = $address_2;
			$transient['orig']['city'] 			= $city;
			$transient['orig']['state'] 		= $state;
			$transient['orig']['region_id']     = $RegionId;
			$transient['orig']['zip'] 			= $zip;
			$transient['orig']['country'] 		= $country;
			
			
			if($result = $this->helper->evaluateResponse($response, $transient, $zip)){
				//$result['address_id'] = $address_id;		
				
				if(!$result['error']){
				
					$resultArray = json_encode(array('error'=>false));
					 $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
					 $resultJson->setData($resultArray);
					 return $resultJson;
				}
				
				$resultArray = json_encode($result);
				$resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
				$resultJson->setData($resultArray);
				return $resultJson; 
			}
			else{
				$resultArray = json_encode(array('error'=>false));
				$resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
				$resultJson->setData($resultArray);
				return $resultJson;;
			}
			
		}
		else{
			$resultArray = json_encode(array('error'=>false));
			$resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
			$resultJson->setData($resultArray);
			return $resultJson;	
		}
    }
}
