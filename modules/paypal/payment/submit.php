<?php

include(dirname(__FILE__).'/../../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../../init.php');

include(dirname(__FILE__).'/../paypal.php');

// code taken from modules/paypal/payment/submit.php displayConfirm(), to skip the extra confirmation page

if (!$cookie->isLogged())
    die('Error: not logged in');
    
$paypal = new CartAPI_Module_PayPal();

unset($cookie->paypal_token);

if ($cart->id_currency != $paypal->getCurrency((int)$cart->id_currency)->id)
{
	$cart->id_currency = (int)($paypal->getCurrency((int)$cart->id_currency)->id);
	$cookie->id_currency = (int)($cart->id_currency);
	$cart->update();
}

$currency_id = $paypal->getCurrency((int)$cart->id_currency)->id;

Tools::redirect('modules/paypal/payment/submit.php?submitPayment=1&currency_payement='.$currency_id.'&');