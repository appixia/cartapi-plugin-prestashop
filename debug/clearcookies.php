<?php

include(dirname(__FILE__).'/../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../init.php');

$cookie->logout();

// unset cookies
if (isset($_SERVER['HTTP_COOKIE'])) 
{
	$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
	foreach($cookies as $cookie) 
	{
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        setcookie($name, '', time()-3600*24*7);
        setcookie($name, '', time()-3600*24*7, '/');
        setcookie($name, '', time()-3600*24*7, '/', '.'.$_SERVER['SERVER_NAME']);
    }
}

echo 'All cookies deleted!';

?>