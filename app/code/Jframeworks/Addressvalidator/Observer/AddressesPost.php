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
class AddressesPost implements ObserverInterface
{

	protected $_objectManager;
	protected $_addressregistry;
	protected $_messageManager;
	protected $customerSession;
	
	public function __construct(
        \Magento\Framework\App\RequestInterface $request,
		\Magento\Framework\ObjectManagerInterface $objectmanager,
		\Jframeworks\Addressvalidator\Helper\Data $helper,
		AddressRegistry $addressregistry,
		\Magento\Framework\Message\ManagerInterface $messageManager,
		\Magento\Customer\Model\Session $customerSession
		
    ) {
       $this->_request = $request; //rest of constructor here
	   $this->_objectManager = $objectmanager;
	   $this->helper = $helper;
	   $this->_addressregistry = $addressregistry;
	   $this->_messageManager = $messageManager;
	   $this->customerSession = $customerSession;
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
		
		$address_type = '';
		$results = array();
		$address_read = array();
		
		if($this->helper->isenable() != 1){	
			return $observer;
		}
		
		if(count($this->_request->getPost('ship'))){
			$ship_addresses = $this->_request->getPost('ship');
			$totalAddress = count($ship_addresses)-1;
				for($i=0;$i<count($ship_addresses);$i++){
					foreach($ship_addresses[$i] as $ship_address){
						
					
						if(in_array($ship_address['address'],$address_read)){
							continue;
						}
						$address = $this->_addressregistry->retrieve($ship_address['address']);
						
						//so either it is dirty or it is the first time thru - either way validate the address!
						//now check if the user opted to use the corrected addr
						
						$first_name = $address->getFirstname();
						$last_name  = $address->getLastname();
						$address_1  = $address->getStreet(1)[0];
						if(count($address->getStreet()) == 2)
						{
							$address_2  = $address->getStreet(1)[1];
						}
						else{
							$address_2  = '';
						}
						$city 		= $address->getCity();
						$state	 	= $address->getRegion();
						$zip 		= $address->getPostcode();
						$country 	= $address->getCountry();
						
						//generate a unique request id
						$requestId = 'Magento_' . time();
						
						$url = '?request_id='.$requestId.'&street='.urlencode($address_1).'&secondary='.urlencode($address_2).'&state='.urlencode($state).'&city='.urlencode($city).'&zipcode='.urlencode($zip).'&country='.urlencode($country);
						
						
						
						if($country != 'US' && $this->helper->isglobalenable() == 1 ){
							$request_url = $this->helper->jframeworksglobalapiurl().$url;
						}
						else if ($country == 'US'){
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
								$result['address_id'] = $ship_address['address'];
								$results[$i] = json_encode($result);
							}

							$address_read[] = $ship_address['address'];
						}
						
					}
					
				}
				
			$this->customerSession->setJframeworksResult($results);
			
			return $observer;
		}
	}
}
