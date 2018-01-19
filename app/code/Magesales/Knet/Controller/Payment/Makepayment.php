<?php
namespace Magesales\Knet\Controller\Payment;
use Magesales\Knet\Controller\Main;
use Magento\Framework\Controller\ResultFactory;

use Indusa\Webservices\Model\Service;

class Makepayment extends Main
{
    public function execute()
    {
		$resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
		$session = $this->checkoutSession;
		$config = \Magento\Framework\App\Filesystem\DirectoryList::getDefaultConfig();
        require_once(BP . '/' . $config['base']['path'] . "/knet/e24PaymentPipe.inc.php");
        $session->setKnetPaymentQuoteId($session->getQuoteId());
		
		$orderIncrementId = $session->getLastRealOrderId();
		$session->setKnetOrder($orderIncrementId);
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderIncrementId);
		
		$successUrl = $this->_storeManager->getStore()->getUrl('',array('_secure'=>true)). 'knet/success.php';
		$errorUrl = $this->_storeManager->getStore()->getUrl('',array('_secure'=>true)). 'knet/error.php';
		
		$resourcePath = BP . '/' . $config['base']['path'] . "/knet/";
		$trackID = date('YmdHis');
		$payment = new \e24PaymentPipe;
		$payment->setErrorUrl($errorUrl);
		$payment->setResponseURL($successUrl);
		$payment->setLanguage('ENG');
		$payment->setCurrency('414'); //414 for KD
		$payment->setResourcePath($resourcePath);
		$payment->setAlias($this->getConfig('alias'));
		$payment->setAction("1"); // 1 = Purchase
		$payment->setAmt(round( $order->getBaseGrandTotal(), 3 ));
		$payment->setTrackId($trackID);
		//$payment->setRef($orderIncrementId);
		//$payment->setUdf1($orderIncrementId);
		$payment->performPaymentInitialization();
		
		$resultRedirect = $this->resultRedirectFactory->create();
		if (strlen($payment->getErrorMsg()) > 0) 
		{
			$this->messageManager->addError($payment->getErrorMsg());
        	$resultRedirect->setPath('checkout/cart');
        	return $resultRedirect;
        }
		else
		{	
			$message = 'KNET Details:<br/>';
			$message .= 'PaymentID: ' . $payment->paymentId . "<br/>";
			$message .= 'TrackID: ' . $trackID . "<br/>";
			$message .= 'Amount: ' . round( $order->getBaseGrandTotal(), 3 ) . "<br/>";
			$message .= 'Time: ' . date('d-m-Y H:i:s') . "<br/>";
			//Save details in DB before redirecting user to KNET. Else redirect to cart.
			if($order)
			{
				$knetUrl = $payment->paymentPage . '?PaymentID=' . $payment->paymentId;
				
                                
                                //Code added start
                                
                                if($order->getLocationId()  == ""){
                                    $order->setAxStoreId(Service::WAREHOUSE_ID);
                                    $order->setLocationId(Service::WAREHOUSE_ID);
                                    $order->setDeliveryFrom("Warehouse");
                                    $order->setDeliveryMethod("homedelivery");
                                }
                                //Code added end
                                
                                $order->setState(\Magento\Sales\Model\Order::STATE_NEW, true, $message );
				$order->save();
				
				header('Location: ' . $knetUrl);
				exit();
			}
			else
			{
				$resultRedirect->setPath('checkout/cart');
        		return $resultRedirect;
			}
		}

    }
}