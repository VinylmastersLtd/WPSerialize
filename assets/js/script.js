jQuery(document).ready(function($) {
    // Hide the loading container initially
    $('#sn-loading-container').hide();

    // Submit form event handler
    $('#sn-serial-number-form').submit(function(e) {
        e.preventDefault(); // Prevent default form submission
        
        // Hide previous search results
        $('#sn-search-results').hide();

        // Show loading spinner
        $('#sn-loading-container').show();

        // Get serial number input value
        var serialNumber = $('#sn-serial-number').val();

        // Create AJAX request data
        var requestData = {
            action: 'sn_search',
            sn_nonce_field: $('#sn_nonce_field').val(),
            'serial-number': serialNumber
        };

        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: requestData,
            success: function(response) {
                // Hide loading spinner
                $('#sn-loading-container').hide();

                if (response.success) {
                    // Display search results
                    displaySearchResults(response.data);
                } else {
                    // Display error message
                    $('#sn-search-results').html('<p>' + response.data.message + '</p>').show();
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                // Hide loading spinner
                $('#sn-loading-container').hide();

                // Log error to console
                console.error('AJAX Error:', errorThrown);
            }
        });
    });

// Function to display search results
function displaySearchResults(data) {
    // Clear previous results
    $('#sn-search-results').empty();

    // Iterate through meta data and display each field and its value
    if (data.meta_data) {
        $.each(data.meta_data, function(key, value) {
            // Check if the value is not empty and the field is not 'expiry-date'
            if (value !== "" && key !== 'expiry-date') {
                var capitalizedKey = key.replace(/-/g, ' ').replace(/\b\w/g, function(char) {
                    return char.toUpperCase();
                });

                // Format dates based on WordPress date format settings
                if (key === 'Shipped' || key === 'Expiry Date') {
                    value = formatDate(value);
                }
				
		

                var fieldHtml = '<div class="sn-meta-field"><strong>' + capitalizedKey + ':</strong> ' + value + '</div>';
                $('#sn-search-results').append(fieldHtml);
            }
        });
    }

    // Append detailed message
    $('#sn-search-results').append('<div class="sn-detailed-message"><strong>Expiry Status:</strong> ' + data.detailed_message + '</div>');

    // Show search results container
    $('#sn-search-results').show();
}



    // Function to format dates based on WordPress date format settings
    function formatDate(dateString) {
        var formattedDate = new Date(dateString);
        if (!isNaN(formattedDate.getTime())) {
            return date_i18n('j F Y', formattedDate);
        }
        return dateString;
    }
});
