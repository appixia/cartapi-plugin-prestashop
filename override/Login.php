<?php

// this is an override class related to buyer login and register
// if you need to customize the module to your needs, make all changes here

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Login.php');

class CartAPI_Handlers_Override_Login extends CartAPI_Handlers_Login
{
	// 	override any functions you want to change (from the core Login.php) here
}

?>
