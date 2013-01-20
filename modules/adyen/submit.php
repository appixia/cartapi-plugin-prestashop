<?php

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../init.php');

include(dirname(__FILE__).'/adyen.php');

if (!$cookie->isLogged())
    die('Error: not logged in');

// override the skin, note that this override is temporary without Configuration::update
Configuration::set('ADYEN_SKINCODE', 'mA8bOvvq');
Configuration::set('ADYEN_PAGE_TYPE', 'multiple');

$adyen = new CartAPI_Module_Adyen();

// Create an order before we redirect the shopper
$adyen->validateOrder($cart->id, _PS_OS_ADYEN_REDIRECTED_, $cart->getOrderTotal(true, 3), $adyen->displayName);

?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script type="text/javascript">

// instead of including the entire jquery library we just implement the single required function
function $(formname)
{
	return document.forms[formname.replace('#','')];
}

</script>
</head>
<body>

<?php
// Now redirect to Adyen
echo $adyen->execPayment($cart);
?>

</body>
</html>