<?php

// this is an override class related to product categories (category list)
// if you need to customize the module to your needs, make all changes here

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Categories.php');

class CartAPI_Handlers_Override_Categories extends CartAPI_Handlers_Categories
{
	// 	override any functions you want to change (from the core Categories.php) here

	// if you want to change the image type for category thumbnails, change it here
	public function getThumbnailImageType()
	{
		return 'large'; // change this to 'large_default' if images don't show in Prestashop 1.5
	}
}

?>