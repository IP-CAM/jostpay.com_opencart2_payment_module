<?php
if(!empty($Error))echo "<div>Error: $Error</div>";
elseif(isset($oncallback))
{
 echo $header; ?><?php echo $column_left; ?><?php echo $column_right; ?>
<div id="content" class='container'>
	<?php 
	//echo $content_top; 
	?>
	
	<div class="breadcrumb">
		<?php foreach ($breadcrumbs as $breadcrumb) { ?>
			<?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
		<?php } ?>
	</div>
	<?php echo $toecho; ?>
	<?php echo $content_bottom; ?>
</div>
<?php echo $footer; 

}
else
{
if(!empty($transaction_history_link))
{
?>
 <div>
 Copy this url to access the Transaction History.<br/>
 <a href='<?php echo $transaction_history_link; ?>'><?php echo $transaction_history_link;?></a>
 </div>
 <?php
 }
 ?>
<form action="<?php echo $action; ?>" method="post">
	<input type="hidden" name="jostpay_mert_id" value="<?php echo $ap_merchant;?>" />
	<input type="hidden" name="jostpay_tranx_id" value="<?php echo $trans_id; ?>" />
	<input type="hidden" name="jostpay_tranx_amt" value="<?php echo $jostpay_amount ; ?>" />
	<input type="hidden" name="jostpay_tranx_curr" value="<?php echo $jostpay_currency_code; ?>" />
	<input type="hidden" name="jostpay_cust_id" value="<?php echo $order_id; ?>" />
	<input type="hidden" name="jostpay_cust_name" value="<?php echo $full_name; ?>" />
	<input type="hidden" name="jostpay_tranx_memo" value="Product" />
	<input type="hidden" name="jostpay_hash" value="<?php echo $jostpay_hash ; ?>" />
	<input type="hidden" name="jostpay_tranx_hash" value="<?php echo $jostpay_tranx_hash ; ?>" />
	<input type="hidden" name="jostpay_tranx_noti_url" value="<?php echo $notify_url;?>" />

	<b> Note : </b> If you Choose Payment Gateway as JostPay, then all other currency format converted into Naira (NGN / N) at the time of Payment!
  <div class="buttons">
    <div class="pull-right">
      <input type="submit" value="<?php echo $button_confirm; ?>" class="btn btn-primary" />
    </div>
  </div>
</form>
<?php
}
?>
