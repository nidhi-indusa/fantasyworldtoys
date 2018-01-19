<?php
	namespace Indusa\Deliverymethod\Observer;
	use Magento\Framework\Event\ObserverInterface;
	use Magento\Framework\App\Request\DataPersistorInterface;
	use Magento\Framework\App\ObjectManager;
	
	class ObserverforAddCustomEmailVariable implements ObserverInterface
	{
		protected $collectionFactory;
		public function __construct(\Indusa\GoogleMapsStoreLocator\Model\ResourceModel\Storelocator\CollectionFactory $collectionFactory
		) {
			
			$this->collectionFactory = $collectionFactory;
		}
		
		/**
			*
			* @param \Magento\Framework\Event\Observer $observer
			* @return void
		*/
		public function execute(\Magento\Framework\Event\Observer $observer)
		{
			$transport = $observer->getTransport();
			$payment = $observer->getData('transport')['order']->getPayment();
			$paymentMethod = $payment->getMethod();
			$AddtionalData[] = json_decode($payment->getAdditionalData(),true);
			if($paymentMethod == "knet"){
				
				$transport['getIsKnet'] = 1;
				$transport['PaymentID'] = $AddtionalData[0]['PaymentID'];
				$transport['TransID'] = $AddtionalData[0]['TranID'];
				$transport['Result'] = $AddtionalData[0]['Result'];
				$transport['RefID'] = $AddtionalData[0]['Ref'];
				$transport['TrackID'] = $AddtionalData[0]['TrackID'];
				
			}
			$deliveryMethod = $observer->getData('transport')['order']->getDeliveryMethod();
						
			if($deliveryMethod == "homedelivery"){
			
				$deliveryDate = date('d-m-Y', strtotime($observer->getData('transport')['order']->getDeliveryDate()));
				$transport['getIsHomedelivery'] = 1;	
				$transport['getDeliveryMethod'] = "Home Delivery";	
				$transport['getDeliveryDate'] = $deliveryDate;	
			}
			elseif($deliveryMethod == "clickandcollect"){
				$transport['getIsClickandcollect'] = 1;	
				$transport['getDeliveryMethod'] = "Click and Collect";	
				
				$AxStoreId =  $observer->getData('transport')['order']->getAxStoreId();
				
				$AXstorearray = array();
								$storecollection = $this->collectionFactory->create()->addFieldToFilter('is_active', 1)->setOrder('creation_time', 'ASC');
								foreach ($storecollection as $strdata) {
									
									foreach ($storecollection as $strdata) {
										$AXstorearray[] = $strdata->getData('ax_storeid');
									}
									
								}
				
				if(in_array($AxStoreId,$AXstorearray))
				{		
					$storecollection = $this->collectionFactory->create()->addFieldToFilter('ax_storeid',  array('eq' =>$AxStoreId))->getFirstItem();
					$storeName = $storecollection->getData('store_name');
					//echo $storeName;die;
				}
				$transport['StoreName'] = $storeName;
			}			
		}
	}		