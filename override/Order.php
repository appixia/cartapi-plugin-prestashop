<?php

// this is an override class related to the buyer's cart and checkout
// if you need to customize the module to your needs, make all changes here

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Order.php');

class CartAPI_Handlers_Override_Order extends CartAPI_Handlers_Order
{
	// 	override any functions you want to change (from the core Order.php) here
}

?>