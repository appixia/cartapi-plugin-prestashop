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

if (!defined('_PS_VERSION_'))
	exit;
	
include_once(dirname(__FILE__).'/Helpers.php');

class AppixiaCartApi extends Module
{

	public function __construct()
	{
		$this->name = 'appixiacartapi';
		$this->tab = 'mobile';
		$this->version = '1.0.2';
		$this->author = 'Appixia';
		$this->need_instance = 0;

		parent::__construct();

		$this->displayName = $this->l('Appixia');
		$this->description = $this->l('Appixia mobile engine integration.');
	}
	
	public function install()
	{
		if (!parent::install() OR !$this->registerHook('header')) return false;
		return true;
	}

	public function uninstall()
	{
		return parent::uninstall();
	}
	
	public function hookHeader($params)
	{
		if (CartAPI_Handlers_Helpers::isAppixiaMobileEngine())
		{
			$url = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
			Tools::redirect('modules/appixiacartapi/pagehook.php?q='.urlencode($url));
			exit;
		}
	}
	
}

?>