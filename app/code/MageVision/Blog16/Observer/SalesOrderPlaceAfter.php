<?php
namespace MageVision\Blog16\Observer;


use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

    class SalesOrderPlaceAfter implements \Magento\Framework\Event\ObserverInterface
    {

        protected $_request;
        /**
         * @param \Magento\Framework\App\RequestInterface $request
         */
        
        protected $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectmanager
     */
        
        public function __construct(
            \Magento\Framework\App\RequestInterface $request,
            \Magento\Framework\ObjectManagerInterface $objectmanager
        ) {
            $this->_request = $request;
            $this->_objectManager = $objectmanager;
        }


        /**
         * @param EventObserver $observer
         * @return void
         */
        public function execute(EventObserver $observer)
        { 

                //working code
               //$order->setAxStoreId(555);
                $reqeustParams = $this->_request->getParams();
 
                $order = $observer->getOrder();
 
                $quoteRepository = $this->_objectManager->create('Magento\Quote\Model\QuoteRepository');
                /** @var \Magento\Quote\Model\Quote $quote */
                $quote = $quoteRepository->get($order->getQuoteId());
                
                $order->setAxStoreId( $quote->getAxStoreId() );
                
                $order->setLocationId( $quote->getLocationId() );
                
                $order->setDeliveryFrom( $quote->getDeliveryFrom() );
                
                $order->setDeliveryMethod( $quote->getDeliveryMethod() );
               
                
                
        }
        
    }
    
