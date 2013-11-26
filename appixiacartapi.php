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
		$this->module_key = 'd6b282f5b364787e7b0f9aac1df1b4f7';
		$this->version = '1.0.5';
		$this->author = 'Appixia';
		$this->need_instance = 0;

		parent::__construct();

		$this->displayName = $this->l('Appixia');
		$this->description = $this->l('Appixia mobile engine integration.');
	}
	
	public function install()
	{
		// regular hooks
		if (!parent::install() OR !$this->registerHook('header')) return false;
		
		// 1.5 specific hooks
		if ((_PS_VERSION_ >= '1.5') && (!$this->registerHook('displayMobileHeader'))) return false;

		return true;
	}

	public function uninstall()
	{
		return parent::uninstall();
	}
	
	public function hookHeader($params)
	{
		return $this->hijackPage();	
	}

	public function hookDisplayMobileHeader()
	{
		return $this->hijackPage();
	}

	// hijacking a page means not letting the app display this page in one of its HtmlViews
	// for example, web pages may be displayed in the app during checkout (paypal.com mobile web checkout)
	// if for some reason, these pages redirect the app back to the website, we want to let the app handle this redirect
	// instead of showing the website inside the HtmlView
	// in any case, hijacking is only done to appixia engines, so regular users will never be hijacked
	public function hijackPage()
	{
		if (CartAPI_Handlers_Helpers::isAppixiaMobileEngine()) // only hijack appixia engines, not regular users
		{
			// allow to disable hijacking by addinging a url parameter (appixiaignore=1&)
			if (isset($_REQUEST['appixiaignore']) && $_REQUEST['appixiaignore']) return;

			// perform the hijack
			$url = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
			$redirectTo = CartAPI_Handlers_Helpers::getShopBaseUrl().'modules/appixiacartapi/pagehook.php?q='.urlencode($url);
			header('Location: '.$redirectTo);
			exit;
		}
	}
	
}

?>