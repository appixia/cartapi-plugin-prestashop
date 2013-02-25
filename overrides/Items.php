<?php

// this is an override class related to products (item details, item list)
// if you need to customize the module to your needs, make all changes here

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Items.php');

class CartAPI_Handlers_Override_Items extends CartAPI_Handlers_Items
{
	// 	override any functions you want to change (from the core Items.php) here

	// if you want to change the image type for product thumbnails, change it here
	public function getThumbnailImageType()
	{
		return 'home';
	}

	// if you want to change the image type for detailed product images (gallery), change it here
	public function getImagesImageType()
	{
		return 'thickbox';
	}

	// this is an example override to add the HTML description of an item to the item resources list
	public function getResourceDictionariesFromProduct($product, $id_lang)
	{
		$resources = array();
		
		$resource = array(
			'Id' => 'Description',
			'Type' => 'HTML',
			'ContentUrl' => CartAPI_Handlers_Helpers::getCartApiHomeUrl().'overrides/cms/product_description.php?id_product='.(int)($product->id).'&',
		);
		$resources[] = $resource;
		
		// all done
		return $resources;
	}
}

?>