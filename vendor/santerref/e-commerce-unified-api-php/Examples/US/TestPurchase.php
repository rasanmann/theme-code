<?php

require "../../mpgClasses.php";

/************************ Request Variables **********************************/

$store_id='monusqa002';
$api_token='qatoken';
//$status = 'false';

/************************ Transaction Variables ******************************/

$orderid='ord-'.date("dmy-G:i:s");
$amount='1.00';
$pan='4242424242424242';
$expiry_date='1412';
$dynamic_descriptor='test';

/************************ Transaction Array **********************************/

$txnArray=array(type=>'purchase',  
         order_id=>$orderid,
         cust_id=>'cust',
         amount=>$amount,
         pan=>$pan,
         expdate=>$expiry_date,
         crypt_type=>'7', 
         commcard_invoice=>'Invoice 5757FRJ8',
         commcard_tax_amount=>'0.15',
         dynamic_descriptor=>$dynamic_descriptor
           );


/************************ Transaction Object *******************************/

$mpgTxn = new mpgTransaction($txnArray);

/************************ Request Object **********************************/

$mpgRequest = new mpgRequest($mpgTxn);
$mpgRequest->setProcCountryCode("US"); //"CA" for sending transaction to Canadian environment
$mpgRequest->setTestMode(true); //false or comment out this line for production transactions

/************************ mpgHttpsPost Object ******************************/

$mpgHttpPost  =new mpgHttpsPost($store_id,$api_token,$mpgRequest);

//Status check example
//$mpgHttpPost = new mpgHttpsPostStatus($store_id,$api_token,$status,$mpgRequest);

/************************ Response Object **********************************/

$mpgResponse=$mpgHttpPost->getMpgResponse();


print("\nCardType = " . $mpgResponse->getCardType());
print("\nTransAmount = " . $mpgResponse->getTransAmount());
print("\nTxnNumber = " . $mpgResponse->getTxnNumber());
print("\nReceiptId = " . $mpgResponse->getReceiptId());
print("\nTransType = " . $mpgResponse->getTransType());
print("\nReferenceNum = " . $mpgResponse->getReferenceNum());
print("\nISO = " . $mpgResponse->getISO());
print("\nResponseCode = " . $mpgResponse->getResponseCode());
print("\nMessage = " . $mpgResponse->getMessage());
print("\nAuthCode = " . $mpgResponse->getAuthCode());
print("\nComplete = " . $mpgResponse->getComplete());
print("\nTransDate = " . $mpgResponse->getTransDate());
print("\nTransTime = " . $mpgResponse->getTransTime());
print("\nTicket = " . $mpgResponse->getTicket());
print("\nTimedOut = " . $mpgResponse->getTimedOut());
print("\nCardLevelResult = " . $mpgResponse->getCardLevelResult());
//print("\nStatusCode = " . $mpgResponse->getStatusCode());
//print("\nStatusMessage = " . $mpgResponse->getStatusMessage());


?>
