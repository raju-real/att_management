$(function () {
    // Autoclose date picker
    $(".datepicker").datepicker({
        format: "yyyy-mm-dd",
        autoclose: true,
        // todayHighlight: true,
        // startDate: '2025-01-01',
        // endDate: '2025-12-31'
        orientation: "bottom", // Force it to open below
    });

    $(".datetimepicker").datetimepicker({
        format: "YYYY-MM-DD HH:mm", // Date + hour:minute
        icons: {
            time: "fa fa-clock",
            date: "fa fa-calendar",
            up: "fa fa-chevron-up",
            down: "fa fa-chevron-down",
            previous: "fa fa-chevron-left",
            next: "fa fa-chevron-right",
            today: "fa fa-screenshot",
            clear: "fa fa-trash",
            close: "fa fa-remove",
        },
    });

    $(".timepicker").datepicker({
        format: "HH:mm", // Only time picking (24-hour format)
    });

    // Flat picker
    flatpickr(".flat_datetimepicker", {
        enableTime: true,
        dateFormat: "Y-m-d H:i:S", // Example: 2025-08-26 18:30:00
        time_24hr: false, // 24-hour format
        minuteIncrement: 1,

        defaultDate: "today",
    });

    flatpickr(".flat_timepicker", {
        enableTime: true,
        noCalendar: true, // Disable calendar
        dateFormat: "H:i:S", // 24-hour format
        time_24hr: true, // Use 24-hour clock
        minuteIncrement: 1,

        defaultDate: "12:00:00", // Optional default time
    });

    flatpickr(".flat_datepicker", {
        dateFormat: "Y-m-d", // Only date
        defaultDate: "today", // Optional: sets default to today
    });

    // Set custom or default date or time or date time
    // Date & time picker
    document.querySelectorAll(".flat_datetimepicker").forEach(function (el) {
        flatpickr(el, {
            enableTime: true,
            dateFormat: "Y-m-d H:i:S",
            time_24hr: true,
                        minuteIncrement: 1,

            defaultDate: el.dataset.default || new Date(), // fallback to today
        });
    });

    // Time only picker
    document.querySelectorAll(".flat_timepicker").forEach(function (el) {
        flatpickr(el, {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i:S",
            minuteIncrement: 1,

            time_24hr: true,
            defaultDate: el.dataset.default || "12:00:00", // fallback to 12:00:00
        });
    });

    // Date only picker
    document.querySelectorAll(".flat_datepicker").forEach(function (el) {
        flatpickr(el, {
            dateFormat: "Y-m-d",
            defaultDate: el.dataset.default || new Date(), // fallback to today
        });
    });
});
