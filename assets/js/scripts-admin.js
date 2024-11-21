// Settings page scripts
document.addEventListener('DOMContentLoaded', function () {
    const testModeCheckbox = document.querySelector('#mono_hire_purchase_test_mode');
    const testStoreId = document.querySelector('#mono_hire_purchase_test_store_id').closest('tr');
    const testSignKey = document.querySelector('#mono_hire_purchase_test_sign_key').closest('tr');
    const testApiUrl = document.querySelector('#mono_hire_purchase_test_api_url').closest('tr');

    const prodStoreId = document.querySelector('#mono_hire_purchase_store_id').closest('tr');
    const prodSignKey = document.querySelector('#mono_hire_purchase_sign_key').closest('tr');
    const prodApiUrl = document.querySelector('#mono_hire_purchase_api_url').closest('tr');

    function toggleFields() {
        if (testModeCheckbox.checked) {
            testStoreId.style.display = '';
            testSignKey.style.display = '';
            testApiUrl.style.display = '';

            prodStoreId.style.display = 'none';
            prodSignKey.style.display = 'none';
            prodApiUrl.style.display = 'none';
        } else {
            testStoreId.style.display = 'none';
            testSignKey.style.display = 'none';
            testApiUrl.style.display = 'none';

            prodStoreId.style.display = '';
            prodSignKey.style.display = '';
            prodApiUrl.style.display = '';
        }
    }

    // Initialize toggle state
    toggleFields();

    // Add event listener for changes in test mode checkbox
    testModeCheckbox.addEventListener('change', toggleFields);
});
jQuery(document).ready(function ($) {
    // Media uploader for image fields
    $('.mono-pay-upload-button').click(function (e) {
        e.preventDefault();
        var button = $(this);
        var id = button.prevAll('input[type="hidden"]').attr('id'); // Adjusted to find the input hidden field

        var custom_uploader = wp.media({
            title: adminScriptLocalizedText.selectImage,
            button: {
                text: adminScriptLocalizedText.useImage
            },
            multiple: false
        }).on('select', function () {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            $('#' + id).val(attachment.url); // Set the image URL in the hidden input
            button.prevAll('.mono-pay-image-preview').html('<img src="' + attachment.url + '" style="max-width:100%; height:auto;" />'); // Update preview
            button.siblings('.mono-pay-remove-button').show(); // Show remove button
        }).open();
    });

    // Remove image functionality
    $('.mono-pay-remove-button').click(function (e) {
        e.preventDefault();
        var button = $(this);
        var input = button.siblings('input[type="hidden"]'); // Adjusted to target the hidden input
        input.val(''); // Clear the input field value
        button.siblings('.mono-pay-image-preview').html(''); // Clear the preview image
        button.hide(); // Hide the remove button
    });

    // Update the preview image on page load if an image exists
    $('.mono-pay-image-preview').each(function () {
        var imgSrc = $(this).siblings('input[type="hidden"]').val(); // Adjusted to target the hidden input
        if (imgSrc) {
            $(this).html('<img src="' + imgSrc + '" style="max-width:100%; height:auto;" />');
            $(this).siblings('.mono-pay-remove-button').show(); // Show remove button if image exists
        }
    });
});

// Edit order scripts
jQuery(document).ready(function ($) {
    console.log('Admin script loaded for Mono API interaction.');

    if (('IN_PROCESS' == $('.mono-order-state-value').text()) && ('WAITING_FOR_STORE_CONFIRM' == $('.mono-order-sub-state-value').text()) && ('SUCCESS' != $('.mono-order-shipment-status-value').text())){
        // Enable the button if the order state is in process, and the sub state is waiting for store confirmation
        $('#confirm-shipment-button').prop('disabled', false);
    } else if ('SUCCESS' == $('.mono-order-shipment-status-value').text()){
        // Disable the button if the shipment state is success
        $('#confirm-shipment-button').prop('disabled', true);
    }
    // Helper function for making Ajax requests
    function sendMonoAjaxRequest(action, orderID, nonce, bankRefunds = true, successCallback) {
        var data = {
            action: action,
            order_id: orderID,
            return_money_to_card: bankRefunds,
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

        sendMonoAjaxRequest('process_mono_pay_order', orderID, nonce, null, function (response) {
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

        sendMonoAjaxRequest('check_mono_order_status', orderID, nonce, null, function (response) {
            $('#mono-pay-order-result').html(`Success: \n ${JSON.stringify(response.data.response, null, 4)}`).removeClass('loading');
            $('.mono-order-state-value').html(response.data.state);
            $('.mono-order-sub-state-value').html(response.data.sub_state);
            
            if ('SUCCESS' === response.data.state && 'SUCCESS' !== response.data.shipment_state) {
                // Enable the button if the order state is success, but shipment is not yet confirmed
                $('#confirm-shipment-button').prop('disabled', false);
            } else if ('IN_PROCESS' === response.data.state && 'WAITING_FOR_STORE_CONFIRM' === response.data.sub_state) {
                // Enable the button if the order state is in process, and the sub state is waiting for store confirmation
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

        sendMonoAjaxRequest('reject_mono_order', orderID, nonce, null, function (response) {
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

        sendMonoAjaxRequest('confirm_mono_order_shipment', orderID, nonce, null, function (response) {
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

    $('#return-order-button').on('click', function (e) {
        e.preventDefault();
        if (!$("#return-order-radio:checked").length) {
            $(".return-order-error").removeClass("hide");
            return;
        } else {
            $(".return-order-error").addClass("hide");
            var orderID = $('#mono-pay-order-id').val();
            var nonce = $('#mono_pay_order_nonce').val();
            var bankRefunds = $("#return-order-radio:checked").val();

            sendMonoAjaxRequest('return_mono_order', orderID, nonce, bankRefunds, function (response) {
                console.log( response );
                if (response.success) {
                    $('#mono-pay-order-result').html(`Success: \n ${JSON.stringify(response.data, null, 4)}`).removeClass('loading');
                    if (true === response.data.order_status_updated) {
                        $(".order_status_updated").removeClass("hide");
                    }
                } else {
                    $('#mono-pay-order-result').html('Error: ' + response.data.message).removeClass('loading');
                }
            });
        }
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