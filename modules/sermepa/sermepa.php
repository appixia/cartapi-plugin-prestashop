<?php

include_once(dirname(__FILE__).'/../../Helpers.php');

// adyen has some notices...
include_once(_PS_MODULE_DIR_.'sermepa/sermepa.php');

class CartAPI_Module_Sermepa extends Sermepa
{

	// returns false on failure, PaymentMethod dictionary on success
	public function Handle_GetPaymentMethod($order)
	{
		$method = array();
		
		$method['Title'] = 'Sermepa';
		$method['Description'] = 'Pay with your credit card';
		
		// config the handling module in the mobile engine
		
		$method['Module'] = 'WebPaymentActivity';
		
		$params = array();
		$params['Url'] = CartAPI_Handlers_Helpers::getCartApiHomeUrl().'modules/sermepa/submit.php';
		$params['CompleteTrigger'] = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'order-confirmation.php';
		$method['ModuleParameters'] = $params;
		
		return $method;
	}
	
	// returns an OrderUpdate Update Value dictionary (order fields), false if nothing to update
	// should also return the status in $status
	public function Handle_GetOrderUpdateAfterPayment($order, $cartOrder, &$status)
	{
		// prepare the fields for the adyen code
		if (isset($order['PaymentDetails']))
		{
			$_GET['Ds_Response'] = urldecode($order['PaymentDetails']['Ds_Response']);
			$_GET['Ds_Order'] = urldecode($order['PaymentDetails']['Ds_Order']);
		}
		
		// run the module's hook payment return since it updates the order there
		$params = array('objOrder' => $cartOrder);
		$this->hookPaymentReturn($params);
		
		$error_msg = $smarty->get_template_vars('error_msg');
		if (isset($error_msg) && $error_msg) $status = 'SermepaError';
		else $status = 'SermepaAuthorized';
				
		// change the order Status field
		$update = array();
		$update['Status'] = $status;
		return $update;
	}
	
}

?>