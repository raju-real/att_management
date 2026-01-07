$(function () {
    // Autoclose date picker
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        // todayHighlight: true,
        // startDate: '2025-01-01',
        // endDate: '2025-12-31'
        orientation: 'bottom'  // Force it to open below
    });

    // $('.datetimepicker1').datetimepicker({
    //     format: 'YYYY-MM-DD HH:mm',
    //     stepping: 30,
    //     useCurrent: false,
    //     minDate: moment().subtract(1, 'month'),
    //     maxDate: moment().add(1, 'month')
    // });

    // $('.timepicker').datetimepicker({
    //     format: 'HH:mm' // Only time picking (24-hour format)
    // });
});