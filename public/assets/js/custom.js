//Busines information validation
$(document).ready(function () {
    $("#business_form").validate({
        rules: {
            business_description: {
                required: true,
                minlength: 65,
                maxlength: 500
            },
            business_website:{
                required: true
            },
            category_id:{
                required: true
            }
        },
        messages: {
            business_description: {
                required: "Business Description is required.",
                minlength: "Description must be at least 65 characters long.",
                maxlength: "Description cannot exceed 500 characters."
            },
            category_id: {
                required: "Category is required."
            },
            business_website: {
                required: "Business website is required.",
            }
        },
        errorClass: "invalid-feedback",
        errorElement: "div",
        highlight: function (element, errorClass, validClass) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass('is-invalid').addClass('is-valid');
        },
        // Customizing error message placement
        errorPlacement: function (error, element) {
            if (element.is("textarea") || element.is("input[type='text']") || element.is("select")) {
                error.insertAfter(element);
            } else {
                error.insertBefore(element);
            }
        }
    });
});

//Address validation
$(document).ready(function () {
    $("#update_address").validate({
        rules: {
            street_line_1: {
                required: true,
            },
            city: {
                required: true,
            },
            province_state_territory: {
                required: true,
            },
            postal_code: {
                required: true,
                digits: true,
            },
            ref_country_id: {
                required: true
            }
        },
        messages: {
            street_line_1: {
                required: "Address line 1 is required."
            },
            city: {
                required: "City is required."
            },
            province_state_territory: {
                required: "Province/State/Territory is required.",
            },
            postal_code: {
                required: "Postal code is required.",
                digits: "Please enter a valid postal code with only digits.",
                maxlength: "Postal code cannot exceed 6 characters.",
                minlength: "Postal code must be exactly 6 digits."
            },
            ref_country_id: {
                required: "Country is required."
            }
        },
        errorClass: "invalid-feedback",
        errorElement: "div",
        highlight: function (element, errorClass, validClass) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).removeClass('is-invalid').addClass('is-valid');
        },
        errorPlacement: function (error, element) {
            if (element.is("select")) {
                error.insertAfter(element);
            } else {
                error.insertAfter(element);
            }
        }
    });
});

//Hours validation
$(document).ready(function () {
    $("#hours_form").validate({
        errorClass: "invalid-feedback",
        errorElement: "div",
        highlight: function (element) {
            $(element).addClass("is-invalid");
        },
        unhighlight: function (element) {
            $(element).removeClass("is-invalid");
        },
        errorPlacement: function (error, element) {
            error.insertAfter(element);
        }
    });

    function toggleDay(day, isClosed) {
        let start = $("select[name='start[" + day + "]']");
        let end = $("select[name='end[" + day + "]']");

        if (isClosed) {
            start.prop("disabled", true).val("").rules("remove");
            end.prop("disabled", true).val("").rules("remove");
        } else {
            start.prop("disabled", false).rules("add", {
                required: true,
                messages: { required: "Start time is required." }
            });
            end.prop("disabled", false).rules("add", {
                required: true,
                messages: { required: "End time is required." }
            });
        }
    }

    // Initial state)
    $(".day-closed").each(function () {
        toggleDay($(this).data("day"), $(this).is(":checked"));
    });

    // On change
    $(".day-closed").on("change", function () {
        toggleDay($(this).data("day"), $(this).is(":checked"));
    });
});