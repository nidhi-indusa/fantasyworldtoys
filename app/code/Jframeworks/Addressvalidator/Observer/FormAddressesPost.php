<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Jframeworks\Addressvalidator\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Http\Context;
use Magento\Customer\Model\AddressRegistry;
/**
 * AdminNotification observer
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class FormAddressesPost implements ObserverInterface
{

	protected $_objectManager;
	protected $_addressregistry;
	protected $_messageManager;
	protected $customerSession;
	protected $_countryCollectionFactory;
	protected $_responseFactory;
    protected $_url;
	
	public function __construct(
        \Magento\Framework\App\RequestInterface $request,
		\Magento\Framework\ObjectManagerInterface $objectmanager,
		\Jframeworks\Addressvalidator\Helper\Data $helper,
		AddressRegistry $addressregistry,
		\Magento\Framework\Message\ManagerInterface $messageManager,
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Directory\Model\Region $countryCollectionFactory,
		\Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url
		
    ) {
       $this->_request = $request; //rest of constructor here
	   $this->_objectManager = $objectmanager;
	   $this->helper = $helper;
	   $this->_addressregistry = $addressregistry;
	   $this->_messageManager = $messageManager;
	   $this->customerSession = $customerSession;
	   $this->_countryCollectionFactory = $countryCollectionFactory;
	   $this->_responseFactory = $responseFactory;
       $this->_url = $url;
    }

    /**
     * Predispath action controller
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
		$customer = $observer->getEvent();
		$address = $customer->getCustomerAddress();
		$address_type = '';
		$results = array();
		$address_read = array();
		$success_url = $this->_request->getPost('success_url');
		$error_url = $this->_request->getPost('error_url');
		$default_shipping = $this->_request->getPost('default_shipping');	

		
		if($this->helper->isenable() != 1){	
			return $observer;
		}
		
		if(!$success_url){
			return $observer;
		}

		//check if address is for shipping
		if(preg_match('/Billing/i',$success_url) && preg_match('/Billing/i',$error_url) && !isset($default_shipping) ){
				return $observer;
		}
		else if(!preg_match('/shipping/i',$success_url) && !preg_match('/shipping/i',$error_url)){
				return $observer;
		}
		
		
	    $id = $this->_request->getParam('id');

		$referUrl =  $error_url;
		if(preg_match('/editShipping/i',$error_url)){
			$referUrl = $error_url.'id/'.$id.'/';
		}
		
	
		$customerBeforeAuthUrl = $this->_url->getUrl('multishipping/checkout_address/editShipping');
		if($id!='')
		{
			$referUrl = $customerBeforeAuthUrl.'id/'.$id.'/';
		}
		
		
		//ok if we have a 'which_to_use' it means the user has selected one - which means we validated already
		//Lets see if they have changed any data, if they have we need to revalidate!!!
		
		//which one did they select
		$selected = $this->_request->getPost('jframeworks_which_to_use');
	
		if(isset($selected)){

			//ok lets see if any of the fields are dirty
			
			
			// create the hidden id used in the html so we can check if it is dirty
			if($selected != 'orig'){
				$selected = "corrected_" . $selected;
			}
			
			$post_addr1 = $this->_request->getPost('jframeworks_ship_addr_' . $selected . '_addr1');
			$post_addr2 = $this->_request->getPost('jframeworks_ship_addr_' . $selected . '_addr2');
			$post_city 	= $this->_request->getPost('jframeworks_ship_addr_' . $selected . '_city');
			$post_state = $this->_request->getPost('jframeworks_ship_addr_' . $selected . '_state');
			$post_country 	= $this->_request->getPost('jframeworks_ship_addr_' . $selected . '_country');
			
			if($selected != 'orig'){
				$region = $this->_countryCollectionFactory->loadByCode($post_state,$post_country);
				If($region->getName())
				{
					$post_state = $region->getName();
				}
			} 
			$post_zip 	= $this->_request->getPost('jframeworks_ship_addr_' . $selected . '_zip');
			

			if(count($address->getStreet()) == 2)
			{
				$address_2  = $address->getStreet(1)[1];
			}
			else{
				$address_2  = '';
			}

			//Now compare them to the form to see if it is dirty
			//Billing or shipping addr?
			$dirty = false;
			
		
			($address->getStreet(1)[0] == $post_addr1) ? $dirty=$dirty : $dirty=true;
			($address_2 == $post_addr2) ? $dirty=$dirty : $dirty=true;
			($address->getCity() == $post_city) ? $dirty=$dirty : $dirty=true;
			($address->getRegion() == $post_state) ? $dirty=$dirty : $dirty=true;
			($address->getPostcode() == $post_zip) ? $dirty=$dirty : $dirty=true;
			($address->getCountry() == $post_country) ? $dirty=$dirty : $dirty=true;		
			
			/* echo $address->getStreet(1)[0] .'=='. $post_addr1.'<br>'.$address_2 .'=='. $post_addr2.'<br>'.$address->getCity() .'=='. $post_city.'<br>'.$address->getRegion() .'=='. $post_state.'<br>'.$address->getPostcode() .'=='. $post_zip.'<br>'.$address->getCountry() .'=='.$post_country;
			die('test'); */ 
			
			//if clean then lets just return the data and we are good to go
			if(!$dirty){
				$sessResults = $this->customerSession->getJframeworksResult();
				if($sessResults){
					if(is_array($sessResults)){
						$i=0;

						$this->customerSession->unsJframeworksResult();
						foreach($sessResults as $k => $sessResult){
							
							$obj = json_decode($sessResult);
							if($obj->address_id != $address->getId()){	
								$results[$i] = json_encode($obj);
							}
							$i++;
						}
					
						//$sessResults = array_values($sessResults);
						$this->customerSession->setJframeworksResult($results);
					}
					else{
						$this->customerSession->unsJframeworksResult();
					}
					
				}
				//TODO for now we return nothing so the order doesnt process
				//faking error on clean!
				return $observer;
			}
			 
		}

			
		//so either it is dirty or it is the first time thru - either way validate the address!
		//now check if the user opted to use the corrected addr

		$address_1  = $address->getStreet(1)[0];
		if(count($address->getStreet()) == 2)
		{
			$address_2  = $address->getStreet(1)[1];
		}
		else{
			$address_2  = '';
		}
		$city 			= $address->getCity();
		$state	 		= $address->getRegion();
		$zip 			= $address->getPostcode();
		$country 		= $address->getCountry();
		
		//generate a unique request id
		$requestId = 'Magento_' . time();
		
		if($country != 'US' && $this->helper->isglobalenable() == 1 ){
			$url = '?request_id='.$requestId.'&street='.urlencode($address_1).'&secondary='.urlencode($address_2).'&state='.urlencode($state).'&city='.urlencode($city).'&zipcode='.urlencode($zip).'&country='.urlencode($country);
			$request_url = $this->helper->jframeworksglobalapiurl().$url;
		}
		else if ( $country == 'US' ){
			$url = '?request_id='.$requestId.'&street='.urlencode($address_1).'&secondary='.urlencode($address_2).'&state='.urlencode($state).'&city='.urlencode($city).'&zipcode='.urlencode($zip);
			$request_url = $this->helper->jframeworksapiurl().$url;
		}
		
		
		if(($country != 'US' && $this->helper->isglobalenable() == 1) || $country == 'US'){
			//Call the api via curl
			if(!$response=$this->helper->callApi($request_url)){
				return $observer;
			}
			
		
			
			$transient 							= array();
			$transient['orig'] 					= array();
			$transient['orig']['addr1'] 		= $address->getStreet(1)[0];
			if(count($address->getStreet()) == 2)
			{
				$transient['orig']['addr2']     = $address->getStreet(1)[1];
			}
			else{
				$transient['orig']['addr2']     = '';
			}
			$transient['orig']['city'] 			= $address->getCity();
			$transient['orig']['state'] 		= $address->getRegion();
			$transient['orig']['region_id']     = $address->getRegionId();
			$transient['orig']['zip'] 			= $address->getPostcode();
			$transient['orig']['country'] 		= $address->getCountry();
			
			if($result = $this->helper->evaluateResponse($response, $transient, $zip)){
				
				if($result['error']){
					$result['address_id'] = (string)$address->getId();
					$sessResults = $this->customerSession->getJframeworksResult();

					if($sessResults){
						if(is_array($sessResults)){
							$i=0;

							$this->customerSession->unsJframeworksResult();
							
							foreach($sessResults as $k => $sessResult){
								
								$obj = json_decode($sessResult);
								if($obj->address_id != $address->getId()){	
									$results[$i] = json_encode($obj);
								}
								$i++;
							}
						}
						$results[$i] = json_encode($result);					
						$this->customerSession->setJframeworksResult($results);
					}
				
					$this->_responseFactory->create()->setRedirect($referUrl)->sendResponse();
					throw new \Magento\Framework\Exception\LocalizedException("We Can not save this address");
					
					
				}
				
			}
		}	
			return $observer;
		
	}
}
