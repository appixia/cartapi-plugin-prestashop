<?php
	include_once(dirname(__FILE__).'/templates.php');
	$firstkey = key($GLOBALS['APPIXIA_DEBUGGER_TEMPLATES']);
?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Appixia Debugger - Cart API</title>
</head>
<body>

<div style="margin-bottom:10px; padding:10px; background-color:#f2dede; font-size:15px; color:#9d0000; border:1px solid #ea9aa8;">
<b>IMPORTANT: When you finish your debugging session, press the "Clear Cookies" button below.</b><br>
If you don't, the debugger cookie might prevent you from seeing your regular store in the browser that accessed the debugger. This issue can only happen in browsers that access the debugger, so don't worry, this can't happen to your users.
</div>

<div style="margin-bottom:10px;">

<select id="templates" multiple="yes" style="width:300px; float: left; height:250px;">

<optgroup label="Default"></optgroup>
<?php

	foreach ($GLOBALS['APPIXIA_DEBUGGER_TEMPLATES'] as $template_name => $template)
	{
		echo '<option value="'.$template_name.'">'.$template_name.'</option>';
	}
	
	$override_template_file = dirname(__FILE__).'/../overrides/debug/templates.php';
	if (file_exists($override_template_file))
	{
		include_once($override_template_file);
		echo '<optgroup label="Override"></optgroup>';
		foreach ($GLOBALS['APPIXIA_DEBUGGER_OVERRIDE_TEMPLATES'] as $template_name => $template)
		{
			echo '<option value="'.$template_name.'">'.$template_name.'</option>';
		}
	}

?>

</select>

<div style="margin-left:310px;  height:250px;">

<p id="description"><?php echo $GLOBALS['APPIXIA_DEBUGGER_TEMPLATES'][$firstkey]['Description']; ?></p>

<textarea id="request" style="width: 100%; height: 180px; font-family: Courier; font-size: 13px; margin-bottom:10px;">
<?php echo $GLOBALS['APPIXIA_DEBUGGER_TEMPLATES'][$firstkey]['Url']; ?>
</textarea>

<input type="button" id="sendbutton" value="Send" title="Send current command to plugin and see response in iframe below"/>
<input type="button" id="logoutclearbutton" value="Logout" title="Cause current cart user to logout from the cart (opposite of BuyerLogin)"/>
<input type="button" id="clearbutton" value="Clear Cookies" title="Clear cookies created during the debugging session"/>

</div>

</div>

<iframe style="width:100%; height: 500px;">

</iframe>

<script src="http://code.jquery.com/jquery-latest.js"></script>
<script>

var template_urls = {};
<?php 
	foreach ($GLOBALS['APPIXIA_DEBUGGER_TEMPLATES'] as $template_name => $template) echo 'template_urls["'.$template_name.'"] = "' . $template['Url'] . '";' . "\n";
	if (isset($GLOBALS['APPIXIA_DEBUGGER_OVERRIDE_TEMPLATES'])) foreach ($GLOBALS['APPIXIA_DEBUGGER_OVERRIDE_TEMPLATES'] as $template_name => $template) echo 'template_urls["'.$template_name.'"] = "' . $template['Url'] . '";' . "\n";
?>

var template_descriptions = {};
<?php 
	foreach ($GLOBALS['APPIXIA_DEBUGGER_TEMPLATES'] as $template_name => $template) echo 'template_descriptions["'.$template_name.'"] = "' . $template['Description'] . '";' . "\n";
	if (isset($GLOBALS['APPIXIA_DEBUGGER_OVERRIDE_TEMPLATES'])) foreach ($GLOBALS['APPIXIA_DEBUGGER_OVERRIDE_TEMPLATES'] as $template_name => $template) echo 'template_descriptions["'.$template_name.'"] = "' . $template['Description'] . '";' . "\n";
?>

$(document).ready(function()
{
	$("select#templates").val("<?php echo $firstkey ?>");

	$("select#templates").change(function()
	{
		$("p#description").text(template_descriptions[$(this).val()]);
		$("textarea#request").val(template_urls[$(this).val()]);
	});
	
	$("input#sendbutton").click(function()
	{
		var url = $("textarea#request").val();
		$("iframe").attr("src", '');
		$("iframe").attr("src", url);
	});
	
	$("input#clearbutton").click(function()
	{
		$("iframe").attr("src", "clearcookies.php");
	});

	$("input#logoutclearbutton").click(function()
	{
		$("iframe").attr("src", "clearcookies.php?logout=1&");
	});
	
});

</script>

</body>
</html>