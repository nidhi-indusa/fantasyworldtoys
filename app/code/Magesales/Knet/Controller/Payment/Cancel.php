<?php
namespace Magesales\Knet\Controller\Payment;
use Magesales\Knet\Controller\Main;
use Magento\Framework\Controller\ResultFactory;

class Cancel extends Main
{
    public function execute()
    {
		$resultRedirect = $this->resultRedirectFactory->create();
		$session = $this->checkoutSession;
        $errorMsg = __(' There was an error occurred during paying process.');
		$orderIncrementId = '';
		
		if(array_key_exists('OrderID', $_GET))
		{
			$orderIncrementId = $_GET['OrderID'];
		}
		else
		{
			$orderIncrementId = $session->getLastRealOrderId();
		}
		    
		if ($orderIncrementId)
		{
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($orderIncrementId);
						
			$orderPayment = $objectManager->create('Magento\Sales\Model\Order\Payment')->load($order->getId());
			
			$amount = round( $order->getGrandTotal(), 3 );
			if($order->getId() && array_key_exists('PaymentID', $_GET))
			{
				//Read URL params
				$paymentID = isset($_GET['PaymentID']) ? $_GET['PaymentID'] : '';
				$presult = isset($_GET['Result']) ? $_GET['Result'] : '';
				$postdate = isset($_GET['PostDate']) ? $_GET['PostDate'] : '';
				$tranid = isset($_GET['TranID']) ? $_GET['TranID'] : '';
				$auth = isset($_GET['Auth']) ? $_GET['Auth'] : '';
				$ref = isset($_GET['Ref']) ? $_GET['Ref'] : '';
				$trackid = isset($_GET['TrackID']) ? $_GET['TrackID'] : '';
				
				$message = 'KNET has declined the payment. <br/><br/>KNET Payment Details:<br/>';
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
				
				$order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
				$order->addStatusToHistory($order->getStatus(),$message);
				$order->save();
			}
			$this->_view->loadLayout();			
			$this->_view->renderLayout();
		}
			
    }
}