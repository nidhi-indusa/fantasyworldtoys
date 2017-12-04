<?php

namespace MageVision\Blog16\Plugin\Checkout\Model;

class ShippingInformationManagement extends  \Magento\Checkout\Model\ShippingInformationManagement{

    protected $quoteRepository;
    protected $jsonHelper;
    protected $_request;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
    \Magento\Quote\Model\QuoteRepository $quoteRepository, \Magento\Framework\App\RequestInterface $request, \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->_request = $request;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     */
    public function beforeSaveAddressInformation(
    \Magento\Checkout\Model\ShippingInformationManagement $subject, $cartId, \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        $extAttributes = $addressInformation->getExtensionAttributes();
        $reqeustParams = $this->_request->getParams();
        
        $AxStoreId = $extAttributes->getAxStoreId();
        $LocationId = $extAttributes->getLocationId();
        $DeliveryFrom = $extAttributes->getDeliveryFrom();
        $TransferOrderQuantity = $extAttributes->getTransferOrderQuantity();
        $DeliveryMethod = $extAttributes->getNewdeliverymethod();
        
//        echo "ShippingInformationManagement...";
//        echo  "<pre>";
//        print_r($extAttributes);
//        die();
        
        
        
        if(isset($TransferOrderQuantity) && $TransferOrderQuantity != 'unknown'){
           $decodedData = $this->jsonHelper->jsonDecode($TransferOrderQuantity);
        }
        
        
        $quote = $this->quoteRepository->getActive($cartId);
        $quote->setAxStoreId($AxStoreId);
        $quote->setLocationId($LocationId);
        $quote->setDeliveryFrom($DeliveryFrom);
        $quote->setDeliveryMethod($DeliveryMethod);
        
        $quoteid = $quote->getEntityId();

        $quote = $this->quoteRepository->get($quoteid);
        foreach ($quote->getAllVisibleItems() as $itemq) {
            if(isset($decodedData) && ($decodedData != '') ){
                if (array_key_exists($itemq->getItemId(), $decodedData)) {
                    $itemq->setTransferOrderQuantity($decodedData[$itemq->getItemId()]);
                }
            }
                $itemq->setAxStoreId($AxStoreId);
                $itemq->save();
        }
        
        //echo "shpping...";
       // echo "<pre>";
       // print_r($quote->getData());
        
       $shippingAddress = $quote->getShippingAddress();
       
        
        
        
        if( $quote->getDeliveryMethod()   == 'clickandcollect'  )
        {

            $shippingAddress->setGrandTotal($shippingAddress->getGrandTotal() - $shippingAddress->getShippingAmount() );
            $shippingAddress->setBaseGrandTotal($shippingAddress->getGrandTotal()  - $shippingAddress->getShippingAmount());
            $shippingAddress->setShippingAmount(0);
            $shippingAddress->setBaseShippingAmount(0);
            $shippingAddress->setShippingInclTax(0);
            $shippingAddress->setBaseShippingInclTax(0);
            
             $quote->setShippingAddress($shippingAddress);
        }
        
        
         //echo '<pre>'; print_r($shippingAddress->getData()); echo '</pre>';
         //die();
        
    }
    
    
    
    
    
    
     
    
}
