require(['jquery'], function () {
    jQuery('body').on('click', '[data-block="minicart"]', function(){
        jQuery('#mini-cart .options .values span[data-bind*="text:"]:not(.dpo_wrapped)').addClass('dpo_wrapped').each(function(){this.innerHTML = this.innerText;});
    });
    //jQuery('#shopping-cart-table .item-options dd:not(.dpo_wrapped)').addClass('dpo_wrapped').each(function(){this.innerHTML = this.innerText;});

});