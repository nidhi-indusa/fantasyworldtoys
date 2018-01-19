<?php

namespace Indusa\Deliverymethod\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Payment\Helper\Data as PaymentHelper;

class ObserverforAddCustomEmailVariable implements ObserverInterface {

    protected $_orderRepositoryInterface;
    protected $collectionFactory;

    /**
     * @var \Magento\Sales\Model\Order\Address\Renderer
     */
    protected $addressRenderer;

    /**
     * @var \Magento\Payment\Helper\PaymentHelper
     */
    protected $paymentHelper;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectmanager
     */
    protected $_objectManager;
    protected $scopeConfig;

    /*
     *  * @param Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     */

    public function __construct(\Indusa\GoogleMapsStoreLocator\Model\ResourceModel\Storelocator\CollectionFactory $collectionFactory, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation, \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder, \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface, \Magento\Directory\Model\CountryFactory $countryFactory, \Magento\Framework\ObjectManagerInterface $objectmanager, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {

        $this->collectionFactory = $collectionFactory;
        $this->_storeManager = $storeManager;

        $this->inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_orderRepositoryInterface = $orderRepositoryInterface;
        $this->_countryFactory = $countryFactory;
        $this->_objectManager = $objectmanager;
        $this->scopeConfig = $scopeConfig;
    }

    public function getCountryname($countryCode) {
        $country = $this->_countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer) {

        $transport = $observer->getTransport();
        $order = $observer->getOrder();



        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $incid = $observer->getData('transport')['order']->getIncrementId();
        $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($incid);


        $billingAddressObj = $order->getBillingAddress();

        $billingAddressArray = $billingAddressObj->getData();

        $billingAddressfirstname = $billingAddressArray['firstname'];

        $billingAddresslastname = $billingAddressArray['lastname'];

        $billingAddresstelephone = $billingAddressArray['telephone'];

        $billingAddressstreet = $billingAddressArray['street'];

        $billingAddresscity = $billingAddressArray['city'];

        $billingAddressemail = $billingAddressArray['email'];

        $billingAddressregion = $billingAddressArray['region'];

        $billingAddresspostcode = $billingAddressArray['postcode'];

        $billingAddresstelephone = $billingAddressArray['telephone'];

        $billingAddresscountryId = $billingAddressArray['country_id'];



        $shippingAddressObj = $order->getShippingAddress();

        $shippingAddressArray = $shippingAddressObj->getData();

        $shippingAddressfirstname = $shippingAddressArray['firstname'];

        $shippingAddresslastname = $shippingAddressArray['lastname'];

        $shippingAddresstelephone = $shippingAddressArray['telephone'];

        $shippingAddressstreet = $shippingAddressArray['street'];

        $shippingAddresscity = $shippingAddressArray['city'];

        $shippingAddressemail = $shippingAddressArray['email'];

        $shippingAddressregion = $shippingAddressArray['region'];

        $shippingAddresspostcode = $shippingAddressArray['postcode'];

        $shippingAddresstelephone = $shippingAddressArray['telephone'];

        $shippingAddresscountryId = $shippingAddressArray['country_id'];




        $orderItems = $order->getAllItems();
        $items = array();
        foreach ($orderItems as $item) {

            //Convert to decimal to int
            if($item->getParentItemId() == ""){
                $transport['getTransferOrderQuantity'] = intval($item->getTransferOrderQuantity());
            }
            else{
                //Fetch from configurble product item id and load transfer order
                 $configurableitem = $objectManager->create('Magento\Sales\Model\Order\Item')->load($item->getParentItemId());
                 $transport['getTransferOrderQuantity'] = intval($configurableitem->getTransferOrderQuantity());
            }    
            

            $transport['getQtyOrdered'] = $item->getQtyOrdered();
            $transport['getProductId'] = $item->getProductId();
            $transport['getName'] = $item->getName();
            $transport['getSku'] = $item->getSku();
            $transport['getBasePrice'] = $item->getBasePrice();

            //$items[] = $item->getTransferOrderQuantity();
        }
        $transport['getBaseShippingAmount'] = $order->getBaseShippingAmount();
        $transport['getBaseGrandTotal'] = $order->getBaseGrandTotal();
        $transport['getTotalQtyOrdered'] = $order->getTotalQtyOrdered();

        $billingAddress = $observer->getData('transport')['order']->getBillingAddress();
        $shippingAddress = $observer->getData('transport')['order']->getShippingAddress();



        $payment = $observer->getData('transport')['order']->getPayment();
        $paymentMethod = $payment->getMethod();
        $AddtionalData[] = json_decode($payment->getAdditionalData(), true);
        if ($paymentMethod == "knet") {

            $transport['getIsKnet'] = 1;
            $transport['PaymentID'] = $AddtionalData[0]['PaymentID'];
            $transport['TransID'] = $AddtionalData[0]['TranID'];
            $transport['Result'] = $AddtionalData[0]['Result'];
            $transport['RefID'] = $AddtionalData[0]['Ref'];
            $transport['TrackID'] = $AddtionalData[0]['TrackID'];
        }
        
        //Cyber Source code start
        if ($paymentMethod == "chcybersource") {
            $additionalInformation = $payment->getAdditionalInformation();
            if (!empty($additionalInformation)) {
                $data = $additionalInformation;
                $transport['getIsCyber'] = 1;
                $transport['TransID'] = $payment->getCcExpMonth() . "-" . $payment->getCcExpYear();
                $transport['cc_number'] = 'xxxxxxxxxxxx' . $additionalInformation['last4'];
                $transport['card_type'] = $this->getCardType($additionalInformation['cardType']);
            }
        }
        //Cyber Source code end

        $deliveryMethod = $observer->getData('transport')['order']->getDeliveryMethod();

        if ($deliveryMethod == "homedelivery") {

            $deliveryDate = date('d-m-Y', strtotime($observer->getData('transport')['order']->getDeliveryDate()));
            $transport['getIsHomedelivery'] = 1;
            $transport['getIsClickandcollect'] = 0;
            $transport['getDeliveryMethod'] = "Home Delivery";
            $transport['getDeliveryDate'] = $deliveryDate;
        } elseif ($deliveryMethod == "clickandcollect") {
            $transport['getIsClickandcollect'] = 1;
            $transport['getIsHomedelivery'] = 0;
            $deliveryDate = 00 - 00 - 0000;
            $transport['getDeliveryMethod'] = "Click and Collect";

            $AxStoreId = $observer->getData('transport')['order']->getAxStoreId();

            $AXstorearray = array();
            $storecollection = $this->collectionFactory->create()->addFieldToFilter('is_active', 1)->setOrder('creation_time', 'ASC');
            foreach ($storecollection as $strdata) {

                foreach ($storecollection as $strdata) {
                    $AXstorearray[] = $strdata->getData('ax_storeid');
                }
            }

            if (in_array($AxStoreId, $AXstorearray)) {
                $storecollection = $this->collectionFactory->create()->addFieldToFilter('ax_storeid', array('eq' => $AxStoreId))->getFirstItem();
                $storeName = $storecollection->getData('store_name');
                $email_store_supervisor = $storecollection->getData('email_store_supervisor');
                //echo $storeName;die;
            }
            $transport['StoreName'] = $storeName;
        }
        if (!isset($email_store_supervisor)) {
            $email_store_supervisor = 'bhagyashri.patel@indusa.com';
        }
        
        if ($paymentMethod == "cashondelivery") {
            $paymentMethod = "Cash On Delivery";
        } else if ($paymentMethod == "chcybersource") {
            $paymentMethod = "CyberSource";
        }


        // Sales Representative
        $sales_name = $this->scopeConfig->getValue('trans_email/ident_sales/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $sales_email = $this->scopeConfig->getValue('trans_email/ident_sales/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        // Custom Email 1 (Ware house)
        $warehouse_salesname = $this->scopeConfig->getValue('trans_email/ident_custom1/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $warehouse_email = $this->scopeConfig->getValue('trans_email/ident_custom1/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        //Toemail
        $toemail = 'bhagyashri.patel@indusa.com';

        if ($deliveryMethod == "homedelivery") {
            $customemails = array($sales_email, $warehouse_email);
        } elseif ($deliveryMethod == "clickandcollect") {
            $customemails = array($sales_email, $warehouse_email, $email_store_supervisor);
        } else {
            $customemails = array($sales_email, $warehouse_email);
        }





        $store = $this->_storeManager->getStore()->getId();
        $transport = $this->_transportBuilder->setTemplateIdentifier('customemail_test_template')
                ->setTemplateOptions(['area' => 'frontend', 'store' => $store])
                ->setTemplateVars(
                        [
                            //'store' => $this->_storeManager->getStore(),
                            'getIsClickandcollect' => $transport['getIsClickandcollect'],
                            'getIsHomedelivery' => $transport['getIsHomedelivery'],
                            'getDeliveryMethod' => $transport['getDeliveryMethod'],
                            'StoreName' => $transport['StoreName'],
                            'getTransferOrderQuantity' => $transport['getTransferOrderQuantity'],
                            'getDeliveryDate' => $deliveryDate,
                            'order' => $order,
                            'items' => $items,
                            'getIsCyber' => $transport['getIsCyber'],
                            'getIsKnet' => $transport['getIsKnet'],
                            'PaymentID' => $transport['PaymentID'],
                            'TransID' => $transport['TransID'],
                            'Result' => $transport['Result'],
                            'RefID' => $transport['RefID'],
                            'TrackID' => $transport['TrackID'],
                            'cc_number' => $transport['cc_number'],
                            'card_type' => $transport['card_type'],
                            'billing' => $billingAddress,
                            'shipping' => $shippingAddress,
                            'shippingcity' => $shippingAddress->getcity(),
                            'billingAddressfirstname' => $billingAddressfirstname,
                            'billingAddresslastname' => $billingAddresslastname,
                            'billingAddressstreet' => $billingAddressstreet,
                            'billingAddresscity' => $billingAddresscity,
                            'billingAddressregion' => $billingAddressregion,
                            'billingAddresspostcode' => $billingAddresspostcode,
                            'billingAddresstelephone' => $billingAddresstelephone,
                            'billingAddresscountryId' => $this->getCountryname($billingAddresscountryId),
                            'shippingAddressfirstname' => $shippingAddressfirstname,
                            'shippingAddresslastname' => $shippingAddresslastname,
                            'shippingAddressstreet' => $shippingAddressstreet,
                            'shippingAddresscity' => $shippingAddresscity,
                            'shippingAddressregion' => $shippingAddressregion,
                            'shippingAddresspostcode' => $shippingAddresspostcode,
                            'shippingAddresstelephone' => $shippingAddresstelephone,
                            'shippingAddresscountryId' => $this->getCountryname($shippingAddresscountryId),
                            'payment_html' => $paymentMethod,
                            'shipping_html' => $transport['order']->getShippingDescription(),
                            'IsNotVirtual' => $transport['order']->getIsNotVirtual(),
                            'increment_id' => $transport['order']->getIncrementId(),
                            'customer_name' => $transport['order']->getCustomerName(),
                            'created_at' => $transport['order']->getCreatedAtFormatted(\IntlDateFormatter::LONG),
                            'store_name' => $transport['order']->getStoreName(),
                            'getTransferOrderQuantity' => $transport['getTransferOrderQuantity'],
                            'getQtyOrdered' => $transport['getQtyOrdered'],
                            'getTotalQtyOrdered' => $transport['getTotalQtyOrdered'],
                            'getName' => $transport['getName'],
                            'getSku' => $transport['getSku'],
                            'getBasePrice' => $transport['getBasePrice'],
                            'getBaseShippingAmount' => $transport['getBaseShippingAmount'],
                            'getBaseGrandTotal' => $transport['getBaseGrandTotal'],
                        ]
                )
                //->setFrom($this->scopeConfig->getValue($sender, 'store', $storeId))
                ->setFrom('general')
                ->addTo($customemails)
                // ->addTo(['bhagyashri.patel@indusa.com', 'bhagyashripatel@gmail.com'])
                ->getTransport();
        $transport->sendMessage();
    }

    /**
     * @param Order $order
     * @return string|null
     */
     //Cyber Source code
    private function getCardType($code) {
        $types = [
            '001' => 'Visa',
            '002' => 'Master Card',
            '003' => 'American Express',
            '004' => 'Discover',
        ];
        return (!empty($types[$code])) ? $types[$code] : $code;
    }

    protected function getFormattedShippingAddress($order) {
        return $order->getIsVirtual() ? null : $this->addressRenderer->format($order->getShippingAddress(), 'html');
    }

    /**
     * @param Order $order
     * @return string|null
     */
    protected function getFormattedBillingAddress($order) {
        return $this->addressRenderer->format($order->getBillingAddress(), 'html');
    }

    /**
     * Get payment info block as html
     *
     * @param Order $order
     * @return string
     */
    public function getPaymentHtml(Order $order) {
        return $this->paymentHelper->getInfoBlockHtml(
                        $order->getPayment(), $this->identityContainer->getStore()->getStoreId()
        );
    }

}
