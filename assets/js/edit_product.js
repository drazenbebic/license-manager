jQuery(function($) {
  'use strict';

  const lmfwcBindWcsEvents = function() {
    let onSubscriptionRenewal = document.querySelectorAll('select.lmfwc_subscription_renewal_action');
    let extendBy = document.querySelectorAll('select.lmfwc_subscription_renewal_interval_type');
    let interval = document.querySelectorAll('input.lmfwc_subscription_renewal_custom_interval');
    let period = document.querySelectorAll('select.lmfwc_subscription_renewal_custom_period');

    if (!onSubscriptionRenewal || !extendBy || !interval || !period) {
      return;
    }

    for (let i = 0; i < onSubscriptionRenewal.length; i++) {
      onSubscriptionRenewal[i].addEventListener('change', function(event) {
        const value = event.target.value;

        if (value === 'issue_new_license') {
          extendBy[i].parentNode.style.display = 'none';
          interval[i].parentNode.style.display = 'none';
          period[i].parentNode.style.display = 'none';
        } else {
          extendBy[i].parentNode.style.display = 'block';

          if (extendBy[i].value === 'subscription') {
            interval[i].parentNode.style.display = 'none';
            period[i].parentNode.style.display = 'none';
          } else {
            interval[i].parentNode.style.display = 'block';
            period[i].parentNode.style.display = 'block';
          }
        }
      });
    }

    for (let j = 0; j < extendBy.length; j++) {
      extendBy[j].addEventListener('change', function(event) {
        const value = event.target.value;

        if (value === 'subscription') {
          interval[j].parentNode.style.display = 'none';
          period[j].parentNode.style.display = 'none';
        } else {
          interval[j].parentNode.style.display = 'block';
          period[j].parentNode.style.display = 'block';
        }
      });
    }
  }

  lmfwcBindWcsEvents();

  $('#woocommerce-product-data').on('woocommerce_variations_loaded', lmfwcBindWcsEvents);
  $('#variable_product_options').on('reload', lmfwcBindWcsEvents);
});