<?php

class CartAPI_Handlers_Order
{
	protected $SHIPPING_ADDRESS_ALIAS = 'Mobile';

	public function Handle_GetOrderUpdate($metadata, $request, $encoder)
	{
		global $cookie;
		
		// required arguments
		if (!isset($request['Order'])) CartAPI_Helpers::dieOnError($encoder, 'IncompleteRequest', 'Order argument missing');
		
		// create the response
		$response = CartAPI_Helpers::createSuccessResponse($encoder, CartAPI_Handlers_Helpers::getLocale());
		$updates = &$encoder->addArray($response, 'Update');
		
		// make sure the global cart object has the correct cart for this order
		if (!$this->syncGlobalCartIdWithOrder($request['Order']))
		{
			// we don't have a cart id yet.. we need to create one
			$this->addGlobalCartIdOrderUpdate($encoder, $updates);
		}
		else // we do already have a cart
		{
			// is this before checkout? (just adding products to the cart)
			if (isset($request['Order']['Status']) && ($request['Order']['Status'] == 'BeforeCheckout'))
			{
				// make sure the cart can change in this stage, and if not, allocate a new one
				if (!$this->verifyGlobalCartCanChange()) $this->addGlobalCartIdOrderUpdate($encoder, $updates);
			}
		}
		
		// make sure the global cart is synchronized with this order
		$this->syncGlobalCartProductsWithOrder($encoder, $request['Order'], $updates);
		
		// update the order prices
		$this->addOrderTotalPrice($encoder, $request['Order'], $updates);
		$this->addOrderShippingPrice($encoder, $request['Order'], $updates);
		$this->addOrderTotalItemPricePrice($encoder, $request['Order'], $updates);
		
		// if we have a shipping address, validate and fix it
		if (isset($request['Order']['ShippingAddress'])) 
		{
			// we must be logged in to handle a shipping address
			if (!$cookie->logged)
			{
				// try to create a guest account, since the user is not logged in and didn't try to register before giving an address
				if (!isset($request['Order']['Buyer'])) CartAPI_Helpers::dieOnError($encoder, 'IncompleteRequest', 'Cannot create guest account without Order.Buyer field');
				$this->createCustomerGuestAccount($encoder, $request['Order']['Buyer'], $request['Order']['ShippingAddress']);
			}
		
			$fixedAddress = $this->validateAddressDictionary($encoder, $request['Order']['ShippingAddress']);
			$fixedAddressDictionary = $this->convertCartAddressToApiAddressDictionary($fixedAddress);
			
			if (!$this->isIdenticalAddressDictionary($fixedAddressDictionary, $request['Order']['ShippingAddress']))
			{
				$this->addShippingAddressOrderUpdate($encoder, $updates, $fixedAddressDictionary);
			}
		}
		else // we don't have a shipping address in the order
		{
			if ($cookie->logged) // user is logged in, try to get the default shipping address for this customer and return it too
			{
				$addressDictionary = $this->getCustomerDefaultShippingAddressDictionary();
				$this->addShippingAddressOrderUpdate($encoder, $updates, $addressDictionary);
			}
		}
		
		// make sure the global cart carrier is synchronized with this order
		$this->syncGlobalCartCarrierWithOrder($request['Order']);
		
		// check if we are immediately after payment (in Prestashop this is the last step)
		if (isset($request['Order']['Status']) && ($request['Order']['Status'] == 'AfterPayment'))
		{
			$status = 'Unknown';
			
			// get the cart order
			$cartOrder = $this->getGlobalCartOrder();
			if ($cartOrder === false) CartAPI_Helpers::dieOnError($encoder, 'InvalidState', 'Order not placed yet on cart');
			
			// let payment module update the order one last time (after payment), and get its status result
			$this->addPaymentModuleOrderUpdateAfterPayment($encoder, $updates, $request['Order'], $cartOrder, $status);
			
			// run the after payment events
			$this->addAfterPaymentOrderUpdate($encoder, $updates, $request['Order'], $cartOrder, $status);
			
		}
				
		// show the response
		$encoder->render($response);
	}

	public function Handle_GetShippingMethods($metadata, $request, $encoder)
	{
		// required arguments
		if (!isset($request['Order'])) CartAPI_Helpers::dieOnError($encoder, 'IncompleteRequest', 'Order argument missing');
		
		// make sure we have a shipping address
		if (!isset($request['Order']['ShippingAddress'])) CartAPI_Helpers::dieOnError($encoder, 'IncompleteRequest', 'Order.ShippingAddress argument missing');
		
		// make sure the global cart object has the correct cart for this order
		if (!$this->syncGlobalCartIdWithOrder($request['Order'])) CartAPI_Helpers::dieOnError($encoder, 'InvalidOrder', 'Order Id not set yet');
		
		// make sure the global cart is synchronized with this order
		$this->syncGlobalCartProductsWithOrder($encoder, $request['Order']);
		
		// sync our shipping address with the cart (and create the address in the db if needed)
		$this->syncGlobalCartShippingAddressWithOrder($encoder, $request['Order']);
		
		// get the carriers
		$result = $this->getCarriers();
		
		// create the response
		$response = CartAPI_Helpers::createSuccessResponse($encoder, CartAPI_Handlers_Helpers::getLocale());

		// add the shipping methods to the response if needed
		if (count($result) > 0) $methods = &$encoder->addArray($response, 'ShippingMethod');

		// encode each method
		foreach ($result as $row)
		{
			// prepare the method php array
			$methodPhpArray = $this->getShippingMethodDictionaryFromCarrier($row);
			if ($methodPhpArray === false) continue;
			
			// override core fields (id and price)
			$methodPhpArray['Id'] = $row['id_carrier'];
			if ($row['is_free']) $methodPhpArray['Price'] = 0;
			else $methodPhpArray['Price'] = $row['price'];
		
			// encode the method
			$method = &$encoder->addPhpArrayToArray($methods, $methodPhpArray);			
		}

		// show the response
		$encoder->render($response);
	}
	
	public function Handle_GetPaymentMethods($metadata, $request, $encoder)
	{
		// required arguments
		if (!isset($request['Order'])) CartAPI_Helpers::dieOnError($encoder, 'IncompleteRequest', 'Order argument missing');
		
		// make sure the global cart object has the correct cart for this order
		if (!$this->syncGlobalCartIdWithOrder($request['Order'])) CartAPI_Helpers::dieOnError($encoder, 'InvalidOrder', 'Order Id not set yet');
		
		// make sure the global cart is synchronized with this order
		$this->syncGlobalCartProductsWithOrder($encoder, $request['Order']);
		
		// get the payment methods
		$result = $this->getPaymentMethodDictionaries($request['Order']);
		
		// create the response
		$response = CartAPI_Helpers::createSuccessResponse($encoder, CartAPI_Handlers_Helpers::getLocale());

		// add the shipping methods to the response if needed
		if (count($result) > 0) $methods = &$encoder->addArray($response, 'PaymentMethod');

		// encode each method
		foreach ($result as $phpArray)
		{
			// encode the method
			$method = &$encoder->addPhpArrayToArray($methods, $phpArray);
		}

		// show the response
		$encoder->render($response);
	}
	
	// dies if can't create account
	protected function createCustomerGuestAccount($encoder, $buyerDictionary, $addressDictionary = array())
	{
		global $cookie;
		
		// taken from AuthController
		
		// no need to create if already logged in and has a customer id
		if ($cookie->logged && $cookie->id_customer) return;

		// make sure we can create a guest account		
		if (!Configuration::get('PS_GUEST_CHECKOUT_ENABLED'))
		{
			CartAPI_Helpers::dieOnError($encoder, 'RegisterNotAuthorized', CartAPI_Handlers_Helpers::removeHtmlTags(Tools::displayError('You cannot create a guest account.')));
		}
		
		// prepare the fields inside the POST (so we can use Prestashop's validateController)
		unset($_POST['email']);
		if (isset($buyerDictionary['Email'])) $_POST['email'] = $buyerDictionary['Email'];
		unset($_POST['passwd']);
		$_POST['passwd'] = md5(time()._COOKIE_KEY_);
		unset($_POST['firstname']);
		if (isset($addressDictionary['FirstName'])) $_POST['firstname'] = $addressDictionary['FirstName']; // take from address as backup
		if (isset($buyerDictionary['FirstName'])) $_POST['firstname'] = $buyerDictionary['FirstName']; // take from buyer if given
		unset($_POST['lastname']);
		if (isset($addressDictionary['LastName'])) $_POST['lastname'] = $addressDictionary['LastName']; // take from address as backup
		if (isset($buyerDictionary['LastName'])) $_POST['lastname'] = $buyerDictionary['LastName']; // take from buyer if given
		
		// verify fields are valid
		$customer = new Customer();
		$errors = $customer->validateControler();
		if (is_array($errors) && (count($errors) > 0)) CartAPI_Helpers::dieOnError($encoder, 'RegisterNotAuthorized', CartAPI_Handlers_Helpers::removeHtmlTags($errors[0]));
		
		// add the new user
		$customer->active = 1;
		$customer->is_guest = 1;
		if (!$customer->add()) CartAPI_Helpers::dieOnError($encoder, 'RegisterNotAuthorized', CartAPI_Handlers_Helpers::removeHtmlTags(Tools::displayError('An error occurred while creating your account.')));
		
		// sync the cookie
		$loginHandler = CartAPI_Handlers_Helpers::newHandlerInstance($encoder, 'Login');
		$loginHandler->syncCookie($customer);
	}
	
	// override this function to handle special shipping methods and customize descriptions
	// carrier is a row from Carrier::getCarriersForOrder
	protected function getShippingMethodDictionaryFromCarrier($carrier)
	{
		$method = array();
		
		$method['Title'] = $carrier['name'];
		$method['Description'] = $carrier['delay'];
		
		return $method;
	}
	
	// returns false if not found
	protected function getPaymentModuleInstance($moduleName)
	{
		$cartApiModuleFilename = dirname(__FILE__).'/modules/'.$moduleName.'/'.$moduleName.'.php';
		$cartApiModuleClassname = 'CartAPI_Module_'.$moduleName;
		if (!file_exists($cartApiModuleFilename)) return false;
		require_once($cartApiModuleFilename);
		if (!class_exists($cartApiModuleClassname, false)) return false;
		return new $cartApiModuleClassname();
	}
	
	protected function addPaymentModuleOrderUpdateAfterPayment($encoder, &$updates, $order, $cartOrder, &$status)
	{
		// get module instance from the order
		if (!isset($order['PaymentMethod'])) CartAPI_Helpers::dieOnError($encoder, 'InvalidOrder', 'PaymentMethod not set yet');
		$paymentMethod = $order['PaymentMethod'];
		if (!isset($paymentMethod['Id'])) CartAPI_Helpers::dieOnError($encoder, 'InvalidOrder', 'PaymentMethod.Id missing');
		$moduleInstance = $this->getPaymentModuleInstance($paymentMethod['Id']);
		if ($moduleInstance === false) CartAPI_Helpers::dieOnError($encoder, 'InvalidOrder', 'PaymentMethod module not found'); // TODO: how to we handle the default payment module here
		
		// call the module function
		$updateValuePhpArray = $moduleInstance->{'Handle_GetOrderUpdateAfterPayment'}($order, $cartOrder, $status);
		if ($updateValuePhpArray === false) return;
		
		// add the update
		$update = &$encoder->addContainerToArray($updates);
		$encoder->addString($update, 'Type', 'Update');
		$value = &$encoder->addPhpArray($update, 'Value', $updateValuePhpArray);
	}
	
	protected function getAfterPaymentOrderUpdate($order, $cartOrder, $staus)
	{
		// do nothing by default, override if needed 
		return false;
	}
	
	protected function addAfterPaymentOrderUpdate($encoder, &$updates, $order, $cartOrder, $status)
	{
		$updateValuePhpArray = $this->getAfterPaymentOrderUpdate($order, $cartOrder, $status);
		if ($updateValuePhpArray === false) return;
		
		// add the update
		$update = &$encoder->addContainerToArray($updates);
		$encoder->addString($update, 'Type', 'Update');
		$value = &$encoder->addPhpArray($update, 'Value', $updateValuePhpArray);
	}
	
	protected function getPaymentMethodDictionaries($order)
	{
		$paymentMethodDictionaries = array();
		$result = Module::getPaymentModules();
		if (!$result) return $paymentMethodDictionaries;
		foreach ($result as $module)
		{
			// check if we have a cart api module for this payment module
			$moduleInstance = $this->getPaymentModuleInstance($module['name']);
			if ($moduleInstance !== false)
			{
				$paymentMethodDictionary = $moduleInstance->{'Handle_GetPaymentMethod'}($order);
				if (is_array($paymentMethodDictionary)) 
				{
					$paymentMethodDictionary['Id'] = $module['name'];
					$paymentMethodDictionaries[] = $paymentMethodDictionary;
				}	
			}
			else
			{
				// we don't have a specific cart api module for this one..
				// for now we do nothing, but in the future we need some sort of backup method to try and do something even though this module isn't supported
			}
		}
		return $paymentMethodDictionaries;
	}
	
	protected function getCarriers()
	{
		global $cookie, $cart;
		
		// code taken from ParentOrderController::_assignCarrier()
		$customer = new Customer((int)($cookie->id_customer));
		$address = new Address((int)($cart->id_address_delivery));
		$id_zone = Address::getZoneById((int)($address->id));
		$carriers = Carrier::getCarriersForOrder($id_zone, $customer->getGroups());
		return $carriers;
	}
	
	protected function syncGlobalCartCarrierWithOrder($order)
	{
		global $cart;
		
		// make sure we have a chosen shipping method
		if (!isset($order['ShippingMethod'])) return;
		if (!isset($order['ShippingMethod']['Id'])) return;
		
		$carrier_id = (int)$order['ShippingMethod']['Id'];
		
		if ($cart->id_carrier != $carrier_id)
		{
			$cart->id_carrier = $carrier_id;
			$cart->update();
		}
	}
	
	// returns false on failure
	protected function convertCartAddressToApiAddressDictionary($cartAddress)
	{
		$apiAddress = array();
		if (!is_object($cartAddress)) return false; // instead of Validate::isLoadedObject since we don't always have an id

		$apiAddress['FirstName'] = $cartAddress->firstname;
		$apiAddress['LastName'] = $cartAddress->lastname;
		$apiAddress['Street1'] = $cartAddress->address1;
		$apiAddress['Street2'] = $cartAddress->address2;
		$apiAddress['Phone1'] = $cartAddress->phone;
		$apiAddress['Phone2'] = $cartAddress->phone_mobile;
		
		/*
		$apiAddress['Phone2'] = '';
		if (empty($cartAddress->phone_mobile)) $apiAddress['Phone1'] = $cartAddress->phone;
		else if (empty($cartAddress->phone)) $apiAddress['Phone1'] = $cartAddress->phone_mobile;
		else 
		{
			$apiAddress['Phone1'] = $cartAddress->phone_mobile;
			$apiAddress['Phone2'] = $cartAddress->phone;
		}
		*/
		
		$apiAddress['Zipcode'] = $cartAddress->postcode;
		$apiAddress['City'] = $cartAddress->city;
		$apiAddress['Country'] = $cartAddress->id_country;
		$apiAddress['State'] = $cartAddress->id_state;
		return $apiAddress;
	}
	
	// returns false if not found
	protected function getCustomerDefaultShippingAddressId($alias = NULL)
	{
		global $cookie;
		
		// code taken from ParentOrderController::_assignAddress()
		$customer = new Customer((int)($cookie->id_customer));
		if (!Validate::isLoadedObject($customer)) return false;
		$customerAddresses = $customer->getAddresses((int)($cookie->id_lang));
		if (!is_array($customerAddresses) OR (count($customerAddresses)==0)) return false;
		
		$address_id = false;
		
		if ($alias === NULL) 
		{
			// if our alias isn't found, return the first address
			$address_id = (int)($customerAddresses[0]['id_address']);
			
			// but also give preference to our alias if found
			$alias = $this->SHIPPING_ADDRESS_ALIAS;	
		}
		
		// try to find an address with matching alias
		foreach ($customerAddresses as $row)
		{
			if ($row['alias'] == $alias) $address_id = (int)$row['id_address'];
		}
		
		return $address_id;
	}
	
	// returns false if not found
	protected function getCustomerDefaultShippingAddressDictionary()
	{
		$id_address = $this->getCustomerDefaultShippingAddressId();
		if ($id_address === false) return false;
		$cartAddress = new Address((int)$id_address);
		return $this->convertCartAddressToApiAddressDictionary($cartAddress);
	}
	
	protected function addAddress($encoder, &$container, $fieldname, $addressDictionary)
	{
		$address = &$encoder->addContainer($container, $fieldname);
		$encoder->addString($address, 'FirstName', $addressDictionary['FirstName']);
		$encoder->addString($address, 'LastName', $addressDictionary['LastName']);
		$encoder->addString($address, 'Street1', $addressDictionary['Street1']);
		$encoder->addString($address, 'Street2', $addressDictionary['Street2']);
		$encoder->addString($address, 'Phone1', $addressDictionary['Phone1']);
		$encoder->addString($address, 'Phone2', $addressDictionary['Phone2']);
		$encoder->addString($address, 'Zipcode', $addressDictionary['Zipcode']);
		$encoder->addString($address, 'City', $addressDictionary['City']);
		$encoder->addString($address, 'Country', $addressDictionary['Country']);
		$encoder->addString($address, 'State', $addressDictionary['State']);
	}
	
	protected function addShippingAddressOrderUpdate($encoder, &$updates, $addressDictionary)
	{
		if ($addressDictionary === false) return;
		$update = &$encoder->addContainerToArray($updates);
		$encoder->addString($update, 'Type', 'Update');
		$value = &$encoder->addContainer($update, 'Value');
		$this->addAddress($encoder, $value, 'ShippingAddress', $addressDictionary);
	}
	
	protected function isIdenticalAddressDictionary($address1, $address2)
	{
		// compare both ways
		foreach ($address1 as $key => $value1)
		{
			if (!empty($value1))
			{
				if (!isset($address2[$key])) return false;
				if ($address2[$key] != $value1) return false;
			}
		}
		foreach ($address2 as $key => $value2)
		{
			if (!empty($value2))
			{
				if (!isset($address1[$key])) return false;
				if ($address1[$key] != $value2) return false;
			}
		}
		return true;
	}
	
	protected function createCustomerAddressIdFromCartAddress($encoder, $address)
	{
		$old_address_id = $this->getCustomerDefaultShippingAddressId($address->alias);
		
		// delete the old address if found
		if ($old_address_id !== false)
		{
			$address_old = new Address((int)$old_address_id);
			if (Validate::isLoadedObject($address_old))
			{
				if ($address_old->isUsed())
				{
					$address_old->delete(); // delete the old address
				}
				else // take over the old address and use its id
				{
					$address->id = (int)($address_old->id);
					$address->date_add = $address_old->date_add;
				}
			}
		}
		
		// create the address
		if (!$address->save()) CartAPI_Helpers::dieOnError($encoder, 'InternalError', 'Error while updating address');
		return $address->id;
	}
	
	// dies if there is a problem, returns cartAddress object
	protected function validateAddressDictionary($encoder, $addressDictionary)
	{
		global $cookie;
		
		// prepare the fields inside the POST (so we can use Prestashop's validateController)
		$_POST['alias'] = $this->SHIPPING_ADDRESS_ALIAS;
		unset($_POST['firstname']);
		if (isset($addressDictionary['FirstName'])) $_POST['firstname'] = $addressDictionary['FirstName'];
		unset($_POST['lastname']);
		if (isset($addressDictionary['LastName'])) $_POST['lastname'] = $addressDictionary['LastName'];
		unset($_POST['address1']);
		if (isset($addressDictionary['Street1'])) $_POST['address1'] = $addressDictionary['Street1'];
		unset($_POST['address2']);
		if (isset($addressDictionary['Street2'])) $_POST['address2'] = $addressDictionary['Street2'];
		unset($_POST['phone']);
		if (isset($addressDictionary['Phone1'])) $_POST['phone'] = $addressDictionary['Phone1'];
		unset($_POST['phone_mobile']);
		if (isset($addressDictionary['Phone2'])) $_POST['phone_mobile'] = $addressDictionary['Phone2'];
		unset($_POST['postcode']);
		if (isset($addressDictionary['Zipcode'])) $_POST['postcode'] = $addressDictionary['Zipcode'];
		unset($_POST['city']);
		if (isset($addressDictionary['City'])) $_POST['city'] = $addressDictionary['City'];
		unset($_POST['id_country']);
		if (isset($addressDictionary['Country'])) $_POST['id_country'] = $addressDictionary['Country'];
		unset($_POST['id_state']);
		if (isset($addressDictionary['State'])) $_POST['id_state'] = $addressDictionary['State'];
		
		// code taken from AddressController::preProcess
		$address = new Address();
		$errors = $address->validateControler();
		
		$address->id_customer = (int)($cookie->id_customer);

		if (!Tools::getValue('phone') AND !Tools::getValue('phone_mobile'))
			$errors[] = Tools::displayError('You must register at least one phone number');
		
		if (!$country = new Country((int)$address->id_country) OR !Validate::isLoadedObject($country))
				CartAPI_Helpers::dieOnErrors($encoder, 'InvalidAddress', CartAPI_Handlers_Helpers::removeHtmlTags($errors));

		/* US customer: normalize the address */
		if ($address->id_country == Country::getByIso('US'))
		{
			include_once(_PS_TAASC_PATH_.'AddressStandardizationSolution.php');
			$normalize = new AddressStandardizationSolution;
			$address->address1 = $normalize->AddressLineStandardization($address->address1);
			$address->address2 = $normalize->AddressLineStandardization($address->address2);
		}

		$zip_code_format = $country->zip_code_format;
		if ($country->need_zip_code)
		{
			if (($postcode = Tools::getValue('postcode')) AND $zip_code_format)
			{
				$zip_regexp = '/^'.$zip_code_format.'$/ui';
				$zip_regexp = str_replace(' ', '( |)', $zip_regexp);
				$zip_regexp = str_replace('-', '(-|)', $zip_regexp);
				$zip_regexp = str_replace('N', '[0-9]', $zip_regexp);
				$zip_regexp = str_replace('L', '[a-zA-Z]', $zip_regexp);
				$zip_regexp = str_replace('C', $country->iso_code, $zip_regexp);
				if (!preg_match($zip_regexp, $postcode))
					$errors[] = '<strong>'.Tools::displayError('Zip/ Postal code').'</strong> '.Tools::displayError('is invalid.').'<br />'.Tools::displayError('Must be typed as follows:').' '.str_replace('C', $country->iso_code, str_replace('N', '0', str_replace('L', 'A', $zip_code_format)));
			}
			elseif ($zip_code_format)
				$errors[] = '<strong>'.Tools::displayError('Zip/ Postal code').'</strong> '.Tools::displayError('is required.');
			elseif ($postcode AND !preg_match('/^[0-9a-zA-Z -]{4,9}$/ui', $postcode))
				$errors[] = '<strong>'.Tools::displayError('Zip/ Postal code').'</strong> '.Tools::displayError('is invalid.').'<br />'.Tools::displayError('Must be typed as follows:').' '.str_replace('C', $country->iso_code, str_replace('N', '0', str_replace('L', 'A', $zip_code_format)));
		}
			
		/*
		if ($country->isNeedDni() AND (!Tools::getValue('dni') OR !Validate::isDniLite(Tools::getValue('dni'))))
			$errors[] = Tools::displayError('Identification number is incorrect or has already been used.');
		elseif (!$country->isNeedDni())
			$address->dni = NULL;
		if (Configuration::get('PS_TOKEN_ENABLE') == 1 AND
			strcmp(Tools::getToken(false), Tools::getValue('token')) AND
			self::$cookie->isLogged(true) === true)
			$errors[] = Tools::displayError('Invalid token');
		*/
		
		if ((int)($country->contains_states) AND !(int)($address->id_state))
			$errors[] = Tools::displayError('This country requires a state selection.');
			
		// finished
		if (count($errors) > 0) CartAPI_Helpers::dieOnErrors($encoder, 'InvalidAddress', CartAPI_Handlers_Helpers::removeHtmlTags($errors));
		
		return $address;
	}
	
	protected function syncGlobalCartShippingAddressWithOrder($encoder, $order)
	{
		global $cart;
		
		if (!isset($order['ShippingAddress'])) return;
		
		// we will sync the cart with this address id
		$address_id = false;
		
		// get the default customer shipping address
		$defaultCustomerAddress = $this->getCustomerDefaultShippingAddressDictionary();
		
		// check if the buyer is using his default address
		if (($defaultCustomerAddress !== false) && $this->isIdenticalAddressDictionary($defaultCustomerAddress, $order['ShippingAddress']))
		{
			// buyer didn't change the default address, let's use it
			$address_id = $this->getCustomerDefaultShippingAddressId();
		}
		else
		{
			// buyer changed the address, this means the address isn't in the db, we need to add it
			$cartAddress = $this->validateAddressDictionary($encoder, $order['ShippingAddress']);
			$address_id = $this->createCustomerAddressIdFromCartAddress($encoder, $cartAddress);
		}
		
		// do the sync
		if ($address_id === false) return;
		$cart->id_address_delivery = $address_id;
		$cart->id_address_invoice = $address_id;
		$cart->update();
	}
	
	protected function getOrderTotalPrice()
	{
		global $cart;
		return Tools::ps_round($cart->getOrderTotal(), 2);
	}
	
	protected function addOrderTotalPrice($encoder, $order, &$updates)
	{
		// get the current order total
		$currentTotalPrice = $this->getOrderTotalPrice();
		
		// make sure we need to update anything
		if (isset($order['TotalPrice']) && (floatval($order['TotalPrice']) == $currentTotalPrice)) return;
		
		// add the update
		$update = &$encoder->addContainerToArray($updates);
		$encoder->addString($update, 'Type', 'Update');
		$value = &$encoder->addContainer($update, 'Value');
		$encoder->addNumber($value, 'TotalPrice', $currentTotalPrice);
	}
	
	protected function getOrderShippingPrice()
	{
		global $cart;
		return Tools::ps_round($cart->getOrderShippingCost(), 2);
	}
	
	protected function addOrderShippingPrice($encoder, $order, &$updates)
	{
		// get the current order shipping price
		$currentShippingPrice = $this->getOrderShippingPrice();
		
		// make sure we need to update anything
		if (isset($order['ShippingPrice']) && (floatval($order['ShippingPrice']) == $currentShippingPrice)) return;
		
		// add the update
		$update = &$encoder->addContainerToArray($updates);
		$encoder->addString($update, 'Type', 'Update');
		$value = &$encoder->addContainer($update, 'Value');
		$encoder->addNumber($value, 'ShippingPrice', $currentShippingPrice);
	}
	
	protected function getOrderTotalItemPrice()
	{
		global $cart;
		return Tools::ps_round($cart->getOrderTotal(true, Cart::ONLY_PRODUCTS), 2);
	}
	
	protected function addOrderTotalItemPricePrice($encoder, $order, &$updates)
	{
		// get the current order total item price
		$currentTotalItemPrice = $this->getOrderTotalItemPrice();
		
		// make sure we need to update anything
		if (isset($order['TotalItemPrice']) && (floatval($order['TotalItemPrice']) == $currentTotalItemPrice)) return;
		
		// add the update
		$update = &$encoder->addContainerToArray($updates);
		$encoder->addString($update, 'Type', 'Update');
		$value = &$encoder->addContainer($update, 'Value');
		$encoder->addNumber($value, 'TotalItemPrice', $currentTotalItemPrice);
	}
	
	// returns false on failure
	protected function getGlobalCartOrder()
	{
		global $cart;
	
		// code taken from OrderConfirmationController preProcess()
		$id_order = Order::getOrderByCartId((int)($cart->id));
		if (!$id_order) return false;
		$cartOrder = new Order((int)($id_order));
		if (!Validate::isLoadedObject($cartOrder)) return false;
		return $cartOrder;
	}
	
	protected function addGlobalCartIdOrderUpdate($encoder, &$updates)
	{
		global $cart, $cookie;
		
		// code taken from CartController preProcess() /* Product addition to the cart */
		
		// add the current temp cart to the db
		$cart->add(); // TODO: consider changing to ->save since the object may already exist (->id > 0), see ObjectModel save() function
		if (!$cart->id) return; // strange error
		// make sure the cookie is syncronized with this id
		if ($cookie->id_cart != (int)$cart->id) $cookie->id_cart = (int)$cart->id;
		
		// add the update
		$update = &$encoder->addContainerToArray($updates);
		$encoder->addString($update, 'Type', 'Update');
		$value = &$encoder->addContainer($update, 'Value');		
		$encoder->addNumber($value, 'Id', $cart->id);
	}
	
	// we can't rely that the cart_id in the cookie holds our cart id
	// since FrontController zeroes it after an order is placed, therefore we must rely on our own Id
	// we use the order Id as the cart id
	// returns false if unable to sync (a cart id does not exist yet), true if everything is ok
	protected function syncGlobalCartIdWithOrder($order)
	{
		global $cart, $cookie;
		
		// see if we even have a cart
		if (!isset($order['Id'])) return false;
		$tempCart = new Cart((int)$order['Id']);
		if (!Validate::isLoadedObject($tempCart)) return false;
		
		// if here, than we have a good cart, use it!
		$cart = $tempCart;
		// make sure the cookie is syncronized with this id
		if ($cookie->id_cart != (int)$cart->id) $cookie->id_cart = (int)$cart->id;
		return true;
	}
	
	// returns false if can't change, optional detailed error message in $errors (out)
	protected function verifyGlobalCartCanChange(&$errors = array())
	{
		global $cart;
		
		if ($cart->OrderExists()) 
		{
			$errors[] = 'Cannot change a cart once an order has been placed';
			return false;
		}
		
		return true;
	}
	
	// adds OrderUpdates if $updates is given (on sync problems)
	// if $updates not given, show die on sync problems
	protected function syncGlobalCartProductsWithOrder($encoder, $order, &$updates = null)
	{
		global $cart;
		
		// helper arrays, in all keys are [productId][productAttributeId] (if no productAttributeId, then it's 0)
		$cartProductQuantities = array(); // value is quantity
		$orderProductQuantities = array(); // value is quantity
		
		// go over the cart products and prepare them for sync
		$_cartProducts = $this->getCartProductsFromCartId($cart->id);
		foreach ($_cartProducts as $row)
		{
			if ($row['id_product_attribute']) 
			{
				$cartProductQuantities[$row['id_product']][$row['id_product_attribute']] = (int)$row['quantity'];
			}
			else 
			{
				$cartProductQuantities[$row['id_product']][0] = (int)$row['quantity'];
			}
		}
		
		// go over the order products and prepare them for sync
		$orderItems = CartAPI_Helpers::getDictionaryKeyAsArray($order, 'OrderItem');
		foreach ($orderItems as $orderItem)
		{
			if (!isset($orderItem['ItemId'])) continue;
			$productAttribute = $this->getProductAttributeIdFromOrderItem($orderItem);
			if ($productAttribute !== false) 
			{
				$orderProductQuantities[$orderItem['ItemId']][$productAttribute] = (int)$orderItem['Quantity'];
			}
			else 
			{
				$orderProductQuantities[$orderItem['ItemId']][0] = (int)$orderItem['Quantity'];
			}
		}
		
		// start syncing
		
		$errors = array();
		$updateFailed = false;
		
		// this accomodates the initial checkDiscountValidity() in the beginning of CartController preProcess()
		// consider putting it back, removed because seems redundant
		/// $this->validateGlobalCartDiscounts($errors, false);
		
		// first, make sure everything in the order is in the cart too
		foreach ($orderProductQuantities as $productId => $productAttributeIds)
		{
			foreach ($productAttributeIds as $productAttributeId => $orderQuantity)
			{
				$cartQuantity = 0;
				if (isset($cartProductQuantities[$productId][$productAttributeId])) $cartQuantity = (int)$cartProductQuantities[$productId][$productAttributeId];
				
				// do the quantity update
				if (!$this->updateGlobalCartProductQuantity($orderQuantity - $cartQuantity, $productId, $productAttributeId, $errors)) $updateFailed = true;
				
				unset($cartProductQuantities[$productId][$productAttributeId]); // to make sure we don't sync it again
			}
		}
		
		// second, if we have anything left in the cart list, it should be removed since it's not in the order anymore
		foreach ($cartProductQuantities as $productId => $productAttributeIds)
		{
			foreach ($productAttributeIds as $productAttributeId => $cartQuantity)
			{
				// remove the product from the cart
				if (!$this->updateGlobalCartProductQuantity((-1)*$cartQuantity, $productId, $productAttributeId, $errors)) $updateFailed = true;
			}
		}
		
		// done syncing
		
		// handle the errors if found
		if ($updateFailed || (count($errors) > 0))
		{
			// if we can't update the order, die
			if ($updates === null) CartAPI_Helpers::dieOnErrors($encoder, 'InvalidOrder', $errors);
			
			// if here, then update the order (add an order update)
			$this->addOrderItemsOrderUpdateFromGlobalCart($encoder, $updates, $errors);
		}
		
	}
	
	// takes the items currently found in the cart and adds them as an order update
	protected function addOrderItemsOrderUpdateFromGlobalCart($encoder, &$updates, $messages = null)
	{
		global $cart;
	
		$_update = &$encoder->addContainerToArray($updates);
		$encoder->addString($_update, 'Type', 'Update');
		$_value = &$encoder->addContainer($_update, 'Value');
			
		// add the error messages to this update
		if (is_array($messages))
		{
			$_messages = &$encoder->addArray($_update, 'Message');
			foreach ($messages as $message) $encoder->addStringToArray($_messages, $message);
		}
			
		// add all order items in the update
		$_orderItems = &$encoder->addArray($_value, 'OrderItem');
		$cartProducts = $this->getCartProductsFromCartId($cart->id);
		$i = 0;
		foreach ($cartProducts as $row)
		{
			$i++;
			$_orderItem = &$encoder->addContainerToArray($_orderItems);
		
			$encoder->addNumber($_orderItem, 'Id', $i);
			$encoder->addNumber($_orderItem, 'ItemId', $row['id_product']);
			$encoder->addNumber($_orderItem, 'Quantity', $row['quantity']);
			
			if ($row['id_product_attribute'])
			{
				$combinationPhpArray = $this->getOrderItemCombinationDictionaryFromProductAttributeId($row['id_product_attribute']);
				if ($combinationPhpArray === false) continue;
				$_combination = &$encoder->addPhpArray($_orderItem, 'Combination', $combinationPhpArray);
			}
		}
	}
	
	// if encounters errors, returns them in $errors (out)
	// returns false if the update failed
	protected function updateGlobalCartProductQuantity($quantityDelta, $productId, $productAttributeId, &$errors)
	{
		global $cookie;
	
		// make sure we have a change
		$quantityDelta = (int)($quantityDelta);
		if ($quantityDelta == 0) return true;
		
		// make sure we can update quantities in this state
		if (!$this->verifyGlobalCartCanChange($errors)) return false;
	
		// validate the update
		if ($productAttributeId == 0) $productAttributeId = null;
		$add = (bool)($quantityDelta > 0);
		$delete = (bool)($quantityDelta < 0);
		$qty = $quantityDelta;
		$producToAdd = new Product((int)($productId), true, (int)($cookie->id_lang));
		if (!$this->validateGlobalCartProductQuantityUpdate($add, $delete, $qty, $producToAdd, $productId, $productAttributeId, $errors)) return false;
		
		// do the actual update
		if ($quantityDelta > 0) $op = 'up';
		else $op = 'down';
		if (!$this->processGlobalCartProductQuantityUpdate($op, abs($qty), $producToAdd, $productId, $productAttributeId, $errors)) return false;
		
		return true;
	}
	
	// returns false if process failed, returns the errors in $errors (out)
	protected function processGlobalCartProductQuantityUpdate($op, $qty, $producToAdd, $idProduct, $idProductAttribute, &$errors)
	{
		global $cart;
		
		// code taken from CartController preProcess()
		
		$updateQuantity = $cart->updateQty($qty, $idProduct, $idProductAttribute, false, $op);
		if ($updateQuantity < 0)
		{
			/* if product has attribute, minimal quantity is set with minimal quantity of attribute*/
			if ((int)$idProductAttribute)
				$minimal_quantity = Attribute::getAttributeMinimalQty((int)$idProductAttribute);
			else
				$minimal_quantity = $producToAdd->minimal_quantity;
			$errors[] = Tools::displayError('You must add', false).' '.$minimal_quantity.' '.Tools::displayError('Minimum quantity', false);
			
			// improvement: when adding below minimum quantity, add the minimum quantity (to make life easier for the user)			
			$currentQuantity = $this->getCurrentGlobalCartProductQuantity($idProduct, $idProductAttribute);
			$cart->updateQty((int)$minimal_quantity - (int)$currentQuantity, $idProduct, $idProductAttribute, false, 'up');
			// end improvement
			
			return false;
		}
		elseif (!$updateQuantity)
		{
			$errors[] = Tools::displayError('You already have the maximum quantity available for this product.', false);
			return false;
		}
		
		if (!$this->validateGlobalCartDiscounts($errors)) return false;
		
		return true;
	}
	
	// returns false if process failed, returns the errors in $errors (out)
	// $reportErrors default changed to false because of Prestashop 1.4.7.0 CartController line 236 ($errors and not $this->errors, bug?!)
	protected function validateGlobalCartDiscounts(&$errors, $reportErrors = false)
	{
		global $cart, $cookie;
		
		// code taken from CartController preProcess()
	
		$discounts = $cart->getDiscounts();					
		foreach($discounts AS $discount)
		{
			$discountObj = new Discount((int)($discount['id_discount']), (int)($cookie->id_lang));
			if ($error = $cart->checkDiscountValidity($discountObj, $discounts, $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS), $cart->getProducts()))
			{
				$cart->deleteDiscount((int)($discount['id_discount']));
				$cart->update();
				
				// reportErrors added to accomodate the initial checkDiscountValidity() in the beginning of CartController preProcess()
				if ($reportErrors)
				{
					$errors[] = CartAPI_Handlers_Helpers::removeHtmlTags($error);
					return false;
				}
			}
		}
		
		return true;
	}
	
	// returns false if validate failed, returns the errors in $errors (out)
	protected function validateGlobalCartProductQuantityUpdate($add, $delete, $qty, $producToAdd, $idProduct, $idProductAttribute, &$errors)
	{
		global $cookie, $cart;
	
		// code taken from CartController preProcess()
		
		if ((!$producToAdd->id OR !$producToAdd->active) AND !$delete)
		{
			$errors[] = Tools::displayError('Product is no longer available.', false);
			return false;
		}
		/* Check the quantity availability */
		if ($idProductAttribute AND is_numeric($idProductAttribute))
		{
			if (!$delete AND !$producToAdd->isAvailableWhenOutOfStock($producToAdd->out_of_stock) AND !Attribute::checkAttributeQty((int)$idProductAttribute, (int)$qty))
			{
				$errors[] = Tools::displayError('There is not enough product in stock.', false);
				return false;
			}
		}
		elseif ($producToAdd->hasAttributes() AND !$delete)
		{
			$errors[] = 'Product must have a combination chosen';
			return false;
		}
		elseif (!$delete AND !$producToAdd->checkQty((int)$qty))
		{
			$errors[] = Tools::displayError('There is not enough product in stock.', false);
			return false;
		}
		/* Check vouchers compatibility */
		if ($add AND (($producToAdd->specificPrice AND (float)($producToAdd->specificPrice['reduction'])) OR $producToAdd->on_sale))
		{
			$discounts = $cart->getDiscounts();
			$hasUndiscountedProduct = null;
			foreach($discounts as $discount)
			{
				if (is_null($hasUndiscountedProduct))
				{
					$hasUndiscountedProduct = false;
					foreach($cart->getProducts() as $product)
					if ($product['reduction_applies'] === false)
					{
						$hasUndiscountedProduct = true;
						break;
					}
				}
				if (!$discount['cumulable_reduction'] && ($discount['id_discount_type'] != 1 || !$hasUndiscountedProduct))
				{
					$errors[] = Tools::displayError('Cannot add this product because current voucher does not allow additional discounts.');
					return false;
				}
			}
		}
	
		return true;
	}
	
	// internal function needed by the min quantity fix (add the min quantity when too little added)
	protected function getCurrentGlobalCartProductQuantity($idProduct, $idProductAttribute = null)
	{
		global $cart;
		
		$result = $cart->containsProduct((int)$idProduct, (int)$idProductAttribute, (int)false);
		if ($result) return (int)$result['quantity'];
		else return 0;
	}
	
	// returns an array with id_product, id_product_attribute
	protected function getCartProductsFromCartId($cart_id)
	{
		$sql = '
			SELECT `id_product`, `id_product_attribute`, `quantity`
			FROM `'._DB_PREFIX_.'cart_product`
			WHERE `id_cart` = '.(int)($cart_id);
		$result = Db::getInstance()->ExecuteS($sql);
		if (!$result OR empty($result)) return array();
		return $result;
	}
	
	// returns false if not found
	protected function getProductAttributeIdFromOrderItem($orderItem)
	{
		if (!isset($orderItem['ItemId'])) return false;
		$itemId = $orderItem['ItemId'];
		
		// make sure this is a combination item
		if (!isset($orderItem['Combination'])) return false;
		
		// make an array of the attribute ids
		$ascAttributeIds = array();
		$variations = CartAPI_Helpers::getDictionaryKeyAsArray($orderItem['Combination'], 'Variation');
		foreach ($variations as $variation)
		{
			$ascAttributeIds[] = (int)$variation['ValueId'];	
		}
		sort($ascAttributeIds);
	
		// get all the attribute combinations for this product
		$sql = '
			SELECT pa.`id_product_attribute`, pac.`id_attribute`
			FROM `'._DB_PREFIX_.'product_attribute` pa 
			LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac ON pac.`id_product_attribute` = pa.`id_product_attribute`
			WHERE pa.`id_product` =  '. (int)$itemId .'
			ORDER BY pac.`id_attribute` ASC';
		$result = Db::getInstance()->ExecuteS($sql);
		if (!$result OR empty($result)) return false;
		
		// make a list of all attributes
		$productAttributes = array();
		foreach ($result as $row)
		{
			$productAttributes[$row['id_product_attribute']][] = (int)$row['id_attribute'];
		}
		
		// try to find a match
		foreach ($productAttributes as $productAttributeId => $ascAttributes)
		{
			if ($ascAttributes == $ascAttributeIds) return $productAttributeId;
		}
		
		// if here than not found
		return false;
	}
	
	// returns false if not found
	protected function getOrderItemCombinationDictionaryFromProductAttributeId($productAttributeId)
	{
		// get all the attribute combinations for this product
		$sql = '
			SELECT a.`id_attribute`, a.`id_attribute_group`
			FROM `'._DB_PREFIX_.'product_attribute_combination` pac 
			LEFT JOIN `'._DB_PREFIX_.'attribute` a ON a.`id_attribute` = pac.`id_attribute`
			WHERE pac.`id_product_attribute` =  '. (int)$productAttributeId;
		$result = Db::getInstance()->ExecuteS($sql);
		if (!$result OR empty($result)) return false;
		
		$variations = array();
		foreach ($result as $row)
		{
			$variation = array();
			$variation['Id'] = $row['id_attribute_group'];
			$variation['ValueId'] = $row['id_attribute'];
			$variations[] = $variation;
		}
		
		$combination = array();
		$combination['Variation'] = $variations;
		
		return $combination;
	}

}

?>