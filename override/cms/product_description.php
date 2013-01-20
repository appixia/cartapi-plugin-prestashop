<?php

include(dirname(__FILE__).'/../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../init.php');

require_once(dirname(__FILE__).'/../../Helpers.php');


// make sure we were given a product id
if (!isset($_GET['id_product'])) die('ERROR: missing product id');
$product = new Product(Tools::getValue('id_product'), true, $cookie->id_lang);
if (!Validate::isLoadedObject($product)) exit(); // show nothing if not found
$smarty->assign('product', $product);

$smarty->force_compile = true; // remove after tpl debug
$smarty->display('product_description.tpl');

?>