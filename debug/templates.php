<?php

$GLOBALS['APPIXIA_DEBUGGER_TEMPLATES'] = array(

	'GetSingleItem' => array(
		'Description' => 'Displays the full details for a single item (Id param holds the Prestashop item id)',
		'Url' => '../api.php?X-OPERATION=GetSingleItem&Id=11&',
	),
	
	'GetItemList - all items (first 25)' => array(
		'Description' => 'Get a list of items (no filter), done in pages (Paging instructs how many items per page and which page to get)',
		'Url' => '../api.php?X-OPERATION=GetItemList&Paging[ElementsPerPage]=25&Paging[PageNumber]=1&',
	),
	
	'GetItemList - items under category (first 25)' => array(
		'Description' => 'Get a list of item filtered under a specific category (Filter[Value] param holds the Prestashop category id)',
		'Url' => '../api.php?X-OPERATION=GetItemList&Paging[ElementsPerPage]=25&Paging[PageNumber]=1&Filter[Field]=CategoryId&Filter[Relation]=Equal&Filter[Value]=11&',
	),
	
	'GetCategoryList - all categories (first 5)' => array(
		'Description' => 'Get a list of categories (no filter), done in pages (Paging instructs how many categories per page and which page to get)',
		'Url' => '../api.php?X-OPERATION=GetCategoryList&Paging[PageNumber]=1&Paging[ElementsPerPage]=5&',
	),
	
	'GetCategoryList - root categories (first 5)' => array(
		'Description' => 'Get a list of subcategories under root (empty Filter[Value])',
		'Url' => '../api.php?X-OPERATION=GetCategoryList&Paging[PageNumber]=1&Paging[ElementsPerPage]=5&Filter[Field]=ParentId&Filter[Relation]=Equal&Filter[Value]=&',
	),
	
	'GetCategoryList - sub-categories of category (first 5)' => array(
		'Description' => 'Get a list of subcategories under a category (Filter[Value] param holds the Prestashop category id of the parent)',
		'Url' => '../api.php?X-OPERATION=GetCategoryList&Paging[PageNumber]=1&Paging[ElementsPerPage]=5&Filter[Field]=ParentId&Filter[Relation]=Equal&Filter[Value]=11&',
	),
	
	'BuyerLogin - using username and password' => array(
		'Description' => 'Buyer login (Username param holds the user email, Password param holds the user password)',
		'Url' => '../api.php?X-OPERATION=BuyerLogin&Username=john%40example.com&Password=123456&',
	),
	
	'BuyerRegister - using username and password' => array(
		'Description' => 'Buyer register (Buyer[] params hold the user information)',
		'Url' => '../api.php?X-OPERATION=BuyerRegister&Buyer[Username]=john%40example.com&Buyer[Password]=123456&Buyer[FirstName]=John&Buyer[LastName]=Smith&',
	),
	
	'CMS Page - plain html' => array(
		'Description' => 'Show a CMS page without modifications (id_cms param holds the Prestashop cms page id)',
		'Url' => '../cms/cms.php?id_cms=11&isolang=en&',
	),

	'CMS Page - fixed links and images' => array(
		'Description' => 'Show a CMS page with removing links and fixing relative image paths (id_cms param holds the Prestashop cms page id)',
		'Url' => '../cms/cms.php?id_cms=11&isolang=en&links=no&img=fix&',
	),

	'GetOrderUpdate - add simple product to cart' => array(
		'Description' => 'Add a product (without variations) to cart (ItemId controls the id of the product being added)',
		'Url' => '../api.php?X-OPERATION=GetOrderUpdate&Order[OrderItem][0][Id]=1&Order[OrderItem][0][Quantity]=1&Order[OrderItem][0][ItemId]=6&',
	),

	'GetOrderUpdate - add combination product to cart' => array(
		'Description' => 'Add a product (with variations) to cart (ItemId controls the id of the product being added, Variation Id and ValueId control the combination)',
		'Url' => '../api.php?X-OPERATION=GetOrderUpdate&Order[OrderItem][0][Id]=1&Order[OrderItem][0][Quantity]=1&Order[OrderItem][0][Combination][Variation][0][Id]=1&Order[OrderItem][0][Combination][Variation][0][ValueId]=15&Order[OrderItem][0][ItemId]=5&',
	),

);

?>