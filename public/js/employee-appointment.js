document.addEventListener('DOMContentLoaded', function () {
    const dateFilterInput = document.querySelector('.employee-date-filter');

    if (dateFilterInput) {
        flatpickr(dateFilterInput, {
            dateFormat: 'Y-m-d',
            allowInput: true,
            locale: 'lt'
        });
    }

    const inputs = document.querySelectorAll('.employee-flatpickr');

    inputs.forEach(function (input) {
        const busyUrl = input.dataset.busyUrl;
        const workTimeFrom = input.dataset.workTimeFrom || '08:00';
        const workTimeTo = input.dataset.workTimeTo || '17:00';

        fetch(busyUrl)
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                const busySlots = data.slots || [];

                flatpickr(input, {
                    enableTime: true,
                    time_24hr: true,
                    dateFormat: 'Y-m-d H:i',
                    minDate: 'today',
                    minuteIncrement: 30,
                    minTime: workTimeFrom,
                    maxTime: workTimeTo,
                    locale: 'lt',
                    disable: [
                        function (date) {
                            const year = date.getFullYear();
                            const month = String(date.getMonth() + 1).padStart(2, '0');
                            const day = String(date.getDate()).padStart(2, '0');
                            const hours = String(date.getHours()).padStart(2, '0');
                            const minutes = String(date.getMinutes()).padStart(2, '0');

                            const formattedDateTime = year + '-' + month + '-' + day + ' ' + hours + ':' + minutes;

                            return busySlots.includes(formattedDateTime);
                        }
                    ]
                });
            });
    });
});