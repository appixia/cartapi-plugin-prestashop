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

class CartAPI_Handlers_Items
{

	public function Handle_GetSingleItem($metadata, $request, $encoder)
	{
		// required arguments
		if (!isset($request['Id'])) CartAPI_Helpers::dieOnError($encoder, 'IncompleteRequest', 'Id argument missing');

		// load the product
		global $cookie;
		$id_lang = $cookie->id_lang;
		$product = new Product($request['Id'], false, $id_lang);
		if (!Validate::isLoadedObject($product)) CartAPI_Helpers::dieOnError($encoder, 'ItemNotFound', Tools::displayError('Product not found'));

		// create the response
		$response = CartAPI_Helpers::createSuccessResponse($encoder, CartAPI_Handlers_Helpers::getLocale());

		// add the item to the response
		$item = &$encoder->addContainer($response, 'Item');

		// fill in the item fields
		$encoder->addNumber($item, 'Id', $product->id);
		$this->addTitleFromProduct($encoder, $item, $product);
	
		$price = $this->getPriceFromProduct($product);
		$encoder->addNumber($item, 'Price', $price);
		$referencePrice = $this->getReferencePriceFromProduct($product);
		if ($referencePrice > $price) $encoder->addNumber($item, 'ReferencePrice', $referencePrice);
	
		$this->addThumbnailUrlFromProduct($encoder, $item, $product);
		$this->addSubtitleFromProduct($encoder, $item, $product);
		$this->addImageUrlFromProduct($encoder, $item, $product, $id_lang);
		$this->addFeaturesFromProduct($encoder, $item, $product, $id_lang);
		$this->addAvailabilityFromProduct($encoder, $item, $product);
		$this->addVariationsAndCombinationsFromProduct($encoder, $item, $product, $id_lang);
		$this->addResourcesFromProduct($encoder, $item, $product, $id_lang);
		$this->addExtraFieldsFromProduct($metadata, $request, $encoder, $item, $product, $id_lang);

		// show the response
		$encoder->render($response);
	}

	public function Handle_GetItemList($metadata, $request, $encoder)
	{
		// required arguments
		if (!isset($request['Paging'])) CartAPI_Helpers::dieOnError($encoder, 'IncompleteRequest', 'Paging argument missing');
		$sql_limit = CartAPI_Helpers::getSqlLimitFromPagingRequest($encoder, $request['Paging']);
	
		global $cookie;
		$id_lang = $cookie->id_lang;

		// optional arguments
		$sql_filters = array();
		if (isset($request['Filter']))
		{
			// change filters before the command is executed
			$this->overrideItemListFilters($request['Filter']);
		
			// TODO: support an array of filters, need to check how this works in the URL param decoder too.. may not be simple
			$db_field_name_map = array('Title' => 'pl.`name`', 'CategoryId' => 'cp.`id_category`');
			$sql_filters[] = CartAPI_Helpers::getSqlFilterFromFilter($encoder, $request['Filter'], $db_field_name_map);
		}
		
		$sql_orderby = 'p.`id_product` desc'; // default sort (newest items first)
		$this->overrideItemListSqlOrderBy($request, $sql_orderby);

		// complete the sql statement
		$sql_filters[] = 'p.`active` = 1';
		$sql_filters[] = 'pl.`id_lang` = '.(int)$id_lang;
		$sql_where = CartAPI_Helpers::getSqlWhereFromSqlFilters($sql_filters);
		$sql = '
			SELECT SQL_CALC_FOUND_ROWS p.`id_product`, pl.`name`, p.`active` 
			FROM `'._DB_PREFIX_.'product` p 
			LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON cp.`id_product` = p.`id_product`
			LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON p.`id_product` = pl.`id_product` 
			'.$sql_where.' 
			GROUP BY `id_product` 
			ORDER BY '.$sql_orderby.'
			'.$sql_limit;

		// load the products and the total element count
		$result = Db::getInstance()->ExecuteS($sql);
		$total_elements_row = Db::getInstance()->getRow('SELECT FOUND_ROWS()');
		$total_elements = intval(array_pop($total_elements_row));
		
		// change results before they are returned
		$this->overrideItemListResult($request, $result, $total_elements);

		// create the response
		$response = CartAPI_Helpers::createSuccessResponseWithPaging($encoder, $request['Paging'], $total_elements, CartAPI_Handlers_Helpers::getLocale());

		// add the items to the response if needed
		if (count($result) > 0) $items = &$encoder->addArray($response, 'Item');

		// encode each item
		foreach ($result as $row)
		{
			// to allow support for overrideItemListResult() to return objects instead of arrays, let's check if it's an object and fix as needed
			if (is_object($row) && ($row instanceof ProductCore))
			{
				$arr = array();
				$arr['id_product'] = $row->id;
				$arr['name'] = $row->name;
				$row = $arr; // make the switch from object to array
			}
		
			// encode the item
			$item = &$encoder->addContainerToArray($items);
			$encoder->addNumber($item, 'Id', $row['id_product']);
			$encoder->addString($item, 'Title', $row['name']);
		
			$price = $this->getPriceFromProductId($row['id_product']);
			$encoder->addNumber($item, 'Price', $price);
			$referencePrice = $this->getReferencePriceFromProductId($row['id_product']);
			if ($referencePrice > $price) $encoder->addNumber($item, 'ReferencePrice', $referencePrice);
		
			$this->addThumbnailUrlFromProductId($encoder, $item, $row['id_product']);
			$this->addExtraFieldsFromProductId($metadata, $request, $encoder, $item, $row['id_product']);
		}

		// show the response
		$encoder->render($response);
	}
	
	protected function overrideItemListFilters(&$filter)
	{
		return; // do nothing by default
	}
	
	protected function overrideItemListSqlOrderBy($request, &$sql_orderby)
	{
		return; // do nothing by default
	}
		
	protected function overrideItemListResult($request, &$result, &$total_elements)
	{
		return; // do nothing by default
	}

	protected function addFeaturesFromProduct($encoder, &$item, $product, $id_lang)
	{
		$features = $product->getFrontFeatures($id_lang);
		if (!is_array($features) || (count($features) == 0)) return;
		$_features = &$encoder->addArray($item, 'Feature');
		foreach ($features as $feature)
		{
			$_feature = &$encoder->addContainerToArray($_features);
			$encoder->addString($_feature, 'Name', $feature["name"]);
			$encoder->addString($_feature, 'Value', $feature["value"]);
		}
	}
	
	// returns an array of arrays (resources)
	protected function getResourceDictionariesFromProduct($product, $id_lang)
	{
		return array(); // do nothing by default
	}
	
	protected function addExtraFieldsFromProductId($metadata, $request, $encoder, &$item, $product_id)
	{
		// do nothing by default
	}
	
	protected function addExtraFieldsFromProduct($metadata, $request, $encoder, &$item, $product, $id_lang)
	{
		// do nothing by default
	}
	
	protected function addResourcesFromProduct($encoder, &$item, $product, $id_lang)
	{
		$resources = $this->getResourceDictionariesFromProduct($product, $id_lang);
		if ((!is_array($resources)) || (count($resources) == 0)) return;
		$_resources = &$encoder->addArray($item, 'Resource');
		foreach ($resources as $resource)
		{
			$_resource = &$encoder->addContainerToArray($_resources);
			foreach ($resource as $key => $value) $encoder->addString($_resource, $key, $value);
		}
	}

	protected function getImageUrl($name, $ids, $type)
	{
		global $link;
		$url = $link->getImageLink($name, $ids, $type); // older prestashop versions return a relative url here, so we must make sure it's absolute
		if (CartAPI_Handlers_Helpers::isAbsoluteUrl($url)) return $url;
		else return CartAPI_Handlers_Helpers::getShopDomain().$url;
	}

	protected function getThumbnailImageType()
	{
		return 'home';
	}

	protected function getThumbnailUrlFromImageId($image_id, $product_id = -1, $link_rewrite = NULL)
	{
		if ($link_rewrite === NULL) $link_rewrite = 'image';
		if ($product_id != -1) $image_id = (int)$product_id . '-' . (int)$image_id;
		return $this->getImageUrl($link_rewrite, $image_id, $this->getThumbnailImageType());
	}

	protected function getImagesImageType()
	{
		return 'thickbox';
	}

	protected function getImageUrlFromImageId($image_id, $product_id = -1, $link_rewrite = NULL)
	{
		if ($link_rewrite === NULL) $link_rewrite = 'image';
		if ($product_id != -1) $image_id = (int)$product_id . '-' . (int)$image_id;
		return $this->getImageUrl($link_rewrite, $image_id, $this->getImagesImageType());
	}

	protected function getPriceFromProduct($product)
	{
		return $product->getPrice(true, NULL, 2);
	}

	protected function getPriceFromProductId($product_id)
	{
		return Product::getPriceStatic($product_id, true, NULL, 2);
	}

	protected function getReferencePriceFromProduct($product)
	{
		return Tools::ps_round($product->getPriceWithoutReduct(false, NULL), 2);
	}

	protected function getReferencePriceFromProductId($product_id)
	{
		return Product::getPriceStatic($product_id, true, NULL, 2, NULL, false, false);
	}

	protected function addThumbnailUrlFromProduct($encoder, &$item, $product)
	{
		// taken from Product::getCoverWs - we're not using getCoverWs directly since it's not avail in old prestashop versions
		$cover = $product->getCover($product->id);
		$cover_image_id = $cover['id_image'];

		$encoder->addString($item, 'ThumbnailUrl', $this->getThumbnailUrlFromImageId($cover_image_id, $product->id));
	}

	protected function addThumbnailUrlFromProductId($encoder, &$item, $product_id)
	{
		$cover_arr = Product::getCover($product_id);
		if (is_array($cover_arr)) $encoder->addString($item, 'ThumbnailUrl', $this->getThumbnailUrlFromImageId(array_pop($cover_arr), $product_id));
	}
	
	protected function getIsAvailableFromProduct($product)
	{
		return $product->checkQty(1);
	}
	
	protected function addAvailabilityFromProduct($encoder, &$item, $product)
	{
		$encoder->addBoolean($item, 'Available', (bool)$this->getIsAvailableFromProduct($product));
	}

	protected function addImageUrlFromProduct($encoder, &$item, $product, $id_lang)
	{
		$images = $product->getImages($id_lang);
		if (!is_array($images) || (count($images) == 0)) return;
		$_imageUrls = &$encoder->addArray($item, 'ImageUrl');
		foreach ($images as $image)
		{
			$image_id = -1;
			if (isset($image['id_image'])) $image_id = $image['id_image'];
			if ($image_id != -1) $encoder->addStringToArray($_imageUrls, $this->getImageUrlFromImageId($image_id, $product->id));
		}
	}
	
	protected function addTitleFromProduct($encoder, &$item, $product)
	{
		$encoder->addString($item, 'Title', $product->name);
	}

	protected function addSubtitleFromProduct($encoder, &$item, $product)
	{
		if ($product->description_short) 
		{
			$subtitle = $product->description_short;
			$subtitle = CartAPI_Handlers_Helpers::removeHtmlTags($subtitle);
			$encoder->addString($item, 'Subtitle', $subtitle);
		}
	}
	
	protected function overrideVariationDefaultValue($variationId, &$defaultValueId)
	{
		return; // do nothing by default
	}

	protected function addVariationsAndCombinationsFromProduct($encoder, &$item, $product, $id_lang)
	{
		$attributesGroups = $product->getAttributesGroups($id_lang);
		if (!is_array($attributesGroups) || (count($attributesGroups) == 0)) return;
		$combinationImages = $product->getCombinationImages($id_lang);
	
		// prepare the groups.. taken from ProductController line 280
		$colors = array();
		$groups = array();
		$combinations = array();
		foreach ($attributesGroups as $row)
		{
			/* Color management */
			if (((isset($row['attribute_color']) AND $row['attribute_color']) OR (file_exists(_PS_COL_IMG_DIR_.$row['id_attribute'].'.jpg'))) AND $row['id_attribute_group'] == $product->id_color_default)
			{
				$colors[$row['id_attribute']]['value'] = $row['attribute_color'];
				$colors[$row['id_attribute']]['name'] = $row['attribute_name'];
				if (!isset($colors[$row['id_attribute']]['attributes_quantity']))
					$colors[$row['id_attribute']]['attributes_quantity'] = 0;
					$colors[$row['id_attribute']]['attributes_quantity'] += (int)($row['quantity']);
			}
		
			if (!isset($groups[$row['id_attribute_group']]))
			{
				$groups[$row['id_attribute_group']] = array(
					'name' => $row['public_group_name'],
					'is_color_group' =>	$row['is_color_group'],
					'default' => -1,
				);
			}
	
			$groups[$row['id_attribute_group']]['attributes'][$row['id_attribute']] = $row['attribute_name'];
			if ($row['default_on'] && $groups[$row['id_attribute_group']]['default'] == -1)
				$groups[$row['id_attribute_group']]['default'] = (int)($row['id_attribute']);
			if (!isset($groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']]))
				$groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']] = 0;
			$groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']] += (int)($row['quantity']);	
				
			$combinations[$row['id_product_attribute']]['attributes_values'][$row['id_attribute_group']] = $row['attribute_name'];
			$combinations[$row['id_product_attribute']]['attributes'][$row['id_attribute_group']] = (int)($row['id_attribute']);
			$combinations[$row['id_product_attribute']]['price'] = (float)($row['price']);
			$combinations[$row['id_product_attribute']]['ecotax'] = (float)($row['ecotax']);
			$combinations[$row['id_product_attribute']]['weight'] = (float)($row['weight']);
			$combinations[$row['id_product_attribute']]['quantity'] = (int)($row['quantity']);
			$combinations[$row['id_product_attribute']]['reference'] = $row['reference'];
			if (isset($row['ean13'])) $combinations[$row['id_product_attribute']]['ean13'] = $row['ean13'];
			if (isset($row['unit_price_impact']))  $combinations[$row['id_product_attribute']]['unit_impact'] = $row['unit_price_impact'];
			if (isset($row['minimal_quantity']))  $combinations[$row['id_product_attribute']]['minimal_quantity'] = $row['minimal_quantity'];
			$combinations[$row['id_product_attribute']]['id_image'] = isset($combinationImages[$row['id_product_attribute']][0]['id_image']) ? $combinationImages[$row['id_product_attribute']][0]['id_image'] : -1;
		}
		
		// check if the product is available when out of stock, and only if not, we need to check quantity of each combination
		$shouldCheckCombinationAvailability = !$product->isAvailableWhenOutOfStock($product->out_of_stock);
		// get the default product price
		$defaultProductPrice = $this->getPriceFromProduct($product);
	
		// add the variations
		$_variations = &$encoder->addArray($item, 'Variation');
		foreach ($groups as $group_id => $group)
		{
			$_variation = &$encoder->addContainerToArray($_variations);
			$encoder->addString($_variation, 'Id', $group_id);
			$encoder->addString($_variation, 'Name', $group["name"]);
			$_variationValues = &$encoder->addArray($_variation, 'Value');
			foreach ($group['attributes'] as $attribute_id => $attribute_value)
			{
				$_variationValue = &$encoder->addContainerToArray($_variationValues);
				$encoder->addString($_variationValue, 'Id', $attribute_id);
				$encoder->addString($_variationValue, 'Name', $attribute_value);
				
				// color
				if (isset($colors[$attribute_id]))
				{
					$encoder->addString($_variationValue, 'RenderType', 'HtmlColor');
					$encoder->addString($_variationValue, 'RenderValue', $colors[$attribute_id]['value']);
				}
			}
			
			// default value
			if (isset($group['default']))
			{
				$defaultValueId = $group['default'];
				$this->overrideVariationDefaultValue($group_id, $defaultValueId);
				$encoder->addString($_variation, 'DefaultValueId', $defaultValueId);
			}
		}
	
		// add the combinations
		if (count($combinations) == 0) return;
		$_combinations = &$encoder->addArray($item, 'Combination');
		foreach ($combinations as $id_product_attribute => $combination)
		{
			$_combination = &$encoder->addContainerToArray($_combinations);
			
			// add the variations that are part of this combination
			$_variations = &$encoder->addArray($_combination, 'Variation');
			foreach ($combination['attributes'] as $id_attribute_group => $attribute_id)
			{
				$_variation = &$encoder->addContainerToArray($_variations);
				$encoder->addString($_variation, 'Id', $id_attribute_group);
				$encoder->addString($_variation, 'ValueId', $attribute_id);
			}
		
			// add the overrides for this combination
			$_override = &$encoder->addContainer($_combination, 'Override');
			
			// thumbnail url override
			if ($combination['id_image'] != -1)
			{
				$encoder->addString($_override, 'ThumbnailUrl', $this->getThumbnailUrlFromImageId($combination['id_image'], $product->id));
			}
		
			// image urls override
			if (isset($combinationImages[$id_product_attribute]) && is_array($combinationImages[$id_product_attribute]) && (count($combinationImages[$id_product_attribute]) > 0))
			{
				$_imageUrls = &$encoder->addArray($_override, 'ImageUrl');
				foreach ($combinationImages[$id_product_attribute] as $combinationImage)
				{
					$image_id = -1;
					if (isset($combinationImage['id_image'])) $image_id = $combinationImage['id_image'];
					if ($image_id != -1) $encoder->addStringToArray($_imageUrls, $this->getImageUrlFromImageId($image_id, $product->id));
				}
			}
			
			// availability override
			if ($shouldCheckCombinationAvailability)
			{
				if ($combination['quantity'] > 0) $encoder->addBoolean($_override, 'Available', true);
				else $encoder->addBoolean($_override, 'Available', false);
			}

			// price override
			$combinationPrice = $product->getPrice(true, $id_product_attribute, 2);
			if ($combinationPrice != $defaultProductPrice)
			{
				$encoder->addNumber($_override, 'Price', $combinationPrice);
			}
		
			// add more overrides..
		}
	}
	
}

?>