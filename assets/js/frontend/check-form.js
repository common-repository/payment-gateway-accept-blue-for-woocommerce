(function($){
  function decodeHtml(html) {
    var txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
  }

  function getZeroPlaceholder(length){
    var zs = decodeHtml('&#952;');
    var zsPlaceholder = '';
    for(var i = 0; i < length; i++){
      zsPlaceholder += zs;
    }
    return length;
  }


  function init_check_masks(){
    var $routingNumber = $('#ab-routing-num');
    var $accountNumber = $('#ab-account-num');


    if ($routingNumber.length > 0) {
      $routingNumber.mask('999999999', {placeholder: '.........'});
    }

    if ($accountNumber.length > 0) {
      $accountNumber.mask('99999999999999999', {placeholder: '.................', autoclear: false});
    }
  }

  // $( 'body' ).on( 'updated_checkout', init_check_masks);
})(jQuery)