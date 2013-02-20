<?php

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

// redirect to correct place
if (isset($_REQUEST['url']) && !empty($_REQUEST['url']))
{
	header('Location: '.$_REQUEST['url']);
	exit;
}

echo 'Cookies deleted!';

?>