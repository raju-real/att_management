$(document).ready(function () {

    let selectedDates = [];

    try {
        selectedDates = JSON.parse($('#office_holidays').val()) || [];
        if (!Array.isArray(selectedDates)) selectedDates = [];
    } catch (e) {
        selectedDates = [];
    }

    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function renderChips() {
        const container = $('#holiday_tags');
        container.empty();

        if (!selectedDates.length) {
            container.append('<small class="text-muted">No office holidays added yet.</small>');
        }
        selectedDates.forEach(function (date) {
            container.append(
                `<div class="holiday-chip">
                    ${esc(date)}
                    <span class="remove-date" data-date="${esc(date)}" title="Remove">&times;</span>
                </div>`
            );
        });

        $('#office_holidays').val(JSON.stringify(selectedDates));
    }

    $(document).on('click', '.remove-date', function () {
        const date = String($(this).data('date'));
        selectedDates = selectedDates.filter(d => d !== date);
        renderChips();
    });

    flatpickr("#holiday_picker", {
        dateFormat: "Y-m-d",
        allowInput: false,
        onChange: function (selected, dateStr, instance) {
            if (dateStr && !selectedDates.includes(dateStr)) {
                selectedDates.push(dateStr);
                selectedDates.sort();
                renderChips();
            }
            instance.clear();
        }
    });

    renderChips();
});
