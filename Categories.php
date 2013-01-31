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

class CartAPI_Handlers_Categories
{

	public function Handle_GetCategoryList($metadata, $request, $encoder)
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
			$this->overrideCategoryListFilters($request['Filter']);
			
			// TODO: support an array of filters, need to check how this works in the URL param decoder too.. may not be simple
			$db_field_name_map = array('ParentId' => 'c.`id_parent`');
			$sql_filters[] = CartAPI_Helpers::getSqlFilterFromFilter($encoder, $request['Filter'], $db_field_name_map);
		}
		
		$sql_orderby = '`position` ASC'; // default sort (by db position)
		if (!property_exists('Category','position')) $sql_orderby = '`name` ASC'; // older prestashop versions don't have the position field
		$this->overrideCategoryListSqlOrderBy($request, $sql_orderby);

		// complete the sql statement
		$sql_filters[] = 'c.`active` = 1';
		$sql_filters[] = 'cl.`id_lang` = '.(int)$id_lang;
		$sql_where = CartAPI_Helpers::getSqlWhereFromSqlFilters($sql_filters);
		$sql = '
			SELECT SQL_CALC_FOUND_ROWS c.`id_category`, cl.`name`, c.`active` 
			FROM `'._DB_PREFIX_.'category` c 
			LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON c.`id_category` = cl.`id_category` 
			'.$sql_where.' 
			GROUP BY `id_category`
			ORDER BY '.$sql_orderby.'
			'.$sql_limit;

		// load the categories and the total element count
		$result = Db::getInstance()->ExecuteS($sql);
		$total_elements_row = Db::getInstance()->getRow('SELECT FOUND_ROWS()');
		$total_elements = intval(array_pop($total_elements_row));		
		
		// change results before they are returned
		$this->overrideCategoryListResult($request, $result, $total_elements);

		// create the response
		$response = CartAPI_Helpers::createSuccessResponseWithPaging($encoder, $request['Paging'], $total_elements);

		// add the items to the response if needed
		if (count($result) > 0) $categories = &$encoder->addArray($response, 'Category');

		// encode each item
		foreach ($result as $row)
		{
			// encode the item
			$category = &$encoder->addContainerToArray($categories);
			$encoder->addString($category, 'Id', $row['id_category']);
			$encoder->addString($category, 'Title', $row['name']);
			// $this->addContainsItemsFromCategoryId($encoder, $category, $row['id_category']);
			$this->addContainsCategoriesFromCategoryId($encoder, $category, $row['id_category']);
			$this->addThumbnailUrlFromCategoryId($encoder, $category, $row['id_category']);
			$this->addResourcesFromCategoryId($encoder, $category, $row['id_category']);
		}

		// show the response
		$encoder->render($response);
	}
	
	protected function overrideCategoryListResult($request, &$result, &$total_elements)
	{
		return; // do nothing by default
	}
	
	protected function overrideCategoryListFilters(&$filter)
	{
		// replace an empty parent with the root category
		if (($filter['Field'] == 'ParentId') && ($filter['Value'] == ''))
		{
			$filter['Value'] = $this->getRootCategoryId();
		}
	}

	protected function overrideCategoryListSqlOrderBy($request, &$sql_orderby)
	{
		return; // do nothing by default
	}
	
	protected function getRootCategoryId()
	{
		// the value 1 is hard-coded in Prestashop as root (under Category::getRootCategory)
		return 1;
	}
	
	protected function getThumbnailImageType()
	{
		return 'large';
	}

	protected function getImageUrl($name, $id_category, $type)
	{
		global $link;
		$url = $link->getCatImageLink($name, $id_category, $type);
		if (CartAPI_Handlers_Helpers::isAbsoluteUrl($url)) return $url; // new prestashop versions (above 1.5) return an absolute url in getCatImageLink
		if (method_exists('Link','getMediaLink')) return $link->getMediaLink($url);
		else return CartAPI_Handlers_Helpers::getShopDomain().$url; // older prestashop versions don't support media servers
	}

	protected function getThumbnailUrlFromImageId($image_id, $link_rewrite = NULL)
	{
		global $link;
		if ($link_rewrite === NULL) $link_rewrite = 'image';
		return $this->getImageUrl($link_rewrite, $image_id, $this->getThumbnailImageType());
	}

	protected function addThumbnailUrlFromCategoryId($encoder, &$category, $category_id)
	{
		if (!file_exists(_PS_CAT_IMG_DIR_.$category_id.'.jpg')) return;
		$encoder->addString($category, 'ThumbnailUrl', $this->getThumbnailUrlFromImageId($category_id));
	}
	
	protected function getContainsItemsFromCategoryId($category_id)
	{
		$containsItems = false;
		$sql = '
			SELECT cp.`id_product`
			FROM `'._DB_PREFIX_.'product` p
			LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON p.`id_product` = cp.`id_product`
			WHERE cp.`id_category` = '.(int)$category_id.' AND p.`active` = 1
			';
		$result = Db::getInstance()->getRow($sql); // getRow adds LIMIT 1 so this will be efficient
		if ($result) $containsItems = true;
		return $containsItems;
	}

	protected function addContainsItemsFromCategoryId($encoder, &$category, $category_id)
	{
		$containsItems = $this->getContainsItemsFromCategoryId($category_id);
		$encoder->addBoolean($category, 'ContainsItems', $containsItems);
	}
	
	protected function getContainsCategoriesFromCategoryId($category_id)
	{
		$containsCategories = false;
		$sql = '
			SELECT c.`id_category`
			FROM `'._DB_PREFIX_.'category` c
			WHERE c.`id_parent` = '.(int)$category_id.' AND c.`active` = 1
			';
		$result = Db::getInstance()->getRow($sql); // getRow adds LIMIT 1 so this will be efficient
		if ($result) $containsCategories = true;
		return $containsCategories;
	}

	protected function addContainsCategoriesFromCategoryId($encoder, &$category, $category_id)
	{
		$containsCategories = $this->getContainsCategoriesFromCategoryId($category_id);
		$encoder->addBoolean($category, 'ContainsCategories', $containsCategories);
	}
	
	protected function getResourceDictionariesFromCategoryId($category_id)
	{
		return array(); // do nothing by default
	}
	
	protected function addResourcesFromCategoryId($encoder, &$category, $category_id)
	{
		$resources = $this->getResourceDictionariesFromCategoryId($category_id);
		if ((!is_array($resources)) || (count($resources) == 0)) return;
		$_resources = &$encoder->addArray($category, 'Resource');
		foreach ($resources as $resource)
		{
			$_resource = &$encoder->addContainerToArray($_resources);
			foreach ($resource as $key => $value) $encoder->addString($_resource, $key, $value);
		}
	}
	
}

?>