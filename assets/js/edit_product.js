jQuery( function ( $ ) {
    'use strict';

    const lmfwcBindWcsEvents = function () {
        let onSubscriptionRenewal = document.querySelectorAll( 'select.lmfwc_subscription_renewal_action' );
        let extendBy              = document.querySelectorAll( 'select.lmfwc_subscription_renewal_interval_type' );
        let interval              = document.querySelectorAll( 'input.lmfwc_subscription_renewal_custom_interval' );
        let period                = document.querySelectorAll( 'select.lmfwc_subscription_renewal_custom_period' );

        if ( ! onSubscriptionRenewal || ! extendBy || ! interval || ! period) {
            return;
        }

        for ( let i = 0; i < onSubscriptionRenewal.length; i++ ) {
            onSubscriptionRenewal[i].addEventListener( 'change', function ( event ) {
                const value = event.target.value;

                if ( value === 'issue_new_license' ) {
                    extendBy[i].parentNode.classList.add( 'hidden' );
                    interval[i].parentNode.classList.add( 'hidden' );
                    period[i].parentNode.classList.add( 'hidden' );
                } else {
                    extendBy[i].parentNode.classList.remove( 'hidden' );

                    if (extendBy[i].value === 'subscription') {
                        interval[i].parentNode.classList.add( 'hidden' );
                        period[i].parentNode.classList.add( 'hidden' );
                    } else {
                        interval[i].parentNode.classList.remove( 'hidden' );
                        period[i].parentNode.classList.remove( 'hidden' );
                    }
                }
            } );
        }

        for ( let j = 0; j < extendBy.length; j++ ) {
            extendBy[j].addEventListener( 'change', function ( event ) {
                const value = event.target.value;

                if ( value === 'subscription' ) {
                    interval[j].parentNode.classList.add( 'hidden' );
                    period[j].parentNode.classList.add( 'hidden' );
                } else {
                    interval[j].parentNode.classList.remove( 'hidden' );
                    period[j].parentNode.classList.remove( 'hidden' );
                }
            } );
        }
    }

    lmfwcBindWcsEvents();

    $( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', lmfwcBindWcsEvents );
    $( '#variable_product_options' ).on( 'reload', lmfwcBindWcsEvents );

    $( document.body ).on( 'woocommerce-product-type-change', function( body, value ) {
        if ( value === 'subscription' || value === 'variable-subscription' ) {
            const renewalActions = $( '.lmfwc_subscription_renewal_action_field' );

            renewalActions.removeClass( 'hidden' );
            renewalActions.each( function () {
                const renewalActionField = $( this );
                const renewalAction = renewalActionField.find( '.lmfwc_subscription_renewal_action' );

                if ( renewalAction.val() === 'extend_existing_license' ) {
                    const renewalIntervalTypeField = renewalActionField.next();
                    const renewalIntervalType = renewalIntervalTypeField.find( '.lmfwc_subscription_renewal_interval_type' );
                    renewalIntervalTypeField.removeClass( 'hidden' );

                    if ( renewalIntervalType.val() === 'custom' ) {
                        const renewalCustomIntervalField = renewalIntervalTypeField.next();
                        const renewalCustomPeriodField = renewalCustomIntervalField.next();
                        renewalCustomIntervalField.removeClass('hidden');
                        renewalCustomPeriodField.removeClass('hidden');
                    }
                }
            } );
        } else {
            $( '.lmfwc_subscription_renewal_action_field' ).addClass( 'hidden' );
            $( '.lmfwc_subscription_renewal_interval_type_field' ).addClass( 'hidden' );
            $( '.lmfwc_subscription_renewal_custom_interval_field' ).addClass( 'hidden' );
            $( '.lmfwc_subscription_renewal_custom_period_field' ).addClass( 'hidden' );
        }
    } );
} );
