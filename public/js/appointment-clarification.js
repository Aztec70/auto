document.addEventListener('DOMContentLoaded', function () {
    const inputs = document.querySelectorAll('.user-clarification-flatpickr');

    inputs.forEach(function (input) {
        const busyUrl = input.dataset.busyUrl;
        const workTimeFrom = input.dataset.workTimeFrom || '08:00';
        const workTimeTo = input.dataset.workTimeTo || '17:00';
        const workDayFrom = parseInt(input.dataset.workDayFrom || '1', 10);
        const workDayTo = parseInt(input.dataset.workDayTo || '5', 10);

        let busySlots = [];

        function getMinutesTotal(time) {
            const parts = time.split(':').map(Number);
            return parts[0] * 60 + parts[1];
        }

        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');

            return year + '-' + month + '-' + day + ' ' + hours + ':' + minutes;
        }

        const fromMinutesTotal = getMinutesTotal(workTimeFrom);
        const toMinutesTotal = getMinutesTotal(workTimeTo);

        fetch(busyUrl)
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                busySlots = Array.isArray(data.slots) ? data.slots : [];

                flatpickr(input, {
                    locale: 'lt',
                    enableTime: true,
                    time_24hr: true,
                    dateFormat: 'Y-m-d H:i',
                    minDate: 'today',
                    minuteIncrement: 30,
                    defaultHour: parseInt(workTimeFrom.split(':')[0], 10),
                    defaultMinute: parseInt(workTimeFrom.split(':')[1], 10),
                    disable: [
                        function (date) {
                            const day = date.getDay() === 0 ? 7 : date.getDay();
                            const formattedDateTime = formatDate(date);

                            if (day < workDayFrom || day > workDayTo) {
                                return true;
                            }

                            return busySlots.includes(formattedDateTime);
                        }
                    ],
                    onClose: function (selectedDates, dateStr, instance) {
                        if (!selectedDates.length) {
                            return;
                        }

                        const selected = selectedDates[0];
                        const day = selected.getDay() === 0 ? 7 : selected.getDay();
                        const minutes = selected.getMinutes();
                        const selectedMinutesTotal = selected.getHours() * 60 + selected.getMinutes();
                        const formatted = formatDate(selected);

                        if (day < workDayFrom || day > workDayTo) {
                            instance.clear();
                            alert('Pasirinktą dieną šis servisas nedirba.');
                            return;
                        }

                        if (![0, 30].includes(minutes)) {
                            instance.clear();
                            alert('Galima rinktis tik laiką kas 30 minučių.');
                            return;
                        }

                        if (selectedMinutesTotal < fromMinutesTotal || selectedMinutesTotal > toMinutesTotal) {
                            instance.clear();
                            alert('Galima rinktis tik darbo laiką nuo ' + workTimeFrom + ' iki ' + workTimeTo + '.');
                            return;
                        }

                        if (busySlots.includes(formatted)) {
                            instance.clear();
                            alert('Šis laikas jau užimtas pasirinktame servise.');
                        }
                    }
                });
            })
            .catch(function (error) {
                console.error('Nepavyko gauti užimtų laikų:', error);
            });
    });
});