<?php
if(class_exists('WC_Subscriptions_Cart')) {
	$has_subscription = WC_Subscriptions_Cart::cart_contains_subscription();
}else{
    $has_subscription = false;
}
if(pgabfw_allow_to_show_credit_card_form()):
?>
<div class="acceptblue-card-wrap">
  
<?php //if ( $has_subscription && !is_user_logged_in() ): ?>
<p><?php //echo __( 'You should be logged in to be able to subscribe to the product', 'payment-gateway-accept-blue-for-woocommerce' ); ?></p>
<?php //else: ?>
  
    <input type="hidden" id="ab-card-detector">
    <div class="ab-card-wrap">
        <label for="ab-card">Card number</label><br/>
        <div class="ab-card-block">
          <input type="text" name="credit_card" placeholder="0000 0000 0000 0000" id="ab-card" autocomplete="off">
          <div class="ab-error-message" style="display: none"></div>
          <div class="ab-thumb-place">
              <div class="ab-thumb-wrap" id="ab-card-logo"></div>
          </div>          
        </div>
    </div>
    <div class="ab-expire-cvv-wrap">
        <div>
            <label for="ab-expire">Exp date</label><br/>
            <input type="text" name="date_expire" placeholder="MM/YYYY" id="ab-expire" autocomplete="off">
            <div class="ab-error-message" style="display: none"></div>
        </div>
        <div>
            <label for="ab-cvv">CVV</label><br/>
            <input type="text" name="cvv" placeholder="..." id="ab-cvv" autocomplete="off">
            <div class="ab-error-message" style="display: none"></div>
        </div>
    </div>
  
  <?php endif; ?>
  
      <div class="ab-save-payment-wrap">
        <?php if(!$has_subscription && !pgabfw_is_add_payment_method_page() && !pgabfw_is_subscription_add_payment_method_page()): ?>
              <input type="checkbox" name="save_payment_method" id="ab-save-payment">
              <label for="ab-save-payment">Save credit card?</label>
          <?php else: ?>
              <input type="hidden" name="save_payment_method" value="on">
              <label for="ab-save-payment">Payment method will be automatically saved for renew subscriptions!</label>
          <?php endif; ?>
      </div>
</div>
<?php do_action('acceptblue_after_credit_card_form'); ?>
<?php //endif; ?>
