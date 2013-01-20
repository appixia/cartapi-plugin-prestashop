<?php

include_once(dirname(__FILE__).'/../../Helpers.php');

include_once(_PS_MODULE_DIR_.'kiala/kiala.php');

class CartAPI_Module_Kiala extends Kiala
{

	// code taken from module kiala function displayPoint
	public static function updateShippingMethodDictionary(&$method)
	{
		global $cart;
		
		// this module is the return url to add the kiala order (with the point) after selection
		$returnUrl = CartAPI_Handlers_Helpers::getCartApiHomeUrl().'modules/kiala/return.php?';
		
		$address = new Address($cart->id_address_delivery);
		$kiala_request = new KialaRequest();
		$url = $kiala_request->getSearchRequest($address, $cart->id_lang, $returnUrl);
		
		$method['Module'] = 'WebShippingActivity';
		$params = array();
		$params['Url'] = $url;
		$params['CompleteTrigger'] = CartAPI_Handlers_Helpers::getShopBaseUrl(); // return.php redirects there after finishing
		$method['ModuleParameters'] = $params;
	}
	
}

?>