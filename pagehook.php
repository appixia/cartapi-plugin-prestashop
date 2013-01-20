<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');

require_once(dirname(__FILE__).'/Helpers.php');

$url = CartAPI_Handlers_Helpers::getShopDomain() . $_REQUEST['q'];

header('Location: bridge://SendMessageToParent/Url?'.urlencode($url));

?>

<html>
<body>

<p>Appixia page hook, delete the "appixia" cookie if you dont want to see this.</p>
<p>Original url: <?php echo $url ?></p>

</body>
</html>