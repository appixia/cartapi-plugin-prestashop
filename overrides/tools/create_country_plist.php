<?php

include(dirname(__FILE__).'/../../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../../init.php');

// debug:
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/errorlog');
error_reporting(E_ALL);

if (!isset($_GET['language_id'])) die('Usage: ?language_id=XXX');
$language_id = $_GET['language_id'];

$sql = '
		SELECT c.`id_country`, c.`name`
		FROM `'._DB_PREFIX_.'country_lang` c 
		WHERE c.`id_lang` = '.(int)$language_id.' 
		ORDER BY c.`name` ASC
		';

$result = Db::getInstance()->ExecuteS($sql);

if (!is_array($result) || (count($result) == 0)) die('No countries found');

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
			<string>'.$row['id_country'].'</string>
		</dict>
';

}

echo '	</array>
</dict>
</plist>
';

?>