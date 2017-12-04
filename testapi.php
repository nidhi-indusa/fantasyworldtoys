<?php
	//echo phpinfo();
	error_reporting(E_ALL);
ini_set("display_errors",1);
/*$opts = array(
            'http'=>array(
                'header' => 'Authorization: Bearer o0fd7c0ft0a530sb2ntsqwma5g1n9yl9'
            ),
			'ssl' => array(
    'local_cert' => '/etc/pki/tls/certs/www_fantasyworldtoys_com.crt'
		)
        );
$wsdlUrl = 'https://ecom.fantasyworldtoys.com/fantasyworldtoys/soap/default?wsdl&services=catalogProductRepositoryV1';
$serviceArgs = array("sku"=>'A46-PC-1850');

$context = stream_context_create($opts);
$soapClient = new SoapClient($wsdlUrl, ['version' => SOAP_1_1, 'context' => $context]);

var_dump($soapClient); 
$soapResponse = $soapClient->catalogProductRepositoryV1DeleteById($serviceArgs);
var_dump($soapResponse);
die;*/

$old = ini_get('default_socket_timeout');
ini_set('max_execution_time', 120);
ini_set('default_socket_timeout', 120);
ini_set('soap.wsdl_cache_enabled',0);
ini_set('soap.wsdl_cache_ttl',0);

  $arrOptions = array(
        'soap_version' => SOAP_1_2,
        'exceptions' => true,
        'trace' => 1,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'login' => 'SafariHouse\indusa',
        'password' => 'Test123@',
		'connection_timeout' => 3600	
    );     

	  //$strWSDl = 'http://62.215.130.187:8301/MicrosoftDynamicsAXAif60/test/xppservice.svc?wsdl';
	//$strWSDl = "http://192.168.0.227/MicrosoftDynamicsAXAif60/Test/xppservice.svc";
	//$strWSDl = "http://www.i2file.com/I2F/services/I2FWebService.wsdl";
   $strWSDl = "http://62.215.130.187:8182/CustomerService.svc?wsdl";
    try {    
	//echo "<pre>s";
		echo "fantasy-test7vov";
        $objClient = new SoapClient($strWSDl, $arrOptions);		 
		$actionHeader = new SoapHeader('http://www.w3.org/2005/08/addressing',
                              'http://tempuri.org/ICustomerService/getCustomerName',
                               'http://62.215.130.187:8182/CustomerService.svc?wsdl');
		$objClient->__setSoapHeaders($actionHeader);		 
		var_dump($objClient->__getFunctions()); 
		//print_r($objClient->__getTypes () ); 
		 
		 
		/* $requestHeaders = $objClient->__getLastRequestHeaders();
$request = $objClient->__getLastRequest();
$responseHeaders = $objClient->__getLastResponseHeaders();
printf("\nRequest Headers -----\n");
print_r($requestHeaders);
printf("\nRequest -----\n");
print_r($request);
printf("\nResponse Headers -----\n");
print_r($responseHeaders);
printf("\nEND\n");
die;*/
//var_dump($objClient); die;
        echo '<h2>Available functions:</h2>';   
        $params = array('AcctNumber' => 99938171);
        
        try {
            //var_dump($objClient->getCustomerName($params));
			var_dump($objClient->getCustomerName($params));
        } catch (SoapFault $arrSOAPFault) {
           echo ('<p style="color:red;">SOAP Fault: <b>'.$arrSOAPFault->faultcode.' '.$arrSOAPFault->faultstring.'</b></p>');
        }        
    } catch (Exception $objError) {
        echo '<b>'.$objError->getMessage().'</b>';         
    }
	