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

namespace Indusa\Deliverymethod\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Indusa\Webservices\Model\Service;

class SalesOrderPlaceAfter implements \Magento\Framework\Event\ObserverInterface {

    protected $_request;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     */
    protected $_objectManager;
    protected $deliverydateconfigprovider;
    protected $_configWriter;
    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectmanager
     */
    public function __construct(
    \Magento\Framework\App\RequestInterface $request, \Indusa\Deliverymethod\Model\DeliveryDateConfigProvider $DeliveryDateConfigProvider,\Magento\Framework\App\Config\Storage\WriterInterface $configWriter,\Magento\Framework\ObjectManagerInterface $objectmanager
    ) {
        $this->_request = $request;
        $this->_objectManager = $objectmanager;
        $this->deliverydateconfigprovider = $DeliveryDateConfigProvider;
        $this->_configWriter = $configWriter;
    }

    /**
     * @param EventObserver $observer
     * @return void
     */
    
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
    
    
    public function execute(EventObserver $observer) {
        $reqeustParams = $this->_request->getParams();
        $order = $observer->getOrder();
        $quoteRepository = $this->_objectManager->create('Magento\Quote\Model\QuoteRepository');
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $quoteRepository->get($order->getQuoteId());

        //Order created from admin start
        if ($quote->getRemoteIp() == "" || $quote->getRemoteIp() == "null") {
            
            date_default_timezone_set('Asia/Kuwait');
            $date_currentdate_time = time();
            //$date = date('Y-m-d h:i:s a', $date_currentdate_time);
            $threePm = mktime(12); //
            if ($date_currentdate_time <= $threePm) {
                $checkdate = date('Y-m-d'); //today();
            } else {
                $checkdate = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
            }
			
            //Recursive logic to get best Deliverydate
            $newdate = $this->CheckBestDeliveryDate($checkdate);
            
            
            $quote->setAxStoreId(Service::WAREHOUSE_ID);
            $quote->setLocationId(Service::WAREHOUSE_ID);
            $quote->setDeliveryFrom("Warehouse");
            $quote->setDeliveryMethod("homedelivery");
            $quote->setDeliveryDate($newdate);
            $quoteRepository->save($quote); // Save quote

            $order->setAxStoreId(Service::WAREHOUSE_ID);
            $order->setLocationId(Service::WAREHOUSE_ID);
            $order->setDeliveryFrom("Warehouse");
            $order->setDeliveryMethod("homedelivery");
            $order->setDeliveryDate($newdate);
        }
        //Order created from admin end
        else {

            $order->setAxStoreId($quote->getAxStoreId());

            $order->setLocationId($quote->getLocationId());

            $order->setDeliveryFrom($quote->getDeliveryFrom());

            $order->setDeliveryMethod($quote->getDeliveryMethod());

            $order->setDeliveryDate($quote->getDeliveryDate());
            $order->setDeliveryComment($quote->getDeliveryComment());
        }
        
//                        $ordergrid = $this->_objectManager->create('\Magento\Sales\Model\ResourceModel\Order') ->load($order->getId());
//                        $ordergrid->setDeliveryFrom($quote->getDeliveryFrom());
//                        $ordergrid->save();
//        
        
    }

}
