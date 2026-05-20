document.addEventListener('DOMContentLoaded', function () {
    const appointmentForm = document.querySelector('[data-appointment-form]');

    if (!appointmentForm) {
        return;
    }

    const categorySelect = document.getElementById(appointmentForm.dataset.categoryId);
    const serviceSelect = document.getElementById(appointmentForm.dataset.serviceId);
    const visitDateInput = document.getElementById(appointmentForm.dataset.visitDateId);
    const visitDateMessage = document.getElementById(appointmentForm.dataset.visitDateMessageId);
    const busySlotsUrl = appointmentForm.dataset.busySlotsUrl;
    const excludeId = appointmentForm.dataset.excludeId || '';

    if (!categorySelect || !serviceSelect || !visitDateInput || !visitDateMessage || !busySlotsUrl) {
        return;
    }

    let busySlots = [];
    let pickerInstance = null;

    const categoryPlaceholderOption = categorySelect.querySelector('option[value=""]');
    const servicePlaceholderOption = serviceSelect.querySelector('option[value=""]');

    if (categoryPlaceholderOption) {
        categoryPlaceholderOption.hidden = true;
    }

    if (servicePlaceholderOption) {
        servicePlaceholderOption.hidden = true;
    }

    const categoryTomSelect = new TomSelect(categorySelect, {
        create: false,
        allowEmptyOption: false,
        plugins: ['clear_button'],
        placeholder: 'Pasirinkite kategoriją',
        maxOptions: 500,
        sortField: {
            field: 'text',
            direction: 'asc'
        }
    });

    const serviceTomSelect = new TomSelect(serviceSelect, {
        create: false,
        allowEmptyOption: false,
        plugins: ['clear_button'],
        placeholder: 'Pasirinkite servisą',
        maxOptions: 500,
        sortField: {
            field: 'text',
            direction: 'asc'
        }
    });

    categoryTomSelect.removeOption('');
    serviceTomSelect.removeOption('');

    function setVisitDateMessage(message = '') {
        visitDateMessage.textContent = message;

        if (message) {
            visitDateInput.setCustomValidity(message);
            visitDateInput.classList.add('is-invalid');
        } else {
            visitDateInput.setCustomValidity('');
            visitDateInput.classList.remove('is-invalid');
        }
    }

    function setSelectInvalid(selectElement, tomSelectInstance, message) {
        const wrapper = tomSelectInstance.wrapper;
        const feedback = selectElement.closest('.mb-3')?.querySelector('.invalid-feedback');

        selectElement.setCustomValidity(message || '');

        if (message) {
            selectElement.classList.add('is-invalid');
            wrapper.classList.add('is-invalid');

            if (feedback) {
                feedback.textContent = message;
            }
        } else {
            selectElement.classList.remove('is-invalid');
            wrapper.classList.remove('is-invalid');

            if (feedback) {
                feedback.textContent = selectElement === categorySelect
                    ? 'Pasirinkite kategoriją.'
                    : 'Pasirinkite servisą.';
            }
        }
    }

    function validateRequiredFields() {
        if (!categorySelect.value) {
            setSelectInvalid(categorySelect, categoryTomSelect, 'Pasirinkite kategoriją.');
        }

        if (!serviceSelect.value) {
            setSelectInvalid(serviceSelect, serviceTomSelect, 'Pasirinkite servisą.');
        }

        if (!visitDateInput.value) {
            setVisitDateMessage('Pasirinkite vizito datą ir laiką.');
        }
    }

    function getSelectedServiceOption() {
        const serviceId = serviceSelect.value;

        if (!serviceId) {
            return null;
        }

        return serviceSelect.querySelector('option[value="' + serviceId + '"]');
    }

    function getServiceSchedule() {
        const selectedOption = getSelectedServiceOption();

        if (!selectedOption) {
            return null;
        }

        const workDayFrom = parseInt(selectedOption.getAttribute('data-work-day-from'), 10);
        const workDayTo = parseInt(selectedOption.getAttribute('data-work-day-to'), 10);
        const workTimeFrom = selectedOption.getAttribute('data-work-time-from');
        const workTimeTo = selectedOption.getAttribute('data-work-time-to');

        if (
            Number.isNaN(workDayFrom) ||
            Number.isNaN(workDayTo) ||
            !workTimeFrom ||
            !workTimeTo
        ) {
            return null;
        }

        const [fromHour, fromMinute] = workTimeFrom.split(':').map(Number);
        const [toHour, toMinute] = workTimeTo.split(':').map(Number);

        return {
            workDayFrom,
            workDayTo,
            workTimeFrom,
            workTimeTo,
            fromHour,
            fromMinute,
            toHour,
            toMinute,
            fromMinutesTotal: fromHour * 60 + fromMinute,
            toMinutesTotal: toHour * 60 + toMinute
        };
    }

    function refreshSelectOptions(tomSelectInstance, originalSelect, preserveValue = true) {
        const currentValue = preserveValue ? originalSelect.value : '';

        tomSelectInstance.clearOptions();

        const originalOptions = originalSelect.querySelectorAll('option');

        originalOptions.forEach(function (option) {
            if (option.hidden) {
                return;
            }

            tomSelectInstance.addOption({
                value: option.value,
                text: option.textContent.trim()
            });
        });

        tomSelectInstance.refreshOptions(false);

        if (preserveValue && currentValue) {
            const stillVisibleOption = originalSelect.querySelector('option[value="' + currentValue + '"]');

            if (stillVisibleOption && !stillVisibleOption.hidden) {
                tomSelectInstance.setValue(currentValue, true);
            } else {
                tomSelectInstance.clear(true);
                originalSelect.value = '';
            }
        }
    }

    function filterServicesByCategory(selectedCategory = categorySelect.value) {
        const options = serviceSelect.querySelectorAll('option');

        options.forEach(function (option) {
            if (option.value === '') {
                option.hidden = true;
                return;
            }

            const categories = option.getAttribute('data-categories');

            if (!selectedCategory) {
                option.hidden = false;
                return;
            }

            option.hidden = !categories || !categories.split(',').includes(selectedCategory);
        });

        refreshSelectOptions(serviceTomSelect, serviceSelect);

        const visibleServiceOptions = Array.from(serviceSelect.querySelectorAll('option')).filter(function (option) {
            return option.value !== '' && !option.hidden;
        });

        if (selectedCategory && visibleServiceOptions.length === 0) {
            serviceTomSelect.clear(true);
            serviceSelect.value = '';
            setSelectInvalid(serviceSelect, serviceTomSelect, 'Šiai kategorijai šiuo metu nėra priskirto serviso.');
        } else if (serviceSelect.value) {
            setSelectInvalid(serviceSelect, serviceTomSelect, '');
        }
    }

    function filterCategoriesByService(selectedService = serviceSelect.value) {
        const options = categorySelect.querySelectorAll('option');

        options.forEach(function (option) {
            if (option.value === '') {
                option.hidden = true;
                return;
            }

            const services = option.getAttribute('data-services');

            if (!selectedService) {
                option.hidden = false;
                return;
            }

            option.hidden = !services || !services.split(',').includes(selectedService);
        });

        refreshSelectOptions(categoryTomSelect, categorySelect);

        if (categorySelect.value) {
            setSelectInvalid(categorySelect, categoryTomSelect, '');
        }
    }

    function buildDisableRules() {
        return [
            function (date) {
                const schedule = getServiceSchedule();

                if (!schedule) {
                    return false;
                }

                const day = date.getDay() === 0 ? 7 : date.getDay();

                if (day < schedule.workDayFrom || day > schedule.workDayTo) {
                    return true;
                }

                const formatted = pickerInstance ? pickerInstance.formatDate(date, 'Y-m-d H:i') : '';

                return formatted ? busySlots.includes(formatted) : false;
            }
        ];
    }

    function applyScheduleToPicker() {
        if (!pickerInstance) {
            return;
        }

        const schedule = getServiceSchedule();

        if (!schedule) {
            pickerInstance.set('disable', []);
            pickerInstance.set('defaultHour', 8);
            pickerInstance.set('defaultMinute', 0);

            if (serviceSelect.value) {
                setVisitDateMessage('Pasirinktas servisas neturi pilnai nurodyto darbo grafiko.');
            } else if (!visitDateInput.value) {
                setVisitDateMessage('');
            }

            return;
        }

        pickerInstance.set('disable', buildDisableRules());
        pickerInstance.set('defaultHour', schedule.fromHour);
        pickerInstance.set('defaultMinute', schedule.fromMinute);

        if (visitDateInput.value) {
            setVisitDateMessage('');
        }
    }

    async function loadBusySlots() {
        const serviceId = serviceSelect.value;

        if (visitDateInput.value) {
            setVisitDateMessage('');
        }

        if (!serviceId) {
            busySlots = [];
            visitDateInput.value = '';

            if (pickerInstance) {
                pickerInstance.clear();
                applyScheduleToPicker();
            }

            return;
        }

        try {
            let url = busySlotsUrl + '?serviceId=' + encodeURIComponent(serviceId);

            if (excludeId) {
                url += '&excludeId=' + encodeURIComponent(excludeId);
            }

            const response = await fetch(url);
            const data = await response.json();

            busySlots = Array.isArray(data.slots) ? data.slots : [];

            if (pickerInstance) {
                const currentValue = visitDateInput.value;
                applyScheduleToPicker();

                if (currentValue && busySlots.includes(currentValue)) {
                    pickerInstance.clear();
                    setVisitDateMessage('Pasirinktas laikas šiame servise jau užimtas. Pasirinkite kitą laiką.');
                }
            }
        } catch (error) {
            console.error('Nepavyko gauti užimtų laikų:', error);
        }
    }

    function validateSelectedDate(instance) {
        const selectedDates = instance.selectedDates;

        if (!selectedDates.length) {
            setVisitDateMessage('Pasirinkite vizito datą ir laiką.');
            return;
        }

        const schedule = getServiceSchedule();

        if (!schedule) {
            instance.clear();
            setVisitDateMessage('Pasirinktas servisas neturi pilnai nurodyto darbo grafiko.');
            return;
        }

        const selected = selectedDates[0];
        const formatted = instance.formatDate(selected, 'Y-m-d H:i');
        const day = selected.getDay() === 0 ? 7 : selected.getDay();
        const hours = selected.getHours();
        const minutes = selected.getMinutes();
        const selectedMinutesTotal = hours * 60 + minutes;

        if (day < schedule.workDayFrom || day > schedule.workDayTo) {
            instance.clear();
            setVisitDateMessage('Pasirinktą dieną šis servisas nedirba.');
            return;
        }

        if (![0, 30].includes(minutes)) {
            instance.clear();
            setVisitDateMessage('Galima rinktis tik laiką kas 30 minučių.');
            return;
        }

        if (selectedMinutesTotal < schedule.fromMinutesTotal || selectedMinutesTotal > schedule.toMinutesTotal) {
            instance.clear();
            setVisitDateMessage('Galima rinktis tik darbo laiką nuo ' + schedule.workTimeFrom + ' iki ' + schedule.workTimeTo + '.');
            return;
        }

        if (busySlots.includes(formatted)) {
            instance.clear();
            setVisitDateMessage('Šis laikas jau užimtas pasirinktame servise.');
            return;
        }

        setVisitDateMessage('');
    }

    pickerInstance = flatpickr(visitDateInput, {
        locale: 'lt',
        enableTime: true,
        time_24hr: true,
        minuteIncrement: 30,
        dateFormat: 'Y-m-d H:i',
        minDate: 'today',
        defaultHour: 8,
        defaultMinute: 0,
        disable: [],
        onOpen: function () {
            if (visitDateInput.value) {
                setVisitDateMessage('');
            }
        },
        onClose: function (selectedDates, dateStr, instance) {
            validateSelectedDate(instance);
        }
    });

    filterServicesByCategory();
    filterCategoriesByService();

    if (categorySelect.value) {
        categoryTomSelect.setValue(categorySelect.value, true);
        setSelectInvalid(categorySelect, categoryTomSelect, '');
    }

    if (serviceSelect.value) {
        serviceTomSelect.setValue(serviceSelect.value, true);
        setSelectInvalid(serviceSelect, serviceTomSelect, '');
    }

    applyScheduleToPicker();
    loadBusySlots();

    categoryTomSelect.on('change', function (value) {
        categorySelect.value = value || '';

        setSelectInvalid(categorySelect, categoryTomSelect, value ? '' : 'Pasirinkite kategoriją.');

        filterServicesByCategory(value || '');
        loadBusySlots();
    });

    serviceTomSelect.on('change', function (value) {
        serviceSelect.value = value || '';

        setSelectInvalid(serviceSelect, serviceTomSelect, value ? '' : 'Pasirinkite servisą.');

        filterCategoriesByService(value || '');
        applyScheduleToPicker();
        loadBusySlots();
    });

    const formElement = appointmentForm.querySelector('form');

    formElement?.addEventListener('submit', function (event) {
        validateRequiredFields();

        if (!visitDateInput.value) {
            event.preventDefault();
            event.stopPropagation();

            setVisitDateMessage('Pasirinkite vizito datą ir laiką.');
            visitDateInput.classList.add('is-invalid');
        }
    });
});