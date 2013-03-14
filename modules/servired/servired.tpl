<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>

Redirecting...

<!-- place the form here -->
<form action="{$urltpv}" method="post" id="servired_form" class="hidden">	
	<input type="hidden" name="Ds_Merchant_Amount" value="{$cantidad}" />
    <input type="hidden" name="Ds_Merchant_Currency" value="{$moneda}" />
	<input type="hidden" name="Ds_Merchant_Order" value="{$pedido}" />
	<input type="hidden" name="Ds_Merchant_MerchantCode" value="{$codigo}" />
	<input type="hidden" name="Ds_Merchant_Terminal" value="{$terminal}" />
	<input type="hidden" name="Ds_Merchant_TransactionType" value="{$trans}" />
	<input type="hidden" name="Ds_Merchant_Titular" value="{$titular}" />
	<input type="hidden" name="Ds_Merchant_MerchantName" value="{$nombre}" />
  {if $notificacion>0}
	<input type="hidden" name="Ds_Merchant_MerchantURL" value="{$urltienda}" />
  {/if}
	<input type="hidden" name="Ds_Merchant_ProductDescription" value="{$productos}" />
	<input type="hidden" name="Ds_Merchant_UrlOK" value="{$UrlOk}" />
	<input type="hidden" name="Ds_Merchant_UrlKO" value="{$UrlKO}" />
	<input type="hidden" name="Ds_Merchant_MerchantSignature" value="{$firma}" />
	<input type="hidden" name="Ds_Merchant_ConsumerLanguage" value="{$idioma_tpv}" />
    <input type="hidden" name="Ds_Merchant_PayMethods" value="T" />
</form>


<script type="text/javascript">

// instead of including the entire jquery library we just implement the single required function
function $(formname)
{
	return document.forms[formname.replace('#','')];
}

// submit the form immediately
$('#servired_form').submit();

</script>

</body>
</html>