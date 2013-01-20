<?php

include(dirname(__FILE__).'/../../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../../init.php');

include(dirname(__FILE__).'/../paypal.php');
include(dirname(__FILE__).'/paypalpayment.php');


// code taken from modules/paypal/payment/submit.php validOrder(), to display errors nicely

echo '<h1>Paypal Error</h1>';

if (!$cookie->isLogged())
    die('Error: not logged in');
    
    
if (!isset($cookie->paypal_token) OR !$cookie->paypal_token)
{
	// before authorization, try to get the authorization error again
	
	$ppPayment = new CartAPI_Module_PayPalPayment();
	
	$result = $ppPayment->getAuthorisation();
	$logs = $ppPayment->getLogs();
	$ppPayment->displayPayPalAPIError($ppPayment->l('Authorisation to PayPal failed', 'submit'), $logs);

}
else
{
	// after authorization

	$paypal = new CartAPI_Module_PayPal();

	if (!$payerID = Tools::htmlentitiesUTF8(strval(Tools::getValue('PayerID'))))
		die('Invalid payerID');
	
	$paypal->makePayPalAPIValidation($cookie, $cart, $cart->id_currency, $payerID, 'payment');
	
}
