<?php

include_once(dirname(__FILE__).'/../../Helpers.php');

// adyen has some notices...
include_once(_PS_MODULE_DIR_.'adyen/adyen.php');

class CartAPI_Module_Adyen extends Adyen
{

	// returns false on failure, PaymentMethod dictionary on success
	public function Handle_GetPaymentMethod($order)
	{
		$method = array();
		
		$method['Title'] = 'Adyen';
		$method['Description'] = 'Pay with your credit card';
		
		// config the handling module in the mobile engine
		
		$method['Module'] = 'WebPaymentActivity';
		
		$params = array();
		$params['Url'] = CartAPI_Handlers_Helpers::getCartApiHomeUrl().'modules/adyen/submit.php';
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
			$_GET['authResult'] = urldecode($order['PaymentDetails']['authResult']);
			$_GET['pspReference'] = urldecode($order['PaymentDetails']['pspReference']);
			$_GET['merchantReference'] = urldecode($order['PaymentDetails']['merchantReference']);
			$_GET['skinCode'] = urldecode($order['PaymentDetails']['skinCode']);
			$_GET['merchantSig'] = urldecode($order['PaymentDetails']['merchantSig']);
		}
		
		// run the module's hook payment return since it updates the order there
		$params = array('objOrder' => $cartOrder);
		$this->hookPaymentReturn($params);
		
		$authResult = $_GET['authResult'];
		$status = 'AdyenError';
		switch ($authResult) {
			case 'AUTHORISED':
				$status = 'AdyenAuthorized';
				break;
			case 'PENDING':
				$status = 'AdyenPending';
				break;
			case 'REFUSED':
				$status = 'AdyenRefused';
				break;
			case 'CANCELLED':
				$status = 'AdyenCancelled';
				break;
			default:
				break;
		}
		
		// change the order Status field
		$update = array();
		$update['Status'] = $status;
		return $update;
	}
	
}

?>