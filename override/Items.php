<?php

// this is an example of an override class

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Items.php');

class CartAPI_Handlers_Override_Items extends CartAPI_Handlers_Items
{
	// 	override any functions you want to change here

	// this is an example override to add the HTML description of an item to the item resources list
	protected function getResourceDictionariesFromProduct($product, $id_lang)
	{
		$resources = array();
		
		$resource = array(
			'Id' => 'Description',
			'Type' => 'HTML',
			'ContentUrl' => CartAPI_Handlers_Helpers::getCartApiHomeUrl().'override/cms/product_description.php?id_product='.(int)($product->id).'&',
		);
		$resources[] = $resource;
		
		// all done
		return $resources;
	}
}

?>