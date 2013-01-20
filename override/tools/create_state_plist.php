<?php

include(dirname(__FILE__).'/../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../init.php');

// debug:
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/errorlog');
error_reporting(E_ALL);

if (!isset($_GET['country_id'])) die('Usage: ?country_id=XXX');
$country_id = $_GET['country_id'];

$sql = '
		SELECT s.`id_state`, s.`name`
		FROM `'._DB_PREFIX_.'state` s 
		WHERE s.`id_country` = '.(int)$country_id.' 
		AND s.`active` = 1
		ORDER BY s.`name` ASC
		';

$result = Db::getInstance()->ExecuteS($sql);

if (!is_array($result) || (count($result) == 0)) die('No states found');

echo '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
	<key>Values</key>
	<array>
';

// encode each item
foreach ($result as $row)
{

	echo '		<dict>
			<key>Label</key>
			<string>'.$row['name'].'</string>
			<key>Value</key>
			<string>'.$row['id_state'].'</string>
		</dict>
';

}

echo '	</array>
</dict>
</plist>
';

?>