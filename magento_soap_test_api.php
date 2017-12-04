<?php
//echo phpinfo();
	error_reporting(E_ALL);
ini_set("display_errors",1);
$opts = array(
            'http'=>array(
                'header' => 'Authorization: Bearer o0fd7c0ft0a530sb2ntsqwma5g1n9yl9'
            ),
			'ssl' => array(
    'local_cert' => '/etc/pki/tls/certs/www_fantasyworldtoys_com.crt'
		)
        );
$wsdlUrl = 'https://ecom.fantasyworldtoys.com/fantasyworldtoys/soap/default?wsdl&services=catalogProductRepositoryV1';
$serviceArgs = array("id"=>1);

$context = stream_context_create($opts);
$soapClient = new SoapClient($wsdlUrl, ['version' => SOAP_1_2, 'context' => $context]);

var_dump($soapClient); die;
$soapResponse = $soapClient->catalogProductRepositoryV1GetList($serviceArgs);
var_dump($soapResponse);
die;
