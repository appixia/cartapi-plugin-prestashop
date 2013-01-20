<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

function mobilize($html, $arg_links, $arg_img)
{
	$dom = new DOMDocument();
	$dom->loadHTML($html);
	
	// remove links if needed
	if ($arg_links == 'no')
	{
		$links = $dom->getElementsByTagName('a');
		while ($links->length > 0)
		{
			foreach ($links as $link) mobilize_remove_element($link);
			$links = $dom->getElementsByTagName('a');
		}
	}
	
	// fix images if needed
	if ($arg_img == 'fix')
	{
		$imgs = $dom->getElementsByTagName('img');
		foreach ($imgs as $img) mobilize_fix_img_src($img);
	}
	
	return $dom->saveHTML(); 
}

// taken from http://stackoverflow.com/questions/4675460/php-dom-remove-element-leave-contents
function mobilize_remove_element(DOMNode $link)
{
	// Move all link tag content to its parent node just before it.
  	while($link->hasChildNodes()) {
    	$child = $link->removeChild($link->firstChild);
    	$link->parentNode->insertBefore($child, $link);
  	}
  	// Remove the link tag.
  	$link->parentNode->removeChild($link);
}

function mobilize_fix_img_src(DOMNode $img)
{
	$src = $img->getAttribute('src');
	$src = '../' . $src;
	$img->setAttribute('src', $src);
}

?>