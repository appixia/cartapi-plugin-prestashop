<?php

include(dirname(__FILE__).'/../../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../../init.php');

$appixia = Module::getInstanceByName('appixiacartapi');
$appixia->hijackPage();
	
?>
<html>
<body>

Cancelling payment

</body>
</html>