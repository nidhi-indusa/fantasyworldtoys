<?php

/**
 * Indusa Deliverymethod
 *
 * @category     Indusa_Deliverymethod
 * @package      Indusa_Deliverymethod
 * @author      Indusa_Deliverymethod Team
 * @copyright    Copyright (c) 2017 Indusa Deliverymethod (http://www.indusa.com/)
 * @license      http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Indusa\Deliverymethod\Plugin\Checkout\Model;

use Magento\Framework\Exception\StateException;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\LocalizedException;

class ShippingInformationManagement extends \Magento\Checkout\Model\ShippingInformationManagement {

    const CONFIG_SCOPE = 'default';
    const CONFIG_SCOPE_ID = 0;

    protected $quoteRepository;
    protected $jsonHelper;
    protected $_request;
    protected $_messageManager;
    protected $deliverydateconfigprovider;

    /* @var \Magento\Framework\App\Config\Storage\WriterInterface */
    protected $_configWriter;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
    \Magento\Quote\Model\QuoteRepository $quoteRepository, \Magento\Framework\App\RequestInterface $request, \Magento\Framework\Json\Helper\Data $jsonHelper, \Magento\Framework\Message\ManagerInterface $messageManager, \Indusa\Deliverymethod\Model\DeliveryDateConfigProvider $DeliveryDateConfigProvider, \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->_request = $request;
        $this->jsonHelper = $jsonHelper;
        $this->_messageManager = $messageManager;
        $this->deliverydateconfigprovider = $DeliveryDateConfigProvider;
        $this->_configWriter = $configWriter;
    }

    /**
     * beforeSaveAddressInformation
     * @throws LocalizedException
     */

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

        $deliveryDate = $extAttributes->getDeliveryDate();
        $deliveryComment = $extAttributes->getDeliveryComment();




        if ($extAttributes->getAxStoreId() == 'unknown' || $extAttributes->getNewdeliverymethod() == 'homedelivery') {
            $AxStoreId = '999';
            $LocationId = '999';
        }


        if (isset($TransferOrderQuantity) && $TransferOrderQuantity != 'unknown') {
            $decodedData = $this->jsonHelper->jsonDecode($TransferOrderQuantity);
        }


        $quote = $this->quoteRepository->getActive($cartId);
        $quote->setAxStoreId($AxStoreId);
        $quote->setLocationId($LocationId);
        $quote->setDeliveryFrom($DeliveryFrom);
        $quote->setDeliveryMethod($DeliveryMethod);

        //Convert mm-dd-yy to yy-dd-mm start
        if ($deliveryDate != '') {
            $date = date_create_from_format('d-m-Y', $deliveryDate);
            $deliveryDate = date_format($date, 'Y-m-d');
            //Convert mm-dd-yy to yy-dd-mm start
        }
        $quote->setDeliveryDate($deliveryDate);


        $quote->setDeliveryComment($deliveryComment);

        $quoteid = $quote->getEntityId();

        $quote = $this->quoteRepository->get($quoteid);
        foreach ($quote->getAllVisibleItems() as $itemq) {
            if (isset($decodedData) && ($decodedData != '')) {
                if (array_key_exists($itemq->getItemId(), $decodedData)) {
                    $itemq->setTransferOrderQuantity($decodedData[$itemq->getItemId()]);
                }
            }
            $itemq->setAxStoreId($AxStoreId);
            $itemq->save();
        }
        $shippingAddress = $quote->getShippingAddress();




        if ($quote->getDeliveryMethod() == 'clickandcollect') {

            $shippingAddress->setGrandTotal($shippingAddress->getGrandTotal() - $shippingAddress->getShippingAmount());
            $shippingAddress->setBaseGrandTotal($shippingAddress->getGrandTotal() - $shippingAddress->getShippingAmount());
            $shippingAddress->setShippingAmount(0);
            $shippingAddress->setBaseShippingAmount(0);
            $shippingAddress->setShippingInclTax(0);
            $shippingAddress->setBaseShippingInclTax(0);

            $quote->setShippingAddress($shippingAddress);
        } else if ($quote->getDeliveryMethod() == 'homedelivery') {
		$deliveryDate_old = '';
            //If(HOMEDELIVERY)
            //IF(DELIVERYDATE !=NULL)


            if ($deliveryDate != '') {

                ///new code addded on 29 dec 2017 start
                $date_currentdate = date('Y-m-d');
                /// DATETIME CURRENTTIME = GET CURRENT SERVER TIME HH:MM:SSS
                date_default_timezone_set('Asia/Kuwait');

                if ($deliveryDate == date('Y-m-d')) {

                    $date_currentdate_time = time(); //strtotime($deliveryDate);
                } else {

                    $date_currentdate_time = strtotime($deliveryDate);
                }
                $threePm = mktime(12); //
                if ($date_currentdate_time <= $threePm) {

                    $deliveryDate = date('Y-m-d'); //today();
                } else {
                    if ($deliveryDate == date('Y-m-d')) {

                        $deliveryDate_old = $deliveryDate;
                        $deliveryDate = date('Y-m-d', strtotime($deliveryDate . ' +1 day'));
                    }else{
                        $deliveryDate_old = $deliveryDate;
                    }
                }
                ///new code addded on 29 dec 2017 end



                $canBeDelivered = 0;
                $canBeDelivered = $this->getCheckDeliveryDateStatus($deliveryDate);

                if ($canBeDelivered) {
                    //SHOW MESSAGE TO USER THAT “Your order will be shipped on date “DELIVERYDATE”
                    //proceed further considering the delivery date chosen

                    $shippingAddress->setGrandTotal($shippingAddress->getGrandTotal() - $shippingAddress->getShippingAmount());
                    $shippingAddress->setBaseGrandTotal($shippingAddress->getGrandTotal() - $shippingAddress->getShippingAmount());
                    $quote->setShippingAddress($shippingAddress);
                } else {
                    //If Error Stay on same screen
                    //$custom_message = $msg = "Your order cannot be shipped on date " . $deliveryDate . " chosen, please choose another date !";
                    //$custom_message = $msg = "Sorry, your order cannot be shipped on date chosen. Please do choose another date !";
                    if ($deliveryDate_old == date('Y-m-d')) {
                        $msg = __('Your order cannot be shipped on date ') . date("d-m-Y", strtotime($deliveryDate_old)) . __('. Please choose another date!');
                    }else{
                         $msg = __('Your order cannot be shipped on date ') . date("d-m-Y", strtotime($deliveryDate)) . __('. Please choose another date!');
                    }

                    $custom_message = $msg;

                    $quote->setCustomMessage($custom_message);
                    $shippingAddress->setGrandTotal($shippingAddress->getGrandTotal() - $shippingAddress->getShippingAmount());
                    $shippingAddress->setBaseGrandTotal($shippingAddress->getGrandTotal() - $shippingAddress->getShippingAmount());
                    $quote->setShippingAddress($shippingAddress);
                    //throw new LocalizedException(__($msg));
                    throw new StateException(__($msg));
                    // $this->_messageManager->addError(__('Your order cannot be shipped on date '). $deliveryDate);
                }
            } else {
                //DATE CURRENTDATE = GET CURRENT DATE FROM SERVER

                $date_currentdate = date('Y-m-d');
                /// DATETIME CURRENTTIME = GET CURRENT SERVER TIME HH:MM:SSS
                //$date_currentdate_time = time();
                date_default_timezone_set('Asia/Kuwait');
                $date_currentdate_time = time();

                $threePm = mktime(12); //

                if ($date_currentdate_time <= $threePm) {
                    $checkdate = date('Y-m-d'); //today();
                } else {
                    $checkdate = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                }

                //Recursive logic to get best Deliverydate
                $newdate = $this->CheckBestDeliveryDate($checkdate);
                $quote->setDeliveryDate($newdate);

                $canBeDelivered = $this->getCheckDeliveryDateStatus($newdate);
                $shippingAddress->setGrandTotal($shippingAddress->getGrandTotal() - $shippingAddress->getShippingAmount());
                $shippingAddress->setBaseGrandTotal($shippingAddress->getGrandTotal() - $shippingAddress->getShippingAmount());
                $quote->setShippingAddress($shippingAddress);

                $this->_messageManager->addSuccessMessage(__('This is a success message'));

                //SHOW MESSAGE TO USER THAT “Your order will be shipped on date “deliveryDate”
                //proceed further considering the deliveryDate
            }
        } else {

            $shippingAddress->setGrandTotal($shippingAddress->getGrandTotal() - $shippingAddress->getShippingAmount());
            $shippingAddress->setBaseGrandTotal($shippingAddress->getGrandTotal() - $shippingAddress->getShippingAmount());
            $quote->setShippingAddress($shippingAddress);
        }
    }

    function getCheckDeliveryDateStatus($date) {

        static $canBeDelivered = 0;
        static $countOfOrders = 0;
        $deliverydatedata = $this->deliverydateconfigprovider->getConfig();
        $maxorders = $deliverydatedata['shipping']['deliverydatemethod']['maxOrders'];
        //countOfOrders = check in WEB DB about all Orders where deliveryDate= DELIVERYDATE AND STATUS != COMPLETED && DELIVERYTYPE = HOMEDELIVERY
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $allorder = $objectManager->create('\Magento\Sales\Model\Order')->getCollection()
                //->addFieldToFilter('status', 'pending')
                ->addFieldToFilter('status', array('neq' => 'complete'))
                ->addFieldToFilter('delivery_method', 'homedelivery')
                ->addFieldToFilter('delivery_date', array('eq' => $date));

        $countOfOrders = count($allorder);
        //        echo "ordercount==".$countOfOrders;echo  "<br>";
        //        echo "maxorders==".$maxorders;echo  "<br>";
        //        die();
        if ($countOfOrders < $maxorders) {
            $canBeDelivered = 1;
            $this->_configWriter->save('indusa_deliverydatemethod/general/show_hide_canBeDelivered', 1, 'default', 0);
        } else {
            $canBeDelivered = 0;
            $this->_configWriter->save('indusa_deliverydatemethod/general/show_hide_canBeDelivered', 0, 'default', 0);
        }
        return $canBeDelivered;
    }

    public function CheckBestDeliveryDate($date) {

        $status = false;
        $i = 0;
        while ($status === false) {
            $deliverydatedata = $this->deliverydateconfigprovider->getConfig();
            $maxorders = $deliverydatedata['shipping']['deliverydatemethod']['maxOrders'];
            //countOfOrders = check in WEB DB about all Orders where deliveryDate= DELIVERYDATE AND STATUS != COMPLETED && DELIVERYTYPE = HOMEDELIVERY
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $allorder = $objectManager->create('\Magento\Sales\Model\Order')->getCollection()
                    //->addFieldToFilter('status', 'pending')
                    ->addFieldToFilter('status', array('neq' => 'complete'))
                    ->addFieldToFilter('delivery_method', 'homedelivery')
                    ->addFieldToFilter('delivery_date', array('eq' => $date));
            $countOfOrders = count($allorder);

            if ($countOfOrders < $maxorders) {
                $canBeDelivered = 1;
                $status = true;
                $this->_configWriter->save('indusa_deliverydatemethod/general/show_hide_canBeDelivered', 1, 'default', 0);
            } else {
                $i++;
                $date = date('Y-m-d', strtotime($date . ' +' . $i . ' day'));
                $status = false;
                $this->_configWriter->save('indusa_deliverydatemethod/general/show_hide_canBeDelivered', 0, 'default', 0);
                //echo $date.">>>>>>>>>>>>>>";echo  "<br>";
                return $this->CheckBestDeliveryDate($date);
            }
        }
        return $date;
    }

}
