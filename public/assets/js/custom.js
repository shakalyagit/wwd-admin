//Hours validation
$(document).ready(function () {
    $("#business_hours_form").validate({
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
