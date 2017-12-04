<?php
	use Magento\Framework\App\Bootstrap;
	include('../app/bootstrap.php');
	$bootstrap = Bootstrap::create(BP, $_SERVER);
	$objectManager = $bootstrap->getObjectManager();
	$storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface'); 
	$baseUrl= $storeManager->getStore()->getBaseUrl();
	
	$paymentID = isset($_REQUEST['paymentid']) ? $_REQUEST['paymentid'] : '';
	$presult = isset($_REQUEST['result']) ? $_REQUEST['result'] : '';
	$postdate = isset($_REQUEST['postdate']) ? $_REQUEST['postdate'] : '';
	$tranid = isset($_REQUEST['tranid']) ? $_REQUEST['tranid'] : '';
	$auth = isset($_REQUEST['auth']) ? $_REQUEST['auth'] : '';
	$ref = isset($_REQUEST['ref']) ? $_REQUEST['ref'] : '';
	$trackid = isset($_REQUEST['trackid']) ? $_REQUEST['trackid'] : '';
	$udf1 = isset($_REQUEST['udf1']) ? $_REQUEST['udf1'] : '';
	$udf2 = isset($_REQUEST['udf2']) ? $_REQUEST['udf2'] : '';
	$udf3 = isset($_REQUEST['udf3']) ? $_REQUEST['udf3'] : '';
	$udf4 = isset($_REQUEST['udf4']) ? $_REQUEST['udf4'] : '';
	$udf5 = isset($_REQUEST['udf5']) ? $_REQUEST['udf5'] : '';
	
	$url = $baseUrl . "knet/payment/success";	
	$result_params = "?PaymentID=" . $paymentID . "&Result=" . $presult . "&PostDate=" . $postdate . "&TranID=" . $tranid . "&Auth=" . $auth . "&Ref=" . $ref . "&TrackID=" . $trackid . "&UDF1=" . $udf1 . "&UDF2=" .$udf2  . "&UDF3=" . $udf3  . "&UDF4=" . $udf4 . "&UDF5=" . $udf5;
	\Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug(print_r(['Success.php Called' => $url . $result_params], true));
	
	// Redirect to payment success page	
	echo 'REDIRECT=' . $url . $result_params;
	exit;
	
?>