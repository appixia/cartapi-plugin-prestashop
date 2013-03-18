<?php

include(dirname(__FILE__).'/../../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../../init.php');

require_once(dirname(__FILE__).'/../Helpers.php');
require_once(dirname(__FILE__).'/mobilize.php');

$cms = false;
if ($id_cms = (int)Tools::getValue('id_cms')) $cms = new CMS($id_cms, $cookie->id_lang);

if (!Validate::isLoadedObject($cms)) die('Not found');
if (property_exists('CMS','active') && (!$cms->active)) die('Not found');

// mobilize fixes
$mobilize_links = Tools::getValue('links');
$mobilize_img = Tools::getValue('img');
if (($mobilize_links !== false) || ($mobilize_img !== false)) $skip_mobilize = false;
else $skip_mobilize = true;

// add css
$head = '';
$css_relpath = '../overrides/cms/cms.css';
$css_file = dirname(__FILE__).'/'.$css_relpath;
if (file_exists($css_file))
{
	$head = '<link href="'.$css_relpath.'" rel="stylesheet" type="text/css" media="all" />';
}


$smarty->assign('cms', $cms);
$smarty->assign('head', $head);

if ($skip_mobilize)
{
	$smarty->display(dirname(__FILE__).'/cms.tpl');
}
else
{
	$html = $smarty->fetch(dirname(__FILE__).'/cms.tpl');
	echo mobilize($html, $mobilize_links, $mobilize_img);
}

?>