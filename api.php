<?php

include(dirname(__FILE__).'/../../config/config.inc.php');

require_once(dirname(__FILE__).'/engine/Engine.php');
require_once(dirname(__FILE__).'/engine/Helpers.php');
require_once(dirname(__FILE__).'/Helpers.php');

// handle the request
$request = CartAPI_Engine::handleRequest();
if ($request === false) die('ERROR');

CartAPI_Handlers_Helpers::preInit($request['metadata']);
require_once(dirname(__FILE__).'/../../init.php');
CartAPI_Handlers_Helpers::setServerNotices();

// mark as an appixia mobile endpoint
// TODO: add some validation of user agent and such, or maybe move this line to the app itself alltogether (maybe should originate from the server at all)
CartAPI_Handlers_Helpers::setAppixiaMobileEngine();

// define all supported operations
$request_router = array
(
	'GetSingleItem' => 'Items',
	'GetItemList' => 'Items',
	'GetCategoryList' => 'Categories',
	'BuyerLogin' => 'Login',
	'BuyerRegister' => 'Login',
	'GetOrderUpdate' => 'Order',
	'GetShippingMethods' => 'Order',
	'GetPaymentMethods' => 'Order',
);

// find the correct operation handler
$operation = $request['metadata']['X-OPERATION'];
$func_name = 'Handle_'.$operation;
$handler = $request_router[$operation];
$handler_filename = $handler . '.php';
$class_name = 'CartAPI_Handlers_'.$handler;

// load the correct file
if (!file_exists(dirname(__FILE__).'/override/'.$handler_filename)) 
{
	// load the base
	require_once(dirname(__FILE__).'/'.$handler_filename);
}
else 
{
	// load the override
	$class_name = 'CartAPI_Handlers_Override_'.$handler;
	require_once(dirname(__FILE__).'/override/'.$handler_filename);
}

// init the class
if (!class_exists($class_name, false)) CartAPI_Helpers::dieOnError($request['encoder'], 'UnsupportedOperation', $operation.' not supported');
$handler_instance = new $class_name();

// sync the locale
CartAPI_Handlers_Helpers::syncLocale($request['metadata']);

// call the operation handler
$handler_instance->{$func_name}($request['metadata'], $request['data'], $request['encoder']);

?>