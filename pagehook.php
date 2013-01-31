<?php
/*
* 
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0).
* It is available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to info@appixia.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to newer
* versions in the future. If you wish to customize the module for your
* needs please look at the /overrides module directory or refer to
* http://kb.appixia.com for more information.
*
*/

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