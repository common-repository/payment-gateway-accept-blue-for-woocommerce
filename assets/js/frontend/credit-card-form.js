(function($) {
  function init_credit_card_masks(){
    var $card = $('#ab-card');
    var $cvv = $('#ab-cvv');
    var $exp = $('#ab-expire');
    var $cardDetector = $('#ab-card-detector');
    var $cardLogoWrapper = $('#ab-card-logo');

    function removeCardError(){
      $card.removeClass('invalid');
      $card.closest('div').find('.ab-error-message').hide();
    }

    var cardDetector = new Cleave('#ab-card', {
      creditCard: true,
      creditCardStrictMode: false,
      onCreditCardTypeChanged: function(type) {
        setCardLogo(type);
      }
    });

    $exp.on('input', function(){
      try{
        var result = this.value.match(/\d{2}\/(?<year>\d{4,99})/);
        if(result && result.groups && result.groups.year){
          var year = result.groups.year;
          var minYear = new Date().getFullYear();
          if(year < minYear){
            $exp.addClass('invalid');
            $exp.closest('div').find('.ab-error-message').text('Exp date is invalid!').show();
          }else{
            $exp.removeClass('invalid');
            $exp.closest('div').find('.ab-error-message').hide();
          }
        }else{
          $exp.addClass('invalid');
          $exp.closest('div').find('.ab-error-message').text('Exp date is invalid! Must be MM/YYYY.').show();
        }
      }catch(e){
        $exp.addClass('invalid');
        $exp.closest('div').find('.ab-error-message').text('Exp date is invalid! Must be MM/YYYY.').show();
      }
    });


    if ($card.length > 0) {
      //$card.mask('9999 9999 9999 9999');

      observeElement($card[0], 'value', (oldValue, newValue) => {
        if(oldValue !== newValue) {
          $cardDetector.val(newValue);
          cardDetector.onChange({inputType: 'text'});
        }
      }, 300);
    }

    if ($cvv.length > 0) {
      $cvv.mask('000', {placeholder: '...'});
    }

    if ($exp.length > 0) {
      $exp.mask('00/0000', {placeholder: 'MM/YYYY'});
    }

    $card.on('input', function (){
      if(!validateCreditCardNumber(this.value)){
        $card.addClass('invalid')
        $card.closest('div').find('.ab-error-message').text('Card number is invalid').show();
      }else{
        removeCardError();
      }
    });


    function tryRemoveLogo(){
      var $img = $('img', $cardLogoWrapper);
      if($img.length > 0){
        $img.remove();
      }
    }

    function addLogo(imgUrl){
      var img = '<img src="' + imgUrl + '" width="100%" height="100%" style="max-height: unset;">';
      $cardLogoWrapper.append(img);
    }

    function setCardLogo(type){
      if(typeof acceptblue_logos[type] !== 'undefined'){
        
        if ( acceptblue_logos[type].includes('american') ) {
          removeCardError();
          console.log('amex');
          $cvv.attr('placeholder', '....');
          //$card.attr('maxlength', 17);
          $cvv.mask('0000', {placeholder: '....'});
          //$card.mask('9999 999999 99999');
          
        } else if ( acceptblue_logos[type].includes('jcb') ) {
          removeCardError();
          console.log('jcb');
          $cvv.attr('placeholder', '...');
          //$card.attr('maxlength', 21);
          $cvv.mask('000', {placeholder: '...'});
          //$card.mask('9999 999999 99999');          
          
        } else if ( acceptblue_logos[type].includes('VISA') ) {
          removeCardError();
          console.log('visa');
          $cvv.attr('placeholder', '...');
          //$card.attr('maxlength', 19);
          $cvv.mask('000', {placeholder: '...'});
          //$card.mask('9999 9999 9999 9999');
          
        } else if ( acceptblue_logos[type].includes('diners-club') ) {
          removeCardError();
          $cvv.attr('placeholder', '...');
          $cvv.mask('000', {placeholder: '...'});
          //$card.mask('9999 999999 9999 999');
          //$card.attr('maxlength', 21);
        } else {
          removeCardError();
          $cvv.attr('placeholder', '...');
          $cvv.mask('000', {placeholder: '...'});
          //$card.mask('9999 9999 9999 9999');
          //$card.attr('maxlength', 19);
        }
        tryRemoveLogo();
        addLogo(acceptblue_logos[type]);
      }

      if(type === 'unknown'){
        $card.addClass('invalid')
        $card.closest('div').find('.ab-error-message').text('Card number is invalid').show();
        tryRemoveLogo();
        $cvv.attr('placeholder', '...');
        $cvv.mask('000', {placeholder: '...'});
      }
    }
  }

  function checkSavedPaymentMethods( element ) {
    if(typeof pgabfwInfo === 'undefined' || typeof pgabfwInfo.customerIsLoggedIn === 'undefined') return false;

    if ( $(element).length > 0 ) {
      $(element).each(function() {
        if ( $(this).children('input').attr('checked')  == 'checked') {
          setTimeout(function() {
            let cardBlock = $('#payment').find('.acceptblue-card-wrap');
            if ( !$(cardBlock).hasClass('saved-metod') ) {
              $( cardBlock ).addClass('saved-metod');  
            }
            $('#payment').find('#ab-card').prop('disabled', true);
            $('#payment').find('#ab-card').attr('placeholder', '**** **** **** ****');
            $('#payment').find('#ab-expire').prop('disabled', true);
            $('#payment').find('#ab-expire').val('**/****');
            $('#payment').find('#ab-cvv').prop('disabled', true);
            $('#payment').find('#ab-cvv').val('***');
            if ( $('#payment').find('.ab-save-payment-wrap').length ) {
              if ( $('#payment').find('.ab-save-payment-wrap').css('display') == 'block' ) {
                $('#payment').find('.ab-save-payment-wrap').css({'display' : 'none'});
              }
            }
          }, 100);           
          
        }
      });
    }else if($(element).length === 0 && pgabfwInfo.customerIsLoggedIn === 'true'){
      let newPayment = $('#wc-acceptblue-cc-payment-token-new');
      if(newPayment.length > 0) newPayment.prop('checked', true);
      $('#payment').find('.ab-save-payment-wrap').css({'display' : 'block'});
    }


  }

  $( 'body' ).on( 'updated_checkout', function () {
    init_credit_card_masks();
  
    // check saved metods and change card block
    checkSavedPaymentMethods('.woocommerce-SavedPaymentMethods-token');

    if ( $( '.woocommerce-SavedPaymentMethods' ).length ) {


      $( '.woocommerce-SavedPaymentMethods' ).on( 'change', 'input', function () {
        if ( $(this).val() == 'new' ) {
          if ( $('#payment').find('.acceptblue-card-wrap').hasClass('saved-metod') ) {
            $('#payment').find('.acceptblue-card-wrap').removeClass('saved-metod');
            $('#payment').find('#ab-card').prop('disabled', false);
            $('#payment').find('#ab-card').attr('placeholder', '0000 0000 0000 0000');
            $('#payment').find('#ab-expire').prop('disabled', false);
            $('#payment').find('#ab-expire').val('');
            $('#payment').find('#ab-cvv').prop('disabled', false);
            $('#payment').find('#ab-cvv').val('');
            if ( $('#payment').find('.ab-save-payment-wrap').length ) {
              if ( $('#payment').find('.ab-save-payment-wrap').css('display') == 'none' ) {
                $('#payment').find('.ab-save-payment-wrap').css({'display' : 'block'});
              }
            }
          }     
        } else {
            if ( !$('#payment').find('.acceptblue-card-wrap').hasClass('saved-metod') ) {
              $('#payment').find('.acceptblue-card-wrap').addClass('saved-metod');  
            }
            $('#payment').find('#ab-card').prop('disabled', true);
            $('#payment').find('#ab-card').attr('placeholder', '**** **** **** ****');
            $('#payment').find('#ab-expire').prop('disabled', true);
            $('#payment').find('#ab-expire').val('**/****');
            $('#payment').find('#ab-cvv').prop('disabled', true);
            $('#payment').find('#ab-cvv').val('***');
            if ( $('#payment').find('.ab-save-payment-wrap').length ) {
              if ( $('#payment').find('.ab-save-payment-wrap').css('display') == 'block' ) {
                $('#payment').find('.ab-save-payment-wrap').css({'display' : 'none'});
              }
            }
          }  
      });
      
    }
    
    $( '.woocommerce-account-fields' ).on( 'change', '#createaccount', function () {
      if ( $(this).is(':checked') ) {
        if ( $('#payment').find('.ab-save-payment-wrap').length ) {
          if ( $('#payment').find('.ab-save-payment-wrap').css('display') == 'none' ) {
            $('#payment').find('.ab-save-payment-wrap').css({'display' : 'block'});
          }
        }
      } else {
        if ( $('#payment').find('.ab-save-payment-wrap').length ) {
          if ( $('#payment').find('.ab-save-payment-wrap').css('display') == 'block' ) {
            $('#payment').find('.ab-save-payment-wrap').css({'display' : 'none'});
          }
        }
      }
    });

    
  });

  function observeElement(element, property, callback, delay = 0) {
    let elementPrototype = Object.getPrototypeOf(element);
    if (elementPrototype.hasOwnProperty(property)) {
      let descriptor = Object.getOwnPropertyDescriptor(elementPrototype, property);
      Object.defineProperty(element, property, {
        get: function() {
          return descriptor.get.apply(this, arguments);
        },
        set: function () {
          let oldValue = this[property];
          descriptor.set.apply(this, arguments);
          let newValue = this[property];
          if (typeof callback == "function") {
            setTimeout(callback.bind(this, oldValue, newValue), delay);
          }
          return newValue;
        }
      });
    }
  }

  function validateCreditCardNumber(cardNumber) {
    cardNumber = cardNumber.replace(/\s/g, '');

    // Check if cardNumber is numeric and has a valid length
    if (!/^\d+$/.test(cardNumber) || cardNumber.length < 13 || cardNumber.length > 19) {
      return false;
    }

    let sum = 0;
    let shouldDouble = false;
    for (let i = cardNumber.length - 1; i >= 0; i--) {
      let digit = parseInt(cardNumber.charAt(i));

      if (shouldDouble) {
        if ((digit *= 2) > 9) digit -= 9;
      }

      sum += digit;
      shouldDouble = !shouldDouble;
    }
    return (sum % 10) === 0;
  }
  
  
})(jQuery);
