<?php
namespace Magesales\Knet\Controller\Payment;
use Magesales\Knet\Controller\Main;
use Magento\Framework\Controller\ResultFactory;

class Success extends Main
{
    public function execute()
    {
		$resultRedirect = $this->resultRedirectFactory->create();
		$session = $this->checkoutSession;
		$orderIncrementId = $session->getLastRealOrderId();
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderIncrementId);
		$orderPayment = $objectManager->create('Magento\Sales\Model\Order\Payment')->load($order->getId());
		$amount = round( $order->getGrandTotal(), 3 );
        
		$paymentID = isset($_GET['PaymentID']) ? $_GET['PaymentID'] : '';
		$presult = isset($_GET['Result']) ? $_GET['Result'] : '';
		$postdate = isset($_GET['PostDate']) ? $_GET['PostDate'] : '';
		$tranid = isset($_GET['TranID']) ? $_GET['TranID'] : '';
		$auth = isset($_GET['Auth']) ? $_GET['Auth'] : '';
		$ref = isset($_GET['Ref']) ? $_GET['Ref'] : '';
		$trackid = isset($_GET['TrackID']) ? $_GET['TrackID'] : '';		
		
		if ($presult == 'CAPTURED'){
			$message = 'KNET Payment Details:<br/>';
			$message .= 'PaymentID: ' . $paymentID . "<br/>";
			$message .= 'Amount: ' . $amount . "<br/>";
			$message .= 'Result: ' . $presult . "<br/>";
			$message .= 'PostDate: ' . $postdate . "<br/>";
			$message .= 'TranID: ' . $tranid . "<br/>";
			$message .= 'Auth: ' . $auth . "<br/>";
			$message .= 'Ref: ' . $ref . "<br/>";
			$message .= 'TrackID: ' . $trackid . "<br/>";
			$message .= 'Time: ' . date('H:i:s') . "<br/>";
			
			//Add Order payment data in sales_order_payment table
			$orderPaymentTransactionData = json_encode($_GET);			
			$orderPayment->setAdditionalData($orderPaymentTransactionData);
			$orderPayment->save();
				
			$order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
			$order->addStatusToHistory($order->getStatus(),$message);
			$objectManager->create('Magento\Sales\Model\OrderNotifier')->notify($order);						
			$order->save();
			
			$this->_view->loadLayout();     		
			$this->_view->renderLayout();
		}
		else 
		{
			$url = $this->_storeManager->getStore()->getBaseUrl().'knet/payment/cancel/';
			$result_params = "?PaymentID=" . $paymentID . "&Result=" . $presult . "&PostDate=" . $postdate . "&TranID=" . $tranid . "&Auth=" . $auth . "&Ref=" . $ref . "&TrackID=" . $trackid ."&OrderID=" .$orderIncrementId ."&Live=" .$orderIncrementId;
			$this->_redirect('knet/payment/cancel'.$result_params);
		}
	}
}