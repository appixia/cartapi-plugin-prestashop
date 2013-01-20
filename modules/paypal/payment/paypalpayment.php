<?php

include_once(_PS_MODULE_DIR_.'paypal/payment/paypalpayment.php');

class CartAPI_Module_PayPalPayment extends PaypalPayment
{	
	// override for our error.php
	public function displayPayPalAPIError($message, $log = false)
	{
		// TODO: improve how this looks
		echo 'PayPalPayment error: '.$message;
		if ($log !== false) var_dump($log);
		exit;
	}
}

?>