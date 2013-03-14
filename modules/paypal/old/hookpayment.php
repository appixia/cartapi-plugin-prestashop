<?php

include(dirname(__FILE__).'/../../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../../init.php');

include(dirname(__FILE__).'/../paypal.php');

// create an instance of the payment module
$paypal = new CartAPI_Module_PayPal();

// run the original module's hookpayment and ignore the result, this fills all the info in smarty
$params = array('cart' => $cart);
$paypal->hookPayment($params);

// output
$smarty->force_compile = true; // remove after tpl debug
$smarty->display(dirname(__FILE__).'/paypal.tpl');

?>