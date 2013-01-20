<?php

if (!defined('_PS_VERSION_'))
	exit;
	
include_once(dirname(__FILE__).'/Helpers.php');

class AppixiaCartApi extends Module
{

	public function __construct()
	{
		$this->name = 'appixiacartapi';
		$this->tab = 'Mobile';
		$this->version = '1.0.1';
		$this->author = 'Appixia';

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