<?php

include_once(dirname(__FILE__).'/../../Helpers.php');

include_once(_PS_MODULE_DIR_.'cuatrob/cuatrob.php');

class CartAPI_Module_Cuatrob extends cuatrob
{

	// returns false on failure, PaymentMethod dictionary on success
	public function Handle_GetPaymentMethod($order)
	{
		$method = array();
		
		$method['Title'] = 'Pago con tarjeta';
		$method['Description'] = 'Conexion segura con Pasat 4B';
		
		// config the handling module in the mobile engine
		
		$method['Module'] = 'WebPaymentActivity';
		
		$params = array();
		$params['Url'] = CartAPI_Handlers_Helpers::getCartApiHomeUrl().'modules/cuatrob/submit.php';
		$params['CompleteTrigger'] = CartAPI_Handlers_Helpers::getShopBaseUrl().'modules/cuatrob/resultado.php';
		$method['ModuleParameters'] = $params;
		
		return $method;
	}
	
	// returns an OrderUpdate Update Value dictionary (order fields), false if nothing to update
	// should also return the status in $status
	public function Handle_GetOrderUpdateAfterPayment($order, $cartOrder, &$status)
	{
		if (isset($order['PaymentDetails']))
		{
			if (isset($order['PaymentDetails']['result'])) $_GET["result"] = urldecode($order['PaymentDetails']['result']);
		}
		
		$status = 'CuatrobError';
		if (isset($_GET["result"]))
		{
			$result	 = $_GET["result"];
			if ($result == 0) $status = 'CuatrobCorrecto';
		}
						
		// change the order Status field
		$update = array();
		$update['Status'] = $status;
		return $update;
	}
	
}

?>