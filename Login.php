<?php
/*
* 
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0).
* It is available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to info@appixia.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to newer
* versions in the future. If you wish to customize the module for your
* needs please look at the /override module directory or refer to
* http://kb.appixia.com for more information.
*
*/

class CartAPI_Handlers_Login
{

	public function Handle_BuyerLogin($metadata, $request, $encoder)
	{
		// login with user and password
		if (isset($request['Username']) && isset($request['Password']))
		{
			// dies on error, syncs cookie on success
			$this->handleBuyerLoginUserPassword($metadata, $request, $encoder);
		}
		else // login with an external service
		if (isset($request['AuthServiceName']) && isset($request['AuthServiceToken']))
		{
			// dies on error, syncs cookie on success
			$this->handleBuyerLoginAuthService($metadata, $request, $encoder);
		}
		else CartAPI_Helpers::dieOnError($encoder, 'IncompleteRequest', 'Login arguments missing');
		
		// if here we assume the customer is authenticated and the cookie is synced
		
		// create the response
		$response = CartAPI_Helpers::createSuccessResponse($encoder);
		
		// add the session id
		$encoder->addString($response, 'SessionId', $this->getCookieSessionId());
	
		// show the response
		$encoder->render($response);
	}

	public function Handle_BuyerRegister($metadata, $request, $encoder)
	{
		// required arguments
		if (!isset($request['Buyer'])) CartAPI_Helpers::dieOnError($encoder, 'IncompleteRequest', 'Buyer argument missing');
	
		// register with user and password
		if (isset($request['Buyer']['Username']) && isset($request['Buyer']['Password']))
		{
			// dies on error, syncs cookie on success
			$this->handleBuyerRegisterUserPassword($metadata, $request, $encoder);
		}
		else // login with an external service
		if (isset($request['Buyer']['AuthServiceName']) && isset($request['Buyer']['AuthServiceToken']))
		{
			// dies on error, syncs cookie on success
			$this->handleBuyerRegisterAuthService($metadata, $request['Buyer'], $encoder);
		}
		else CartAPI_Helpers::dieOnError($encoder, 'IncompleteRequest', 'Register arguments missing');
		
		// if here we assume the customer is authenticated and the cookie is synced
		
		// create the response
		$response = CartAPI_Helpers::createSuccessResponse($encoder);
		
		// see if we need to login too, then add the session id
		if (!isset($request['Login']) || ($request['Login'] == 'true'))
		{
			$encoder->addString($response, 'SessionId', $this->getCookieSessionId());
		}
	
		// show the response
		$encoder->render($response);
	}

	public function getCookieSessionId()
	{
		global $cookie;
		if (method_exists('Cookie','getName')) return $cookie->getName();
		else return md5('ps'._COOKIE_KEY_); // old prestashop versions don't have the getName method on Cookie
	}

	// syncs the current cookie with the given $customer (Prestashop object)
	public function syncCookie($customer)
	{
		global $cookie, $cart;
		
		$cookie->id_customer = (int)($customer->id);
		$cookie->customer_lastname = $customer->lastname;
		$cookie->customer_firstname = $customer->firstname;
		$cookie->logged = 1;
		if (method_exists('Customer','isGuest')) $cookie->is_guest = $customer->isGuest();
		$cookie->passwd = $customer->passwd;
		$cookie->email = $customer->email;
		
		// try to reuse the last cart (which wasn't ordered of course) of this logged in customer
		if (Configuration::get('PS_CART_FOLLOWING') AND (empty($cookie->id_cart) OR Cart::getNbProducts($cookie->id_cart) == 0))
			$cookie->id_cart = (int)(Cart::lastNoneOrderedCart((int)($customer->id)));
			
		// fix the secure key if we have a cart
		if (Validate::isLoadedObject($cart))
		{
			$cart->secure_key = $customer->secure_key;
			$cart->update();
		}
		
		return $cookie;
	}

	public function handleBuyerLoginUserPassword($metadata, $request, $encoder)
	{
		// code from AuthController SubmitLogin
	
		$email = $request['Username'];
		$passwd = $request['Password'];
		$customer = new Customer();
		if (!Validate::isEmail($email) OR ($passwd AND !Validate::isPasswd($passwd))) CartAPI_Helpers::dieOnError($encoder, 'LoginNotAuthorized', CartAPI_Handlers_Helpers::removeHtmlTags(Tools::displayError('Authentication failed')));
		$authentication = $customer->getByEmail(trim($email), trim($passwd));	
		if (!$authentication OR !$customer->id)
		{
			/* Handle brute force attacks */
			sleep(1);
			CartAPI_Helpers::dieOnError($encoder, 'LoginNotAuthorized', CartAPI_Handlers_Helpers::removeHtmlTags(Tools::displayError('Authentication failed')));
		}
	
		// if here than passed authentication
		$this->syncCookie($customer);
		
		// run the after login events
		$this->afterBuyerLogin($customer);
	}
	
	// override to implement
	public function handleBuyerLoginAuthService($metadata, $request, $encoder)
	{
		CartAPI_Helpers::dieOnError($encoder, 'UnsupportedAuthService', 'Service not supported');
	}
	
	public function handleBuyerRegisterUserPassword($metadata, $request, $encoder)
	{
		// prepare the fields inside the POST (so we can use Prestashop's validateController)
		unset($_POST['email']);
		if (isset($request['Buyer']['Username'])) $_POST['email'] = $request['Buyer']['Username'];
		unset($_POST['passwd']);
		if (isset($request['Buyer']['Password'])) $_POST['passwd'] = $request['Buyer']['Password'];
		unset($_POST['firstname']);
		if (isset($request['Buyer']['FirstName'])) $_POST['firstname'] = $request['Buyer']['FirstName'];
		unset($_POST['lastname']);
		if (isset($request['Buyer']['LastName'])) $_POST['lastname'] = $request['Buyer']['LastName'];
	
		// verify fields are valid
		$customer = new Customer();
		$errors = $customer->validateControler();
		if (is_array($errors) && (count($errors) > 0)) CartAPI_Helpers::dieOnError($encoder, 'RegisterNotAuthorized', CartAPI_Handlers_Helpers::removeHtmlTags($errors[0]));
	
		// make sure the customer doesn't already exist
		if (Customer::customerExists($_POST['email'])) CartAPI_Helpers::dieOnError($encoder, 'RegisterNotAuthorized', CartAPI_Handlers_Helpers::removeHtmlTags(Tools::displayError('An account is already registered with this e-mail, please fill in the password or request a new one.')));
	
		// add the new user
		$customer->active = 1;
		if (property_exists('Customer','is_guest')) $customer->is_guest = 0;
		if (!$customer->add()) CartAPI_Helpers::dieOnError($encoder, 'RegisterNotAuthorized', CartAPI_Handlers_Helpers::removeHtmlTags(Tools::displayError('An error occurred while creating your account.')));
		
		// see if we need to login too
		if (!isset($request['Login']) || ($request['Login'] == 'true'))
		{
			$cookie = $this->syncCookie($customer);
			
			// run the after login events, actually don't since prestashop AuthController doesn't do it
			// $this->afterBuyerLogin($customer);
		}
		
		// run the after register events
		$this->afterBuyerRegister($customer, $request['Buyer']);
	}
	
	// override to implement
	public function handleBuyerRegisterAuthService($metadata, $buyerInfo, $encoder)
	{
		CartAPI_Helpers::dieOnError($encoder, 'UnsupportedAuthService', 'Service not supported');
	}
	
	// default behavior, override if need to do something else
	public function afterBuyerLogin($customer, $method = 'UserPassword')
	{
		// run the Prestashop hook
		Module::hookExec('authentication');
	}
	
	// default behavior, override if need to do something else
	public function afterBuyerRegister($customer, $buyerInfo)
	{
		global $cookie;
	
		// send an email just like in the Prestashop AuthController
		if ((!property_exists('Customer','is_guest')) || (!$customer->is_guest))
		{
			Mail::Send((int)$cookie->id_lang, 'account', CartAPI_Handlers_Helpers::compatibilityMailTranslate('Welcome!', (int)$cookie->id_lang), 
				array('{firstname}' => $customer->firstname, '{lastname}' => $customer->lastname, '{email}' => $customer->email, '{passwd}' => Tools::getValue('passwd')), $customer->email, $customer->firstname.' '.$customer->lastname);
		}
	
		// run the Prestashop hook
		Module::hookExec('createAccount', array(
			'_POST' => $_POST,
			'newCustomer' => $customer
		));
	}

}

?>