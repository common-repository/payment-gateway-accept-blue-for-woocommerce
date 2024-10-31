
(function($){
  var $refundScript = $('#acceptblue-refund-button-template');
  var $refundAction = $('#woocommerce-order-items .refund-actions');

  if($refundAction.length === 0 || $refundScript.length === 0) return false;

  var $refundTemplate = $($refundScript.html());
  var $refundAmountInput = $('#refund_amount');
  var $refundReasonInput = $('#refund_reason');

  var refundPrice = 0;
  var refundReason = '';

  $refundTemplate.prop('disabled', true);

  $refundAction.prepend($refundTemplate);

  function toggleEnabled(input){
    if(input.value.length > 0) {
      $refundTemplate.prop('disabled', false);
    }else{
      $refundTemplate.prop('disabled', true);
    }
  }



  $refundAmountInput.on('change', function(){
    toggleEnabled(this);
    refundPrice = this.value;
  });
  $refundReasonInput.on('input', function(){
    refundReason = this.value;
  });

  function block () {
    $( '#woocommerce-order-items' ).block({
      message: null,
      overlayCSS: {
        background: '#fff',
        opacity: 0.6
      }
    });
  }

  function unblock () {
    $( '#woocommerce-order-items' ).unblock();
  }

  $refundTemplate.on('click', function(e){
    e.preventDefault();
    e.stopPropagation();
    if ( window.confirm( woocommerce_admin_meta_boxes.i18n_do_refund ) ) {
      block();

      var line_item_qtys = {};
      var line_item_totals = {};
      var line_item_tax_totals = {};

      $('.refund input.refund_order_item_qty').each(function(index, item) {
        if ($(item).closest('tr').data('order_item_id')) {
          if (item.value) {
            line_item_qtys[$(item).
                closest('tr').
                data('order_item_id')] = item.value;
          }
        }
      });

      $('.refund input.refund_line_total').each(function(index, item) {
        if ($(item).closest('tr').data('order_item_id')) {
          line_item_totals[$(item).
              closest('tr').
              data('order_item_id')] = accounting.unformat(
              item.value,
              woocommerce_admin.mon_decimal_point
          );
        }
      });

      $('.refund input.refund_line_tax').each(function(index, item) {
        if ($(item).closest('tr').data('order_item_id')) {
          var tax_id = $(item).data('tax_id');

          if (!line_item_tax_totals[$(item).
              closest('tr').
              data('order_item_id')]) {
            line_item_tax_totals[$(item).
                closest('tr').
                data('order_item_id')] = {};
          }

          line_item_tax_totals[$(item).
              closest('tr').
              data('order_item_id')][tax_id] = accounting.unformat(
              item.value,
              woocommerce_admin.mon_decimal_point
          );
        }
      });

      var data = {
        action: 'woocommerce_refund_line_items',
        order_id: woocommerce_admin_meta_boxes.post_id,
        refund_amount: refund_amount,
        refunded_amount: refunded_amount,
        refund_reason: refund_reason,
        line_item_qtys: JSON.stringify(line_item_qtys, null, ''),
        line_item_totals: JSON.stringify(line_item_totals, null, ''),
        line_item_tax_totals: JSON.stringify(line_item_tax_totals, null, ''),
        api_refund: $(this).is('.do-api-refund'),
        restock_refunded_items: $('#restock_refunded_items:checked').length
            ? 'true'
            : 'false',
        security: woocommerce_admin_meta_boxes.order_item_nonce
      };

      var acceptblueRefundData = {
        action: acceptblue_gateway_info.action,
        refNum: acceptblue_gateway_info.refNum
      }

      $.ajax({
        url: acceptblue_gateway_info.ajaxUrl,
        type: 'POST',
        data: acceptblueRefundData,
      }).done(function(response){
        if(response.success) {
          $.ajax({
            url: woocommerce_admin_meta_boxes.ajax_url,
            data: data,
            type: 'POST',
            success: function(response) {
              if (true === response.success) {
                window.location.reload();
              }
              else {
                window.alert(response.data);
                wc_meta_boxes_order_items.reload_items();
                unblock();
              }
            },
            complete: function() {
              unblock();
              window.wcTracks.recordEvent('order_edit_refunded', {
                order_id: data.order_id,
                status: $('#order_status').val(),
                api_refund: data.api_refund,
                has_reason: Boolean(data.refund_reason.length),
                restock: 'true' === data.restock_refunded_items
              });
            }
          });
          console.log('refund success---->', response);
        }else{
          alert('Refund error: ' + response.data);
          console.log('refund success---->', response);
        }
      }).fail(function(response){
        console.log('refund error---->', response);
        unblock();
      });

      //

    }
  });

})(jQuery);