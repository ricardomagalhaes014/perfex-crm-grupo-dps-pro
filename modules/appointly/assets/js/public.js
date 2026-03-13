$(function () {
    const $datetimeInput = $('input[name="date"]'); // Campo de data/hora combinado

    $datetimeInput.on('change', function () {
        const fullDateTime = $(this).val(); // Ex: "2025-03-25 14:30"
        const dateOnly = fullDateTime.split(' ')[0]; // Isola apenas a data "2025-03-25"

        if (!dateOnly) return;

        $.ajax({
            url: site_url + 'appointly/appointments_public/busyDates',
            type: 'POST',
            dataType: 'json',
            data: { date: dateOnly },
            success: function (busyTimes) {
                if (typeof flatpickr === 'undefined') {
                    console.warn('Flatpickr não encontrado.');
                    return;
                }

                const instance = $datetimeInput[0]._flatpickr;
                if (!instance) return;

                const busyHours = busyTimes.map(item => item.start_hour);

                instance.set('disable', [
                    function (date) {
                        const selectedDate = date.toISOString().split('T')[0];
                        const selectedHour = date.toTimeString().substring(0, 5);
                        return selectedDate === dateOnly && busyHours.includes(selectedHour);
                    }
                ]);
            },
            error: function () {
                console.error('Erro ao obter horários ocupados.');
            }
        });
    });
});