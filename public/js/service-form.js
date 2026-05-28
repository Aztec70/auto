document.addEventListener('DOMContentLoaded', function () {
    const serviceForm = document.querySelector('[data-service-form]');

    if (!serviceForm) {
        return;
    }

    const formElement = serviceForm.querySelector('form');
    const categoriesId = serviceForm.dataset.categoriesId;
    const categorySelect = document.getElementById(categoriesId);
    const timeInputs = serviceForm.querySelectorAll('.js-timepicker');
    const fileInput = serviceForm.querySelector('input[type="file"]');
    const allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp'];

    const workDayFrom = serviceForm.querySelector('[name$="[workDayFrom]"]');
    const workDayTo = serviceForm.querySelector('[name$="[workDayTo]"]');
    const workTimeFrom = serviceForm.querySelector('[name$="[workTimeFrom]"]');
    const workTimeTo = serviceForm.querySelector('[name$="[workTimeTo]"]');

    let workTimeFromPicker = null;
    let workTimeToPicker = null;

    if (categorySelect) {
        new TomSelect(categorySelect, {
            plugins: ['remove_button'],
            create: false,
            maxItems: null,
            hideSelected: true,
            closeAfterSelect: false,
            placeholder: 'Pasirinkite kategorijas'
        });
    }

    function timeToMinutes(value) {
        if (!value || !value.includes(':')) {
            return null;
        }

        const parts = value.split(':');
        const hours = Number(parts[0]);
        const minutes = Number(parts[1]);

        if (Number.isNaN(hours) || Number.isNaN(minutes)) {
            return null;
        }

        return hours * 60 + minutes;
    }

    function minutesToTime(minutes) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;

        return String(hours).padStart(2, '0') + ':' + String(mins).padStart(2, '0');
    }

    function updateWorkDayOptions() {
        if (!workDayFrom || !workDayTo) {
            return;
        }

        const fromValue = Number(workDayFrom.value);

        Array.from(workDayTo.options).forEach(function (option) {
            if (!option.value) {
                option.disabled = false;
                return;
            }

            option.disabled = fromValue && Number(option.value) <= fromValue;
        });

        if (workDayTo.value && fromValue && Number(workDayTo.value) <= fromValue) {
            workDayTo.value = '';
        }
    }

    function updateWorkTimeOptions() {
        if (!workTimeFrom || !workTimeTo || !workTimeToPicker) {
            return;
        }

        const fromMinutes = timeToMinutes(workTimeFrom.value);
        const toMinutes = timeToMinutes(workTimeTo.value);

        if (fromMinutes === null) {
            workTimeToPicker.set('minTime', null);
            return;
        }

        const minEndTime = fromMinutes + 30;

        if (minEndTime >= 24 * 60) {
            workTimeTo.value = '';
            workTimeToPicker.clear();
            return;
        }

        workTimeToPicker.set('minTime', minutesToTime(minEndTime));

        if (toMinutes !== null && toMinutes <= fromMinutes) {
            workTimeTo.value = '';
            workTimeToPicker.clear();
        }
    }

    timeInputs.forEach(function (input) {
        const picker = flatpickr(input, {
            locale: 'lt',
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            time_24hr: true,
            minuteIncrement: 30,
            onChange: function () {
                updateWorkTimeOptions();
            }
        });

        if (input === workTimeFrom) {
            workTimeFromPicker = picker;
        }

        if (input === workTimeTo) {
            workTimeToPicker = picker;
        }
    });

    if (workDayFrom && workDayTo) {
        workDayFrom.addEventListener('change', updateWorkDayOptions);
        workDayTo.addEventListener('change', updateWorkDayOptions);
        updateWorkDayOptions();
    }

    if (workTimeFrom && workTimeTo) {
        workTimeFrom.addEventListener('change', updateWorkTimeOptions);
        workTimeTo.addEventListener('change', updateWorkTimeOptions);
        updateWorkTimeOptions();
    }

    serviceForm.querySelectorAll('[data-character-counter]').forEach(function (field) {
        const counterId = field.dataset.characterCounter;
        const counter = document.getElementById(counterId);
        const maxLength = Number(field.getAttribute('maxlength')) || 0;

        if (!counter || !maxLength) {
            return;
        }

        const updateCounter = function () {
            counter.textContent = field.value.length + ' / ' + maxLength;

            if (field.value.length > maxLength) {
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        };

        field.addEventListener('input', updateCounter);
        updateCounter();
    });

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            const file = fileInput.files[0];

            fileInput.setCustomValidity('');
            fileInput.classList.remove('is-invalid');

            if (!file) {
                return;
            }

            if (!allowedImageTypes.includes(file.type)) {
                fileInput.setCustomValidity('Įkelkite JPG, PNG arba WEBP formato nuotrauką.');
                fileInput.classList.add('is-invalid');
                return;
            }

            if (file.size > 4 * 1024 * 1024) {
                fileInput.setCustomValidity('Nuotrauka negali būti didesnė nei 4 MB.');
                fileInput.classList.add('is-invalid');
            }
        });
    }

    if (formElement) {
        formElement.addEventListener('submit', function (event) {
            updateWorkDayOptions();
            updateWorkTimeOptions();

            if (fileInput && fileInput.files.length > 0) {
                fileInput.dispatchEvent(new Event('change'));
            }

            if (!formElement.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            formElement.classList.add('was-validated');
        });
    }
});