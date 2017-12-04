<?php
// Method: POST, PUT, GET etc
// Data: array("param" => "value") ==> index.php?param=value

	error_reporting(E_ALL);
ini_set("display_errors",1);
echo "rest api2";
$xml = "<eCommerceAPI><username></username><password></password><requestID>1</requestID><serviceName>createOrderAndCustomer</serviceName><orders></orders></eCommerceAPI>";
$userData = array("salesOrderXML" => $xml,"requestId" => 1);
$ch = curl_init("http://62.215.130.187:8182/MagentoAx/MagentoAx.svc/postOrderDetails");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Content-Lenght: " . strlen(json_encode($userData))));
 
echo $ch;
$result = curl_exec($ch);
echo "<pre>";
var_dump(json_decode($result));

    ?>