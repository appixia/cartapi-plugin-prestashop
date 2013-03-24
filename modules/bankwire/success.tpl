{*
*  This file shows the bankwire confirmation message which includes the bank details
*  It is very similar to the website file PRESTASHOP/modules/bankwire/payment_return.tpl
*  If you've changed your website tpl, you can place the same changes in this tpl
*  Please notice how l2 is used for translations instead of l - this is because l2 can
*  take translations from other tpl file so you don't need to add backoffice translations
*  for this file separately, they are just taken from the original payment_return.tpl
*}

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
	.bold {
		font-weight: bold;
	}
	.border {
		border: 1px solid #e5e5e5;
		background-color: #f5f5f5;
		padding: 1px;
	}
	.price {
		font-size: 16px;
		font-weight: bold;	
	}
</style>
</head>
<body style="font-family: Helvetica,Arial,sans-serif; font-size: 14px; color: #000000; padding:10px;">

{l2 s='Please send us a bank wire with:' mod='bankwire' tpl='payment_return'}
<br /><br />- {l2 s='an amount of' mod='bankwire' tpl='payment_return'} <span class="price border">{$total_to_pay}</span>
<br /><br />- {l2 s='to the order  of' mod='bankwire' tpl='payment_return'} <br><span class="bold border">{if $bankwireOwner}{$bankwireOwner}{else}___________{/if}</span>
<br /><br />- {l2 s='with these details' mod='bankwire' tpl='payment_return'} <br><span class="bold border">{if $bankwireDetails}{$bankwireDetails}{else}___________{/if}</span>
<br /><br />- {l2 s='to this bank' mod='bankwire' tpl='payment_return'} <br><span class="bold border">{if $bankwireAddress}{$bankwireAddress}{else}___________{/if}</span>
<br /><br />- {l2 s='Do not forget to include your order number' mod='bankwire' tpl='payment_return'} <span class="bold border">{$id_order}</span> {l2 s='in the subject of your bank wire' mod='bankwire' tpl='payment_return'}
<br /><br />{l2 s='An e-mail has been sent to you with this information.' mod='bankwire' tpl='payment_return'}
<br /><br /><span class="bold">{l2 s='Your order will be sent as soon as we receive your settlement.' mod='bankwire' tpl='payment_return'}</span>

</body>
</html>