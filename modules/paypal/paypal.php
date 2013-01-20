<?php

include_once(dirname(__FILE__).'/../../Helpers.php');

include_once(_PS_MODULE_DIR_.'paypal/paypal.php');

class CartAPI_Module_PayPal extends PayPal
{

	// returns false on failure
	public function Handle_GetPaymentMethod($order)
	{
		$method = array();
		
		$method['Title'] = 'PayPal';
		$method['Description'] = 'Pay with your PayPal account';
		
		// config the handling module in the mobile engine
		
		$method['Module'] = 'WebPaymentActivity';
		$params = array();
		
		if (Configuration::get('PAYPAL_PAYMENT_METHOD') == _PAYPAL_INTEGRAL_EVOLUTION_)
		{
			// integral_evolution/paypal.tpl
			$params['Url'] = CartAPI_Handlers_Helpers::getShopBaseUrl().'modules/paypal/integral_evolution/redirect.php';
			$params['CompleteTrigger'] = PayPal::getShopDomain(true, true).__PS_BASE_URI__.'order-confirmation.php';
			$params['CancelTrigger'] = PayPal::getShopDomain(true, true).__PS_BASE_URI__;
		}
		elseif (Configuration::get('PAYPAL_PAYMENT_METHOD') == _PAYPAL_INTEGRAL_ OR Configuration::get('PAYPAL_PAYMENT_METHOD') == _PAYPAL_OPTION_PLUS_)
		{
			if ($this->_isPayPalAPIAvailable())
			{
				// payment/payment.tpl
				$params['Url'] = CartAPI_Handlers_Helpers::getCartApiHomeUrl().'modules/paypal/payment/submit.php';
				$params['CompleteTrigger'] = CartAPI_Handlers_Helpers::getShopBaseUrl().'order-confirmation.php';
				$params['CancelTrigger'] = PayPal::getShopDomainSsl(true, true).__PS_BASE_URI__.'order'; // either order.php or order-opc.php
				$params['RedirectTrigger'] = array(
					'Trigger' => CartAPI_Handlers_Helpers::getShopBaseUrl().'modules/paypal/payment/submit.php',
					'Redirect' => CartAPI_Handlers_Helpers::getCartApiHomeUrl().'modules/paypal/payment/error.php',
				);
			}
			else
			{
				// standard/paypal.tpl
				$params['Url'] = CartAPI_Handlers_Helpers::getShopBaseUrl().'modules/paypal/standard/redirect.php';
				$params['CompleteTrigger'] = PayPal::getShopDomain(true, true).__PS_BASE_URI__.'order-confirmation.php';
				$params['CancelTrigger'] = PayPal::getShopDomain(true, true).__PS_BASE_URI__;
			}
		}
		
		$method['ModuleParameters'] = $params;
		
		return $method;
	}
	
	// returns an OrderUpdate Update Value dictionary (order fields), false if nothing to update
	// should also return the status in $status
	public function Handle_GetOrderUpdateAfterPayment($order, $cartOrder, &$status)
	{
		// the original module's hookPaymentReturn is pretty much empty
		$status = 'PayPalSuccess';
		$update = array();
		$update['Status'] = $status;
		return $update;
	}
	
	// copied since private
	private function _isPayPalAPIAvailable()
	{
		if (Configuration::get('PAYPAL_API_USER') != NULL AND Configuration::get('PAYPAL_API_PASSWORD') != NULL AND Configuration::get('PAYPAL_API_SIGNATURE') != NULL)
			return true;
		return false;
	}
	
	// override for our error.php
	public function displayPayPalAPIError($message, $log = false)
	{
		// TODO: improve how this looks
		echo 'PayPal error: '.$message;
		if ($log !== false) var_dump($log);
		exit;
	}
}

?>