(function($){
  var $transactionTypeSelect = $('#woocommerce_acceptblue-cc_transaction_type');

  if($transactionTypeSelect.length === 0) return false;

  var $chargeOnStatusChange = $('#woocommerce_acceptblue-cc_charge_order');
  var $chargeVirtual = $('#woocommerce_acceptblue-cc_charge_virtual');

  function disableOptions() {
    $chargeOnStatusChange.prop('disabled', true);
    $chargeVirtual.prop('disabled', true);
  }

  function enableOptions() {
    $chargeOnStatusChange.prop('disabled', false);
    $chargeVirtual.prop('disabled', false);
  }

  $transactionTypeSelect.on('change', function(){
    var $selected = $('option:selected', this);

    if($selected.val() === 'charge'){
      disableOptions();
    }else{
      enableOptions()
    }
  });

  $transactionTypeSelect.trigger('change');
})(jQuery);

(function($){
  var $debugModeChackbox = $('#woocommerce_acceptblue-cc_enabled_debug_mode, #woocommerce_acceptblue-ach_enabled_debug_mode');

  var $publicKeyInput = $('#woocommerce_acceptblue-cc_public_key, #woocommerce_acceptblue-ach_public_key');
  var $sourceKeyInput = $('#woocommerce_acceptblue-cc_source_key, #woocommerce_acceptblue-ach_source_key');
  var $pinCodeInput = $('#woocommerce_acceptblue-cc_pin_code, #woocommerce_acceptblue-ach_pin_code');

  var $sendBox_publicKeyInput = $('#woocommerce_acceptblue-cc_sandbox_public_key, #woocommerce_acceptblue-ach_sandbox_public_key');
  var $sendBox_sourceKeyInput = $('#woocommerce_acceptblue-cc_sandbox_source_key, #woocommerce_acceptblue-ach_sandbox_source_key');
  var $sendBox_pinCodeInput = $('#woocommerce_acceptblue-cc_sandbox_pin_code, #woocommerce_acceptblue-ach_sandbox_pin_code');

  function setDebugModeToFields(status = true){
    var styles = function(status, type){
      var s = {};
      if(type === 'live'){
        s.opacity = (status)? .4 : 1;
        if(status) { s.outline = 'none'; s.boxShadow = 'none'; s.borderColor = '#8c8f94'; }
        return s;
      }
      if(type === 'dev'){
        s.opacity = (status)? 1 : .4;
        if(!status) { s.outline = 'none'; s.boxShadow = 'none'; s.borderColor = '#8c8f94'; }
        return s;
      }
    }

    $publicKeyInput.css(styles(status, 'live'));
    $publicKeyInput.prop('readonly', status);

    $sourceKeyInput.css(styles(status, 'live'));
    $sourceKeyInput.prop('readonly', status);

    $pinCodeInput.css(styles(status, 'live'));
    $pinCodeInput.prop('readonly', status);


    $sendBox_publicKeyInput.css(styles(status, 'dev'));
    $sendBox_publicKeyInput.prop('readonly', !status);

    $sendBox_sourceKeyInput.css(styles(status, 'dev'));
    $sendBox_sourceKeyInput.prop('readonly', !status);

    $sendBox_pinCodeInput.css(styles(status, 'dev'));
    $sendBox_pinCodeInput.prop('readonly', !status);
  }

  $debugModeChackbox.on('change', function(e){
    if($(this).is(':checked')){
      setDebugModeToFields(true);
    }else{
      setDebugModeToFields(false);
    }
  });

  $debugModeChackbox.trigger('change');

})(jQuery);