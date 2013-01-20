<?php

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../init.php');

include(dirname(__FILE__).'/sermepa.php');

if (!$cookie->isLogged())
    die('Error: not logged in');

$sermepa = new CartAPI_Module_Sermepa();

// Create an order before we redirect the shopper
$sermepa->validateOrder($cart->id, _PS_OS_SERMEPA_WAITING_, $cart->getOrderTotal(true, 3), $sermepa->name);

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
// Now redirect to Sermepa
echo $sermepa->execPayment($cart);
?>

</body>
</html>