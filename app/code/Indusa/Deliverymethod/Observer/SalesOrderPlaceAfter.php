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

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectmanager
     */
    public function __construct(
    \Magento\Framework\App\RequestInterface $request, \Magento\Framework\ObjectManagerInterface $objectmanager
    ) {
        $this->_request = $request;
        $this->_objectManager = $objectmanager;
    }

    /**
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer) {
        $reqeustParams = $this->_request->getParams();
        $order = $observer->getOrder();
        $quoteRepository = $this->_objectManager->create('Magento\Quote\Model\QuoteRepository');
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $quoteRepository->get($order->getQuoteId());

        //Order created from admin start
        if ($quote->getRemoteIp() == "" || $quote->getRemoteIp() == "null") {
            $quote->setAxStoreId(Service::WAREHOUSE_ID);
            $quote->setLocationId(Service::WAREHOUSE_ID);
            $quote->setDeliveryFrom("Warehouse");
            $quote->setDeliveryMethod("homedelivery");
            $quoteRepository->save($quote); // Save quote

            $order->setAxStoreId(Service::WAREHOUSE_ID);
            $order->setLocationId(Service::WAREHOUSE_ID);
            $order->setDeliveryFrom("Warehouse");
            $order->setDeliveryMethod("homedelivery");
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
