<?php

include(dirname(__FILE__).'/../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../init.php');

include(dirname(__FILE__).'/cuatrob.php');

$cuatrob = new CartAPI_Module_Cuatrob();

// get all the fields
$transRef = $cart->id;
$store = $cuatrob->datosoperacion();
$enpruebas = Configuration::get('CUATROB_PRUEBAS');

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

<form action="<?php if ($enpruebas) { ?>https://tpv2.4b.es/simulador/teargral.exe<?php } else { ?>https://tpv.4b.es/tpvv/teargral.exe<?php } ?>" method="post" id="cuatrob_form" class="hidden">	
	<input type="hidden" name="uid" value="<?php echo $transRef ?>" />
	<input type="hidden" name="cc" value="<?php echo $store ?>" />
</form>

<script type="text/javascript">
	$('#cuatrob_form').submit();
</script>

</body>
</html>