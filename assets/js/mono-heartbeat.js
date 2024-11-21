jQuery(document).ready(function ($) {
    // Send order ID in heartbeat data
    $(document).on('heartbeat-send.wp-heartbeat', function (e, data) {

        var orderID = monoOrderData.orderID; // Use the localized WooCommerce order ID
        if (orderID) {
            data.mono_order_status = {
                order_id: orderID
            };
        }
    });

    // Process the heartbeat response
    $(document).on('heartbeat-tick.wp-heartbeat', function (e, data) {

        if (data.mono_order_status) {
            var $col2Element = $('.col-2');  // Parent element to apply background fade
            var fadeDuration = 400; // Set a consistent duration for all animations
            var isUpdated = false; // Track if any updates are made

            // Update state if it has changed
            if (data.mono_order_status.state && $('.mono-order-state-value').text() !== data.mono_order_status.state) {
                var $stateElement = $('.mono-order-state-value');
                $stateElement.fadeOut(fadeDuration, function () {
                    $(this).text(data.mono_order_status.state).fadeIn(fadeDuration);
                });
                if ('SUCCESS' === data.mono_order_status.state && 'SUCCESS' !== $('.mono-order-shipment-status-value').text()) {
                    // Enable the button if the order state is success, but shipment is not yet confirmed
                    $('#confirm-shipment-button').prop('disabled', false);
                } else if ('IN_PROCESS' === data.mono_order_status.state && 'WAITING_FOR_STORE_CONFIRM' === data.mono_order_status.sub_state) {
                    // Enable the button if the order state is in process, and the sub state is waiting for store confirmation
                    $('#confirm-shipment-button').prop('disabled', false);
                } else {
                    // In all other cases, disable the button
                    $('#confirm-shipment-button').prop('disabled', true);
                }
                isUpdated = true; // Mark as updated
            }

            // Update sub_state if it has changed
            if (data.mono_order_status.sub_state && $('.mono-order-sub-state-value').text() !== data.mono_order_status.sub_state) {
                var $subStateElement = $('.mono-order-sub-state-value');
                $subStateElement.fadeOut(fadeDuration, function () {
                    $(this).text(data.mono_order_status.sub_state).fadeIn(fadeDuration);
                });
                isUpdated = true; // Mark as updated
            }
            
            // Apply background color animation if any updates were made
            if (isUpdated) {
                $col2Element.css('background-color', '#8bc34a'); // Temporary highlight color
                $col2Element.animate({ backgroundColor: '#ffffff' }, fadeDuration); // Animate back to original
                if (true === data.mono_order_status.order_status_updated) {
                    $(".order_status_updated").removeClass("hide");
                }
            }
        }
    });
});