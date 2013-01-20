<?php

include(dirname(__FILE__).'/../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../init.php');

include(dirname(__FILE__).'/kiala.php');

// make sure a point was selected
if (!isset($_REQUEST['shortkpid']))
{
	echo 'Kiala did not return anything';
	var_dump($_REQUEST);
	exit;
}

// $kiala = new CartAPI_Module_Kiala();
// kiala does not have code to parse a Point class from $_REQUEST (only from xml), so parse manually

// code taken from module kiala kiala.php displayPoint
$kiala_order = KialaOrder::getEmptyKialaOrder($cart->id);
$kiala_order->point_short_id = $_REQUEST['shortkpid'];
$kiala_order->point_name = $_REQUEST['kpname'];
$kiala_order->point_street = $_REQUEST['street'];
$kiala_order->point_zip = $_REQUEST['zip'];
$kiala_order->point_city = $_REQUEST['city'];
$kiala_order->point_location_hint = $_REQUEST['locationhint'];
$kiala_order->id_cart = (int)$cart->id;
$kiala_order->save();

Tools::redirect('');

?>