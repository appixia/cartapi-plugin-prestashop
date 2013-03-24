<?php

include(dirname(__FILE__).'/../../../../config/config.inc.php');

// preInit registers smarty l2 which we need for cross-tpl translations
// normally translations would only work if we rename success.tpl to payment_return.tpl
// if we want our own tpl name, we can "steal" translations from other tpl files using our l2
include_once(dirname(__FILE__).'/../../Helpers.php');
CartAPI_Handlers_Helpers::preInit();

require_once(dirname(__FILE__).'/../../../../init.php');

include(dirname(__FILE__).'/bankwire.php');

// create an instance of the payment module
$bankwire = new CartAPI_Module_BankWire();

// get some extra params
$cartOrder = getCartOrder();
if ($cartOrder === FALSE) die("ERROR: Cannot get cart order");
$orderTotal = $cartOrder->total_paid;
$cartCurrency = new Currency((int)($cartOrder->id_currency));

// set some smarty params
$smarty->assign(array(
	'total_to_pay' => Tools::displayPrice($orderTotal, $cartCurrency, false),
	'bankwireDetails' => nl2br2($bankwire->details),
	'bankwireAddress' => nl2br2($bankwire->address),
	'bankwireOwner' => $bankwire->owner,
	'id_order' => (int)$cartOrder->id
));

// output
$smarty->force_compile = true; // remove after tpl debug
$smarty->display(dirname(__FILE__).'/success.tpl');


//////////////////////////////////////////////////////////////////////////

function getCartOrder()
{
	global $cart;
	$id_order = Order::getOrderByCartId((int)($cart->id));
	if (!$id_order) return false;
	$cartOrder = new Order((int)($id_order));
	if (!Validate::isLoadedObject($cartOrder)) return false;
	return $cartOrder;
}

?>