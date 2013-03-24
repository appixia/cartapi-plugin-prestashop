<?php

include_once(dirname(__FILE__).'/../../Helpers.php');

include_once(_PS_MODULE_DIR_.'servired/servired.php');

class CartAPI_Module_Servired extends servired
{

	// returns false on failure, PaymentMethod dictionary on success
	public function Handle_GetPaymentMethod($order)
	{
		$method = array();
		
		$method['Title'] = 'Servired';
		$method['Description'] = 'Pay with your credit card';
		$method['ThumbnailUrl'] = CartAPI_Handlers_Helpers::getCartApiHomeUrl().'modules/servired/icon.png';
		
		// config the handling module in the mobile engine
		
		$method['Module'] = 'WebPaymentActivity';
		
		$params = array();
		$params['Url'] = CartAPI_Handlers_Helpers::getCartApiHomeUrl().'modules/servired/hookpayment.php';
		$params['CompleteTrigger'] = CartAPI_Handlers_Helpers::getShopBaseUrl();
		$method['ModuleParameters'] = $params;
		
		return $method;
	}
	
	// returns an OrderUpdate Update Value dictionary (order fields), false if nothing to update
	// should also return the status in $status
	public function Handle_GetOrderUpdateAfterPayment($order, $cartOrder, &$status)
	{
		$status = 'ServiredError';

		// check if payment was successful
		if (isset($order['PaymentDetails']))
		{
			// success has url params, failure doesnt
			if (isset($order['PaymentDetails']['id_module']))
			{
				$status = 'ServiredSuccess';
			}
		}
				
		// change the order Status field
		$update = array();
		$update['Status'] = $status;
		return $update;
	}
	
}

?>