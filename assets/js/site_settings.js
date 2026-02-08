$(document).ready(function () {

    let selectedDates = [];

    try {
        selectedDates = JSON.parse($('#office_holidays').val()) || [];
    } catch (e) {
        selectedDates = [];
    }

    function renderChips() {
        let container = $('#holiday_tags');
        container.empty();

        selectedDates.forEach(function (date) {
            container.append(
                `<div class="holiday-chip">
                    ${date}
                    <span class="remove-date" data-date="${date}">&times;</span>
                </div>`
            );
        });

        $('#office_holidays').val(JSON.stringify(selectedDates));
    }

    $(document).on('click', '.remove-date', function () {
        let date = $(this).data('date');
        selectedDates = selectedDates.filter(d => d !== date);
        renderChips();
    });

    flatpickr("#holiday_picker", {
        dateFormat: "Y-m-d",
        allowInput: false,
        onChange: function (selected, dateStr) {
            if (dateStr && !selectedDates.includes(dateStr)) {
                selectedDates.push(dateStr);
                selectedDates.sort();
                renderChips();
            }
        }
    });

    renderChips();
});
