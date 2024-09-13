jQuery(document).ready(function ($) {
    console.log('Admin script loaded for Mono API interaction.');

    // Helper function for making Ajax requests
    function sendMonoAjaxRequest(action, orderID, nonce, successCallback) {
        var data = {
            action: action,
            order_id: orderID,
            security: nonce
        };

        $('#mono-pay-order-result').addClass('loading');

        $.post(ajaxurl, data, function (response) {
            if (response.success) {
                successCallback(response);
            } else {
                $('#mono-pay-order-result').html('Error: ' + response.data.message).removeClass('loading');
            }
        }).fail(function (xhr) {
            console.log('Error details:', xhr.responseText);
            $('#mono-pay-order-result').removeClass('loading').html('Request failed. Please try again.');
        });
    }

    // Button click handlers
    $('#mono-pay-order-button').on('click', function (e) {
        e.preventDefault();
        var orderID = $('#mono-pay-order-id').val();
        var nonce = $('#mono_pay_order_nonce').val();

        sendMonoAjaxRequest('process_mono_pay_order', orderID, nonce, function (response) {
            $('#mono-pay-order-result').html(`Success: \n ${JSON.stringify(response.data.response, null, 4)}`).removeClass('loading');
            if (response.data.mono_pay_status === 'Created') {
                $('.mono-pay-status-value').html(response.data.mono_pay_status);
                $('.mono-pay-order-id-value').html(response.data.mono_pay_order_id);
                $('#mono-pay-order-button').prop('disabled', true);
                $('.mono-order-state-value').html('N/A');
                $('.mono-order-sub-state-value').html('N/A');
                $('#check-mono-order-status-button, #reject-mono-order-button').prop('disabled', false);
            }
        });
    });

    $('#check-mono-order-status-button').on('click', function (e) {
        e.preventDefault();
        var orderID = $('#mono-pay-order-id').val();
        var nonce = $('#mono_pay_order_nonce').val();

        sendMonoAjaxRequest('check_mono_order_status', orderID, nonce, function (response) {
            $('#mono-pay-order-result').html(`Success: \n ${JSON.stringify(response.data.response, null, 4)}`).removeClass('loading');
            $('.mono-order-state-value').html(response.data.state);
            $('.mono-order-sub-state-value').html(response.data.sub_state);
            
            if ('SUCCESS' === response.data.state && 'SUCCESS' !== response.data.shipment_state) {
                // Enable the button if the order state is success, but shipment is not yet confirmed
                $('#confirm-shipment-button').prop('disabled', false);
            } else {
                // In all other cases, disable the button
                $('#confirm-shipment-button').prop('disabled', true);
            }
        });
    });

    $('#reject-mono-order-button').on('click', function (e) {
        e.preventDefault();
        var orderID = $('#mono-pay-order-id').val();
        var nonce = $('#mono_pay_order_nonce').val();

        sendMonoAjaxRequest('reject_mono_order', orderID, nonce, function (response) {
            $('#mono-pay-order-result').html(`Success: \n ${JSON.stringify(response.data.response, null, 4)}`).removeClass('loading');
            $('.mono-pay-status-value, .mono-pay-order-id-value').html('N/A');            
            $('.mono-order-state-value').html(response.data.state);
            $('.mono-order-sub-state-value').html(response.data.sub_state);
            $('#mono-pay-order-button').prop('disabled', false);
            $('#check-mono-order-status-button, #reject-mono-order-button, #confirm-shipment-button').prop('disabled', true);
            if (true === response.data.order_status_updated){
                $(".order_status_updated").removeClass("hide");
            }
        });
    });

    // Use sendMonoAjaxRequest for confirming shipment
    $('#confirm-shipment-button').on('click', function (e) {
        e.preventDefault();
        var orderID = $('#mono-pay-order-id').val();
        var nonce = $('#mono_pay_order_nonce').val();

        sendMonoAjaxRequest('confirm_mono_order_shipment', orderID, nonce, function (response) {
            if (response.success) {
                $('#mono-pay-order-result').html('Success: Shipment confirmed successfully.').removeClass('loading');
                $('#reject-mono-order-button').prop('disabled', true);
                $('#confirm-shipment-button').prop('disabled', true);
                $('.mono-order-shipment-status-value').html(response.data.state);
                if (true === response.data.order_status_updated) {
                    $(".order_status_updated").removeClass("hide");
                }
            } else {
                $('#mono-pay-order-result').html('Error: ' + response.data.message).removeClass('loading');
            }
        });
    });

    $('#reload').on('click', function () {
        location.reload();
    });

    $('.description code').on('click', function () {
        // Create a temporary input element
        var $tempInput = $('<input>');
        $('body').append($tempInput);

        // Set the input's value to the text inside the code tag
        $tempInput.val($(this).text()).select();

        // Copy the text to the clipboard
        document.execCommand('copy');

        // Remove the temporary input
        $tempInput.remove();

        // Show an alert to notify the user
        alert(adminScriptLocalizedText.copySuccess);
    });
});