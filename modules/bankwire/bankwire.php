<?php

include_once(dirname(__FILE__).'/../../Helpers.php');

include_once(_PS_MODULE_DIR_.'bankwire/bankwire.php');

class CartAPI_Module_BankWire extends BankWire
{

	// returns false on failure, PaymentMethod dictionary on success
	public function Handle_GetPaymentMethod($order)
	{
		$method = array();
		
		$method['Title'] = 'BankWire';
		$method['Description'] = 'Pay with a bank wire transfer';
		$method['ThumbnailUrl'] = CartAPI_Handlers_Helpers::getCartApiHomeUrl().'modules/bankwire/icon.png';
		
		// config the handling module in the mobile engine
		
		$method['Module'] = 'WebPaymentActivity';
		
		$params = array();
		$params['Url'] = $params['Url'] = CartAPI_Handlers_Helpers::getCartApiHomeUrl().'modules/bankwire/validation.php';
		$params['CompleteTrigger'] = CartAPI_Handlers_Helpers::getShopBaseUrl().'order-confirmation.php';
		$params['CancelTrigger'] = CartAPI_Handlers_Helpers::getShopBaseUrl().'order.php';
		$method['ModuleParameters'] = $params;
		
		return $method;
	}
	
	// returns an OrderUpdate Update Value dictionary (order fields), false if nothing to update
	// should also return the status in $status
	public function Handle_GetOrderUpdateAfterPayment($order, $cartOrder, &$status)
	{
		$status = 'BankWireError';

		// check if payment was successful
		$state = $cartOrder->getCurrentState();
		if ($state == Configuration::get('PS_OS_BANKWIRE') OR $state == Configuration::get('PS_OS_OUTOFSTOCK'))
		{
			$status = 'BankWireSuccess';
		}
				
		// change the order Status field
		$update = array();
		$update['Status'] = $status;
		return $update;
	}
	
}

?>