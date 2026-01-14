// Main Application JavaScript

$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Initialize popovers
    $('[data-toggle="popover"]').popover();
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert:not(.alert-permanent)').fadeOut('slow');
    }, 5000);
    
    // Confirm delete actions
    $('.confirm-delete').click(function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Date picker initialization
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });
    
    // Form validation
    $('form').submit(function() {
        var requiredFields = $(this).find('[required]');
        var valid = true;
        
        requiredFields.each(function() {
            if ($(this).val() === '') {
                $(this).addClass('is-invalid');
                valid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!valid) {
            alert('Please fill all required fields');
            return false;
        }
        
        return true;
    });
    
    // Auto-format phone numbers
    $('.phone-format').on('input', function() {
        var phone = $(this).val().replace(/\D/g, '');
        if (phone.length > 10) phone = phone.substring(0, 10);
        $(this).val(phone);
    });
    
    // Auto-format Aadhaar
    $('.aadhaar-format').on('input', function() {
        var aadhaar = $(this).val().replace(/\D/g, '');
        if (aadhaar.length > 12) aadhaar = aadhaar.substring(0, 12);
        $(this).val(aadhaar);
    });
    
    // Auto-format PAN
    $('.pan-format').on('input', function() {
        var pan = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (pan.length > 10) pan = pan.substring(0, 10);
        $(this).val(pan);
    });
    
    // Calculate age from DOB
    $('.dob-calc').on('change', function() {
        var dob = new Date($(this).val());
        var today = new Date();
        var age = today.getFullYear() - dob.getFullYear();
        var m = today.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
            age--;
        }
        $('#age-display').text(age + ' years');
    });
});

// AJAX Helper Functions
function ajaxRequest(url, data, callback) {
    $.ajax({
        url: url,
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (callback) callback(response);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('An error occurred. Please try again.');
        }
    });
}

// File Upload Preview
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#' + previewId).attr('src', e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Format Date
function formatDate(dateStr) {
    var date = new Date(dateStr);
    var options = { day: '2-digit', month: 'short', year: 'numeric' };
    return date.toLocaleDateString('en-IN', options);
}

// Calculate Experience
function calculateExperience(doj) {
    var joinDate = new Date(doj);
    var today = new Date();
    
    var years = today.getFullYear() - joinDate.getFullYear();
    var months = today.getMonth() - joinDate.getMonth();
    var days = today.getDate() - joinDate.getDate();
    
    if (days < 0) {
        months--;
        days += 30; // Approximate month days
    }
    
    if (months < 0) {
        years--;
        months += 12;
    }
    
    return years + ' years, ' + months + ' months, ' + days + ' days';
}